<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laboratory_turnaround_time_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

$reqStart = $_GET['reqStart'] ?? '';
$reqEnd   = $_GET['reqEnd'] ?? '';
$chgStart = $_GET['chgStart'] ?? '';
$chgEnd   = $_GET['chgEnd'] ?? '';
$search    = $_GET['search'] ?? '';

$where = "WHERE hprocm.costcenter = 'LABOR'";
$params = [];

if (!empty($reqStart) && !empty($reqEnd)) {
    $where .= " AND hdocord.dodate BETWEEN :reqStart AND :reqEnd";
    $params[':reqStart'] = $reqStart . " 00:00:00";
    $params[':reqEnd']   = $reqEnd . " 23:59:59";
}

if (!empty($chgStart) && !empty($chgEnd)) {
    $where .= " AND hdocord.charged_date BETWEEN :chgStart AND :chgEnd";
    $params[':chgStart'] = $chgStart . " 00:00:00";
    $params[':chgEnd']   = $chgEnd . " 23:59:59";
}

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

$sql = "
SELECT
	hperson.hpercode,
	CONCAT (
        hperson.patlast,
        ', ',
        `hperson`.`patfirst`,
        CASE
            WHEN hperson.patsuffix IS NULL
            OR hperson.patsuffix = 'NOTAP'
            OR hperson.patsuffix = 'N/A' THEN ''
            ELSE CONCAT (' ', hperson.patsuffix)
        END,
        CASE
            WHEN hperson.patmiddle IS NULL
            OR hperson.patmiddle = ''
            OR hperson.patmiddle = 'N/A' THEN ''
            ELSE CONCAT (', ', hperson.patmiddle)
        END
    ) AS patient,
    hprocm.procdesc AS `procedure`,
    (
                CASE
                        WHEN henctr.toecode = 'ADM' THEN 'ADMISSION'
                        WHEN henctr.toecode = 'OPD' THEN 'OUTPATIENT'
                        WHEN henctr.toecode = 'ER' THEN 'EMERGENCY'
                        WHEN henctr.toecode = 'ERADM' THEN 'EMERGENCY->ADMISSION'
                        WHEN henctr.toecode = 'OPADM' THEN 'OUTPATIENT->ADMISSION'
                END
        ) as encounter,
        DATE_FORMAT (hdocord.dodate, '%m/%d/%Y %r') as `request_date`,
        DATE_FORMAT (hdocord.charged_date, '%m/%d/%Y %r') as `charged_date`,
        CONCAT(
    FLOOR(TIMESTAMPDIFF(SECOND, hdocord.dodate, hdocord.charged_date) / 86400),
    ' days - ',
    SEC_TO_TIME(
        MOD(
            TIMESTAMPDIFF(SECOND, hdocord.dodate, hdocord.charged_date),
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
        `hdocord`.`ordcon`,
    `hdocord`.`remarks`
FROM hdocord
LEFT JOIN hperson
	ON hperson.hpercode = hdocord.hpercode
LEFT JOIN hprocm 
    ON hprocm.proccode = hdocord.proccode
LEFT JOIN henctr
    ON henctr.hpercode = hdocord.hpercode
    AND henctr.enccode = hdocord.enccode
$where
ORDER BY
    hdocord.dodate DESC
";
$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    if (strpos($key, 'Start') !== false || strpos($key, 'End') !== false) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    } else {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$stmt->execute();

echo "<table border='1'>";
echo "<tr>
        <th>Hpercode</th>
        <th>Patient</th>
        <th>Procedure</th>
        <th>Encounter</th>
        <th>Request Date</th>
        <th>Charged Date</th>
        <th>Turnaround Time (DHMS)</th>
        <th>eStatus</th>
        <th>Encounter Status</th>
        <th>Order Condition</th>
        <th>Remarks</th>
    </tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['hpercode']) . "</td>";
    echo "<td>" . htmlspecialchars($row['patient']) . "</td>";
    echo "<td>" . htmlspecialchars($row['procedure']) . "</td>";
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
?>