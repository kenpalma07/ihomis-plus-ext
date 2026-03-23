<?php
$startDate = $_GET['start_date'] ?? date('Y-01-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');

$sql = "
SELECT
    COUNT(*) AS total_admissions,

    SUM(CASE 
        WHEN disdate IS NOT NULL 
        AND disdate BETWEEN '$startDate' AND '$endDate'
        THEN 1 ELSE 0 
    END) AS total_discharges,

    SUM(CASE 
        WHEN disdate IS NULL 
        THEN 1 ELSE 0 
    END) AS current_inpatients,

    SUM(CASE 
        WHEN newold = 'N' 
        AND admdate BETWEEN '$startDate' AND '$endDate'
        THEN 1 ELSE 0 
    END) AS new_patients,

    SUM(CASE 
        WHEN newold = 'O' 
        AND admdate BETWEEN '$startDate' AND '$endDate'
        THEN 1 ELSE 0 
    END) AS old_patients,

    (
        SELECT COUNT(DISTINCT h1.hpercode)
        FROM hadmlog h1
        JOIN hadmlog h2 
            ON h1.hpercode = h2.hpercode
            AND h2.admdate > h1.disdate
        WHERE h1.disdate BETWEEN '$startDate' AND '$endDate'
    ) AS readmitted_patients,

    ROUND(
        (
            SELECT COUNT(DISTINCT h1.hpercode)
            FROM hadmlog h1
            JOIN hadmlog h2 
                ON h1.hpercode = h2.hpercode
                AND h2.admdate > h1.disdate
            WHERE h1.disdate BETWEEN '$startDate' AND '$endDate'
        ) 
        / NULLIF(
            SUM(CASE 
                WHEN disdate BETWEEN '$startDate' AND '$endDate' 
                THEN 1 ELSE 0 
            END),0
        ) * 100
    ,2) AS readmission_rate

FROM hadmlog
WHERE admdate BETWEEN '$startDate' AND '$endDate';
";
?>

<?php
$pdo = new PDO("mysql:host=localhost;dbname=hospital_dbo", "root", "root");
$stmt = $pdo->prepare($sql);

$params = [
    $startDate,
    $endDate,   // total_discharges
    $startDate,
    $endDate,   // new_patients
    $startDate,
    $endDate,   // old_patients
    $startDate,
    $endDate,   // readmitted_patients subquery
    $startDate,
    $endDate,   // readmitted_patients subquery for ROUND
    $startDate,
    $endDate,   // ROUND denominator SUM
    $startDate,
    $endDate    // main WHERE admdate
];

$stmt->execute($params);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<?php
// Map query results to dashboard cards
$dashboardStats = [
    'Registered Patients' => number_format($stats['total_admissions']),
    'Current Inpatients' => number_format($stats['current_inpatients']),
    'New Patients' => number_format($stats['new_patients']),
    'Old Patients' => number_format($stats['old_patients']),
    'Readmitted Patients' => number_format($stats['readmitted_patients']),
    'Readmission Rate (%)' => number_format($stats['readmission_rate'], 2) . '%',
    'Total Discharges' => number_format($stats['total_discharges'])
];
?>

<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <span class="navbar-brand mb-0 h1 text-white ">
        MEDICAL RECORDS - DASHBOARD
    </span>
</nav>
<br>

<div class="px-md-3">
    <div class="alert alert-info alert-compact-left" role="alert">
        <p class="alert-heading"><b>NOTE:</b> This dashboard provides an overview of patient admissions, discharges, and readmissions within the selected date range. Use the date filters to customize the data displayed.</p>
    </div>
</div>

<div class="px-md-3">

    <form method="get" class="mb-3 d-flex align-items-center gap-2">
        <input type="hidden" name="page" value="viewDashboard">

        <div class="input-group">
            <span class="input-group-text">From</span>
            <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
        </div>

        <div class="input-group">
            <span class="input-group-text">To</span>
            <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
        </div>

        <button type="submit" class="btn btn-success">Filter</button>
    </form>

</div>

<div class="container-fluid px-3 mt-3">
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-3">
        <?php foreach ($dashboardStats as $key => $value): ?>
            <div class="col">
                <div class="card text-white bg-success shadow-sm h-100">
                    <div class="card-body d-flex align-items-center">
                        <i class="fa-solid fa-bed fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-0"><?= $value ?></h5>
                            <small class="text-white"><?= $key ?></small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>



<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .card {
        border-radius: 0.5rem;
    }

    .card-body i {
        opacity: 0.8;
    }
</style>