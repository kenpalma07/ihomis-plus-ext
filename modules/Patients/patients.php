<?php
$pageTitle = "Masterlist";
require 'db.php';
?>


<!-- MAIN CONTENT -->

<!-- TOP NAVBAR -->
<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <button class="navbar-toggler d-md-none collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <span class="navbar-brand mb-0 h1 text-white ">
        MASTERLIST
    </span>
</nav>
<br>

<div class="px-md-3">
    <div class="alert alert-warning alert-compact-left" role="alert">
        <p class="alert-heading" style="font-size: 12px;"><b>NOTE:</b> This masterlist includes all patient encounters across all departments. Use the search box to filter by <b>HPERCODE</b>, <b>Patient Name</b>, or <b>Encounter Type</b>.</p>
    </div>
</div>

<div class="px-md-1">
    <div class="table-container">
        <table id="patientTable" class="table table-striped table-bordered nowrap px-md-1" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th>HPERCODE</th>
                    <th>PATIENT</th>
                    <th>BIRTHDATE</th>
                    <th>ENCOUNTER</th>
                    <th>REGISTRATION DATE</th>
                    <th>DISCHARGE DATE</th>
                    <th>DISCHARGE BY</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>