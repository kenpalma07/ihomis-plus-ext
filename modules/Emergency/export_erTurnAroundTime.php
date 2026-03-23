<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=emergency_turnaround_time.xls");
header("Pragma: no-cache");
header("Expires: 0");

// GET filters
$regStart = $_GET['regStart'] ?? '';
$regEnd   = $_GET['regEnd'] ?? '';
$disStart = $_GET['disStart'] ?? '';
$disEnd   = $_GET['disEnd'] ?? '';
$search   = $_GET['search'] ?? '';

// BASE QUERY
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(logs.hpercode LIKE :search 
                OR p.patlast LIKE :search 
                OR p.patfirst LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($regStart)) {
    $where[] = "logs.erdate >= :regStart";
    $params[':regStart'] = $regStart;
}
if (!empty($regEnd)) {
    $where[] = "logs.erdate <= :regEnd";
    $params[':regEnd'] = $regEnd;
}
if (!empty($disStart)) {
    $where[] = "logs.erdtedis >= :disStart";
    $params[':disStart'] = $disStart;
}
if (!empty($disEnd)) {
    $where[] = "logs.erdtedis <= :disEnd";
    $params[':disEnd'] = $disEnd;
}

$whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// MAIN QUERY
$sql = "
SELECT 
logs.hpercode,
CONCAT(
    p.patlast, ', ', p.patfirst,
    CASE 
        WHEN p.patsuffix IS NULL OR p.patsuffix IN ('NOTAP','N/A') THEN '' 
        ELSE CONCAT(' ', p.patsuffix) 
    END,
    CASE 
        WHEN p.patmiddle IS NULL OR p.patmiddle IN ('','N/A') THEN '' 
        ELSE CONCAT(', ', p.patmiddle) 
    END
) AS patient,
p.patbdate AS birthdate,
DATE_FORMAT(logs.erdate, '%m/%d/%Y %r') AS registration_date,
DATE_FORMAT(logs.erdtedis, '%m/%d/%Y %r') AS discharged_date,
CONCAT(
    FLOOR(TIMESTAMPDIFF(SECOND, logs.erdate, logs.erdtedis) / 86400),
    ' days - ',
    SEC_TO_TIME(
        MOD(TIMESTAMPDIFF(SECOND, logs.erdate, logs.erdtedis), 86400)
    )
) AS turnaround_dhms,
logs.discharge_by
FROM herlog AS logs
JOIN hperson p ON logs.hpercode = p.hpercode
$whereSql
ORDER BY logs.erdate DESC
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();

// OUTPUT
echo "<table border='1'>";
echo "<tr>
        <th>HPERCODE</th>
        <th>PATIENT</th>
        <th>BIRTHDATE</th>
        <th>REGISTRATION DATE</th>
        <th>DISCHARGED DATE</th>
        <th>TURNAROUND TIME</th>
        <th>DISCHARGED BY</th>
    </tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['hpercode']) . "</td>";
    echo "<td>" . htmlspecialchars($row['patient']) . "</td>";
    echo "<td>" . htmlspecialchars($row['birthdate']) . "</td>";
    echo "<td>" . htmlspecialchars($row['registration_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discharged_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['turnaround_dhms']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discharge_by']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>