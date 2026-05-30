$(document).ready(function () {
	initStockRoomInventory();
	initIssuedStockTable();
	initIssuedSuppliesStockTable();
});

let stockTable = null;

function initStockRoomInventory() {
	if (!$.fn.DataTable.isDataTable("#stockInventoryTable")) {
		stockTable = $("#stockInventoryTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "Controllers/StockRoomController.php",
				type: "POST",
				data: function (d) {
					d.action = "stockInventory";

					// ✅ FIXED: correct IDs
					d.start_date = $("#inventoryStockStart").val() || null;
					d.end_date = $("#inventoryStockEnd").val() || null;
				},
				dataSrc: function (json) {
					// ✅ SAFE fallback
					if (!json) return [];

					// ✅ Update summary cards
					if (json.totals) {
						$("#totalStock").text(json.totals.totalStock ?? 0);

						$("#totalValue").text(formatNumber(json.totals.totalValue));

						$("#expiredStock").text(json.totals.expiredStock ?? 0);

						$("#expiredValue").text(formatNumber(json.totals.expiredValue));
					}

					return json.data || [];
				},
				error: function (xhr, error, thrown) {
					console.error("DataTable AJAX Error:", error);
				},
			},

			pageLength: 10,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			order: [
				// [12, "asc"], // trigger_order (priority)
				[0, "desc"], // entry_date (latest first)
			],
			scrollX: true,
			dom:
				"<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
				"<'row'<'col-12'tr>>" +
				"<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",

			buttons: [
				{
					extend: "excelHtml5",
					text: "Excel",
					className: "btn btn-success btn-sm",
					title: "Stock Room Inventory Report",
					exportOptions: {
						columns: ":visible",
					},
				},
				{
					extend: "csvHtml5",
					text: "CSV",
					className: "btn btn-primary btn-sm",
					title: "Stock Room Inventory Report",
				},
				{
					extend: "print",
					text: "Print",
					className: "btn btn-secondary btn-sm",
					title: "Stock Room Inventory Report",
				},
			],

			columns: [
				{ data: "entry_date" },
				{ data: "expiry_date" },
				{ data: "estatus" },
				{ data: "refno_salesinvo" },
				{ data: "supplier" },
				{ data: "item_name" },
				{ data: "account_type" },
				{ data: "lot_number" },
				{ data: "stock_balance" },
				{ data: "purchase_price" },
				{ data: "selling_price" },
				{ data: "isActiveStatus" },
				{ data: "trigger_order", visible: false },
			],
			createdRow: function (row, data) {
				if (data.estatus === "EXPIRED") {
					$(row).addClass("table-danger");
					$("td:eq(2)", row).addClass("text-danger fw-bold");
				} else if (data.estatus === "NEAR EXPIRE") {
					$(row).addClass("table-warning");
					$("td:eq(2)", row).addClass("text-warning fw-bold");
				} else {
					$("td:eq(2)", row).addClass("text-success fw-bold");
				}

				if (data.stock_balance === "No Stock Balance") {
					$("td:eq(8)", row).addClass("text-danger fw-bold");
				}

				if (data.selling_price === "No Selling Price") {
					$("td:eq(10)", row).addClass("text-danger fw-bold");
				}
			},
		});
	} else {
		// ✅ Prevent reinitialization issue
		stockTable = $("#stockInventoryTable").DataTable();
	}

	// ✅ FILTER BUTTON
	$("#filterStockInventory")
		.off("click")
		.on("click", function () {
			stockTable.ajax.reload();
		});

	// ✅ RESET BUTTON
	$("#resetStockInventory")
		.off("click")
		.on("click", function () {
			$("#inventoryStockStart").val("");
			$("#inventoryStockEnd").val("");

			stockTable.ajax.reload();
		});
}

// ✅ HELPER FUNCTION (clean formatting)
function formatNumber(value) {
	return parseFloat(value ?? 0).toLocaleString(undefined, {
		minimumFractionDigits: 2,
		maximumFractionDigits: 2,
	});
}

function initIssuedStockTable() {
	var issuedDMSRTable = null;
	let container = $("#issuedDMStockFilter");

	if ($("#issuedDMSRTable").length) {
		issuedDMSRTable = $("#issuedDMSRTable").DataTable({
			processing: true,
			serverSide: true,
			responsive: true,
			autoWidth: true,
			ajax: {
				url: "Controllers/StockRoomController.php",
				type: "POST",
				data: function (d) {
					d.action = "issuedStocks";
					d.startDate = $("#issuedStockStart").val() || null;
					d.endDate = $("#issuedStockEnd").val() || null;
				},
				dataSrc: function (json) {
					if (json.totals) {
						$("#totalDrugs").text(json.totals.totalDrugs ?? 0);
						$("#totalIssued").text(json.totals.totalIssued ?? 0);
						$("#totalReceived").text(json.totals.totalReceived ?? 0);
					}
					return json.data;
				},
			},
			pageLength: 10,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			scrollX: true,
			scrollCollapse: true,
			order: [[2, "desc"]],
			language: {
				processing:
					'<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
			},
			dom:
				"<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
				"<'row'<'col-12'tr>>" +
				"<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
			buttons: [
				{
					extend: "excelHtml5",
					className: "btn btn-success",
					title: "Issued Stock Room (Drugs and Medicines) Report",
				},
				{
					extend: "csvHtml5",
					className: "btn btn-primary",
					title: "Issued Stock Room (Drugs and Medicines) Report",
				},
				{
					extend: "print",
					className: "btn btn-secondary",
					title: "Issued Stock Room (Drugs and Medicines) Report",
				},
			],
			columns: [
				{
					data: "control_id",
				},
				{
					data: "order_date",
				},
				{
					data: "issued_date",
				},
				{
					data: "turnaround_dhms",
				},
				{
					data: "lot_number",
				},
				{
					data: "drug_description",
				},
				{
					data: "quantity_request",
				},
				{
					data: "quantity_issued",
				},
				{
					data: "quantity_received",
				},
				{
					data: "from_location",
				},
				{
					data: "Status",
				},
				{
					data: "Received",
				},
				{
					data: "issued_by",
				},
			],
			createdRow: function (row, data) {
				if (data.quantity_received === "Unreceived") {
					$("td:eq(8)", row).addClass("text-danger fw-bold");
				}

				if (data.Received === "Unserved") {
					$("td:eq(11)", row)
						.removeClass("bg-success bg-success-subtle text-success text-dark")
						.addClass("bg-danger-subtle text-danger fw-bold");
				} else {
					$("td:eq(11)", row)
						.removeClass("bg-danger text-white")
						.addClass("bg-success-subtle text-success fw-bold");
				}

				if (data.Status === "Served") {
					$("td:eq(10)", row)
						.removeClass("bg-danger text-danger")
						.addClass("bg-success-subtle text-success fw-bold");
				}

				if (data.from_location === "(ER) EMERGENCY") {
					$("td:eq(9)", row)
						.removeClass("bg-danger text-danger")
						.addClass("bg-danger text-white fw-bold border border-danger");
				} else if (data.from_location === "(CSR) CENTRAL SUPPLY") {
					$("td:eq(9)", row)
						.removeClass("bg-danger text-danger")
						.addClass(
							"bg-secondary text-white fw-bold border border-secondary",
						);
				} else if (data.from_location === "(PHARM) PHARMACY") {
					$("td:eq(9)", row)
						.removeClass("bg-danger text-danger")
						.addClass("bg-info-subtle text-dark fw-bold border border-info");
				} else if (data.from_location === "(OPD) OUTPATIENT") {
					$("td:eq(9)", row)
						.removeClass("bg-danger text-danger")
						.addClass("bg-primary text-white fw-bold border border-primary");
				} else {
					$("td:eq(9)", row)
						.removeClass("bg-danger text-danger")
						.addClass("bg-warning text-white fw-bold border border-warning");
				}

				if (data.quantity_issued > data.quantity_received) {
					$("td:eq(7)", row).addClass("bg-danger-subtle text-danger fw-bold");
					$("td:eq(6)", row).addClass("bg-danger-subtle text-danger fw-bold");
				}
			},
			rowCallback: function (row, data) {
				if (!data.turnaround_dhms) return;

				let text = data.turnaround_dhms.toLowerCase();
				let totalHours = 0;

				let dayMatch = text.match(/(\d+)\s*day/);
				if (dayMatch) totalHours += parseInt(dayMatch[1]) * 24;

				let timeMatch = text.match(/(\d+):(\d+)(?::(\d+))?/);
				if (timeMatch) {
					let h = parseInt(timeMatch[1]) || 0;
					let m = parseInt(timeMatch[2]) || 0;
					let s = parseInt(timeMatch[3]) || 0;
					totalHours += h + m / 60 + s / 3600;
				}

				if (totalHours > 8) {
					$("td:eq(3)", row)
						.removeClass("bg-success bg-success-subtle text-success text-dark")
						.addClass("bg-danger-subtle text-danger fw-bold");
				} else if (totalHours > 4) {
					$("td:eq(3)", row)
						.removeClass("bg-success bg-success-subtle text-success text-dark")
						.addClass("bg-warning-subtle fw-bold");
				}
			},
		});
	}
	// ✅ FILTER BUTTON

	$("#filterDMIssuedStock").click(function () {
		issuedDMSRTable.ajax.reload(); // ✅ FIXED
	});
	$("#resetDMIssuedStock").click(function () {
		$("#issuedStockStart").val(container.data("start"));
		$("#issuedStockEnd").val(container.data("end"));
		issuedDMSRTable.ajax.reload(); // ✅ FIXED
	});
	$(document).on("click", "#exportIssuedDMStockroom", function (e) {
		e.preventDefault();

		let startDate = $("#issuedStockStart").val() || "";
		let endDate = $("#issuedStockEnd").val() || "";

		/* GET CURRENT DATATABLE SEARCH */
		let search = issuedDMSRTable.search() || "";

		let order = issuedDMSRTable.order();

		let params = new URLSearchParams({
			startDate: startDate,
			endDate: endDate,
			search: search,
			"order[0][column]": order[0][0],
			"order[0][dir]": order[0][1],
		});

		let url =
			window.location.origin +
			"/modules/Stockroom/exportIssuedDMStockroom.php?" +
			params.toString();

		window.location.href = url;
	});
}

function initIssuedSuppliesStockTable() {
	var issuedSuppliesStockroomTable = null;
	let container = $("#issuedSuppliesStockFilter");

	if ($("#issuedSuppliesStockroomTable").length) {
		issuedSuppliesStockroomTable = $("#issuedSuppliesStockroomTable").DataTable(
			{
				processing: true,
				serverSide: true,
				responsive: true,
				autoWidth: true,
				ajax: {
					url: "Controllers/StockRoomController.php",
					type: "POST",
					data: function (d) {
						d.action = "issuedSupplies";
						d.startDate = $("#issuedStockSupplyStart").val() || null;
						d.endDate = $("#issuedStockSupplyEnd").val() || null;
					},
					dataSrc: function (json) {
						if (json.totals) {
							$("#totalSupplies").text(json.totals.totalSupplies ?? 0);
							$("#totalIssuedSupplies").text(
								json.totals.totalIssuedSupplies ?? 0,
							);
							$("#totalReceivedSupplies").text(
								json.totals.totalReceivedSupplies ?? 0,
							);
						}
						return json.data;
					},
				},
				pageLength: 10,
				lengthMenu: [
					[5, 10, 15, 50, 100, -1],
					[5, 10, 15, 50, 100, "All"],
				],
				scrollX: true,
				scrollCollapse: true,
				order: [[2, "desc"]],
				language: {
					processing:
						'<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
				},
				dom:
					"<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
					"<'row'<'col-12'tr>>" +
					"<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
				buttons: [
					{
						extend: "excelHtml5",
						className: "btn btn-success",
						title: "Issued Stock Room (Drugs and Medicines) Report",
					},
					{
						extend: "csvHtml5",
						className: "btn btn-primary",
						title: "Issued Stock Room (Drugs and Medicines) Report",
					},
					{
						extend: "print",
						className: "btn btn-secondary",
						title: "Issued Stock Room (Drugs and Medicines) Report",
					},
				],
				columns: [
					{
						data: "control_id",
					},
					{
						data: "order_date",
					},
					{
						data: "issued_date",
					},
					{
						data: "turnaround_dhms",
					},
					{
						data: "lot_number",
					},
					{
						data: "supply_name",
					},
					{
						data: "quantity_request",
					},
					{
						data: "quantity_issued",
					},
					{
						data: "quantity_received",
					},
					{
						data: "from_location",
					},
					{
						data: "Status",
					},
					{
						data: "Received",
					},
					{
						data: "issued_by",
					},
				],
				createdRow: function (row, data) {
					if (data.quantity_received === "Pending") {
						$("td:eq(8)", row).addClass("text-warning fw-bold");
					}

					if (data.Received === "Unserved") {
						$("td:eq(11)", row)
							.removeClass(
								"bg-success bg-success-subtle text-success text-dark",
							)
							.addClass("bg-danger-subtle text-danger fw-bold");
					} else {
						$("td:eq(11)", row)
							.removeClass("bg-danger text-white")
							.addClass("bg-success-subtle text-success fw-bold");
					}

					if (data.Status === "Served") {
						$("td:eq(10)", row)
							.removeClass("bg-danger text-danger")
							.addClass("bg-success-subtle text-success fw-bold");
					}

					if (data.from_location === "(ER) EMERGENCY") {
						$("td:eq(9)", row)
							.removeClass("bg-danger text-danger")
							.addClass("bg-danger text-white fw-bold");
					} else if (data.from_location === "(CSR) CENTRAL SUPPLY") {
						$("td:eq(9)", row)
							.removeClass("bg-danger text-danger")
							.addClass("bg-secondary text-white fw-bold");
					} else if (data.from_location === "(PHARM) PHARMACY") {
						$("td:eq(9)", row)
							.removeClass("bg-danger text-danger")
							.addClass("bg-info text-white fw-bold");
					} else if (data.from_location === "(OPD) OUTPATIENT") {
						$("td:eq(9)", row)
							.removeClass("bg-danger text-danger")
							.addClass("bg-primary text-white fw-bold");
					} else {
						$("td:eq(9)", row)
							.removeClass("bg-danger text-danger")
							.addClass("bg-warning text-white fw-bold");
					}

					if (data.quantity_issued > data.quantity_received) {
						$("td:eq(7)", row).addClass("bg-danger-subtle text-danger fw-bold");
						$("td:eq(6)", row).addClass("bg-danger-subtle text-danger fw-bold");
					}
				},
				rowCallback: function (row, data) {
					if (!data.turnaround_dhms) return;

					let text = data.turnaround_dhms.toLowerCase();
					let totalHours = 0;

					let dayMatch = text.match(/(\d+)\s*day/);
					if (dayMatch) totalHours += parseInt(dayMatch[1]) * 24;

					let timeMatch = text.match(/(\d+):(\d+)(?::(\d+))?/);
					if (timeMatch) {
						let h = parseInt(timeMatch[1]) || 0;
						let m = parseInt(timeMatch[2]) || 0;
						let s = parseInt(timeMatch[3]) || 0;
						totalHours += h + m / 60 + s / 3600;
					}

					if (totalHours > 8) {
						$("td:eq(3)", row)
							.removeClass(
								"bg-success bg-success-subtle text-success text-dark",
							)
							.addClass("bg-danger-subtle text-danger fw-bold");
					} else if (totalHours > 4) {
						$("td:eq(3)", row)
							.removeClass(
								"bg-success bg-success-subtle text-success text-dark",
							)
							.addClass("bg-warning-subtle fw-bold");
					}
				},
			},
		);
	}
	// ✅ FILTER BUTTON
	$("#filterSuppliesIssuedStock").click(function () {
		issuedSuppliesStockroomTable.ajax.reload(); // ✅ FIXED
	});
	$("#resetSuppliesIssuedStock").click(function () {
		$("#issuedStockSupplyStart").val(container.data("start"));
		$("#issuedStockSupplyEnd").val(container.data("end"));
		issuedSuppliesStockroomTable.ajax.reload(); // ✅ FIXED
	});
	console.log(window.location.origin);
	$(document).on("click", "#exportIssuedSupplyStockroom", function (e) {
		e.preventDefault();

		let startDate = $("#issuedStockSupplyStart").val() || "";
		let endDate = $("#issuedStockSupplyEnd").val() || "";

		/* GET CURRENT DATATABLE SEARCH */
		let search = issuedSuppliesStockroomTable.search() || "";

		let order = issuedSuppliesStockroomTable.order();

		let params = new URLSearchParams({
			startDate: startDate,
			endDate: endDate,
			search: search,
			"order[0][column]": order[0][0],
			"order[0][dir]": order[0][1],
		});

		let url =
			window.location.origin +
			"/modules/Stockroom/exportIssuedSuppliesStockroom.php?" +
			params.toString();

		window.location.href = url;
	});
}
