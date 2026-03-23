<?php
// modules/PatientController.php

require '../db.php';

// Get DataTables parameters
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 15);
$searchValue = $_GET['search']['value'] ?? '';
$orderColumnIndex = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'asc';

// Map column indexes to database columns
$columns = [
    0 => 'logs.hpercode',
    1 => 'patient',
    2 => 'p.patbdate',
    3 => 'logs.encounter',
    4 => 'logs.encounter_date',
    5 => 'logs.discharge_date',
    6 => 'logs.discharge_by'
];

$orderColumn = $columns[$orderColumnIndex] ?? 'logs.discharge_date';

// Base SQL
$sqlBase = "
    FROM (
        SELECT hpercode, 'ADMISSION' AS encounter, admdate AS encounter_date, disdate AS discharge_date, discharge_by 
        FROM hadmlog
        UNION ALL
        SELECT hpercode, 'EMERGENCY', erdate, erdtedis, discharge_by
        FROM herlog
        UNION ALL
        SELECT hpercode, 'OUTPATIENT', opddate, opddtedis, discharge_by
        FROM hopdlog
    ) AS logs
    JOIN hperson p ON logs.hpercode = p.hpercode
    LEFT JOIN hpersonal h ON logs.discharge_by = h.employeeid
";

// Total records without filtering
$totalQuery = $pdo->query("SELECT COUNT(*) $sqlBase");
$totalRecords = $totalQuery->fetchColumn();

// Filtering
$filterSql = "";
$params = [];

if (!empty($searchValue)) {
    $filterSql = " WHERE logs.hpercode LIKE :search OR p.patlast LIKE :search OR p.patfirst LIKE :search OR logs.encounter LIKE :search ";
    $params[':search'] = "%$searchValue%";
}

// Total records after filtering
$stmtFiltered = $pdo->prepare("SELECT COUNT(*) $sqlBase $filterSql");
$stmtFiltered->execute($params);
$totalFiltered = $stmtFiltered->fetchColumn();

// Main query with ordering and limit
$sql = "
    SELECT logs.hpercode,
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
    p.patbdate AS birthdate,
    logs.encounter,
    logs.encounter_date,
    logs.discharge_date,
    CONCAT(
        h.lastname, ', ', h.firstname,
        CASE WHEN h.middlename IS NULL OR h.middlename = '' THEN '' ELSE CONCAT(' ', h.middlename) END
    ) AS discharge_by
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
