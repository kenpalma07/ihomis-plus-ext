<?php
require 'db.php';
$pageTitle = "Emergency Report";
?>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <button class="navbar-toggler d-md-none collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <span class="navbar-brand mb-0 h1 text-white ">
        EMERGENCY
    </span>
</nav>
<br>

<div class="px-md-3">
    <div class="alert alert-warning alert-compact-left" role="alert">
        <p class="alert-heading"><b>NOTE:</b> This masterlist includes all patient encounters across all departments. Use the search box to filter by <b>HPERCODE</b>, <b>Patient Name</b>, or <b>Encounter Type</b>.</p>
    </div>
</div>

<h6 class="px-md-3 text-muted">TURNAROUND TIME</h6>

<div class="px-md-3 mb-3">
    <form id="erFilterForm" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label"><b>Registration From</b></label>
            <input type="date" id="regStart" class="form-control" value="<?= date('Y-m-01') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><b>Registration To</b></label>
            <input type="date" id="regEnd" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label"><b>Discharge From</b></label>
            <input type="date" id="disStart" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label"><b>Discharge To</b></label>
            <input type="date" id="disEnd" class="form-control">
        </div>
        <div class="col-md-3">
            <button type="button" id="filterBtn" class="btn btn-primary mt-2">Filter</button>
            <button type="button" id="resetBtn" class="btn btn-secondary mt-2">Reset</button>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <a href="#" id="exportEmergencyBtn" class="btn btn-success w-100">
                <i class="bi bi-file-earmark-excel"></i> Export all to Excel
            </a>
        </div>
    </form>
</div>

<div class="px-md-1">
    <div class="table-container">
        <table id="erTable" class="table table-striped table-bordered nowrap px-md-1" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th>HPERCODE</th>
                    <th>PATIENT</th>
                    <th>BIRTHDATE</th>
                    <th>REGISTRATION DATE</th>
                    <th>DISCHARGED DATE</th>
                    <th>TURNAROUND TIME</th>
                    <th>DISCHARGE BY</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>