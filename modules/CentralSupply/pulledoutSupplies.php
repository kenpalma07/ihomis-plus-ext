<?php
$pageTitle = "Pulled Out Non-Drugs and Supplies";

$startDate = $_GET['startDate'] ?? date('Y-01-01');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
?>

<div id="csPulledOutFilters"
    data-start="<?= $startDate ?? '' ?>"
    data-end="<?= $endDate ?? '' ?>">
</div>

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
        <p class="alert-heading"><b>NOTE:</b> This masterlist includes all <b class="text-danger">PULLED OUT NON-DRUGS / SUPPLIES</b> in the inventory either EXPIRED OR FORCE PULLED OUT. Use the search box to filter by <b>HPERCODE</b>, <b>Drug/Medicine Name</b>, or <b>Lot Number</b>.</p>
    </div>
</div>

<h6 class="px-md-3 text-muted">PULLED OUT NON-DRUGS AND SUPPLIES</h6>

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
            <input type="date" id="pulledoutCSStart" class="form-control"
                value="<?= htmlspecialchars($startDate) ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label"><b>Entry Date To</b></label>
            <input type="date" id="pulledoutCSEnd" class="form-control"
                value="<?= htmlspecialchars($endDate) ?>">
        </div>

        <div class="col-md-3">
            <button type="button" id="filterPulledOutCS" class="btn btn-primary">
                Filter
            </button>

            <button type="button" id="resetPulledOutCS" class="btn btn-secondary">
                Reset
            </button>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <a href="#" id="exportUndoPulledOutCSExcel" class="btn btn-success w-100">
                <i class="bi bi-file-earmark-excel"></i> Export all to Excel
            </a>
        </div>

    </form>
</div>

<div class="px-md-1">
    <div class="table-container">
        <table id="pulledoutSuppliesTable" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th>LOT NUMBER</th>
                    <th>SUPPLY</th>
                    <th>STOCK BALANCE</th>
                    <th>BEGINNING BALANCE</th>
                    <th>DISPENSED</th>
                    <th>SELLING PRICE</th>
                    <th>ENTRY DATE</th>
                    <th>EXPIRY DATE</th>
                    <th>DATE MODIFIED</th>
                    <th>ACCOUNT TYPE</th>
                    <th>STATUS</th>
                    <th>LOCATION</th>
                    <th>REMARKS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Undo Pulled Out Modal -->
<div class="modal fade" id="undoPulledOutCSModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"> Confirm Undo Pull Out</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p><strong>Non-drugs / Supplies:</strong></p>
                <p id="modalUndoCS" class="fw-bold"></p>
                <div class="mb-3 text-start">
                    <label class="form-label"><b>Reason / Remarks</b></label>
                    <textarea id="undoPulledOutCSRemarks" class="form-control" rows="3" placeholder="Enter reason for undoing pull out..."></textarea>
                </div>
                <hr>
                <p class="text-danger fw-bold">Are you sure you want to undo the pull out of this item?</p>
            </div>

            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <input type="hidden" name="undoSupplies" id="confirmUndoSupply">
                <button type="button" id="confirmUndoPulledOutCS" class="btn btn-success">Yes, Undo Pull Out</button>
            </div>
        </div>
    </div>
</div>