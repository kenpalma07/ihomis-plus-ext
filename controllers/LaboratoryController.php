<?php
require '../db.php';

// DataTables parameters
$draw = intval($_GET['draw'] ?? 1);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 15);
$searchValue = $_GET['search']['value'] ?? '';
$orderColumnIndex = $_GET['order'][0]['column'] ?? 0;
$orderDir = $_GET['order'][0]['dir'] ?? 'desc';

// Date filters
$reqStart = $_GET['reqStart'] ?? '';
$reqEnd   = $_GET['reqEnd'] ?? '';
$chgStart = $_GET['chgStart'] ?? '';
$chgEnd   = $_GET['chgEnd'] ?? '';

// Column mapping
$columns = [
    0 => 'hperson.hpercode',
    1 => 'patient',
    2 => 'hprocm.procdesc',
    3 => 'henctr.toecode',
    4 => 'hdocord.dodate',
    5 => 'hdocord.charged_date',
    6 => 'turnaround_dhms',
    7 => 'estatus',
    //8 => 'encounter_status'
];

$orderColumn = $columns[$orderColumnIndex] ?? 'hdocord.dodate';

// Base query
$sqlBase = "
FROM hdocord
LEFT JOIN hperson 
    ON hperson.hpercode = hdocord.hpercode
LEFT JOIN hprocm 
    ON hprocm.proccode = hdocord.proccode
LEFT JOIN henctr
    ON henctr.hpercode = hdocord.hpercode
    AND henctr.enccode = hdocord.enccode
WHERE hprocm.costcenter = 'LABOR'
";

// Filters
$filterConditions = [];
$params = [];

// Search
if (!empty($searchValue)) {
    $filterConditions[] = "(hperson.hpercode LIKE :search 
        OR hperson.patlast LIKE :search
        OR hperson.patfirst LIKE :search
        OR hprocm.procdesc LIKE :search)";
    $params[':search'] = "%$searchValue%";
}

// Request Date Filter
if (!empty($reqStart)) {
    $filterConditions[] = "hdocord.dodate >= :reqStart";
    $params[':reqStart'] = $reqStart;
}
if (!empty($reqEnd)) {
    $filterConditions[] = "hdocord.dodate <= :reqEnd";
    $params[':reqEnd'] = $reqEnd;
}

// Charged Date Filter
if (!empty($chgStart)) {
    $filterConditions[] = "hdocord.charged_date >= :chgStart";
    $params[':chgStart'] = $chgStart;
}
if (!empty($chgEnd)) {
    $filterConditions[] = "hdocord.charged_date <= :chgEnd";
    $params[':chgEnd'] = $chgEnd;
}

$filterSql = count($filterConditions) > 0 ? ' AND ' . implode(' AND ', $filterConditions) : '';

// Total records
$totalQuery = $pdo->query("SELECT COUNT(*) $sqlBase");
$totalRecords = $totalQuery->fetchColumn();

// Total filtered
$stmtFiltered = $pdo->prepare("SELECT COUNT(*) $sqlBase $filterSql");
$stmtFiltered->execute($params);
$totalFiltered = $stmtFiltered->fetchColumn();

// Main Query
$sql = "
SELECT
hperson.hpercode,

CONCAT(
    hperson.patlast, ', ', hperson.patfirst,
    CASE WHEN hperson.patsuffix IS NULL OR hperson.patsuffix IN ('NOTAP','N/A') THEN '' ELSE CONCAT(' ', hperson.patsuffix) END,
    CASE WHEN hperson.patmiddle IS NULL OR hperson.patmiddle IN ('','N/A') THEN '' ELSE CONCAT(', ', hperson.patmiddle) END
) AS patient,

hprocm.procdesc,

CASE
    WHEN henctr.toecode = 'ADM' THEN 'ADMISSION'
    WHEN henctr.toecode = 'OPD' THEN 'OUTPATIENT'
    WHEN henctr.toecode = 'ER' THEN 'EMERGENCY'
    WHEN henctr.toecode = 'ERADM' THEN 'EMERGENCY->ADMISSION'
    WHEN henctr.toecode = 'OPADM' THEN 'OUTPATIENT->ADMISSION'
END AS encounter,

DATE_FORMAT(hdocord.dodate,'%m/%d/%Y %r') AS request_date,
DATE_FORMAT(hdocord.charged_date,'%m/%d/%Y %r') AS charged_date,

CONCAT(
FLOOR(TIMESTAMPDIFF(SECOND,hdocord.dodate,hdocord.charged_date)/86400),
' days - ',
SEC_TO_TIME(
MOD(TIMESTAMPDIFF(SECOND,hdocord.dodate,hdocord.charged_date),86400)
)
) AS turnaround_dhms,

CASE
    WHEN hdocord.estatus = 'U' THEN 'Unserved'
    WHEN hdocord.estatus = 'P' THEN 'Pending'
    WHEN hdocord.estatus = 'S' THEN 'Served'
    ELSE 'Unknown'
END AS estatus,

CASE
    WHEN henctr.encstat = 'A' THEN 'Active'
    WHEN henctr.encstat = 'I' THEN 'Inactive'
    ELSE 'Unknown'
END AS encounter_status,

hdocord.remarks,
hdocord.ordcon

$sqlBase
$filterSql

ORDER BY $orderColumn $orderDir
";

// Apply LIMIT only if length > 0
if ($length > 0) {
    $sql .= " LIMIT :start, :length";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
} else {
    $stmt = $pdo->prepare($sql);
}

// Bind filter params
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
