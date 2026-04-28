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
            AND hp.isActive = 'Y'
            -- AND hp.lotno IS NOT NULL AND hp.lotno != ''
            -- AND hp.isActive IS NOT NULL AND hp.isActive != 'N'
            -- AND hp.stock_status IS NOT NULL AND hp.stock_status != 'N'
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
        hp.lotno LIKE :search
        OR g.GENDESC LIKE :search
        OR h.brandname LIKE :search
        OR h.dmdnost LIKE :search
        OR h.strecode LIKE :search
        OR h.formcode LIKE :search
        OR hp.stockbal LIKE :search
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

/* =========================
   MAIN QUERY
========================= */
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
    COALESCE(NULLIF(hp.dmselprice, ''), 'No Selling Price') AS selling_price,
    COALESCE(NULLIF(hp.dmduprice, ''), 'No Unit Price') AS unit_price,
    hp.dmdprdte AS entry_date,
    COALESCE(NULLIF(hp.expiry, ''), 'No Expiration Date') AS expiration_date,
    hp.dmhdrsub AS account_type,

    CASE
        WHEN hp.expiry < CURDATE() AND hp.isActive = 'N' THEN 'EXPIRED/PULLOUT'
        WHEN hp.expiry < CURDATE() AND hp.isActive = 'Y' THEN 'EXPIRED'
        WHEN hp.expiry >= CURDATE() AND hp.isActive = 'N' THEN 'PULLOUT'
        WHEN hp.expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
        ELSE 'GOOD'
    END AS status

FROM hdmhdrprice hp

LEFT JOIN hdmhdr h
    ON hp.dmdcomb = h.dmdcomb
    AND hp.dmdctr = h.dmdctr

LEFT JOIN hdruggrp dg
    ON h.grpcode = dg.grpcode

LEFT JOIN hgen g
    ON dg.gencode = g.gencode

$where

ORDER BY hp.expiry ASC
";

/* =========================
   EXECUTE
========================= */
$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->execute();

/* =========================
   OUTPUT TABLE
========================= */
echo "<table border='1'>";

echo "<tr>
    <th>Lot Number</th>
    <th>Drug/Medicine</th>
    <th>Stock Balance</th>
    <th>Beginning Balance</th>
    <th>Total Dispensed</th>
    <th>Selling Price</th>
    <th>Entry Date</th>
    <th>Expiration Date</th>
    <th>Account Type</th>
    <th>Status</th>
</tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    echo "<tr>";
    echo "<td style='mso-number-format:\"\\@\";'>" . $row['lot_number'] . "</td>";
    echo "<td>" . $row['drug_description'] . "</td>";
    echo "<td>" . $row['stock_balance'] . "</td>";
    echo "<td>" . $row['beg_balance'] . "</td>";
    echo "<td>" . $row['total_dispensed'] . "</td>";
    echo "<td>" . $row['selling_price'] . "</td>";
    echo "<td>" . $row['entry_date'] . "</td>";
    echo "<td>" . $row['expiration_date'] . "</td>";
    echo "<td>" . $row['account_type'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}

echo "</table>";
