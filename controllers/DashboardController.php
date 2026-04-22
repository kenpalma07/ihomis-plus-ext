<?php
require '../db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'loadDashboard':
        loadDashboard($pdo);
        break;
}

function loadDashboard($pdo)
{
    try {
        $startDate = $_GET['start_date'] ?? null;
        $endDate   = $_GET['end_date'] ?? null;

        $where = " WHERE 1=1 ";
        $params = [];

        // =====================
        // DATE FILTER LOGIC (ADMISSIONS)
        // =====================
        if (!empty($startDate)) {
            $where .= " AND admdate >= :startDate ";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND admdate <= :endDate ";
            $params[':endDate'] = $endDate . " 23:59:59";
        }

        // =====================
        // MAIN QUERY (ADMISSIONS)
        // =====================
        $sql = "
        SELECT
            COUNT(*) AS total_admissions,

            SUM(CASE WHEN disdate IS NOT NULL 
                AND disdate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 END)
            AS total_discharges,
            SUM(CASE WHEN disdate IS NULL THEN 1 ELSE 0 END) AS current_inpatients,

            SUM(CASE WHEN newold = 'N' 
                AND admdate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 END)
            AS new_patients,

            SUM(CASE WHEN newold = 'O' THEN 1 ELSE 0 END) AS old_patients,

            (
                SELECT COUNT(DISTINCT h1.hpercode)
                FROM hadmlog h1
                JOIN hadmlog h2 
                    ON h1.hpercode = h2.hpercode
                    AND h2.admdate > h1.disdate
                WHERE h1.disdate BETWEEN :startDate AND :endDate
            ) AS readmitted_patients,

            SUM(CASE 
                WHEN pexpireddate IS NOT NULL 
                AND dispcode = 'DIEDD'
                AND pexpireddate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 
            END) AS total_deaths,

            ROUND(
                    (
                        SELECT COUNT(DISTINCT h1.hpercode)
                        FROM hadmlog h1
                        JOIN hadmlog h2 
                            ON h1.hpercode = h2.hpercode
                            AND h2.admdate > h1.disdate
                        WHERE h1.disdate >= :startDate 
                        AND h1.disdate < DATE_ADD(:endDate, INTERVAL 1 DAY)
                    ) 
                    / NULLIF(
                        SUM(CASE 
                            WHEN disdate >= :startDate 
                            AND disdate < DATE_ADD(:endDate, INTERVAL 1 DAY)
                            THEN 1 ELSE 0 
                        END), 0
                    ) * 100
                ,2) AS readmission_rate

        FROM hadmlog
        $where
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // =====================
        // EMERGENCY FILTER LOGIC (YOUR REQUEST APPLIED HERE)
        // =====================
        $erWhere = " WHERE 1=1 ";
        $erParams = [];

        if (!empty($startDate)) {
            $erWhere .= " AND erdate >= :startDate ";
            $erParams[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $erWhere .= " AND erdate <= :endDate ";
            $erParams[':endDate'] = $endDate . " 23:59:59";
        }

        // =====================
        // EMERGENCY QUERY
        // =====================
        $erSql = "
        SELECT
            COUNT(*) AS total_er_visits,

            SUM(CASE WHEN erdtedis IS NOT NULL 
                AND erdtedis BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 END)
            AS total_er_discharges,

            SUM(CASE WHEN erdtedis IS NULL THEN 1 ELSE 0 END) AS current_er_patients,

            SUM(CASE 
                WHEN pexpireddate IS NOT NULL 
                AND dispcode = 'DIEDD'
                AND pexpireddate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 
            END) AS total_er_deaths,

            SUM(CASE WHEN newold = 'N'
                AND erdate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 END)
            AS er_new_patients,

            SUM(CASE WHEN newold = 'O' THEN 1 ELSE 0 END) AS er_old_patients,

            (
                SELECT COUNT(DISTINCT h1.hpercode)
                FROM herlog h1
                JOIN herlog h2 
                    ON h1.hpercode = h2.hpercode
                    AND h2.erdate > h1.erdtedis
                WHERE h1.erdtedis BETWEEN :startDate AND :endDate
            ) AS er_readmitted_patients,

            ROUND(
                    (
                        SELECT COUNT(DISTINCT h1.hpercode)
                        FROM herlog h1
                        JOIN herlog h2 
                            ON h1.hpercode = h2.hpercode
                            AND h2.erdate > h1.erdtedis
                        WHERE h1.erdtedis BETWEEN :startDate AND :endDate
                    ) 
                    / NULLIF(
                        SUM(CASE 
                            WHEN erdtedis BETWEEN :startDate AND :endDate 
                            THEN 1 ELSE 0 
                        END), 0
                    ) * 100
                ,2) AS er_readmission_rate

        FROM herlog
        $erWhere
        ";

        $erStmt = $pdo->prepare($erSql);
        $erStmt->execute($erParams);

        $erStats = $erStmt->fetch(PDO::FETCH_ASSOC);

        // =====================
        // RESPONSE
        // =====================
        echo json_encode([
            "status" => "success",
            "data" => [
                // ADMISSIONS
                "total_admissions" => (int)$stats['total_admissions'],
                "current_inpatients" => (int)$stats['current_inpatients'],
                "new_patients" => (int)$stats['new_patients'],
                "old_patients" => (int)$stats['old_patients'],
                "readmitted_patients" => (int)$stats['readmitted_patients'],
                "total_discharges" => (int)$stats['total_discharges'],
                "readmission_rate" => round($stats['readmission_rate'] ?? 0, 2),
                "total_deaths" => (int)$stats['total_deaths'],

                // EMERGENCY
                "total_er_visits" => (int)($erStats['total_er_visits'] ?? 0),
                "total_er_discharges" => (int)($erStats['total_er_discharges'] ?? 0),
                "current_er_patients" => (int)($erStats['current_er_patients'] ?? 0),
                "total_er_deaths" => (int)($erStats['total_er_deaths'] ?? 0),
                "er_new_patients" => (int)($erStats['er_new_patients'] ?? 0),
                "er_old_patients" => (int)($erStats['er_old_patients'] ?? 0),
                "er_readmitted_patients" => (int)($erStats['er_readmitted_patients'] ?? 0),
                "er_readmission_rate" => round($erStats['er_readmission_rate'] ?? 0, 2),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}
