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

<div class="px-md-3 mb-3">
    <form id="filterForm" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label"><b>Entry Date From</b></label>
            <input type="date" id="inventoryStart" class="form-control"
                value="<?= htmlspecialchars($startDate) ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label"><b>Entry Date To</b></label>
            <input type="date" id="inventorySupplyEnd" class="form-control"
                value="<?= htmlspecialchars($endDate) ?>">
        </div>

        <div class="col-md-3">
            <button type="button" id="filterSupplyInventory" class="btn btn-primary">
                Filter
            </button>

            <button type="button" id="resetSupplyInventory" class="btn btn-secondary">
                Reset
            </button>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <a href="#" id="exportInventoryExcel" class="btn btn-success w-100">
                <i class="bi bi-file-earmark-excel"></i> Export all to Excel
            </a>
        </div>
    </form>
</div>

<div class="px-md-1">
    <div class="table-container">
        <table id="inventorySuppliesTable" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>LOT NUMBER</th>
                    <th>SUPPLY</th>
                    <th>STOCK BALANCE</th>
                    <th>SELLING PRICE</th>
                    <th>ENTRY DATE</th>
                    <th>EXPIRY DATE</th>
                    <th>ACCOUNT TYPE</th>
                    <th>STATUS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>