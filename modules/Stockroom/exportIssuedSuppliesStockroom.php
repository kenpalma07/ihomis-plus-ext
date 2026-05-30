<?php

set_time_limit(0);
ini_set('memory_limit', '1024M');

require __DIR__ . '/../../db.php';

/* =========================
   HEADERS (Excel)
========================= */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=inventory_report.xls");
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
$where = "WHERE 1=1";
$params = [];

if (!empty($startDate)) {
	$where .= " AND order_date >= :startDate";
	$params[':startDate'] = $startDate . " 00:00:00";
}
if (!empty($endDate)) {
	$where .= " AND hrq.dteissue <= :endDate";
	$params[':endDate'] = $endDate . " 23:59:59";
}

if (!empty($search)) {
	$where .= " AND
		hrq.control_id LIKE :search
		OR COALESCE(NULLIF(TRIM(hrq.lotno), ''), 'No Lot Number') LIKE :search
		OR CONCAT_WS(' ', h2.cl2desc, h2.uomcode) LIKE :search
		OR COALESCE(NULLIF(TRIM(hrqdrq.qtyrcv), ''), 'Pending') LIKE :search
		OR (
			CASE
				WHEN hrq.locacode = 'PHARM' THEN '(PHARM) PHARMACY'
				WHEN hrq.locacode = 'CSR' THEN '(CSR) CENTRAL SUPPLY'
				WHEN hrq.locacode = 'ER' THEN '(ER) EMERGENCY'
				ELSE 
				CONCAT
				(
					'(', ward.wardcode, ')', ' ', ward.wardname
				)
			END
		) LIKE :search
		OR (
			CASE
				WHEN hrqdrq.rqdstatus = 'S' THEN 'Served'
				ELSE 'Unserved'
			END
		) LIKE :search
		OR (
			CASE
				WHEN hrqdrq.crqdstatus = 'S' THEN 'Received'
				ELSE 'Unserved'
			END
		) LIKE :search
		OR CONCAT(
			hp.lastname, ', ',
			hp.firstname,
			CASE
				WHEN hp.middlename IS NULL OR hp.middlename = ''
				THEN ''
				ELSE CONCAT(' ', hp.middlename)
			END
		) LIKE :search
	";
	$params[':search'] = "%$search%";
}

$baseFrom = "
	FROM hrqissue hrq

	INNER JOIN (
		SELECT
			control_id,
			MIN(dtereq) AS order_date,
			qtyreq,
			qtyrcv,
			rqdstatus,
			crqdstatus
		FROM hrqdrequest
		WHERE rqdstatus = 'S'
		AND crqdstatus IN ('S', 'U')
		GROUP BY control_id
	) hrqdrq
		ON hrq.control_id = hrqdrq.control_id

	LEFT JOIN hclass2 h2
		ON h2.cl2comb = hrq.cl2comb
		
	LEFT JOIN hpersonal hp
		ON hp.employeeid = hrq.issuedby
		
	LEFT JOIN hward ward
		ON ward.wardcode = hrq.locacode
";

$columns = [
	0 => 'control_id',
	1 => 'order_date',
	2 => 'issued_date',
	3 => 'turnaround_dhms',
	4 => 'lot_number',
	5 => 'supply_name',
	6 => 'quantity_request',
	7 => 'quantity_issued',
	8 => 'quantity_received',
	9 => 'from_location',
	10 => 'Status',
	11 => 'Received',
	12 => 'issued_by'
];

$countSql = "SELECT COUNT(*) $baseFrom $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$orderColumnIndex = $_GET['order'][0]['column'] ?? 0;
$orderDir = strtolower($_GET['order'][0]['dir'] ?? 'asc');
$orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

$orderColumn = $columns[$orderColumnIndex] ?? 'issued_date';

$sql = "
	SELECT
		hrq.control_id AS control_id,
		hrqdrq.order_date AS order_date,
		hrq.dteissue AS issued_date,
		
		CONCAT(
			FLOOR(TIMESTAMPDIFF(SECOND, hrqdrq.order_date, hrq.dteissue) / 86400),
			' days - ',
			SEC_TO_TIME(
				MOD(
					TIMESTAMPDIFF(SECOND, hrqdrq.order_date, hrq.dteissue),
					86400
				)
			)
		) AS turnaround_dhms,
		
		COALESCE(NULLIF(TRIM(hrq.lotno), ''), 'No Lot Number') AS lot_number,
		CONCAT_WS(' ', h2.cl2desc, h2.uomcode) AS supply_name,
		hrqdrq.qtyreq AS quantity_request,
		hrq.issue_qty AS quantity_issued,
		COALESCE(NULLIF(TRIM(hrqdrq.qtyrcv), ''), 'Pending') AS quantity_received,
		CASE
			WHEN hrq.locacode = 'PHARM' THEN '(PHARM) PHARMACY'
			WHEN hrq.locacode = 'CSR' THEN '(CSR) CENTRAL SUPPLY'
			WHEN hrq.locacode = 'ER' THEN '(ER) EMERGENCY'
			WHEN hrq.locacode = 'OPD' THEN '(OPD) OUTPATIENT'
			ELSE 
			CONCAT
			(
				'(', ward.wardcode, ')', ' ', ward.wardname
			)
		END AS from_location,
		
		CASE
			WHEN hrqdrq.rqdstatus = 'S' THEN 'Served'
			ELSE 'Unserved'
		END AS `Status`,

		CASE
			WHEN hrqdrq.crqdstatus = 'S' THEN 'Received'
			ELSE 'Unserved'
		END AS `Received`,
		
		CONCAT(
			hp.lastname, ', ',
			hp.firstname,
			CASE
				WHEN hp.middlename IS NULL OR hp.middlename = ''
				THEN ''
				ELSE CONCAT(' ', hp.middlename)
			END
		) AS issued_by

	$baseFrom
	$where
	ORDER BY $orderColumn $orderDir
";

$stmt = $pdo->prepare($sql);

foreach ($params as $k => $v) {
	$stmt->bindValue($k, $v);
}

$stmt->execute();

$thead = "
   background-color:#198754;
   color:#ffffff;
   font-weight:bold;
";

echo "<table border='1'>";
echo "<tr>
	<th style='{$thead}'>CONTROL ID</th>
	<th style='{$thead}'>ORDER DATE</th>
	<th style='{$thead}'>DATE ISSUED</th>
	<th style='{$thead}'>TURNAROUND TIME</th>
	<th style='{$thead}'>LOT NUMBER</th>
	<th style='{$thead}'>SUPPLY NAME</th>
	<th style='{$thead}'>QTY REQUESTED</th>
	<th style='{$thead}'>QTY ISSUED</th>
	<th style='{$thead}'>QTY RECEIVED</th>
	<th style='{$thead}'>FROM LOCATION</th>
	<th style='{$thead}'>STATUS</th>
	<th style='{$thead}'>RECEIVED</th>
	<th style='{$thead}'>ISSUED BY</th>
</tr>";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

	/* =========================
      DEFAULT STYLES
   ========================= */
	$qtyReceivedStyle = "";
	$fromLocationStyle = "";
	$statusStyle = "";
	$receivedStyle = "";
	$qtyRequestStyle = "";
	$qtyIssuedStyle = "";

	/* =========================
      QTY RECEIVED
   ========================= */
	if ($row['quantity_received'] === "Unreceived") {
		$qtyReceivedStyle = "
         color:#dc3545;
         font-weight:bold;
      ";
	}

	/* =========================
      RECEIVED
   ========================= */
	if ($row['Received'] === "Unserved") {

		$receivedStyle = "
         background-color:#f8d7da;
         color:#dc3545;
         font-weight:bold;
      ";
	} else {

		$receivedStyle = "
         background-color:#d1e7dd;
         color:#198754;
         font-weight:bold;
      ";
	}

	/* =========================
      STATUS
   ========================= */
	if ($row['Status'] === "Served") {

		$statusStyle = "
         background-color:#d1e7dd;
         color:#198754;
         font-weight:bold;
      ";
	} else {

		$statusStyle = "
         background-color:#f8d7da;
         color:#dc3545;
         font-weight:bold;
      ";
	}

	/* =========================
      FROM LOCATION
   ========================= */
	if ($row['from_location'] === "(ER) EMERGENCY") {

		$fromLocationStyle = "
         background-color:#dc3545;
         color:#ffffff;
         font-weight:bold;
      ";
	} else if ($row['from_location'] === "(CSR) CENTRAL SUPPLY") {

		$fromLocationStyle = "
         background-color:#6c757d;
         color:#ffffff;
         font-weight:bold;
      ";
	} else if ($row['from_location'] === "(PHARM) PHARMACY") {

		$fromLocationStyle = "
         background-color:#0dcaf0;
         color:#ffffff;
         font-weight:bold;
      ";
	} else {

		$fromLocationStyle = "
         background-color:#ffc107;
         color:#ffffff;
         font-weight:bold;
      ";
	}

	if ($row['quantity_issued'] > $row['quantity_request']) {
		$qtyIssuedStyle = "
         background-color:#f8d7da;
         color:#dc3545;
         font-weight:bold;
      ";

		$qtyRequestStyle = "
         background-color:#f8d7da;
         color:#dc3545;
         font-weight:bold;
      ";
	}

	echo "<tr>";

	echo "<td style='mso-number-format:\"\\@\";'>{$row['control_id']}</td>";
	echo "<td>{$row['order_date']}</td>";
	echo "<td>{$row['issued_date']}</td>";
	echo "<td>{$row['turnaround_dhms']}</td>";
	echo "<td style='mso-number-format:\"\\@\";'>{$row['lot_number']}</td>";
	echo "<td>{$row['supply_name']}</td>";
	echo "<td style='{$qtyRequestStyle}'>{$row['quantity_request']}</td>";
	echo "<td style='{$qtyIssuedStyle}'>{$row['quantity_issued']}</td>";

	/* QTY RECEIVED */
	echo "<td style='{$qtyReceivedStyle}'>{$row['quantity_received']}</td>";

	/* FROM LOCATION */
	echo "<td style='{$fromLocationStyle}'>{$row['from_location']}</td>";

	/* STATUS */
	echo "<td style='{$statusStyle}'>{$row['Status']}</td>";

	/* RECEIVED */
	echo "<td style='{$receivedStyle}'>{$row['Received']}</td>";

	echo "<td>{$row['issued_by']}</td>";

	echo "</tr>";
}

echo "</table>";
