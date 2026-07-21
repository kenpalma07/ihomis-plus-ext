<?php

require '../db.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
	case 'loadZBBDashboard':
		loadZBBDashboard($pdo);
		break;	
	case 'loadZBBPatientTable':
		loadZBBPatientTable($pdo);
		break;
}

function loadZBBDashboard($pdo)
{
	try {
		$startDate = $_GET['start_date'] ?? '';
		$endDate   = $_GET['end_date'] ?? '';

		if (empty($startDate) || empty($endDate)) {
			throw new Exception("Start date and End date are required.");
		}

		$sql = "
            SELECT
                COUNT(zbb.id) AS total_zbb_patients,
                COALESCE(SUM(acc.patotamt), 0) AS total_actual_charges,
                COALESCE(SUM(acc.paphic), 0) AS total_philhealth_charges,
                COALESCE(SUM(acc.pabal), 0) AS total_balance
            FROM hzbb zbb
            LEFT JOIN hpatacct acc
                ON zbb.enccode = acc.enccode
            WHERE acc.padteas >= :startDate
			AND acc.padteas <= :endDate
        ";

		$stmt = $pdo->prepare($sql);

		$stmt->execute([
			':startDate' => $startDate . ' 00:00:00',
			':endDate'   => $endDate . ' 23:59:59'
		]);

		$data = $stmt->fetch(PDO::FETCH_ASSOC);

		echo json_encode([
			"status" => "success",
			"data" => [
				"total_zbb_patients" => (int)($data['total_zbb_patients'] ?? 0),
				"total_actual_charges" => (float)($data['total_actual_charges'] ?? 0),
				"total_philhealth_charges" => (float)($data['total_philhealth_charges'] ?? 0),
				"total_balance" => (float)($data['total_balance'] ?? 0)
			]
		]);
	} catch (Exception $e) {
		echo json_encode([
			"status" => "error",
			"message" => $e->getMessage()
		]);
	}
}

function loadZBBPatientTable($pdo)
{
	try {
		$draw   = $_POST['draw'] ?? 1;
		$start  = $_POST['start'] ?? 0;
		$length = $_POST['length'] ?? 10;

		$search = $_POST['search']['value'] ?? '';

		$startDate = $_POST['startDate'] ?? '';
		$endDate   = $_POST['endDate'] ?? '';

		$where = "
            WHERE 1=1
        ";
		$params = [];

		if (!empty($startDate)) {
			$where .= " AND acc.padteas >= :startDate";
			$params[':startDate'] = $startDate . " 00:00:00";
		}

		if (!empty($endDate)) {
			$where .= " AND acc.padteas <= :endDate";
			$params[':endDate'] = $endDate . " 23:59:59";
		}

		if (!empty($search)) {
			$where .= " AND (
                zbb.hpercode LIKE :search
				OR CONCAT(
					hp.patlast, ', ', hp.patfirst,
					CASE 
						WHEN hp.patsuffix IS NULL OR hp.patsuffix IN ('NOTAP','N/A') THEN ''
						ELSE CONCAT(' ', hp.patsuffix)
					END,
					CASE 
						WHEN hp.patmiddle IS NULL OR hp.patmiddle IN ('','N/A') THEN ''
						ELSE CONCAT(', ', hp.patmiddle)
					END
				) LIKE :search
            )";
			$params[':search'] = "%$search%";
		}

		$baseFrom = "
            FROM hzbb AS zbb
			LEFT JOIN hpatacct AS acc
				ON zbb.enccode = acc.enccode
			LEFT JOIN hperson AS hp
				ON zbb.hpercode = hp.hpercode
        ";

		$countSql = "SELECT COUNT(*) $baseFrom $where";
		$countStmt = $pdo->prepare($countSql);
		$countStmt->execute($params);
		$total = $countStmt->fetchColumn();

		$columns = [
			0 => 'zbb.hpercode',
			1 => 'hp.patlast',
			2 => 'acc.patotamt',
			3 => 'acc.paphic',
			4 => 'acc.pabal',
			5 => 'acc.padteas'
		];

		$orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
		$orderDir = strtolower($_POST['order'][0]['dir'] ?? 'desc');
		$orderDir = $orderDir === 'asc' ? 'ASC' : 'DESC';

		$orderColumn = $columns[$orderColumnIndex] ?? 'entry_date';

		$sql = "
			SELECT
				zbb.hpercode AS hpercode,
				CONCAT(
					hp.patlast, ', ', hp.patfirst,
					CASE 
						WHEN hp.patsuffix IS NULL OR hp.patsuffix IN ('NOTAP','N/A') THEN ''
						ELSE CONCAT(' ', hp.patsuffix)
					END,
					CASE 
						WHEN hp.patmiddle IS NULL OR hp.patmiddle IN ('','N/A') THEN ''
						ELSE CONCAT(', ', hp.patmiddle)
					END
				) AS patient,
				acc.patotamt AS total_actual_charges,
				acc.paphic AS total_philhealth_charges,
				acc.pabal AS total_balance,
				acc.padteas AS entry_date
			$baseFrom
			$where
			ORDER BY acc.padteas DESC
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

		echo json_encode([
			"draw" => intval($draw),
			"recordsTotal" => intval($total),
			"recordsFiltered" => intval($total),
			"data" => $data
		]);
	} catch (Exception $e) {
		echo json_encode([
			"status" => "error",
			"message" => $e->getMessage()
		]);
	}
}
