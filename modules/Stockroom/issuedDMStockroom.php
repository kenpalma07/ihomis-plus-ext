<?php
$pageTitle = "Issued Drugs and Medicine to Pharmacy";

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');
?>

<div id="issuedDMStockFilter"
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

	<span class="navbar-brand mb-0 h1 text-white ">
		STOCK ROOM
	</span>
</nav>
<br>

<div class="px-md-3">
	<div class="alert alert-info alert-compact-left" role="alert">
		<p class="alert-heading"><b>NOTE:</b> This masterlist includes all drugs and medicines issued to patients. Use the search box to filter by <b>HPERCODE</b>, <b>Patient Name</b>, or <b>Drug/Medicine Name</b>.</p>
	</div>
</div>

<h6 class="px-md-3 text-muted">ISSUED DRUGS AND MECIDINE FOR PHARMACY</h6>

<div class="px-md-3">
	<div class="row mb-1">
		<div class="col-md-4">
			<div class="alert alert-primary py-2">
				<strong>Total Drugs Requested:</strong> <span id="totalDrugs">0</span>
			</div>
		</div>

		<div class="col-md-4">
			<div class="alert alert-success py-2">
				<strong>Total Drugs Issued:</strong> <span id="totalIssued">0</span>
			</div>
		</div>

		<div class="col-md-4">
			<div class="alert alert-info py-2">
				<strong>Total Drugs Received:</strong> <span id="totalReceived">0</span>
			</div>
		</div>
	</div>
</div>

<div class="px-md-3 mb-3">
	<form id="filterForm" class="row g-2 align-items-end">
		<div class="col-md-3">
			<label class="form-label"><b>Date Issued From</b></label>
			<input type="date" id="issuedStockStart" class="form-control"
				value="<?= htmlspecialchars($startDate) ?>">
		</div>

		<div class="col-md-3">
			<label class="form-label"><b>Date Issued To</b></label>
			<input type="date" id="issuedStockEnd" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
		</div>

		<div class="col-md-3">
			<button type="button" id="filterDMIssuedStock" class="btn btn-primary">
				Filter
			</button>
			<button type="button" id="resetDMIssuedStock" class="btn btn-secondary">
				Reset
			</button>
		</div>
		<div class="col-md-3 d-flex align-items-end">
			<a href="#" id="exportIssuedDMStockroom" class="btn btn-success w-100">
				<i class="bi bi-file-earmark-excel"></i> Export all to Excel
			</a>
		</div>
	</form>
</div>

<div class="px-md-1">
	<div class="table-container">
		<table id="issuedDMSRTable" class="table table-striped table-bordered nowrap px-md-1" style="width:100%;">
			<thead class="table-success">
				<tr>
					<th>CONTROL ID</th>
					<th>ORDER DATE</th>
					<th>DATE ISSUED</th>
					<th>TURNAROUND TIME</th>
					<th>LOT NUMBER</th>
					<th>DRUG/MEDICINE</th>
					<th>QTY REQUESTED</th>
					<th>QTY ISSUED</th>
					<th>QTY RECEIVED</th>
					<th>FROM LOCATION</th>
					<th>STATUS</th>
					<th>RECEIVED</th>
					<th>ISSUED BY</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>

<!-- <style>
	#issuedDMSRTable td {
		border: 1px solid black !important;
	}
</style> -->