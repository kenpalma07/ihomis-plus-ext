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
        $startDate = $_GET['start_date'] ?? date('Y-01-01');
        $endDate   = $_GET['end_date'] ?? date('Y-m-d');

        $sql = "
        SELECT
            COUNT(*) AS total_admissions,

            SUM(CASE 
                WHEN disdate IS NOT NULL 
                AND disdate BETWEEN :start AND :end
                THEN 1 ELSE 0 
            END) AS total_discharges,

            SUM(CASE 
                WHEN disdate IS NULL 
                THEN 1 ELSE 0 
            END) AS current_inpatients,

            SUM(CASE 
                WHEN newold = 'N' 
                AND admdate BETWEEN :start AND :end
                THEN 1 ELSE 0 
            END) AS new_patients,

            SUM(CASE 
                WHEN newold = 'O' 
                AND admdate BETWEEN :start AND :end
                THEN 1 ELSE 0 
            END) AS old_patients,

            (
                SELECT COUNT(DISTINCT h1.hpercode)
                FROM hadmlog h1
                JOIN hadmlog h2 
                    ON h1.hpercode = h2.hpercode
                    AND h2.admdate > h1.disdate
                WHERE h1.disdate BETWEEN :start AND :end
            ) AS readmitted_patients,

            SUM(CASE 
                WHEN pexpireddate IS NOT NULL 
                AND dispcode = 'DIEDD'
                AND pexpireddate >= :start 
                AND pexpireddate < DATE_ADD(:end, INTERVAL 1 DAY)
                THEN 1 ELSE 0 
            END) AS total_deaths

        FROM hadmlog
        WHERE admdate BETWEEN :start AND :end
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':start' => $startDate,
            ':end'   => $endDate
        ]);

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Compute rate in PHP (cleaner & avoids SQL duplication)
        $readmissionRate = 0;
        if (!empty($stats['total_discharges'])) {
            $readmissionRate = ($stats['readmitted_patients'] / $stats['total_discharges']) * 100;
        }

        // =====================
        // EMERGENCY STATS
        // =====================
        $erSql = "
            SELECT
                COUNT(*) AS total_er_visits,

                SUM(CASE 
                    WHEN erdtedis IS NOT NULL 
                    AND erdtedis BETWEEN :start AND :end
                    THEN 1 ELSE 0 
                END) AS total_er_discharges,

                SUM(CASE 
                    WHEN erdtedis IS NULL 
                    THEN 1 ELSE 0 
                END) AS current_er_patients,

                SUM(CASE 
                    WHEN pexpireddate IS NOT NULL 
                    AND dispcode = 'DIEDD'
                    AND pexpireddate BETWEEN :start AND :end
                    THEN 1 ELSE 0 
                END) AS total_er_deaths,

                SUM(CASE 
                    WHEN newold = 'N' 
                    AND erdate BETWEEN :start AND :end
                    THEN 1 ELSE 0 
                END) AS er_new_patients,

                SUM(CASE 
                    WHEN newold = 'O' 
                    AND erdate BETWEEN :start AND :end
                    THEN 1 ELSE 0 
                END) AS er_old_patients,

                (
                    SELECT COUNT(DISTINCT h1.hpercode)
                    FROM herlog h1
                    JOIN herlog h2 
                        ON h1.hpercode = h2.hpercode
                        AND h2.erdate > h1.erdtedis
                    WHERE h1.erdtedis BETWEEN :start AND :end
                ) AS er_readmitted_patients

            FROM herlog
            WHERE erdate BETWEEN :start AND :end
        ";

        $erStmt = $pdo->prepare($erSql);
        $erStmt->execute([
            ':start' => $startDate,
            ':end'   => $endDate
        ]);

        $erStats = $erStmt->fetch(PDO::FETCH_ASSOC);

        $erReadmissionRate = 0;
        if (!empty($erStats['total_er_discharges'])) {
            $erReadmissionRate = ($erStats['er_readmitted_patients'] / $erStats['total_er_discharges']) * 100;
        }

        echo json_encode([
            "status" => "success",
            "data" => [
                "total_admissions" => (int)$stats['total_admissions'],
                "current_inpatients" => (int)$stats['current_inpatients'],
                "new_patients" => (int)$stats['new_patients'],
                "old_patients" => (int)$stats['old_patients'],
                "readmitted_patients" => (int)$stats['readmitted_patients'],
                "total_discharges" => (int)$stats['total_discharges'],
                "readmission_rate" => round($readmissionRate, 2),
                "total_deaths" => (int)$stats['total_deaths'],
                "total_er_visits" => (int)($erStats['total_er_visits'] ?? 0),
                "total_er_discharges" => (int)($erStats['total_er_discharges'] ?? 0),
                "current_er_patients" => (int)($erStats['current_er_patients'] ?? 0),
                "total_er_deaths" => (int)($erStats['total_er_deaths'] ?? 0),
                "er_new_patients" => (int)($erStats['er_new_patients'] ?? 0),
                "er_old_patients" => (int)($erStats['er_old_patients'] ?? 0),
                "er_readmitted_patients" => (int)($erStats['er_readmitted_patients'] ?? 0),
                "er_readmission_rate" => round($erReadmissionRate, 2),
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}
