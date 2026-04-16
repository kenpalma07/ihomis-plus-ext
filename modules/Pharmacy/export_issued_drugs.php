<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

// Headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=issued_drugs_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Get parameters (camelCase - matches JS)
$startDate = $_GET['startDate'] ?? '';
$endDate   = $_GET['endDate'] ?? '';
$search    = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

// ✅ Safer date filtering (works for DATE or DATETIME)
if (!empty($startDate) && !empty($endDate)) {
    $where .= " AND DATE(i.issuedte) BETWEEN :startDate AND :endDate";
    $params[':startDate'] = $startDate;
    $params[':endDate']   = $endDate;
}

// Optional search (if you want to support it later)
if (!empty($search)) {
    $where .= " AND (
        i.lotno LIKE :search
        OR CONCAT_WS(' ',
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
        ) LIKE :search
        OR CONCAT(
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
        ) LIKE :search
    )";
    $params[':search'] = "%$search%";
}

// Main query
$sql = "
SELECT
    COALESCE(NULLIF(i.lotno, ''), 'No Lot Number') AS lot_number,

    CONCAT_WS(' ',
        CONCAT(
            g.GENDESC,
            CASE 
                WHEN h.brandname IS NULL OR h.brandname = ''
                THEN ''
                ELSE CONCAT(' (', h.brandname, ')')
            END
        ),
        COALESCE(h.dmdnost, ''),
        COALESCE(h.strecode, ''),
        COALESCE(h.formcode, '')
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
    IFNULL(r.qty_returned, 0) AS quantity_returned,

    CASE
        WHEN hx.estatus = 'R' THEN 'Prescription Only'
        ELSE 'Order'
    END AS order_type,

    chg.chrgdesc AS account_type,

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
    hx.dodate AS order_date

FROM hrxoissue i

LEFT JOIN (
    SELECT docointkey, dmdcomb, dmdctr, SUM(qty) AS qty_returned
    FROM hrxoreturn
    GROUP BY docointkey, dmdcomb, dmdctr
) r
    ON i.docointkey = r.docointkey
    AND i.dmdcomb = r.dmdcomb
    AND i.dmdctr = r.dmdctr

LEFT JOIN hdmhdr h 
    ON i.dmdcomb = h.dmdcomb
    AND i.dmdctr = h.dmdctr

LEFT JOIN hdruggrp dg ON h.grpcode = dg.grpcode
LEFT JOIN hgen g ON dg.gencode = g.gencode

LEFT JOIN hcharge chg ON i.chrgcode = chg.chrgcode
LEFT JOIN hperson p ON i.hpercode = p.hpercode
LEFT JOIN hpersonal hp ON hp.employeeid = i.issuedby
LEFT JOIN hrxo hx ON i.docointkey = hx.docointkey

$where

ORDER BY i.issuedte DESC
";

// Execute query
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();

// Output Excel table
echo "<table border='1'>
<tr>
<th>Drug/Medicine</th>
<th>HPERCODE</th>
<th>Patient</th>
<th>Issued Qty</th>
<th>Returned Qty</th>
<th>Order Type</th>
<th>Account Type</th>
<th>Issued By</th>
<th>Date Issued</th>
</tr>";

// Loop data safely
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['drug_description']) . "</td>";
    echo "<td style='mso-number-format:\"\\@\";'>" . htmlspecialchars($row['hpercode']) . "</td>";
    echo "<td>" . htmlspecialchars($row['patient']) . "</td>";
    echo "<td>" . $row['quantity_issued'] . "</td>";
    echo "<td>" . $row['quantity_returned'] . "</td>";
    echo "<td>" . htmlspecialchars($row['order_type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['account_type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['issued_by']) . "</td>";
    echo "<td>" . date('Y-m-d H:i', strtotime($row['date_issued'])) . "</td>";
    echo "</tr>";
}

echo "</table>";
