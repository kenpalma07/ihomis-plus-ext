<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=issued_drugs_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";

echo "<tr>
<th>Drug/Medicine</th>
<th>HPERCODE</th>
<th>Patient</th>
<th>Issued Qty</th>
<th>Returned Qty</th>
<th>Order Type</th>
<th>Issued By</th>
<th>Date Issued</th>
</tr>";

$sql = "
SELECT
    CONCAT_WS(' ',
        CONCAT_WS('', g.GENDESC,
            CASE
                WHEN h.brandname IS NULL OR h.brandname = ''
                THEN ''
                ELSE CONCAT(' (', h.brandname, ')')
            END
        ),
        COALESCE(h.dmdnost,''),
        COALESCE(h.strecode,''),
        COALESCE(h.formcode,'')
    ) AS drug_description,

    p.hpercode,

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

    i.qty AS quantity_issued,

    IFNULL(r.qty_returned,0) AS quantity_returned,

    CASE
        WHEN rx.status = 'R' THEN 'Prescription Only'
        ELSE 'Order'
    END AS order_type,

    CONCAT(
        hp.lastname, ', ',
        hp.firstname,
        CASE
            WHEN hp.middlename IS NULL OR hp.middlename = ''
            THEN ''
            ELSE CONCAT(' ', hp.middlename)
        END
    ) AS issued_by,

    i.issuedte AS date_issued,
    i.lotno AS lot_number

FROM hrxoissue i

LEFT JOIN (
    SELECT
        docointkey,
        dmdcomb,
        dmdctr,
        SUM(qty) AS qty_returned
    FROM hrxoreturn
    GROUP BY docointkey, dmdcomb, dmdctr
) r
    ON i.docointkey = r.docointkey
    AND i.dmdcomb = r.dmdcomb
    AND i.dmdctr = r.dmdctr

LEFT JOIN hdmhdr h
    ON i.dmdcomb = h.dmdcomb

LEFT JOIN hdruggrp dg
    ON h.grpcode = dg.grpcode

LEFT JOIN hgen g
    ON dg.gencode = g.gencode

LEFT JOIN hrxissue rx
    ON i.dmdcomb = rx.dmdcomb
    AND i.dmdctr = rx.dmdctr
    AND i.lotno = rx.lotno

LEFT JOIN hperson p
    ON i.hpercode = p.hpercode

LEFT JOIN hpersonal hp
    ON hp.employeeid = COALESCE(rx.issuedby, i.issuedby)
	 
ORDER BY drug_description ASC";

$stmt = $pdo->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    echo "<tr>";
    echo "<td>" . $row['drug_description'] . "</td>";
    echo "<td style='mso-number-format:\"\\@\";'>" . $row['hpercode'] . "</td>";
    echo "<td>" . $row['patient'] . "</td>";
    echo "<td>" . $row['quantity_issued'] . "</td>";
    echo "<td>" . $row['quantity_returned'] . "</td>";
    echo "<td>" . $row['order_type'] . "</td>";
    echo "<td>" . $row['issued_by'] . "</td>";
    echo "<td>" . $row['date_issued'] . "</td>";
    echo "</tr>";
}

echo "</table>";
