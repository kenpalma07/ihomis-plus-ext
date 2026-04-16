<?php
$pageTitle = "Stockroom Inventory";

$startDate = $_GET['startDate'] ?? date('Y-01-01');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
?>

<nav class="navbar navbar-light bg-success sticky-top flex-md-nowrap p-2 shadow">
	<button class="navbar-toggler d-md-none collapsed"
		type="button"
		data-bs-toggle="collapse"
		data-bs-target="#sidebarMenu">
		<span class="navbar-toggler-icon"></span>
	</button>

	<span class="navbar-brand mb-0 h1 text-white">
		STOCKROOM
	</span>
</nav>

<br>

<div class="px-md-3">
	<div class="alert alert-info alert-compact-left" role="alert">
		<p class="alert-heading"><b>NOTE:</b> This masterlist includes all items in the stockroom inventory. Use the search box to filter by <b>HPERCODE</b>, <b>Item Name</b>, or <b>Lot Number</b>. <b class="text-danger">EXPIRED STOCK IS NOT INCLUDED IN THE TOTAL STOCK.</b></p>
	</div>
</div>

<h6 class="px-md-3 text-muted">INVENTORY STOCKROOM</h6>

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
			<input type="date" id="inventoryStockStart" class="form-control"
				value="<?= htmlspecialchars($startDate) ?>">
		</div>

		<div class="col-md-3">
			<label class="form-label"><b>Entry Date To</b></label>
			<input type="date" id="inventoryStockEnd" class="form-control"
				value="<?= htmlspecialchars($endDate) ?>">
		</div>

		<div class="col-md-3">
			<button type="button" id="filterStockInventory" class="btn btn-primary">
				Filter
			</button>

			<button type="button" id="resetStockInventory" class="btn btn-secondary">
				Reset
			</button>
		</div>

		<div class="col-md-3 d-flex align-items-end">
			<a href="#" id="exportInventoryStockExcel" class="btn btn-success w-100">
				<i class="bi bi-file-earmark-excel"></i> Export all to Excel
			</a>
		</div>
	</form>
</div>