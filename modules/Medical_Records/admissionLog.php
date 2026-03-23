<?php
$pageTitle = "Medical Records Report";
?>

<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <button class="navbar-toggler d-md-none collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <span class="navbar-brand mb-0 h1 text-white ">
        MEDICAL RECORDS - ADMISSION LOG
    </span>
</nav>
<br>

<div class="px-md-3">
    <div class="alert alert-warning alert-compact-left" role="alert">
        <p class="alert-heading"><b>NOTE:</b> This masterlist includes all patient encounters who have beem <b> RE-ADMITTED </b> in the facility. Use the search box to filter by <b>HPERCODE</b> or <b>Patient Name</b>.</p>
    </div>
</div>

<h6 class="px-md-3 text-muted">RE-ADMITTED PATIENTS</h6>

<div class="px-md-1">
    <div class="table-container">
        <table id="admLogTable" class="table table-striped table-bordered nowrap px-md-1" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th>HPERCODE</th>
                    <th>PATIENT</th>
                    <th>BIRTHDATE</th>
                    <th>ADMISSION COUNT</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>