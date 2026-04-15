<?php
require '../db.php';

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'admissionLog':
        loadAdmissionLog($pdo);
        break;
    case 'erLog':
        loadERLog($pdo);
        break;
}

function loadAdmissionLog($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;
        $search = $_POST['search']['value'] ?? '';

        $where = "WHERE 1=1";
        $params = [];

        // ✅ SEARCH
        if (!empty($search)) {
            $where .= " AND (
                logs.hpercode LIKE :search
                OR p.patlast LIKE :search
                OR p.patfirst LIKE :search
                OR p.patmiddle LIKE :search

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

        /* =========================
           BASE QUERY
        ========================= */
        $baseQuery = "
            FROM hadmlog logs
            JOIN hperson p ON logs.hpercode = p.hpercode
            $where
        ";

        /* =========================
           COUNT
        ========================= */
        $countSql = "
            SELECT COUNT(*) FROM (
                SELECT logs.hpercode
                $baseQuery
                GROUP BY logs.hpercode
                HAVING COUNT(logs.enccode) > 1
            ) x
        ";

        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        /* =========================
           MAIN QUERY
        ========================= */
        $sql = "
            SELECT
                logs.hpercode,

                MAX(
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
                    )
                ) AS patient,

                MAX(p.patbdate) AS birthdate,
                COUNT(logs.enccode) AS admission_count

            $baseQuery

            GROUP BY logs.hpercode
            HAVING COUNT(logs.enccode) > 1
            ORDER BY admission_count DESC
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
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage()
        ]);
    }
    exit;
}

function loadERLog($pdo)
{
    try {
        $draw   = $_POST['draw'] ?? 1;
        $start  = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;
        $search = $_POST['search']['value'] ?? '';

        $where = "WHERE 1=1";
        $params = [];

        // ✅ SEARCH
        if (!empty($search)) {
            $where .= " AND (
                logs.hpercode LIKE :search
                OR p.patlast LIKE :search
                OR p.patfirst LIKE :search
                OR p.patmiddle LIKE :search

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

        /* =========================
           BASE QUERY
        ========================= */
        $baseQuery = "
            FROM erlog logs
            JOIN hperson p ON logs.hpercode = p.hpercode
            $where
        ";

        /* =========================
           COUNT
        ========================= */
        $countSql = "
            SELECT COUNT(*) FROM (
                SELECT logs.hpercode
                $baseQuery
                GROUP BY logs.hpercode
                HAVING COUNT(logs.enccode) > 1
            ) x
        ";

        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        /* =========================
           MAIN QUERY
        ========================= */
        $sql = "
            SELECT
                logs.hpercode,

                MAX(
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
                    )
                ) AS patient,
                MAX(p.patbdate) AS birthdate,
                COUNT(logs.enccode) AS er_count
            $baseQuery
            GROUP BY logs.hpercode
            HAVING COUNT(logs.enccode) > 1
            ORDER BY er_count DESC
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
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
            "error" => $e->getMessage()
        ]);
    }
    exit;
}
