<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';
/* =========================
   HEADERS (Excel)
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=pulledOutSupplies_report.xls");
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
    AND hc2.itemcode IS NOT NULL AND hc2.itemcode != ''
    AND hc2.expiry IS NOT NULL
    AND hc2.stockloc IN ('CSSR', 'CSR')
    AND hc2.isActive = 'N'
";

$params = [];

/* DATE FILTER */
if (!empty($startDate) && !empty($endDate)) {
    $where .= " AND hc2.cl2dtmd BETWEEN :startDate AND :endDate";
    $params[':startDate'] = $startDate . " 00:00:00";
    $params[':endDate']   = $endDate . " 23:59:59";
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

/* =========================
   MAIN QUERY
========================= */
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
    ORDER BY trigger_order DESC
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
   OUTPUT EXCEL
========================= */
echo "<table border='1'>
    <thead>
        <tr>
            <th>LOT NUMBER</th>
            <th>SUPPLY NAME</th>
            <th>STOCK BALANCE</th>
            <th>BEGINNING BALANCE</th>
            <th>TOTAL DISPENSED</th>
            <th>SELLING PRICE</th>
            <th>ENTRY DATE</th>
            <th>EXPIRY DATE</th>
            <th>ACCOUNT TYPE</th>
            <th>STATUS</th>
            <th>CS REMARKS</th>
        </tr>
    </thead>
    <tbody>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td style='mso-number-format:\"\\@\";'>" . $row['lot_number'] . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['stock_balance']) . "</td>";
    echo "<td>" . $row['beg_balance'] . "</td>";
    echo "<td>" . $row['total_dispensed'] . "</td>";
    echo "<td>" . $row['selling_price'] . "</td>";
    echo "<td>" . $row['entry_date'] . "</td>";
    echo "<td>" . $row['expiry_date'] . "</td>";
    echo "<td>" . htmlspecialchars($row['account_type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . htmlspecialchars($row['cs_remarks']) . "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
