<?php
// Controllers/EmergencyController.php
require '../db.php';

// DataTables parameters
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 15);
$searchValue = $_GET['search']['value'] ?? '';
$orderColumnIndex = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

// Date range filters
$regStart = $_GET['regStart'] ?? '';
$regEnd   = $_GET['regEnd'] ?? '';
$disStart = $_GET['disStart'] ?? '';
$disEnd   = $_GET['disEnd'] ?? '';

// Map column index to database columns
$columns = [
    0 => 'logs.hpercode',
    1 => 'patient',
    2 => 'p.patbdate',
    3 => 'logs.erdate',
    4 => 'logs.erdtedis',
    5 => 'turnaround_dhms',
    6 => 'logs.discharge_by'
];
$orderColumn = $columns[$orderColumnIndex] ?? 'logs.erdate';

// Base SQL
$sqlBase = "
    FROM herlog AS logs
    JOIN hperson p ON logs.hpercode = p.hpercode
";

// Build filtering conditions
$filterConditions = [];
$params = [];

if (!empty($searchValue)) {
    $filterConditions[] = "(logs.hpercode LIKE :search OR p.patlast LIKE :search OR p.patfirst LIKE :search)";
    $params[':search'] = "%$searchValue%";
}

if (!empty($regStart)) {
    $filterConditions[] = "logs.erdate >= :regStart";
    $params[':regStart'] = $regStart;
}
if (!empty($regEnd)) {
    $filterConditions[] = "logs.erdate <= :regEnd";
    $params[':regEnd'] = $regEnd;
}
if (!empty($disStart)) {
    $filterConditions[] = "logs.erdtedis >= :disStart";
    $params[':disStart'] = $disStart;
}
if (!empty($disEnd)) {
    $filterConditions[] = "logs.erdtedis <= :disEnd";
    $params[':disEnd'] = $disEnd;
}

$filterSql = '';
if (count($filterConditions) > 0) {
    $filterSql = ' WHERE ' . implode(' AND ', $filterConditions);
}

// Total records without filtering
$totalQuery = $pdo->query("SELECT COUNT(*) $sqlBase");
$totalRecords = $totalQuery->fetchColumn();

// Total records after filtering
$stmtFiltered = $pdo->prepare("SELECT COUNT(*) $sqlBase $filterSql");
$stmtFiltered->execute($params);
$totalFiltered = $stmtFiltered->fetchColumn();

// Main query with ordering and limit
$sql = "
SELECT 
logs.hpercode,
CONCAT(
    p.patlast, ', ', p.patfirst,
    CASE WHEN p.patsuffix IS NULL OR p.patsuffix IN ('NOTAP','N/A') THEN '' ELSE CONCAT(' ', p.patsuffix) END,
    CASE WHEN p.patmiddle IS NULL OR p.patmiddle IN ('','N/A') THEN '' ELSE CONCAT(', ', p.patmiddle) END
) AS patient,
p.patbdate AS birthdate,
logs.erdate AS registration_date,
logs.erdtedis AS discharged_date,
CONCAT(
    FLOOR(TIMESTAMPDIFF(SECOND, logs.erdate, logs.erdtedis) / 86400),
    'days - ',
    SEC_TO_TIME(
        MOD(TIMESTAMPDIFF(SECOND, logs.erdate, logs.erdtedis),
        86400)
    )
) AS turnaround_dhms,
logs.discharge_by
$sqlBase
$filterSql
ORDER BY $orderColumn $orderDir
LIMIT :start, :length
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_NUM);

// Return JSON
echo json_encode([
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data" => $data
]);
