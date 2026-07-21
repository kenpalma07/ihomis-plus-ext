<?php
$pageTitle = "ZBB Dashboard";

$startDate = $_GET['start_date'] ?? date('Y-06-15');
$endDate   = $_GET['end_date'] ?? date('Y-m-d');
?>
<div id="csInventoryFilters"
	data-start="<?= $startDate ?? '' ?>"
	data-end="<?= $endDate ?? '' ?>">
</div>

<nav class="navbar navbar-light bg-success sticky-top shadow">
	<span class="navbar-brand text-white px-3">
		ZBB - DASHBOARD
	</span>
</nav>
<br>

<div class="px-md-3">
	<div class="alert alert-info alert-compact-left" role="alert">
		<span class="fw-bold">Note:</span> This dashboard provides an overview of the Zero-Based Budgeting (ZBB) process, including budget allocations, expenditures, and performance metrics. Use the navigation menu to access detailed reports and logs.
	</div>
</div>

<div class="px-md-3">
	<div class="mb-3 d-flex align-items-center gap-2">
		<div class="input-group">
			<span class="input-group-text">From</span>
			<input type="date" id="zbb_start_date" class="form-control"
				value="<?= htmlspecialchars($startDate) ?>">
		</div>

		<div class="input-group">
			<span class="input-group-text">To</span>
			<input type="date" id="zbb_end_date" class="form-control"
				value="<?= htmlspecialchars($endDate) ?>">
		</div>

		<button id="filterBtn" class="btn btn-success w-100">
			Filter
		</button>
	</div>
</div>

<div class="container-fluid mt-3">
	<div class="card bg-light shadow-sm">
		<div class="card-body">
			<h5 class="card-title mb-4">ZBB STATISTICS</h5>
			<div class="row g-3">
				<?php
				$cards = [
					"total_zbb_patients" => "Total ZBB Count Patients",
					"total_actual_charges" => "Total Actual Charges",
					"total_philhealth_charges" => "Total PhilHealth Charges",
					"total_balance" => "Total Balance"
				];

				foreach ($cards as $id => $label):
					$isClickable = ($id === 'total_zbb_patients');
				?>
					<div class="col-md-3">
						<div class="card bg-success text-white shadow-sm h-100 <?= $isClickable ? 'dashboard-card' : '' ?>"
							<?= $isClickable ? "data-type='patients' data-metric='$id' style='cursor:pointer;'" : '' ?>>
							<div class="card-body d-flex align-items-center">
								<i class="fa-solid fa-bed fa-2x me-3"></i>
								<div>
									<h5 id="<?= $id ?>">0</h5>
									<small><?= $label ?></small>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<div class="container-fluid mt-2 mb-3">
	<div class="card bg-light shadow-sm">
		<div class="card-body">
			<div class="px-md-1">
				<div class="table-container">
					<table id="zbbPatientTable" class="table table-striped table-bordered nowrap px-md-1" style="width:100%">
						<thead class="table-success">
							<tr>
								<th>HPERCODE</th>
								<th>NAME</th>
								<th>TOTAL ACTUAL CHARGES</th>
								<th>TOTAL PHILHEALTH CHARGES</th>
								<th>TOTAL BALANCE</th>
								<th>ENTRY DATE</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
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