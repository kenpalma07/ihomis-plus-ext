<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=radiology_turnaround_time.xls");
header("Pragma: no-cache");
header("Expires: 0");

// GET filters
$reqStart = $_GET['reqStart'] ?? '';
$reqEnd   = $_GET['reqEnd'] ?? '';
$chgStart = $_GET['chgStart'] ?? '';
$chgEnd   = $_GET['chgEnd'] ?? '';
$search   = $_GET['search'] ?? '';

// BASE WHERE
$where = "WHERE hprocm.costcenter = 'RADIO'";
$params = [];

// SEARCH (multi-column)
if (!empty($search)) {
    $where .= " AND (
        hperson.hpercode LIKE :search 
        OR hperson.patlast LIKE :search
        OR hperson.patfirst LIKE :search
        OR hprocm.procdesc LIKE :search
        OR (
             CASE
                WHEN henctr.toecode = 'ADM' THEN 'ADMISSION'
                WHEN henctr.toecode = 'OPD' THEN 'OUTPATIENT'
                WHEN henctr.toecode = 'ER' THEN 'EMERGENCY'
                WHEN henctr.toecode = 'ERADM' THEN 'EMERGENCY->ADMISSION'
                WHEN henctr.toecode = 'OPADM' THEN 'OUTPATIENT->ADMISSION'
            END
        ) LIKE :search
    )";
    $params[':search'] = "%$search%";
}

// REQUEST DATE FILTER
if (!empty($reqStart)) {
    $where .= " AND hdocord.dodate >= :reqStart";
    $params[':reqStart'] = $reqStart . " 00:00:00";
}

if (!empty($reqEnd)) {
    $where .= " AND hdocord.dodate <= :reqEnd";
    $params[':reqEnd'] = $reqEnd . " 23:59:59";
}

// CHARGED DATE FILTER
if (!empty($chgStart)) {
    $where .= " AND hdocord.charged_date >= :chgStart";
    $params[':chgStart'] = $chgStart . " 00:00:00";
}

if (!empty($chgEnd)) {
    $where .= " AND hdocord.charged_date <= :chgEnd";
    $params[':chgEnd'] = $chgEnd . " 23:59:59";
}

// MAIN QUERY
$sql = "
SELECT
hperson.hpercode,

CONCAT(
    hperson.patlast, ', ', hperson.patfirst,
    CASE 
        WHEN hperson.patsuffix IS NULL OR hperson.patsuffix IN ('NOTAP','N/A') 
        THEN '' 
        ELSE CONCAT(' ', hperson.patsuffix) 
    END,
    CASE 
        WHEN hperson.patmiddle IS NULL OR hperson.patmiddle IN ('','N/A') 
        THEN '' 
        ELSE CONCAT(', ', hperson.patmiddle) 
    END
) AS patient,

hprocm.procdesc AS procname,

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
        MOD(
            TIMESTAMPDIFF(SECOND,hdocord.dodate,hdocord.charged_date),
            86400
        )
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

FROM hdocord
LEFT JOIN hperson 
    ON hperson.hpercode = hdocord.hpercode
LEFT JOIN hprocm 
    ON hprocm.proccode = hdocord.proccode
LEFT JOIN henctr
    ON henctr.hpercode = hdocord.hpercode
    AND henctr.enccode = hdocord.enccode

$where

ORDER BY hdocord.dodate DESC
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
    <th>PROCEDURE</th>
    <th>ENCOUNTER</th>
    <th>REQUEST DATE</th>
    <th>CHARGED DATE</th>
    <th>TURNAROUND TIME</th>
    <th>STATUS</th>
    <th>ENCOUNTER STATUS</th>
    <th>ORDER CONDITION</th>
    <th>REMARKS</th>
</tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['hpercode']) . "</td>";
    echo "<td>" . htmlspecialchars($row['patient']) . "</td>";
    echo "<td>" . htmlspecialchars($row['procname']) . "</td>";
    echo "<td>" . htmlspecialchars($row['encounter']) . "</td>";
    echo "<td>" . htmlspecialchars($row['request_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['charged_date']) . "</td>";
    echo "<td>" . htmlspecialchars($row['turnaround_dhms']) . "</td>";
    echo "<td>" . htmlspecialchars($row['estatus']) . "</td>";
    echo "<td>" . htmlspecialchars($row['encounter_status']) . "</td>";
    echo "<td>" . htmlspecialchars($row['ordcon']) . "</td>";
    echo "<td>" . htmlspecialchars($row['remarks']) . "</td>";
    echo "</tr>";
}
echo "</table>";
