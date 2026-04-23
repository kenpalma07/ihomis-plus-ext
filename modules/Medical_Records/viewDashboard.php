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
            <h5 class="card-title mb-4">ADMISSION STATISTICS</h5>
            <!-- Cards -->
            <div class="row g-3">

                <?php
                $cards = [
                    "total_admissions" => "Registered Patients",
                    "current_inpatients" => "Current Inpatients",
                    "new_patients" => "New Patients",
                    "old_patients" => "Returned Patients",
                    "readmitted_patients" => "Readmitted Patients",
                    "readmission_rate" => "Readmission Rate (%)",
                    "total_discharges" => "Total Discharges",
                    "total_deaths" => "Total Deaths"
                ];

                $clickableMetrics = [
                    "admission" => [
                        "current_inpatients",
                        "readmitted_patients",
                        "total_deaths"
                    ],
                    "emergency" => [
                        "current_er_patients",
                        "er_readmitted_patients",
                        "total_er_deaths"

                    ],
                    "outpatient" => [
                        "current_opd_patients",
                        "readmitted_opd_patients",
                        "total_opd_deaths"
                    ]
                ];

                foreach ($cards as $id => $label):

                    $isClickable = in_array($id, $clickableMetrics['admission']);
                ?>
                    <div class="col-md-3">
                        <div class="card bg-success text-white shadow-sm h-100 
                            <?= $isClickable ? 'dashboard-card' : '' ?>"

                            <?= $isClickable ? "data-type='admission' data-metric='$id' style='cursor:pointer;'" : '' ?>>

                            <div class="card-body d-flex align-items-center">

                                <i class="fa-solid fa-bed fa-2x me-3"></i>

                                <div>
                                    <h5 id="<?= $id ?>">0</h5>
                                    <small>
                                        <?= $label ?>

                                        <?php if ($isClickable): ?>
                                            <span class="text-white fw-bold ms-1">(view)</span>
                                        <?php endif; ?>
                                    </small>
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
                        "er_old_patients" => "ER Returned Patients",
                        "er_readmitted_patients" => "ER Readmitted",
                        "er_readmission_rate" => "ER Readmission Rate (%)",
                        "total_er_discharges" => "ER Discharges",
                        "total_er_deaths" => "ER Deaths",
                    ];

                    $clickableMetrics = [
                        "admission" => [
                            "current_inpatients",
                            "readmitted_patients",
                            "total_deaths"
                        ],
                        "emergency" => [
                            "current_er_patients",
                            "er_readmitted_patients",
                            "total_er_deaths"

                        ],
                        "outpatient" => [
                            "current_opd_patients",
                            "readmitted_opd_patients",
                            "total_opd_deaths"
                        ]
                    ];

                    foreach ($cards as $id => $label):

                        $isClickable = in_array($id, $clickableMetrics['emergency']);
                    ?>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white shadow-sm h-100 
                            <?= $isClickable ? 'dashboard-card' : '' ?>"

                                <?= $isClickable ? "data-type='emergency' data-metric='$id' style='cursor:pointer;'" : '' ?>>

                                <div class="card-body d-flex align-items-center">

                                    <i class="fa-solid fa-bed fa-2x me-3"></i>

                                    <div>
                                        <h5 id="<?= $id ?>">0</h5>
                                        <small>
                                            <?= $label ?>

                                            <?php if ($isClickable): ?>
                                                <span class="text-white fw-bold ms-1">(view)</span>
                                            <?php endif; ?>
                                        </small>
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
            <h5 class="card-title mb-4">OUTPATIENT STATISTICS</h5>
            <!-- Cards -->
            <div class="row g-3">

                <?php
                $cards = [
                    "total_opd_visits" => "OPD Visits",
                    "current_opd_patients" => "Current OPD Inpatients",
                    "new_opd_patients" => "New OPD Patients",
                    "old_opd_patients" => "OPD Returned Patients",
                    "readmitted_opd_patients" => "Readmitted OPD Patients",
                    "readmission_opd_rate" => "OPD Readmission Rate (%)",
                    "total_opd_discharges" => "OPD Discharges",
                    "total_opd_deaths" => "OPD Deaths",
                ];

                $clickableMetrics = [
                    "admission" => [
                        "current_inpatients",
                        "readmitted_patients",
                        "total_deaths"
                    ],
                    "emergency" => [
                        "current_er_patients",
                        "er_readmitted_patients",
                        "total_er_deaths"

                    ],
                    "outpatient" => [
                        "current_opd_patients",
                        "readmitted_opd_patients",
                        "total_opd_deaths"
                    ]
                ];

                foreach ($cards as $id => $label):

                    $isClickable = in_array($id, $clickableMetrics['outpatient']);
                ?>
                    <div class="col-md-3">
                        <div class="card bg-warning text-black shadow-sm h-100 
                            <?= $isClickable ? 'dashboard-card' : '' ?>"

                            <?= $isClickable ? "data-type='outpatient' data-metric='$id' style='cursor:pointer;'" : '' ?>>

                            <div class="card-body d-flex align-items-center">

                                <i class="fa-solid fa-bed fa-2x me-3"></i>

                                <div>
                                    <h5 id="<?= $id ?>">0</h5>
                                    <small>
                                        <?= $label ?>

                                        <?php if ($isClickable): ?>
                                            <span class="text-primary fw-bold ms-1">(view)</span>
                                        <?php endif; ?>
                                    </small>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>
<br>

<div class="modal fade" id="patientModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Patient List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table id="viewPatient" class="table table-bordered table-striped" style="width:100%">
                    <thead class="table-success">
                        <tr>
                            <th>HPERCODE</th>
                            <th>PATIENT</th>
                            <th>DATE REGISTERED</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<style>
    .dashboard-card {
        transition: 0.2s ease;
    }

    .dashboard-card:hover {
        transform: scale(1.03);
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    }
</style>