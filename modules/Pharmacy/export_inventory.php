<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

/* =========================
   HEADERS (Excel)
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=inventory_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   INPUT FILTERS (same as DataTable)
========================= */
$startDate = $_GET['startDate'] ?? '';
$endDate   = $_GET['endDate'] ?? '';
$search    = $_GET['search'] ?? '';

/* =========================
   WHERE CONDITIONS
========================= */
$where = "WHERE 1=1
            AND inv.lotno IS NOT NULL
            AND inv.lotno != ''
            AND inv.isActive IS NOT NULL
            AND inv.isActive != 'N'
            AND inv.stock_status IS NOT NULL
        ";

$params = [];

/* DATE FILTER */
if (!empty($startDate) && !empty($endDate)) {
    $where .= " AND hp.dmdprdte BETWEEN :startDate AND :endDate";
    $params[':startDate'] = $startDate . " 00:00:00";
    $params[':endDate']   = $endDate . " 23:59:59";
}

/* SEARCH FILTER */
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
        OR COALESCE(NULLIF(hp.dmselprice, ''), 'No Selling Price') LIKE :search
        OR COALESCE(NULLIF(hp.dmduprice, ''), 'No Unit Price') LIKE :search
        OR hp.dmdprdte LIKE :search
        OR hp.expiry LIKE :search
        OR hp.dmhdrsub LIKE :search

        OR (
            CASE
                WHEN hp.expiry < CURDATE() AND hp.isActive = 'N' THEN 'EXPIRED/PULLOUT'
                WHEN hp.expiry < CURDATE() AND hp.isActive = 'Y' THEN 'EXPIRED'
                WHEN hp.expiry >= CURDATE() AND hp.isActive = 'N' THEN 'PULLOUT'
                WHEN hp.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
                ELSE 'GOOD'
            END
        ) LIKE :search
    )";
    $params[':search'] = "%$search%";
}

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
   MAIN QUERY
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

    ORDER BY trigger_order ASC, entry_date DESC
";

/* =========================
   EXECUTE
========================= */
$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->execute();

$thead = "
   background-color:#198754;
   color:#ffffff;
   font-weight:bold;
";

/* =========================
   OUTPUT TABLE
========================= */
echo "<table border='1'>";

echo "<tr>
    <th style='{$thead}'>LOT NUMBER</th>
    <th style='{$thead}'>DRUG AND MEDICINE</th>
    <th style='{$thead}'>STOCK BALANCE</th>
    <th style='{$thead}'>BEGINNING BALANCE</th>
    <th style='{$thead}'>TOTAL DISPENSED</th>
    <th style='{$thead}'>TOTAL RETURNED</th>
    <th style='{$thead}'>NET DISPENSED</th>
    <th style='{$thead}'>ADJUSMENT (ADDITION)</th>
    <th style='{$thead}'>ADJUSTMENT (DEDUCTION)</th>
    <th style='{$thead}'>EXPECTED ENDING BALANCE</th>
    <th style='{$thead}'>SELLING PRICE</th>
    <th style='{$thead}'>UNIT PRICE</th>
    <th style='{$thead}'>ENTRY DATE</th>
    <th style='{$thead}'>EXPIRY DATE</th>
    <th style='{$thead}'>ACCOUNT TYPE</th>
    <th style='{$thead}'>STATUS</th>
</tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    /* =========================
       COLOR ENTIRE ROW
       (Only across existing 16 columns)
    ========================= */
    $rowStyle = "";

    switch (strtoupper(trim($row['status']))) {
        case 'EXPIRED':
            $rowStyle = "background-color:#ff9999;";
            break;

        case 'EXPIRED/PULLOUT':
            $rowStyle = "background-color:#ff4d4d; color:#ffffff;";
            break;

        case 'NEAR EXPIRE':
            $rowStyle = "background-color:#fff599;";
            break;

        case 'PULLOUT':
            $rowStyle = "background-color:#d9d9d9;";
            break;

        default:
            $rowStyle = "background-color:#ffffff;";
            break;
    }

    echo "<tr>";

    echo "<td style='{$rowStyle} mso-number-format:\"\\@\";'>{$row['lot_number']}</td>"; // 1
    echo "<td style='{$rowStyle}'>{$row['drug_description']}</td>"; // 2
    echo "<td style='{$rowStyle}'>{$row['stock_balance']}</td>"; // 3
    echo "<td style='{$rowStyle}'>{$row['beg_balance']}</td>"; // 4
    echo "<td style='{$rowStyle}'>{$row['total_dispensed']}</td>"; // 5
    echo "<td style='{$rowStyle}'>{$row['total_returned']}</td>"; // 6
    echo "<td style='{$rowStyle}'>{$row['net_dispensed']}</td>"; // 7
    echo "<td style='{$rowStyle}'>{$row['adjustment_addition']}</td>"; // 8
    echo "<td style='{$rowStyle}'>{$row['adjustment_deduction']}</td>"; // 9
    echo "<td style='{$rowStyle}'>{$row['calculated_expected_stock']}</td>"; // 10
    echo "<td style='{$rowStyle}'>{$row['selling_price']}</td>"; // 11
    echo "<td style='{$rowStyle}'>{$row['unit_price']}</td>"; // 12
    echo "<td style='{$rowStyle}'>{$row['entry_date']}</td>"; // 13
    echo "<td style='{$rowStyle}'>{$row['expiration_date']}</td>"; // 14
    echo "<td style='{$rowStyle}'>{$row['account_type']}</td>"; // 15
    echo "<td style='{$rowStyle}'>{$row['status']}</td>"; // 16

    echo "</tr>";
}

echo "</table>";
