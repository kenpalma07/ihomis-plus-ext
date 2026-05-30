<?php
require '../db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'erTurnaroundTime':
        loadERTurnaroundTime($pdo);
        break;
    default:
        echo json_encode(["error" => "Invalid action"]);
        exit;
}

function loadERTurnaroundTime($pdo)
{
    try {

        $draw       = $_POST['draw'] ?? 1;
        $start      = $_POST['start'] ?? 0;
        $length     = $_POST['length'] ?? 10;

        $search = $_POST['search']['value'] ?? '';

        $regStart = $_POST['regStart'] ?? '';
        $regEnd   = $_POST['regEnd'] ?? '';
        $disStart = $_POST['disStart'] ?? '';
        $disEnd   = $_POST['disEnd'] ?? '';

        /* =========================
           WHERE BUILDER
        ========================= */
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($regStart)) {
            $where .= " AND logs.erdate >= :regStart";
            $params[':regStart'] = $regStart . " 00:00:00";
        }

        if (!empty($regEnd)) {
            $where .= " AND logs.erdate <= :regEnd";
            $params[':regEnd'] = $regEnd . " 23:59:59";
        }

        if (!empty($disStart)) {
            $where .= " AND logs.erdtedis >= :disStart";
            $params[':disStart'] = $disStart . " 00:00:00";
        }

        if (!empty($disEnd)) {
            $where .= " AND logs.erdtedis <= :disEnd";
            $params[':disEnd'] = $disEnd . " 23:59:59";
        }

        if (!empty($search)) {
            $where .= " AND (
                logs.hpercode LIKE :search
                OR p.patlast LIKE :search
                OR p.patfirst LIKE :search
                OR h.lastname LIKE :search
                OR h.firstname LIKE :search
            )";
            $params[':search'] = "%$search%";
        }

        /* =========================
           BASE QUERY
        ========================= */
        $baseFrom = "
            FROM herlog logs
            LEFT JOIN hperson p ON logs.hpercode = p.hpercode
            LEFT JOIN hpersonal h ON logs.discharge_by = h.employeeid
        ";

        $countSql = "SELECT COUNT(*) $baseFrom $where";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalRecords = $stmt->fetchColumn();

        /* =========================
           ORDER SAFE
        ========================= */
        $columns = [
            0 => 'logs.hpercode',
            1 => 'patient',
            2 => 'p.patbdate',
            3 => 'logs.erdate',
            4 => 'logs.erdtedis',
            6 => 'h.lastname'
        ];

        $orderColumnIndex = $_POST['order'][0]['column'] ?? 3;
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'desc');
        $orderDir = $orderDir === 'desc' ? 'DESC' : 'ASC';

        $orderColumn = $columns[$orderColumnIndex] ?? 'logs.erdate';

        /* =========================
           MAIN QUERY
        ========================= */
        $sql = "
            SELECT 
                logs.hpercode,

                CONCAT(
                    p.patlast, ', ', p.patfirst,
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
                logs.erdate AS registration_date,
                logs.erdtedis AS discharged_date,

                CONCAT(
                    FLOOR(TIMESTAMPDIFF(SECOND, logs.erdate, logs.erdtedis) / 86400),
                    ' days - ',
                    SEC_TO_TIME(MOD(TIMESTAMPDIFF(SECOND, logs.erdate, logs.erdtedis), 86400))
                ) AS turnaround_dhms,

                CONCAT(h.lastname, ', ', h.firstname) AS discharge_by

            $baseFrom
            $where
            ORDER BY $orderColumn $orderDir
        ";

        /* =========================
           LIMIT (ONLY ONCE!)
        ========================= */
        if ($length != -1) {
            $sql .= " LIMIT :start, :length";
        }

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        if ($length != -1) {
            $stmt->bindValue(':start', $start, PDO::PARAM_INT);
            $stmt->bindValue(':length', $length, PDO::PARAM_INT);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* =========================
           RESPONSE
        ========================= */
        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data

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
