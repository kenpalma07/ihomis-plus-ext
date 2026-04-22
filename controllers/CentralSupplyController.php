<?php
require '../db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'supplyInventory':
        loadCSInventory($pdo);
        break;
    case 'pullout':
        pullOutDrug($pdo);
        break;
    case 'csPullOutHistory':
        loadCSPullOutHistory($pdo);
        break;
    case 'undoPullOutCS':
        undoPullOutCS($pdo);
        break;
    case 'issuedSupplies':
        loadCSIssued($pdo);
        break;
}

function loadCSInventory($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;

        $search = $_POST['search']['value'] ?? '';

        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';

        $where = "WHERE 1=1
            AND hc2.isActive IS NOT NULL AND hc2.isActive != 'N'
        ";
        $params = [];

        // AND hc2.itemcode IS NOT NULL AND hc2.itemcode != ''
        // AND hc2.stockbal IS NOT NULL AND hc2.stockbal > 0

        if (!empty($startDate)) {
            $where .= " AND hc2.cl2dteas >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND hc2.cl2dteas <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        if (!empty($search)) {
            $where .= " AND (
                COALESCE(NULLIF(hc2.itemcode, ''), 'No Lot Number') LIKE :search
                OR h2.cl2desc LIKE :search

                OR (
                    CASE 
                        WHEN hc2.stockbal IS NULL OR hc2.stockbal = '0' THEN 'No Stock Balance'
                        ELSE hc2.stockbal
                    END
                ) LIKE :search
                OR COALESCE(NULLIF(hc2.cl2retprc, ''), 'No Selling Price') LIKE :search
                OR DATE(hc2.cl2dteas) LIKE :search
                OR DATE(hc2.expiry) LIKE :search
                OR DATE(hc2.cl2dtmd) LIKE :search
                OR chg.chrgdesc LIKE :search
                OR hc2.stockloc LIKE :search
                OR (
                    CASE
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'Y' THEN 'EXPIRED'
                        WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 'PULLOUT'
                        WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                        ELSE 'GOOD'
                    END
                ) LIKE :search
                OR hc2.remarks LIKE :search
            )";
            $params[':search'] = "%$search%";
        }

        $baseFrom = "
            FROM hclass2h hc2
            LEFT JOIN hclass2 h2
                ON h2.cl2comb = hc2.cl2comb
            LEFT JOIN hcharge chg 
                ON hc2.hclass2sub = chg.chrgcode
        ";

        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $columns = [
            0 => 'lot_number',
            1 => 'supply_name',
            2 => 'stock_balance',
            3 => 'beg_balance',
            4 => 'total_dispensed',
            5 => 'selling_price',
            6 => 'entry_date',
            7 => 'expiry_date',
            8 => 'date_modified',
            9 => 'account_type',
            10 => 'status',
            11 => 'cost_center',
            12 => 'cs_remarks'
        ];

        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'desc');
        $orderDir = $orderDir === 'asc' ? 'ASC' : 'DESC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'entry_date';

        // ✅ DEFAULT: latest first
        $secondarySort = "entry_date DESC";

        // ✅ If user sorts → override
        if (isset($_POST['order'])) {
            $secondarySort = "$orderColumn $orderDir";
        }

        $sql = "
            SELECT * FROM (
                SELECT
                    COALESCE(NULLIF(hc2.itemcode, ''), 'No Lot Number') AS lot_number,
                    CONCAT_WS(' ', h2.cl2desc, h2.uomcode) AS supply_name,

                    hc2.cl2dtmd,
                    hc2.itemcode,

                    CASE 
                        WHEN hc2.stockbal IS NULL OR hc2.stockbal = '0' THEN 'No Stock Balance'
                        ELSE hc2.stockbal
                    END AS stock_balance,

                    hc2.begbal AS beg_balance,
                    (hc2.begbal - hc2.stockbal) AS total_dispensed,
                    
                    COALESCE(NULLIF(hc2.cl2retprc, ''), 'No Selling Price') AS selling_price,
                    
                    hc2.cl2dteas AS entry_date,
                    hc2.expiry AS expiry_date,
                    hc2.cl2dtmd AS date_modified,
                    chg.chrgdesc AS account_type,

                    CASE
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                        WHEN hc2.expiry < CURDATE() THEN 'EXPIRED'
                        WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 'PULLOUT'
                        WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                        ELSE 'GOOD'
                    END AS status,

                    CASE
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'Y' THEN 0
                        WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
                        WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 3
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 4
                        ELSE 2
                    END AS trigger_order,

                    hc2.stockloc AS cost_center,
                    hc2.remarks AS cs_remarks,
                    hc2.delintkey AS id

                $baseFrom
                $where
            ) AS t
            ORDER BY trigger_order ASC, $secondarySort
        ";

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

        $totalSql = "
            SELECT
                SUM(CASE WHEN hc2.expiry >= CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) ELSE 0 END) AS totalStock,

                SUM(CASE WHEN hc2.expiry >= CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) * hc2.cl2retprc ELSE 0 END) AS totalValue,

                SUM(CASE WHEN hc2.expiry < CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) ELSE 0 END) AS expiredStock,

                SUM(CASE WHEN hc2.expiry < CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) * hc2.cl2retprc ELSE 0 END) AS expiredValue

            FROM hclass2h hc2

            LEFT JOIN hclass2 h2
                ON h2.cl2comb = hc2.cl2comb

            LEFT JOIN hcharge chg 
                ON hc2.hclass2sub = chg.chrgcode

            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

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
    $cl2dteas = $_POST['cl2dteas'] ?? '';
    $itemcode = $_POST['itemcode'] ?? '';
    $remarks  = $_POST['remarks'] ?? '';

    if (!$cl2dteas || !$itemcode) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid item reference"
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

    $sql = "UPDATE hclass2h
            SET isActive = 'N',
                remarks = :remarks
            WHERE cl2dteas = :cl2dteas
              AND itemcode = :itemcode
              AND isActive = 'Y'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cl2dteas' => $cl2dteas,
        ':itemcode' => $itemcode,
        ':remarks'  => $remarks
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Item already pulled out or not found"
        ]);
    }
}

function loadCSPullOutHistory($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;

        $search = $_POST['search']['value'] ?? '';

        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';

        $where = "WHERE 1=1
            AND hc2.itemcode IS NOT NULL AND hc2.itemcode != ''
            AND hc2.expiry IS NOT NULL
            AND hc2.stockloc IN ('CSSR', 'CSR')
            AND hc2.isActive = 'N'
        ";

        $params = [];

        // DATE FILTERS
        if (!empty($startDate)) {
            $where .= " AND hc2.cl2dteas >= :startDate";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND hc2.cl2dteas <= :endDate";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        // SEARCH
        if (!empty($search)) {
            $where .= " AND (
                COALESCE(NULLIF(hc2.itemcode, ''), 'No Lot Number') LIKE :search
                OR h2.cl2desc LIKE :search

                OR (
                    CASE 
                        WHEN hc2.stockbal IS NULL OR hc2.stockbal = 0 
                        THEN 'No Stock Balance'
                        ELSE hc2.stockbal
                    END
                ) LIKE :search

                OR COALESCE(NULLIF(hc2.cl2retprc, ''), 'No Selling Price') LIKE :search
                OR DATE(hc2.cl2dteas) LIKE :search
                OR DATE(hc2.expiry) LIKE :search
                OR DATE(hc2.cl2dtmd) LIKE :search

                OR chg.chrgdesc LIKE :search
                OR hc2.stockloc LIKE :search

                OR (
                    CASE
                        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                        WHEN hc2.expiry < CURDATE() THEN 'EXPIRED'
                        WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 'PULLOUT'
                        WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                        ELSE 'GOOD'
                    END
                ) LIKE :search

                OR (hc2.begbal - hc2.stockbal) LIKE :search

                OR hc2.remarks LIKE :search
            )";

            $params[':search'] = "%$search%";
        }

        $baseFrom = "
            FROM hclass2h hc2
            LEFT JOIN hclass2 h2 ON h2.cl2comb = hc2.cl2comb
            LEFT JOIN hcharge chg ON hc2.hclass2sub = chg.chrgcode
        ";

        // TOTAL COUNT (WITH FILTER)
        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $recordsFiltered = $countStmt->fetchColumn();

        // TOTAL COUNT (WITHOUT SEARCH)
        $countTotalSql = "
            SELECT COUNT(*) $baseFrom 
            WHERE 1=1
            AND hc2.itemcode IS NOT NULL AND hc2.itemcode != ''
            AND hc2.expiry IS NOT NULL
            AND hc2.stockloc IN ('CSSR', 'CSR')
            AND hc2.isActive = 'N'
        ";
        $countTotalStmt = $pdo->prepare($countTotalSql);
        $countTotalStmt->execute();
        $recordsTotal = $countTotalStmt->fetchColumn();

        // ORDERING (SAFE)
        $columns = [
            0 => 'hc2.itemcode',
            1 => 'h2.cl2desc',
            2 => 'hc2.stockbal',
            3 => 'hc2.cl2retprc',
            4 => 'hc2.cl2dteas',
            5 => 'hc2.expiry',
            6 => 'hc2.cl2dtmd',
            7 => 'chg.chrgdesc',
            8 => 'trigger_order'
        ];

        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
        $orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'hc2.itemcode';

        // MAIN QUERY
        $sql = "
            SELECT
                COALESCE(NULLIF(hc2.itemcode, ''), 'No Lot Number') AS lot_number,
                CONCAT_WS(' ', h2.cl2desc, h2.uomcode) AS supply_name,

                CASE 
                    WHEN hc2.stockbal IS NULL OR hc2.stockbal = 0 THEN 'No Stock Balance'
                    ELSE hc2.stockbal
                END AS stock_balance,
                hc2.begbal AS beg_balance,
                (hc2.begbal - hc2.stockbal) AS total_dispensed,
                hc2.cl2dtmd,
                hc2.itemcode,
                
                COALESCE(NULLIF(hc2.cl2retprc, ''), 'No Selling Price') AS selling_price,
                
                hc2.cl2dteas AS entry_date,
                hc2.expiry AS expiry_date,
                hc2.cl2dtmd AS date_modified,
                chg.chrgdesc AS account_type,

                CASE
                    WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                    WHEN hc2.expiry < CURDATE() THEN 'EXPIRED'
                    WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 'PULLOUT'
                    WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                    ELSE 'GOOD'
                END AS status,

                hc2.remarks AS cs_remarks,

                CASE
                    WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'Y' THEN 0
                    WHEN hc2.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
                    WHEN hc2.expiry >= CURDATE() AND hc2.isActive = 'N' THEN 3
                    WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'N' THEN 4
                    ELSE 2
                END AS trigger_order,

                hc2.stockloc AS cost_center

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

        // TOTALS
        $totalSql = "
            SELECT
                SUM(CASE WHEN hc2.expiry >= CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) ELSE 0 END) AS totalStock,

                SUM(CASE WHEN hc2.expiry >= CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) * hc2.cl2retprc ELSE 0 END) AS totalValue,

                SUM(CASE WHEN hc2.expiry < CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) ELSE 0 END) AS expiredStock,

                SUM(CASE WHEN hc2.expiry < CURDATE()
                    THEN CAST(hc2.stockbal AS DECIMAL(10,2)) * hc2.cl2retprc ELSE 0 END) AS expiredValue

            $baseFrom
            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
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

function undoPullOutCS($pdo)
{
    $cl2dteas  = $_POST['cl2dteas'] ?? '';
    $itemcode = $_POST['itemcode'] ?? '';
    $remarks  = $_POST['remarks'] ?? '';

    if (!$cl2dteas || !$itemcode) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid item reference"
        ]);
        return;
    }

    if (!$remarks) {
        echo json_encode([
            "status" => "error",
            "message" => "Remarks is required"
        ]);
        return;
    }

    $sql = "UPDATE hclass2h
            SET isActive = 'Y',
                remarks = :remarks
            WHERE cl2dteas = :cl2dteas
              AND itemcode = :itemcode
              AND isActive = 'N'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cl2dteas' => $cl2dteas,
        ':itemcode' => $itemcode,
        ':remarks'  => $remarks
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Item not found or already active"
        ]);
    }

    exit;
}

function loadCSIssued($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;

        $startDate = $_POST['startDate'] ?? '';
        $endDate   = $_POST['endDate'] ?? '';
        $search = $_POST['search']['value'] ?? '';

        $where = "WHERE 1=1
            AND hrq.qty IS NOT NULL AND hrq.qty > 0
        ";
        $params = [];

        if (!empty($startDate) && !empty($endDate)) {
            $where .= " AND hrq.issuedte BETWEEN :startDate AND :endDate";
            $params[':startDate'] = $startDate . " 00:00:00";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        if (!empty($search)) {
            $where .= " AND (
                hrq.itemcode LIKE :search
                OR hc.cl2desc LIKE :search
                OR hp.hpercode LIKE :search

                OR CONCAT(hp.patfirst, ' ', hp.patlast) LIKE :search
                OR CONCAT(hp.patlast, ', ', hp.patfirst) LIKE :search
                OR CONCAT(hp.patfirst, ' ', hp.patlast, ' ', hp.patmiddle) LIKE :search
                OR CONCAT(hp.patlast, ', ', hp.patfirst, ' ', hp.patmiddle) LIKE :search
                OR hp.patlast LIKE :search
                OR hp.patfirst LIKE :search
                OR hp.patmiddle LIKE :search

                OR hrq.pcchrgcode LIKE :search
                OR chg.chrgdesc LIKE :search
                OR CONCAT(hpl.lastname, ', ', hpl.firstname) LIKE :search
                OR hpl.firstname LIKE :search
                OR hpl.lastname LIKE :search
            )";
        }

        $baseFrom = "
            FROM hrqdissue hrq

            LEFT JOIN hclass2 hc
                ON hrq.cl2comb = hc.cl2comb

            LEFT JOIN hperson hp
                ON hrq.hpercode = hp.hpercode

            LEFT JOIN hrqd hq
                ON hrq.docointkey = hq.docointkey
                AND hrq.cl2comb = hq.cl2comb

            -- ✅ FIXED RETURN JOIN
            LEFT JOIN (
                SELECT docointkey, cl2comb, SUM(qty) AS qty
                FROM hrqdreturn
                GROUP BY docointkey, cl2comb
            ) hr
                ON hrq.docointkey = hr.docointkey
                AND hrq.cl2comb = hr.cl2comb

            LEFT JOIN hcharge chg 
                ON hrq.chrgcode = chg.chrgcode

            LEFT JOIN hpersonal hpl
                ON hrq.givenby = hpl.employeeid
        ";

        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $columns = [
            0 => 'hq.dodate',
            1 => 'hrq.issuedte',
            2 => 'turnaround_time',
            3 => 'hrq.itemcode',
            4 => 'hc.cl2desc',
            5 => 'hp.hpercode',
            6 => 'patient_name',
            7 => 'hrq.pcchrgcode',
            8 => 'hq.pchrgqty',
            9 => 'hrq.qty',
            10 => 'hrq.pchrgup',
            11 => 'hrq.pcchrgamt',
            12 => 'hr.qty',
            13 => 'chg.chrgdesc',
            14 => 'issued_by'
        ];

        $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
        $orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'hq.dodate';

        $sql = "
            SELECT
            hq.dodate AS order_date,
            hrq.issuedte AS issued_date,
            CONCAT(
                FLOOR(TIMESTAMPDIFF(SECOND, hq.dodate, hrq.issuedte) / 86400),
                'days - ',
                SEC_TO_TIME(
                    MOD(
                        TIMESTAMPDIFF(SECOND, hq.dodate, hrq.issuedte), 86400
                    )
                )
            ) AS turnaround_time,
            hrq.itemcode AS lot_number,
            hc.cl2desc AS supply_name,
            hp.hpercode AS hpercode,
            CONCAT(
                hp.patlast, ', ',
                hp.patfirst,
                CASE
                    WHEN hp.patsuffix IS NULL OR hp.patsuffix IN ('NOTAP','N/A') THEN ''
                    ELSE CONCAT(' ', hp.patsuffix)
                END,
                CASE
                    WHEN hp.patmiddle IS NULL OR hp.patmiddle IN ('','N/A') THEN ''
                    ELSE CONCAT(', ', hp.patmiddle)
                END
            ) AS patient,
            hrq.pcchrgcod AS charge_code,
            hq.pchrgqty AS request_quantity,
            hrq.qty AS issued_quantity,
            hrq.pchrgup AS selling_price,
            hrq.pcchrgamt AS total_amount,
            hr.qty AS returned_quantity,
            chg.chrgdesc AS account_type,
            CONCAT(
                hpl.lastname, ', ',
                hpl.firstname
            ) AS issued_by
             
            $baseFrom
            $where
            ORDER BY $orderColumn $orderDir
        ";

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

        $totalSql = "
            SELECT
                COUNT(*) AS total_records,
                SUM(hrq.qty) AS total_issued,
                SUM(IFNULL(hr.qty, 0)) AS total_returned
            $baseFrom
            $where
        ";

        $totalStmt = $pdo->prepare($totalSql);
        $totalStmt->execute($params);
        $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

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
}
