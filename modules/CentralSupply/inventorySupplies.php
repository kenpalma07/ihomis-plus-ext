<?php
$pageTitle = "Central Supply Inventory";

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');
?>

<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
    <button class="navbar-toggler d-md-none collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>

    <span class="navbar-brand mb-0 h1 text-white">
        CENTRAL SUPPLY
    </span>
</nav>

<br>

<div class="px-md-3">
    <div class="alert alert-info alert-compact-left" role="alert">
        <p class="alert-heading"><b>NOTE:</b> This masterlist includes all supplies in the inventory. Use the search box to filter by <b>HPERCODE</b>, <b>Supply Name</b>, or <b>Lot Number</b>. <b class="text-danger">EXPIRED STOCK IS NOT INCLUDED IN THE TOTAL STOCK.</b></p>
    </div>
</div>

<h6 class="px-md-3 text-muted">INVENTORY SUPPLIES</h6>

<div class="px-md-3">
    <div class="row mb-1">
        <div class="col-md-3">
            <div class="alert alert-primary py-2">
                <strong>Total Stock:</strong>
                <span id="totalStock">0</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="alert alert-success py-2">
                <strong>Total Value:</strong>
                <span id="totalValue">0.00</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="alert alert-danger py-2">
                <strong>Expired Stock:</strong>
                <span id="expiredStock">0</span>
            </div>
        </div>

        <div class="col-md-3">
            <div class="alert alert-warning py-2">
                <strong>Expired Value:</strong>
                <span id="expiredValue">0.00</span>
            </div>
        </div>
    </div>
</div>