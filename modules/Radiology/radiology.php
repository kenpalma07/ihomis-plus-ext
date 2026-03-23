<?php
require 'db.php';
$pageTitle = "Radiology Report";
?>

<!-- TOP NAVBAR -->
<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <button class="navbar-toggler d-md-none collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <span class="navbar-brand mb-0 h1 text-white">
        RADIOLOGY
    </span>
</nav>

<br>

<div class="px-md-3">
    <div class="alert alert-warning alert-compact-left" role="alert">
        <p class="alert-heading">
            <b>NOTE:</b> This masterlist shows <b>radiology requests</b> and their
            <b>TURNAROUND TIME</b> from <b>Request Date</b> to <b>Charge Date</b>.
        </p>
    </div>
</div>

<h6 class="px-md-3 text-muted">TURNAROUND TIME</h6>

<!-- FILTERS -->
<div class="px-md-3 mb-3">
    <form id="labFilterForm" class="row g-2 align-items-end">

        <div class="col-md-3">
            <label class="form-label"><b>Request From</b></label>
            <input type="date" id="reqStart" class="form-control">
        </div>

        <div class="col-md-3">
            <label class="form-label"><b>Request To</b></label>
            <input type="date" id="reqEnd" class="form-control">
        </div>

        <div class="col-md-3">
            <label class="form-label"><b>Charged From</b></label>
            <input type="date" id="chgStart" class="form-control">
        </div>

        <div class="col-md-3">
            <label class="form-label"><b>Charged To</b></label>
            <input type="date" id="chgEnd" class="form-control">
        </div>

        <div class="col-md-3">
            <button type="button" id="filterBtn" class="btn btn-primary mt-2">Filter</button>
            <button type="button" id="resetBtn" class="btn btn-secondary mt-2">Reset</button>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <a href="#" id="exportRadiologyBtn" class="btn btn-success w-100">
                <i class="bi bi-file-earmark-excel"></i> Export all to Excel
            </a>
        </div>

    </form>
</div>

<!-- TABLE -->
<div class="px-md-1">
    <div class="table-container">
        <table id="radTable" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th>HPERCODE</th>
                    <th>PATIENT</th>
                    <th>PROCEDURE</th>
                    <th>LOCATION</th>
                    <th>REQUEST DATE</th>
                    <th>CHARGED DATE</th>
                    <th>TURNAROUND TIME</th>
                    <th>STATUS</th>
                    <th>ENC STATUS</th>
                    <th>REMARKS</th>
                    <th>ORDCON</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>