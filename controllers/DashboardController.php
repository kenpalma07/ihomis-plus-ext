<?php
require '../db.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'loadDashboard':
        loadDashboard($pdo);
        break;
    case 'getPatientsByMetric':
        getPatientsByMetric($pdo);
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
        // OUTPATIENT FILTER LOGIC (YOUR REQUEST APPLIED HERE)
        // =====================
        $opdWhere = " WHERE 1=1 ";
        $opdParams = [];

        if (!empty($startDate)) {
            $opdWhere .= " AND opddate >= :startDate ";
            $opdParams[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $opdWhere .= " AND opddate <= :endDate ";
            $opdParams[':endDate'] = $endDate . " 23:59:59";
        }

        // =====================
        // OUTPATIENT QUERY
        // =====================
        $opdSql = "
            SELECT
                COUNT(*) AS total_opd_visits,
                
                SUM(CASE 
                    WHEN opddtedis IS NULL 
                    THEN 1 ELSE 0 
                END) AS current_opd_patients,
                
                SUM(CASE 
                    WHEN opddtedis IS NOT NULL
                    AND opddate BETWEEN :startDate AND :endDate
                    THEN 1 ELSE 0 
                END) AS total_opd_discharges,
            
            SUM(CASE 
                WHEN newold = 'N' 
                AND opddate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 
            END) AS new_opd_patients,
            
            SUM(CASE 
                WHEN newold = 'O' 
                AND opddate BETWEEN :startDate AND :endDate
                THEN 1 ELSE 0 
            END) AS old_opd_patients,
            
            (
                SELECT COUNT(DISTINCT h1.hpercode)
                FROM hopdlog h1
                JOIN hopdlog h2 
                    ON h1.hpercode = h2.hpercode
                    AND h2.opddate > h1.opddtedis
                WHERE h1.opddtedis BETWEEN :startDate AND :endDate
            ) AS readmitted_opd_patients,
            
            SUM(CASE 
                WHEN pexpireddate IS NOT NULL 
                AND opddisp = 'DIEDD'
                AND pexpireddate >= :startDate
                AND pexpireddate < DATE_ADD(:endDate, INTERVAL 1 DAY)
                THEN 1 ELSE 0 
            END) AS total_opd_deaths,
            
            ROUND(
                    (
                        SELECT COUNT(DISTINCT h1.hpercode)
                        FROM hopdlog h1
                        JOIN hopdlog h2 
                            ON h1.hpercode = h2.hpercode
                            AND h2.opddate > h1.opddtedis
                        WHERE h1.opddtedis >= :startDate 
                        AND h1.opddtedis < DATE_ADD(:endDate, INTERVAL 1 DAY)
                    ) 
                    / NULLIF(
                        SUM(CASE 
                            WHEN opddtedis >= :startDate 
                            AND opddtedis < DATE_ADD(:endDate, INTERVAL 1 DAY)
                            THEN 1 ELSE 0 
                        END), 0
                    ) * 100
                ,2) AS readmission_opd_rate
                
            FROM hopdlog
            $opdWhere
        ";

        $opdStmt = $pdo->prepare($opdSql);
        $opdStmt->execute($opdParams);

        $opdStats = $opdStmt->fetch(PDO::FETCH_ASSOC);

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

                // OUTPATIENT
                "total_opd_visits" => (int)($opdStats['total_opd_visits'] ?? 0),
                "current_opd_patients" => (int)($opdStats['current_opd_patients'] ?? 0),
                "total_opd_discharges" => (int)($opdStats['total_opd_discharges'] ?? 0),
                "new_opd_patients" => (int)($opdStats['new_opd_patients'] ?? 0),
                "old_opd_patients" => (int)($opdStats['old_opd_patients'] ?? 0),
                "readmitted_opd_patients" => (int)($opdStats['readmitted_opd_patients'] ?? 0),
                "total_opd_deaths" => (int)($opdStats['total_opd_deaths'] ?? 0),
                "readmission_opd_rate" => round($opdStats['readmission_opd_rate'] ?? 0, 2)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
}

function getPatientsByMetric($pdo)
{
    $draw = $_POST['draw'];
    $start = $_POST['start'];
    $length = $_POST['length'];

    $metric = $_POST['metric'] ?? '';
    $type = $_POST['type'] ?? '';

    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

    $params = [];

    // =========================
    // EMERGENCY (HERLOG)
    // =========================
    if ($type === 'emergency') {

        $where = " WHERE er.erdate IS NOT NULL ";
        $params = [];

        // ✅ DATE FILTER (CONSISTENT + CORRECT)
        if (!empty($startDate)) {
            $where .= " AND er.erdate >= :startDate ";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND er.erdate < DATE_ADD(:endDate, INTERVAL 1 DAY) ";
            $params[':endDate'] = $endDate . " 00:00:00";
        }

        // ✅ CURRENT ER PATIENTS
        if ($metric === 'current_er_patients') {
            $where .= " AND er.erdtedis IS NULL ";
        }

        // ✅ READMITTED ER PATIENTS
        if ($metric === 'er_readmitted_patients') {

            $where = "
                WHERE er.erdtedis >= :startDate
                AND er.erdtedis < DATE_ADD(:endDate, INTERVAL 1 DAY)
                AND EXISTS (
                    SELECT 1
                    FROM herlog h2
                    WHERE h2.hpercode = er.hpercode
                    AND h2.erdate > er.erdtedis
                )
            ";
        }

        // ✅ FINAL QUERY (FIXED GROUP + ORDER)
        $sql = "
            SELECT 
                er.hpercode,
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
                MAX(er.erdate) AS date_registered
            FROM herlog er
            LEFT JOIN hperson hp ON er.hpercode = hp.hpercode
            $where
            GROUP BY er.hpercode
            ORDER BY date_registered DESC
            LIMIT :start, :length
        ";
    }

    // =========================
    // ADMISSION (ADMLOG)
    // =========================
    else if ($type === 'admission') {

        $params = [];
        $where = " WHERE adm.admdate IS NOT NULL ";

        // ✅ STANDARD DATE FILTER
        if (!empty($startDate)) {
            $where .= " AND adm.admdate >= :startDate ";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND adm.admdate < DATE_ADD(:endDate, INTERVAL 1 DAY) ";
            $params[':endDate'] = $endDate . " 00:00:00";
        }

        // ✅ CURRENT INPATIENTS
        if ($metric === 'current_inpatients') {
            $where .= " AND adm.disdate IS NULL ";
        }

        // ✅ READMITTED (IMPORTANT: RESET WHERE)
        if ($metric === 'readmitted_patients') {

            $where = "
                WHERE adm.disdate >= :startDate
                AND adm.disdate < DATE_ADD(:endDate, INTERVAL 1 DAY)
                AND EXISTS (
                    SELECT 1
                    FROM hadmlog h2
                    WHERE h2.hpercode = adm.hpercode
                    AND h2.admdate > adm.disdate
                )
            ";
        }

        $sql = "
            SELECT 
                adm.hpercode,
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
                MAX(adm.admdate) AS date_registered
            FROM hadmlog adm
            LEFT JOIN hperson hp ON adm.hpercode = hp.hpercode
            $where
            GROUP BY adm.hpercode
            ORDER BY date_registered DESC
            LIMIT :start, :length
        ";
    } else if ($type === 'outpatient') {
        $params = [];
        $where = " WHERE opd.opddate IS NOT NULL ";

        // ✅ STANDARD DATE FILTER
        if (!empty($startDate)) {
            $where .= " AND opd.opddate >= :startDate ";
            $params[':startDate'] = $startDate . " 00:00:00";
        }

        if (!empty($endDate)) {
            $where .= " AND opd.opddate < DATE_ADD(:endDate, INTERVAL 1 DAY) ";
            $params[':endDate'] = $endDate . " 00:00:00";
        }

        // ✅ CURRENT OPD
        if ($metric === 'current_opd_patients') {
            $where .= " AND opd.opddtedis IS NULL 
                AND opddate BETWEEN :startDate AND :endDate
                ORDER BY date_registered DESC
                LIMIT :start, :length
            ";
        }

        // ✅ READMITTED OPD (MATCH DASHBOARD LOGIC)
        if ($metric === 'readmitted_opd_patients') {

            $where = "
                WHERE opd.opddtedis IS NOT NULL
                AND opd.opddtedis >= :startDate
                AND opd.opddtedis < DATE_ADD(:endDate, INTERVAL 1 DAY)
                AND EXISTS (
                    SELECT 1
                    FROM hopdlog h2
                    WHERE h2.hpercode = opd.hpercode
                    AND h2.opddate > opd.opddtedis
                )
                
                GROUP BY opd.hpercode
                ORDER BY date_registered DESC
                LIMIT :start, :length
            ";
        }

        $sql = "
            SELECT 
                opd.hpercode,
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
                opd.opddate AS date_registered
            FROM hopdlog opd
            LEFT JOIN hperson hp ON opd.hpercode = hp.hpercode
            $where
            
        ";
    }

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }

    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // COUNT
    $countSql = str_replace("LIMIT :start, :length", "", $sql);
    $countStmt = $pdo->prepare($countSql);

    foreach ($params as $key => $val) {
        $countStmt->bindValue($key, $val);
    }

    $countStmt->execute();
    $recordsFiltered = $countStmt->rowCount();

    if ($type === 'emergency') {
        $total = $pdo->query("SELECT COUNT(*) FROM herlog")->fetchColumn();
    } elseif ($type === 'admission') {
        $total = $pdo->query("SELECT COUNT(*) FROM hadmlog")->fetchColumn();
    } elseif ($type === 'outpatient') {
        $total = $pdo->query("SELECT COUNT(*) FROM hopdlog")->fetchColumn();
    }

    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => intval($total),
        "recordsFiltered" => intval($recordsFiltered),
        "data" => $data
    ]);
}
