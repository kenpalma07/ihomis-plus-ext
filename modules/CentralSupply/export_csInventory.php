<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

/* =========================
   HEADERS (Excel)
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=cs_inventory_report.xls");
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
            AND hc2.isActive = 'Y'
            ";

$params = [];

/* DATE FILTER */
if (!empty($startDate)) {
    $where .= " AND hc2.cl2dteas >= :startDate";
    $params[':startDate'] = $startDate . " 00:00:00";
}

if (!empty($endDate)) {
    $where .= " AND hc2.cl2dteas <= :endDate";
    $params[':endDate'] = $endDate . " 23:59:59";
}

/* SEARCH FILTER */
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

                OR CAST(hc2.cl2retprc AS CHAR) LIKE :search
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
    )";
    $params[':search'] = '%' . $search . '%';
}

/* =========================
   MAIN QUERY
========================= */
$sql = "
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
        WHEN hc2.expiry < CURDATE() AND hc2.isActive = 'Y' THEN 'EXPIRED'
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
    hc2.delintkey AS id

FROM hclass2h hc2

LEFT JOIN hclass2 h2
    ON h2.cl2comb = hc2.cl2comb

LEFT JOIN hcharge chg 
    ON hc2.hclass2sub = chg.chrgcode
$where
ORDER BY trigger_order, hc2.cl2dteas DESC, hc2.cl2dtmd DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   OUTPUT EXCEL
========================= */

echo "<table border='1'>";

echo "<tr>
        <th>Lot Number</th>
        <th>Supply Name</th>
        <th>Stock Balance</th>
        <th>Beginning Balance</th>
        <th>Drugs Dispensed</th>
        <th>Selling Price</th>
        <th>Entry Date</th>
        <th>Expiration Date</th>
        <th>Account Type</th>
        <th>Status</th>
    </tr>";

foreach ($results as $row) {
    echo "<tr>";
    echo "<td style='mso-number-format:\"\\@\";'>" . $row['lot_number'] . "</td>";
    echo "<td>" . $row['supply_name'] . "</td>";
    echo "<td>" . $row['stock_balance'] . "</td>";
    echo "<td>" . $row['beg_balance'] . "</td>";
    echo "<td>" . $row['total_dispensed'] . "</td>";
    echo "<td style='mso-number-format:\"0.00\";'>" . $row['selling_price'] . "</td>";
    echo "<td>" . $row['entry_date'] . "</td>";
    echo "<td>" . $row['expiry_date'] . "</td>";
    echo "<td>" . $row['account_type'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}

echo "</table>";
