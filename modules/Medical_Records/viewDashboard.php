<?php
$pageTitle = "Dashboard";
?>

<nav class="navbar navbar-light bg-success sticky-top shadow">
    <span class="navbar-brand text-white px-3">
        MEDICAL RECORDS - DASHBOARD
    </span>
</nav>
<br>

<div class="px-md-3">
    <div class="alert alert-info alert-compact-left" role="alert">
        <p class="alert-heading"><b>NOTE:</b> This dashboard provides an overview of patient admissions, discharges, and readmissions within the selected date range. Use the date filters to customize the data displayed.<b style="color: red;"> NOTE: THIS ONLY RECORDS NUMBER OF PATIENT'S ADMISSION</b></p>
    </div>
</div>

<div class="px-md-3">
    <div class="mb-3 d-flex align-items-center gap-2">
        <div class="input-group">
            <span class="input-group-text">From</span>
            <input type="date" id="start_date" class="form-control"
                value="<?= date('Y-01-01') ?>">
        </div>

        <div class="input-group">
            <span class="input-group-text">To</span>
            <input type="date" id="end_date" class="form-control"
                value="<?= date('Y-m-d') ?>">
        </div>

        <button id="filterBtn" class="btn btn-success w-100">
            Filter
        </button>
    </div>
</div>

<div class="container-fluid mt-3">

    <div class="card bg-light shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">ADMISSION STATISTICS</h6>
                <!-- Cards -->
                <div class="row g-3">

                    <?php
                    $cards = [
                        "total_admissions" => "Registered Patients",
                        "current_inpatients" => "Current Inpatients",
                        "new_patients" => "New Patients",
                        "old_patients" => "Old Patients",
                        "readmitted_patients" => "Readmitted Patients",
                        "readmission_rate" => "Readmission Rate (%)",
                        "total_discharges" => "Total Discharges",
                        "total_deaths" => "Total Deaths"
                    ];

                    foreach ($cards as $id => $label):
                    ?>
                        <div class="col-md-3">
                            <div class="card bg-success text-white shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="fa-solid fa-bed fa-2x me-3"></i>
                                    <div>
                                        <h5 id="<?= $id ?>">0</h5>
                                        <small><?= $label ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-3">

    <div class="card bg-light shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">EMERGENCY STATISTICS</h6>
                <!-- Cards -->
                <div class="row g-3">

                    <?php
                    $cards = [
                        "total_er_visits" => "ER Visits",
                        "current_er_patients" => "Current ER Patients",
                        "er_new_patients" => "ER New Patients",
                        "er_old_patients" => "ER Old Patients",
                        "er_readmitted_patients" => "ER Readmitted",
                        "er_readmission_rate" => "ER Readmission Rate (%)",
                        "total_er_discharges" => "ER Discharges",
                        "total_er_deaths" => "ER Deaths",
                    ];

                    foreach ($cards as $id => $label):
                    ?>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white shadow-sm h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="fa-solid fa-bed fa-2x me-3"></i>
                                    <div>
                                        <h5 id="<?= $id ?>">0</h5>
                                        <small><?= $label ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
        </div>
    </div>
</div>