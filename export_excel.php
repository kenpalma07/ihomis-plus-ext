<?php
require 'db.php';

// Same query as above
$sql = "
SELECT
    hdocord.docointkey AS intkey,
    DATE_FORMAT(hdocord.dodate, '%m/%d/%Y %r') AS request_date,
    DATE_FORMAT(hdocord.charged_date, '%m/%d/%Y %r') AS charged_date,
    CONCAT(
        FLOOR(TIMESTAMPDIFF(SECOND, hdocord.dodate, hdocord.charged_date) / 86400),
        '-',
        SEC_TO_TIME(
            MOD(
                TIMESTAMPDIFF(SECOND, hdocord.dodate, hdocord.charged_date),
                86400
            )
        )
    ) AS turnaround_dhms,
    CONCAT(
        hperson.patlast, ', ',
        hperson.patfirst,
        CASE
            WHEN hperson.patsuffix IS NULL OR hperson.patsuffix IN ('NOTAP','N/A') THEN ''
            ELSE CONCAT(' ', hperson.patsuffix)
        END,
        CASE
            WHEN hperson.patmiddle IS NULL OR hperson.patmiddle IN ('','N/A') THEN ''
            ELSE CONCAT(', ', hperson.patmiddle)
        END
    ) AS patient,
    (
        CASE
            WHEN henctr.toecode = 'ADM' THEN (
                SELECT CONCAT(
                    hward.wardname, SPACE(3),
                    hroom.rmname, SPACE(3),
                    hbed.bdintkey
                )
                FROM hpatroom r
                INNER JOIN hadmlog ON hadmlog.enccode = r.enccode
                INNER JOIN hward ON r.wardcode = hward.wardcode
                INNER JOIN hroom ON r.rmintkey = hroom.rmintkey
                INNER JOIN hbed ON r.bdintkey = hbed.bdintkey
                WHERE r.enccode = hdocord.enccode
                  AND r.hprdate = (
                      SELECT MAX(a.hprdate)
                      FROM hpatroom a
                      WHERE a.enccode = hdocord.enccode
                  )
            )
            WHEN henctr.toecode = 'OPD' THEN 'OUTPATIENT'
            WHEN henctr.toecode = 'ER' THEN 'EMERGENCY'
            WHEN henctr.toecode = 'ERADM' THEN 'EMERGENCY->ADMISSION'
            WHEN henctr.toecode = 'OPADM' THEN 'OUTPATIENT->ADMISSION'
            ELSE 'UNKNOWN'
        END
    ) AS room,
    hdocord.remarks,
    hdocord.enccode,
    hdocord.hpercode,
    hdocord.pcchrgcod,
    hprocm.procdesc,
    hdocord.dopriority,
    hdocord.estatus,
    hdocord.ordcon,
    hperson.patlast
FROM hdocord
LEFT JOIN hproc   ON hproc.prikey = hdocord.prikey
LEFT JOIN hprocm  ON hprocm.proccode = hproc.proccode
LEFT JOIN henctr  ON henctr.enccode = hdocord.enccode
LEFT JOIN hperson ON hperson.hpercode = hdocord.hpercode
WHERE hdocord.estatus IN ('U', 'P', 'S')
  AND hdocord.ordcon != 'CANOR'
  AND hdocord.dostat = 'A'
  AND henctr.encstat = 'A'
  AND hprocm.costcenter = 'LABOR'
ORDER BY hdocord.dodate DESC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers to download as Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=lab_turnaround.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output column headers
if (!empty($rows)) {
    echo implode("\t", array_keys($rows[0])) . "\n";
}

// Output data
foreach ($rows as $row) {
    echo implode("\t", array_values($row)) . "\n";
}
exit;
