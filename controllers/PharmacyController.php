<?php
require '../db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'inventory':
        loadInventory($pdo);
        break;
    case 'issued':
        loadIssued($pdo);
        break;
    case 'pullout':
        pullOutDrug($pdo);
        break;
    case 'pulledOut':
        pulledOutDM($pdo);
        break;
    case 'undoPullOut':
        undoPullOutDrug($pdo);
        break;
    default:
        echo json_encode([
            "error" => "Invalid action"
        ]);
        break;
}

function loadInventory($pdo)
{
    try {

        /* =========================
           DATATABLES PARAMS
        ========================= */
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;

        $search = $_POST['search']['value'] ?? '';

        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';

        /* =========================
           WHERE CONDITIONS
        ========================= */
        $where = "WHERE 1=1
                    -- AND inv.lotno IS NOT NULL
                    -- AND inv.lotno != ''
                    -- AND inv.isActive IS NOT NULL
                    -- AND inv.isActive != 'N'
                    -- AND inv.stock_status IS NOT NULL
                ";
        $params = [];

        // DATE FILTER (works if only start or end is provided)
        if (!empty($startDate)) {
            $where .= " AND inv.dmdprdte >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $where .= " AND inv.dmdprdte <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        // SEARCH
        if (!empty($search)) {
            $where .= " AND (
                COALESCE(NULLIF(inv.lotno, ''), 'No Lot Number') LIKE :search
                OR g.GENDESC LIKE :search
                OR h.brandname LIKE :search
                OR h.dmdnost LIKE :search
                OR h.strecode LIKE :search
                OR h.formcode LIKE :search
                OR (
                    CASE
                        WHEN inv.stockbal IS NULL OR inv.stockbal = 0 THEN 'No Stock Balance'
                        ELSE inv.stockbal
                    END  
                ) LIKE :search
                OR COALESCE(NULLIF(inv.dmselprice, ''), 'No Selling Price') LIKE :search
                OR COALESCE(NULLIF(inv.dmduprice, ''), 'No Unit Price') LIKE :search
                OR DATE(inv.dmdprdte) LIKE :search
                OR DATE(inv.expiry) LIKE :search
                OR inv.dmhdrsub LIKE :search

                OR (
                    CASE
                        WHEN inv.expiry < CURDATE() AND inv.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                        WHEN inv.expiry < CURDATE() AND inv.isActive = 'Y' THEN 'EXPIRED'
                        WHEN inv.expiry >= CURDATE() AND inv.isActive = 'N' THEN 'PULLOUT'
                        WHEN inv.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                        ELSE 'GOOD'
                    END
                ) LIKE :search
            )";
            $params[':search'] = "%$search%";
        }

        /* =========================
           BASE FROM + JOIN
        ========================= */
        $baseFrom = "
            FROM hdmhdrprice inv

                -- =========================
                -- DRUG MASTER
                -- =========================
                LEFT JOIN hdmhdr h
                    ON inv.dmdcomb = h.dmdcomb
                    AND inv.dmdctr = h.dmdctr

                LEFT JOIN hcharge chg
                    ON inv.dmhdrsub = chg.chrgcode

                LEFT JOIN hdruggrp dg
                    ON h.grpcode = dg.grpcode

                LEFT JOIN hgen g
                    ON dg.gencode = g.gencode

                -- =========================
                -- ISSUED / RETURNED
                -- =========================
                LEFT JOIN (
                    SELECT
                        i.lotno,
                        i.dmdcomb,
                        i.dmdctr,
                        SUM(i.qty) AS total_quantity_issued,
                        IFNULL(SUM(r.qty_returned), 0) AS total_quantity_returned
                    FROM hrxoissue i

                    LEFT JOIN (
                        SELECT
                            docointkey,
                            dmdcomb,
                            dmdctr,
                            lotno,
                            SUM(qty) AS qty_returned
                        FROM hrxoreturn
                        GROUP BY docointkey, dmdcomb, dmdctr, lotno
                    ) r
                        ON i.docointkey = r.docointkey
                        AND i.dmdcomb = r.dmdcomb
                        AND i.dmdctr = r.dmdctr
                        AND i.lotno = r.lotno

                    WHERE i.qty IS NOT NULL
                    AND i.qty > 0

                    GROUP BY i.lotno, i.dmdcomb, i.dmdctr
                ) issued
                    ON inv.lotno = issued.lotno
                    AND inv.dmdcomb = issued.dmdcomb
                    AND inv.dmdctr = issued.dmdctr

                -- =========================
                -- ADJUSTMENTS
                -- =========================
                LEFT JOIN (
                    SELECT
                        a.lotno,
                        a.dmdcomb,
                        a.dmdctr,

                        -- PLUS adjustments
                        SUM(
                            CASE
                                WHEN a.plusminus = '+'
                                    AND (a.adjcancel IS NULL OR a.adjcancel != 'Y')
                                THEN a.qty
                                ELSE 0
                            END
                        ) AS adjustment_addition,

                        -- MINUS adjustments
                        SUM(
                            CASE
                                WHEN a.plusminus = '-'
                                    AND (a.adjcancel IS NULL OR a.adjcancel != 'Y')
                                THEN a.qty
                                ELSE 0
                            END
                        ) AS adjustment_deduction

                    FROM hadjust a

                    WHERE a.qty IS NOT NULL
                    AND a.qty > 0

                    GROUP BY
                        a.lotno,
                        a.dmdcomb,
                        a.dmdctr
                ) adj
                    ON inv.lotno = adj.lotno
                    AND inv.dmdcomb = adj.dmdcomb
                    AND inv.dmdctr = adj.dmdctr
        ";

        /* =========================
           TOTAL RECORDS
        ========================= */
        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $columns = [
            0 => 'lot_number',
            1 => 'drug_description',
            2 => 'stock_balance',
            3 => 'beg_balance',
            4 => 'total_dispensed',
            5 => 'total_returned',
            6 => 'net_dispensed',
            7 => 'adjustment_addition',
            8 => 'adjustment_deduction',
            9 => 'calculated_expected_stock',
            10 => 'selling_price',
            11 => 'unit_price',
            12 => 'entry_date',
            13 => 'expiration_date',
            14 => 'account_type',
            15 => 'status',
        ];

        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
        $orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'entry_date';

        // ✅ Default secondary sort (latest first)
        $secondarySort = "entry_date DESC";

        // If user clicks another column → override
        if (isset($_POST['order'])) {
            $secondarySort = "$orderColumn $orderDir";
        }

        /* =========================
           MAIN DATA QUERY
        ========================= */
        $sql = "
            SELECT
            -- =========================
            -- INVENTORY DETAILS
            -- =========================
            COALESCE(NULLIF(inv.lotno, ''), 'No Lot Number') AS lot_number,

            CONCAT_WS(' ',
                CONCAT_WS('', g.GENDESC,
                    CASE
                        WHEN h.brandname IS NULL OR h.brandname = ''
                        THEN ''
                        ELSE CONCAT(' (', h.brandname, ')')
                    END
                ),
                COALESCE(h.dmdnost, ''),
                COALESCE(h.strecode, ''),
                COALESCE(h.formcode, '')
            ) AS drug_description,

            CASE
                WHEN inv.stockbal IS NULL OR inv.stockbal = 0 THEN 'No Stock Balance'
                ELSE inv.stockbal
            END AS stock_balance,

            inv.begbal AS beg_balance,

            -- =========================
            -- DISPENSING
            -- =========================
            IFNULL(issued.total_quantity_issued, 0) AS total_dispensed,

            IFNULL(issued.total_quantity_returned, 0) AS total_returned,

            (
                IFNULL(issued.total_quantity_issued, 0)
                - IFNULL(issued.total_quantity_returned, 0)
            ) AS net_dispensed,

            -- =========================
            -- ADJUSTMENTS
            -- =========================
            IFNULL(adj.adjustment_addition, 0) AS adjustment_addition,

            IFNULL(adj.adjustment_deduction, 0) AS adjustment_deduction,

            -- =========================
            -- OPTIONAL TRUE STOCK MOVEMENT
            -- beg + additions - deductions - net dispensed
            -- =========================
            (
                inv.begbal
                + IFNULL(adj.adjustment_addition, 0)
                - IFNULL(adj.adjustment_deduction, 0)
                - (
                    IFNULL(issued.total_quantity_issued, 0)
                    - IFNULL(issued.total_quantity_returned, 0)
                )
            ) AS calculated_expected_stock,

            -- =========================
            -- PRICING
            -- =========================
            COALESCE(NULLIF(inv.dmselprice, ''), 'No Selling Price') AS selling_price,
            COALESCE(NULLIF(inv.dmduprice, ''), 'No Unit Price') AS unit_price,

            inv.dmdprdte AS entry_date,
            COALESCE(NULLIF(inv.expiry, ''), 'No Expiration Date') AS expiration_date,

            chg.chrgdesc AS account_type,

            -- =========================
            -- STATUS
            -- =========================
            CASE
                WHEN inv.expiry < CURDATE() AND inv.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                WHEN inv.expiry < CURDATE() AND inv.isActive = 'Y' THEN 'EXPIRED'
                WHEN inv.expiry >= CURDATE() AND inv.isActive = 'N' THEN 'PULLOUT'
                WHEN inv.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                ELSE 'GOOD'
            END AS status,

            CASE
                WHEN inv.expiry < CURDATE() AND inv.isActive = 'Y' THEN 0
                WHEN inv.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
                WHEN inv.expiry < CURDATE() AND inv.isActive = 'N' THEN 4
                WHEN inv.expiry >= CURDATE() AND inv.isActive = 'N' THEN 3
                ELSE 2
            END AS trigger_order,

            inv.delintkey AS id

            $baseFrom
            $where
            
            ORDER BY trigger_order ASC, $secondarySort
        ";

        // LIMIT
        if ($length != -1) {
            $sql .= " LIMIT :start, :length";
        }

        $stmt = $pdo->prepare($sql);

        // Bind params
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        if ($length != -1) {
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* =========================
           TOTALS QUERY
        ========================= */
        $totalSql = "
            SELECT
                SUM(CASE WHEN inv.expiry >= CURDATE()
                    THEN inv.stockbal ELSE 0 END) AS totalStock,

                SUM(CASE WHEN inv.expiry >= CURDATE()
                    THEN inv.stockbal * inv.dmselprice ELSE 0 END) AS totalValue,

                SUM(CASE WHEN inv.expiry < CURDATE()
                    THEN inv.stockbal ELSE 0 END) AS expiredStock,

                SUM(CASE WHEN inv.expiry < CURDATE()
                    THEN inv.stockbal * inv.dmselprice ELSE 0 END) AS expiredValue

            $baseFrom
            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

        /* =========================
           FINAL JSON RESPONSE
        ========================= */
        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($total),
            "recordsFiltered" => intval($total),
            "data" => $data,
            "totals" => $totals
        ]);
    } catch (Exception $e) {

        // VERY IMPORTANT: prevents DataTables crash
        echo json_encode([
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage()
        ]);
    }

    exit;
}

function loadIssued($pdo)
{
    try {

        /* =========================
           DATATABLES PARAMS
        ========================= */
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;


        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';
        $search    = $_POST['search']['value'] ?? '';

        /* =========================
           WHERE CONDITIONS
        ========================= */
        $where = "WHERE 1=1
            AND i.qty IS NOT NULL AND i.qty > 0";
        $params = [];

        // ✅ DATE FILTER
        if (!empty($startDate) && !empty($endDate)) {
            $where .= " AND i.issuedte BETWEEN :startDate AND :endDate";
            $params[':startDate'] = $startDate . " 00:00:00";
            $params[':endDate']   = $endDate . " 23:59:59";
        }

        // ✅ SEARCH FILTER (FULL LIKE INVENTORY)
        if (!empty($search)) {
            $where .= " AND (
            -- Drug description
            COALESCE(NULLIF(i.lotno, ''), 'No Lot Number') LIKE :search
            OR TRIM ( 
                CONCAT_WS(' ',
                    CONCAT(
                        g.GENDESC,
                        CASE 
                            WHEN h.brandname IS NULL OR h.brandname = '' THEN ''
                            ELSE CONCAT(' (', h.brandname, ')')
                        END
                    ),
                    COALESCE(h.dmdnost, ''),
                    COALESCE(h.strecode, ''),
                    COALESCE(h.formcode, '')
                )
            ) LIKE :search
            OR p.hpercode LIKE :search
            OR TRIM (
                CONCAT(
                    p.patlast, ', ',
                    p.patfirst,
                    CASE
                        WHEN p.patsuffix IS NULL OR p.patsuffix IN ('NOTAP','N/A') THEN ''
                        ELSE CONCAT(' ', p.patsuffix)
                    END,
                    CASE
                        WHEN p.patmiddle IS NULL OR p.patmiddle IN ('','N/A') THEN ''
                        ELSE CONCAT(', ', p.patmiddle)
                    END
                )
            ) LIKE :search
            OR i.pcchrgcod LIKE :search
            OR
            (
                CASE
                    WHEN hx.estatus = 'R' THEN 'Prescription Only'
                    ELSE 'Order'
                END
            ) LIKE :search
            OR chg.chrgdesc LIKE :search
            OR TRIM (
                CONCAT(
                    hp.lastname, ', ',
                    hp.firstname,
                    CASE
                        WHEN hp.middlename IS NULL OR hp.middlename = ''
                        THEN ''
                        ELSE CONCAT(' ', hp.middlename)
                    END
                )
            ) LIKE :search
        )";

            $params[':search'] = "%$search%";
        }

        /* =========================
           BASE FROM (REUSABLE)
        ========================= */
        $baseFrom = "
            FROM hrxoissue i

        LEFT JOIN (
            SELECT docointkey, dmdcomb, dmdctr, SUM(qty) AS qty_returned
            FROM hrxoreturn
            GROUP BY docointkey,dmdcomb,dmdctr
        ) r
            ON i.docointkey = r.docointkey
            AND i.dmdcomb = r.dmdcomb
            AND i.dmdctr = r.dmdctr

        -- ✅ FIXED JOIN
        LEFT JOIN hdmhdr h 
            ON i.dmdcomb = h.dmdcomb
            AND i.dmdctr = h.dmdctr

        LEFT JOIN hdruggrp dg ON h.grpcode = dg.grpcode
        LEFT JOIN hgen g ON dg.gencode = g.gencode

        LEFT JOIN hcharge chg ON i.chrgcode = chg.chrgcode

        LEFT JOIN hperson p ON i.hpercode = p.hpercode
        LEFT JOIN hpersonal hp ON hp.employeeid = i.issuedby
        LEFT JOIN hrxo hx ON i.docointkey = hx.docointkey

        ";

        /* =========================
           TOTAL COUNT (FILTERED)
        ========================= */
        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $columns = [
            0 => 'order_date',
            1 => 'date_issued',
            2 => 'turnaround_time',
            3 => 'lot_number',
            4 => 'drug_description',
            5 => 'hpercode',
            6 => 'patient',
            7 => 'charge_code',
            8 => 'request_quantity',
            9 => 'quantity_issued',
            10 => 'quantity_returned',
            11 => 'net_dispensed',
            12 => 'selling_price',
            13 => 'total_amount',
            14 => 'order_type',
            15 => 'account_type',
            16 => 'issued_by'
        ];

        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
        $orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'date_issued';

        /* =========================
           MAIN DATA QUERY
        ========================= */
        $sql = "
            SELECT
                hx.dodate AS order_date,
                i.issuedte AS date_issued,
                
                CONCAT(
                    FLOOR(TIMESTAMPDIFF(SECOND, hx.dodate, i.issuedte) / 86400),
                    ' days - ',
                    SEC_TO_TIME(
                        MOD(
                            TIMESTAMPDIFF(SECOND, hx.dodate, i.issuedte), 86400
                        )
                    )
                ) AS turnaround_time,
                
                COALESCE(NULLIF(i.lotno, ''), 'No Lot Number') AS lot_number,
                CONCAT_WS(' ',
                    CONCAT(
                        g.GENDESC,
                        CASE 
                            WHEN h.brandname IS NULL OR h.brandname = '' THEN ''
                            ELSE CONCAT(' (', h.brandname, ')')
                        END
                    ),
                    COALESCE(h.dmdnost, ''),
                    COALESCE(h.strecode, ''),
                    COALESCE(h.formcode, '')
                ) AS drug_description,

                p.hpercode,

                CONCAT(
                    p.patlast, ', ',
                    p.patfirst,
                    CASE
                        WHEN p.patsuffix IS NULL OR p.patsuffix IN ('NOTAP','N/A') THEN ''
                        ELSE CONCAT(' ', p.patsuffix)
                    END,
                    CASE
                        WHEN p.patmiddle IS NULL OR p.patmiddle IN ('','N/A') THEN ''
                        ELSE CONCAT(', ', p.patmiddle)
                    END
                ) AS patient,
                
                COALESCE(i.pcchrgcod, '') AS charge_code,
                hx.pchrgqty AS request_quantity,
                i.qty AS quantity_issued,
                IFNULL(r.qty_returned,0) AS quantity_returned,
                (
                    IFNULL(i.qty, 0)
                    - IFNULL(r.qty_returned, 0)
                ) AS net_dispensed,
                hx.pchrgup AS selling_price,
                hx.pcchrgamt AS total_amount,

                CASE
                    WHEN hx.estatus = 'R' THEN 'Prescription Only'
                    ELSE 'Order'
                END AS order_type,

                chg.chrgdesc AS account_type,

                CONCAT(
                    hp.lastname, ', ',
                    hp.firstname,
                    CASE
                        WHEN hp.middlename IS NULL OR hp.middlename = ''
                        THEN ''
                        ELSE CONCAT(' ', hp.middlename)
                    END
                ) AS issued_by

            $baseFrom
            $where
            ORDER BY $orderColumn $orderDir
        ";

        // LIMIT
        if ($length != -1) {
            $sql .= " LIMIT :start, :length";
        }

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        if ($length != -1) {
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* =========================
           TOTALS (FILTERED)
        ========================= */
        $totalSql = "
            SELECT
                COUNT(*) AS totalDrugs,
                SUM(i.qty) AS totalIssued,
                SUM(IFNULL(r.qty_returned,0)) AS totalReturned
            $baseFrom
            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

        /* =========================
           RESPONSE
        ========================= */
        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($total),
            "recordsFiltered" => intval($total),
            "data" => $data,
            "totals" => $totals
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage()
        ]);
    }
    exit;
}

function pullOutDrug($pdo)
{
    $id = $_POST['id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if (!$id) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid ID"
        ]);
        return;
    }

    if (empty($remarks)) {
        echo json_encode([
            "status" => "error",
            "message" => "Remarks is required"
        ]);
        return;
    }

    $sql = "UPDATE hdmhdrprice 
            SET isActive = 'N',
                dmdrem = :remarks
            WHERE delintkey = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':remarks' => $remarks
    ]);

    echo json_encode([
        "status" => "success"
    ]);
}

function undoPullOutDrug($pdo)
{
    $id = $_POST['id'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if (!$id) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid ID"
        ]);
        return;
    }

    if (empty($remarks)) {
        echo json_encode([
            "status" => "error",
            "message" => "Remarks is required"
        ]);
        return;
    }

    $sql = "UPDATE hdmhdrprice 
            SET isActive = 'Y',
                stock_status = 'Y',
                dmdrem = :remarks
            WHERE delintkey = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':remarks' => $remarks
    ]);

    echo json_encode([
        "status" => "success"
    ]);
}

function pulledOutDM($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;

        $search = $_POST['search']['value'] ?? '';

        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';

        $where = "WHERE 1=1
            AND hp.lotno IS NOT NULL AND hp.lotno != ''
            AND hp.isActive IS NOT NULL AND hp.isActive != 'Y'
        ";
        $params = [];

        if (!empty($startDate)) {
            $where .= " AND hp.dmdprdte >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }
        if (!empty($endDate)) {
            $where .= " AND hp.dmdprdte <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        // SEARCH
        if (!empty($search)) {
            $where .= " AND (
                hp.lotno LIKE :search
                OR g.GENDESC LIKE :search
                OR h.brandname LIKE :search
                OR h.dmdnost LIKE :search
                OR h.strecode LIKE :search
                OR h.formcode LIKE :search
                OR chg.chrgdesc LIKE :search
                OR DATE(hp.dmdprdte) LIKE :search
                OR DATE(hp.expiry) LIKE :search

                OR (
                    CASE
                        WHEN hp.expiry < CURDATE() AND hp.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                        WHEN hp.expiry < CURDATE() AND hp.isActive = 'Y' THEN 'EXPIRED'
                        WHEN hp.expiry >= CURDATE() AND hp.isActive = 'N' THEN 'PULLOUT'
                        WHEN hp.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                        ELSE 'GOOD'
                    END
                ) LIKE :statusSearch
            )";

            $params[':search'] = "%$search%";
            $params[':statusSearch'] = "%$search%";
        }

        $baseFrom = "
            FROM hdmhdrprice hp

            LEFT JOIN hdmhdr h
                ON hp.dmdcomb = h.dmdcomb
                AND hp.dmdctr = h.dmdctr

            LEFT JOIN hcharge chg ON hp.dmhdrsub = chg.chrgcode

            LEFT JOIN hdruggrp dg
                ON h.grpcode = dg.grpcode

            LEFT JOIN hgen g
                ON dg.gencode = g.gencode
        ";

        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $columns = [
            0 => 'hp.lotno',
            1 => 'g.GENDESC',
            2 => 'hp.stockbal',
            3 => 'hp.begbal',
            4 => '(hp.begbal - hp.stockbal)',
            5 => 'hp.dmselprice',
            6 => 'hp.dmdprdte',
            7 => 'hp.expiry',
            8 => 'chg.chrgdesc',
            9 => 'trigger_order' // computed column
        ];
        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = $_POST['order'][0]['dir'] ?? 'asc';

        $orderColumn = $columns[$orderColumnIndex] ?? 'hp.lotno';

        $sql = "
            SELECT
                COALESCE(NULLIF(hp.lotno, ''), 'No Lot Number') AS lot_number,
                CONCAT_WS(' ',
                    CONCAT_WS('', g.GENDESC,
                        CASE
                            WHEN h.brandname IS NULL OR h.brandname = ''
                            THEN ''
                            ELSE CONCAT(' (', h.brandname, ')')
                        END
                    ),
                    COALESCE(h.dmdnost, ''),
                    COALESCE(h.strecode, ''),
                    COALESCE(h.formcode, '')
                ) AS drug_description,
                COALESCE(NULLIF(hp.stockbal, ''), 'No Stock Balance') AS stock_balance,
                hp.begbal AS beg_balance,
                (hp.begbal - hp.stockbal) AS total_dispensed,
                hp.dmselprice AS selling_price,
                hp.dmdprdte AS entry_date,
                COALESCE(NULLIF(hp.expiry, ''), 'No Expiration Date') AS expiration_date,
                chg.chrgdesc AS account_type,

                CASE
                    WHEN hp.expiry < CURDATE() AND hp.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                    WHEN hp.expiry < CURDATE() AND hp.isActive = 'Y' THEN 'EXPIRED'
                    WHEN hp.expiry >= CURDATE() AND hp.isActive = 'N' THEN 'PULLOUT'
                    WHEN hp.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                    ELSE 'GOOD'
                END AS status,

                CASE
                    WHEN hp.expiry < CURDATE() AND hp.isActive = 'Y' THEN 0
                    WHEN hp.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
                    WHEN hp.expiry < CURDATE() AND hp.isActive = 'N' THEN 4
                    WHEN hp.expiry >= CURDATE() AND hp.isActive = 'N' THEN 3
                    ELSE 2
                END AS trigger_order,

                hp.delintkey AS id,
                hp.dmdrem AS remarks

            $baseFrom
            $where
            ORDER BY $orderColumn $orderDir
        ";

        // LIMIT
        if ($length != -1) {
            $sql .= " LIMIT :start, :length";
        }

        $stmt = $pdo->prepare($sql);

        // Bind params
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        if ($length != -1) {
            $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
            $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSql = "
            SELECT
                SUM(CASE WHEN hp.expiry >= CURDATE()
                    THEN hp.stockbal ELSE 0 END) AS totalStock,

                SUM(CASE WHEN hp.expiry >= CURDATE()
                    THEN hp.stockbal * hp.dmselprice ELSE 0 END) AS totalValue,

                SUM(CASE WHEN hp.expiry < CURDATE()
                    THEN hp.stockbal ELSE 0 END) AS expiredStock,

                SUM(CASE WHEN hp.expiry < CURDATE()
                    THEN hp.stockbal * hp.dmselprice ELSE 0 END) AS expiredValue

            $baseFrom
            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

        /* =========================
           FINAL JSON RESPONSE
        ========================= */
        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($total),
            "recordsFiltered" => intval($total),
            "data" => $data,
            "totals" => $totals
        ]);
    } catch (Exception $e) {

        // VERY IMPORTANT: prevents DataTables crash
        echo json_encode([
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage()
        ]);
    }

    exit;
}
