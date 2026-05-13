<?php
require '../db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
	case 'stockInventory':
		loadStockInventory($pdo);
		break;
	case 'issuedStocks':
		issuedStocks($pdo);
		break;
}

function loadStockInventory($pdo)
{
	try {
		$draw 	= $_POST['draw'] ?? 1;
		$start 	= $_POST['start'] ?? 0;
		$length = $_POST['length'] ?? 10;

		$search = $_POST['search']['value'] ?? '';

		$startDate = $_POST['start_date'] ?? '';
		$endDate   = $_POST['end_date'] ?? '';

		$where = "WHERE 1=1
			AND stck.isActive IS NOT NULL AND stck.isActive != 'I'
		";
		$params = [];

		if (!empty($startDate)) {
			$where .= " AND stck.dateadd >= :startDate";
			$params[':startDate'] = $startDate . ' 00:00:00';
		}

		if (!empty($endDate)) {
			$where .= " AND stck.dateadd <= :endDate";
			$params[':endDate'] = $endDate . ' 23:59:59';
		}

		if (!empty($search)) {
			$where .= " AND (
				stck.dateadd LIKE :search
				OR stck.expire LIKE :search
				OR stck.sales_invoice LIKE :search
				OR stck.lotno LIKE :search
				OR stck.stock LIKE :search
				OR stck.purchase LIKE :search
				OR stck.selling LIKE :search

				OR sup.suppname LIKE :search
				OR g.GENDESC LIKE :search
				OR h.dmdnost LIKE :search
				OR h.strecode LIKE :search
				OR h.formcode LIKE :search
				OR h2.cl2desc LIKE :search
				OR h2.uomcode LIKE :search
				OR chg.chrgdesc LIKE :search
			)";

			$params[':search'] = "%$search%";
		}

		$baseFrom = "
			FROM hstockroom stck

			LEFT JOIN hsupplier sup
    			ON stck.suppcode = sup.suppcode

			LEFT JOIN hdmhdr h
				ON stck.itemcode = h.dmdcomb

			LEFT JOIN hdruggrp dg
				ON h.grpcode = dg.grpcode

			LEFT JOIN hgen g
				ON dg.gencode = g.gencode

			LEFT JOIN (
				SELECT DISTINCT cl2comb
				FROM hclass2h
			) hc2
				ON stck.itemcode = hc2.cl2comb

			LEFT JOIN (
				SELECT
					cl2comb,
					MAX(cl2desc) AS cl2desc,
					MAX(uomcode) AS uomcode
				FROM hclass2
				GROUP BY cl2comb
			) h2
				ON h2.cl2comb = hc2.cl2comb

			LEFT JOIN hcharge chg 
				ON stck.account = chg.chrgcode
		";

		$countSql = "SELECT COUNT(*) $baseFrom $where";
		$stmt = $pdo->prepare($countSql);
		$stmt->execute($params);
		$total = $stmt->fetchColumn();

		$columns = [
			0 => 'entry_date',
			1 => 'expiry_date',
			2 => 'estatus',
			3 => 'refno_salesinvo',
			4 => 'supplier',
			5 => 'item_name',
			6 => 'account_type',
			7 => 'lot_number',
			8 => 'stock_balance',
			9 => 'purchase_price',
			10 => 'selling_price',
			11 => 'isActiveStatus'
		];

		$orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
		$orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
		$orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

		$orderColumn = $columns[$orderColumnIndex] ?? 'entry_date';

		// ✅ Default secondary sort (latest first)
		$secondarySort = "entry_date DESC";

		// If user clicks another column → override
		if (isset($_POST['order'])) {
			$secondarySort = "$orderColumn $orderDir";
		}

		$sql = "
			SELECT
				stck.dateadd AS entry_date,
				stck.`expire` AS expiry_date,

				CASE
					WHEN stck.`expire` < CURDATE() AND stck.isActive = 'I' THEN 'EXPIRED/PULLOUT'
					WHEN stck.`expire` < CURDATE() AND stck.isActive = 'A' THEN 'EXPIRED'
					WHEN stck.`expire` >= CURDATE() AND stck.isActive = 'I' THEN 'PULLOUT'
					WHEN stck.`expire` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'NEAR EXPIRE'
					ELSE 'GOOD'
				END AS estatus,

				sup.suppname AS supplier,
				stck.sales_invoice AS refno_salesinvo,

				CASE
					WHEN stck.itemtype = 'DM' THEN
						CONCAT_WS(' ',
							g.GENDESC,
							COALESCE(h.dmdnost, ''),
							COALESCE(h.strecode, ''),
							COALESCE(h.formcode, '')
						)
					WHEN stck.itemtype = 'SM' THEN
						CONCAT_WS(' ',
							h2.cl2desc,
							h2.uomcode
						)
					ELSE 'Unknown Item Type'
				END AS item_name,

				chg.chrgdesc AS account_type,
				stck.lotno AS lot_number,
				stck.stock AS stock_balance,
				stck.purchase AS purchase_price,
				stck.selling AS selling_price,

				CASE
					WHEN stck.isActive = 'A' THEN 'Active'
					WHEN stck.isActive = 'I' THEN 'Inactive'
					ELSE 'No Status'
				END AS isActiveStatus,

				CASE
					WHEN stck.`expire` < CURDATE() AND stck.isActive = 'A' THEN 0
					WHEN stck.`expire` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1
					WHEN stck.`expire` >= CURDATE() AND stck.isActive = 'I' THEN 3
					WHEN stck.`expire` < CURDATE() AND stck.isActive = 'I' THEN 4
					ELSE 2
				END AS trigger_order

			$baseFrom
			$where
		
		ORDER BY trigger_order ASC, $secondarySort
		";

		if ($length != -1) {
			$sql .= " LIMIT :start, :length";
		}

		$stmt = $pdo->prepare($sql);

		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v);
		}

		if ($length != -1) {
			$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
			$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
		}

		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$totalSql = "
            SELECT
                SUM(CASE WHEN stck.`expire` >= CURDATE()
                    THEN CAST(stck.stock AS DECIMAL(10,2)) ELSE 0 END) AS totalStock,

                SUM(CASE WHEN stck.`expire` >= CURDATE()
                    THEN CAST(stck.stock AS DECIMAL(10,2)) * stck.selling ELSE 0 END) AS totalValue,

                SUM(CASE WHEN stck.`expire` < CURDATE()
                    THEN CAST(stck.stock AS DECIMAL(10,2)) ELSE 0 END) AS expiredStock,

                SUM(CASE WHEN stck.`expire` < CURDATE()
                    THEN CAST(stck.stock AS DECIMAL(10,2)) * stck.selling ELSE 0 END) AS expiredValue

			$baseFrom
            $where
		";

		$totalStmt = $pdo->prepare($totalSql);
		$totalStmt->execute($params);
		$totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

		echo json_encode([
			"draw" => intval($draw),
			"recordsTotal" => intval($total),
			"recordsFiltered" => intval($total),
			"data" => $data,
			"totals" => $totals
		]);
	} catch (Exception $e) {
		echo json_encode([
			"draw" => 0,
			"recordsTotal" => 0,
			"recordsFiltered" => 0,
			"data" => [],
			"error" => $e->getMessage()
		]);
	}
}

function issuedStocks($pdo)
{
	try {
		$draw 	= $_POST['draw'] ?? 1;
		$start 	= $_POST['start'] ?? 0;
		$length = $_POST['length'] ?? 10;

		$startDate = $_POST['startDate'] ?? '';
		$endDate   = $_POST['endDate'] ?? '';
		$search    = $_POST['search']['value'] ?? '';

		$where = "WHERE 1=1
			AND hrx.status IN ('S', 'R')
		";
		$params = [];

		// DATE FILTER (works if only start or end is provided)
		if (!empty($startDate)) {
			$where .= " AND hrx.dteissue >= :startDate";
			$params[':startDate'] = $startDate . " 00:00:00";
		}
		if (!empty($endDate)) {
			$where .= " AND hrx.dteissue <= :endDate";
			$params[':endDate'] = $endDate . " 23:59:59";
		}

		if (!empty($search)) {
			$where .= " AND (
				COALESCE(NULLIF(hrx.lotno, ''), 'No Lot Number') LIKE :search
				OR TRIM(
					CONCAT_WS(' ',
						CONCAT(
							COALESCE(g.GENDESC, ''),
							CASE
								WHEN COALESCE(NULLIF(TRIM(h.brandname), ''), '') <> ''
								THEN CONCAT(' (', h.brandname, ')')
								ELSE ''
							END
						),
						NULLIF(TRIM(h.dmdnost), ''),
						NULLIF(TRIM(h.strecode), ''),
						NULLIF(TRIM(h.formcode), '')
					)
				) LIKE :search
				OR COALESCE(NULLIF(TRIM(hrx.locacode), 'PHARM'), 'Pharmacy') LIKE :search
				OR (
					CASE
						WHEN hrx.status IN ('S', 'R') THEN 'Served'
						ELSE 'Unserved'
					END
				) LIKE :search
				OR (
					CASE
						WHEN hrx.status = 'R' THEN 'Received'
						WHEN hrx.status = 'S' THEN 'Unserved'
						ELSE 'Unknown'
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
				OR hrx.control_id LIKE :search
				OR COALESCE(NULLIF(TRIM(hrxrq.qtyrcv), ''), 'Not yet received') LIKE :search
			)";
			$params[':search'] = "%$search%";
		}

		$baseFrom = "
			FROM hrxissue hrx

			INNER JOIN hrxrequest hrxrq
				ON hrx.control_id = hrxrq.control_id

				AND hrxrq.rxstatus = 'S'
				AND hrxrq.phrxstatus IN ('S', 'U')

			LEFT JOIN hdmhdr h
				ON hrx.dmdcomb = h.dmdcomb
				AND hrx.dmdctr = h.dmdctr

			LEFT JOIN hdruggrp dg
				ON h.grpcode = dg.grpcode

			LEFT JOIN hgen g
				ON dg.gencode = g.gencode
				
			LEFT JOIN hpersonal hp
				ON hp.employeeid = hrx.issuedby
		";

		$countSql = "SELECT COUNT(*) $baseFrom $where";
		$countStmt = $pdo->prepare($countSql);
		$countStmt->execute($params);
		$total = $countStmt->fetchColumn();

		$columns = [
			0 => 'control_id',
			1 => 'order_date',
			2 => 'issued_date',
			3 => 'turnaround_dhms',
			4 => 'lot_number',
			5 => 'drug_description',
			6 => 'quantity_request',
			7 => 'quantity_issued',
			8 => 'quantity_received',
			9 => 'from_location',
			10 => 'Status',
			11 => 'Received',
			12 => 'issued_by'
		];

		$orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
		$orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
		$orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

		$orderColumn = $columns[$orderColumnIndex] ?? 'entry_date';

		$sql = "
			SELECT
				hrxrq.dtereq AS order_date,
				hrx.dteissue AS issued_date,

				CONCAT(
					FLOOR(TIMESTAMPDIFF(SECOND, hrxrq.dtereq, hrx.dteissue) / 86400),
					' days - ',
					SEC_TO_TIME(
						MOD(
							TIMESTAMPDIFF(SECOND, hrxrq.dtereq, hrx.dteissue),
							86400
						)
					)
				) AS turnaround_dhms,

				COALESCE(NULLIF(TRIM(hrx.lotno), ''), 'No Lot Number') AS lot_number,

				TRIM(
					CONCAT_WS(' ',
						CONCAT(
							COALESCE(g.GENDESC, ''),
							CASE
								WHEN COALESCE(NULLIF(TRIM(h.brandname), ''), '') <> ''
								THEN CONCAT(' (', h.brandname, ')')
								ELSE ''
							END
						),
						NULLIF(TRIM(h.dmdnost), ''),
						NULLIF(TRIM(h.strecode), ''),
						NULLIF(TRIM(h.formcode), '')
					)
				) AS drug_description,

				hrxrq.qtyreq AS quantity_request,
				hrx.issue_qty AS quantity_issued,
				COALESCE(NULLIF(TRIM(hrxrq.qtyrcv), ''), 'Not yet received') AS quantity_received,
				
				COALESCE(NULLIF(TRIM(hrx.locacode), 'PHARM'), 'Pharmacy') AS from_location,
				

				CASE
					WHEN hrx.status IN ('S', 'R') THEN 'Served'
					ELSE 'Unserved'
				END AS `Status`,

				CASE
					WHEN hrx.status = 'R' THEN 'Received'
					WHEN hrx.status = 'S' THEN 'Unserved'
					ELSE 'Unknown'
				END AS `Received`,

				hrxrq.rxstatus,
				hrxrq.phrxstatus,
				CONCAT(
				hp.lastname, ', ',
				hp.firstname,
				CASE
					WHEN hp.middlename IS NULL OR hp.middlename = ''
					THEN ''
					ELSE CONCAT(' ', hp.middlename)
				END
				) AS issued_by,
				hrx.control_id as control_id
			$baseFrom
			$where
			ORDER BY $orderColumn $orderDir
		";

		if ($length != -1) {
			$sql .= " LIMIT :start, :length";
		}

		$stmt = $pdo->prepare($sql);

		foreach ($params as $k => $v) {
			$stmt->bindValue($k, $v);
		}

		if ($length != -1) {
			$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
			$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
		}

		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$totalSql = "
            SELECT
                SUM(hrxrq.qtyreq) AS totalDrugs,
				SUM(hrxrq.qtyrcv) AS totalReceived,
                SUM(hrx.issue_qty) AS totalIssued
                -- SUM(IFNULL(r.qty_returned,0)) AS totalReturned
            $baseFrom
            $where
        ";

		$totalStmt = $pdo->prepare($totalSql);
		$totalStmt->execute($params);
		$totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

		echo json_encode([
			"draw" => intval($draw),
			"recordsTotal" => intval($total),
			"recordsFiltered" => intval($total),
			"data" => $data,
			"totals" => $totals
		]);
	} catch (Exception $e) {
		echo json_encode([
			"draw" => 0,
			"recordsTotal" => 0,
			"recordsFiltered" => 0,
			"data" => [],
			"error" => $e->getMessage()
		]);
	}
	exit;
}
