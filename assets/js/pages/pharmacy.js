$(document).ready(function () {
	let pullOutId = null;
	initInventoryTable();
	initPulledOutDMTable();
	initIssuedDMTable();
});

function initInventoryTable() {
	var inventoryTable = null;
	let container = $("#dmInventoryFilters");

	if ($("#inventoryTable").length) {
		inventoryTable = $("#inventoryTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "Controllers/PharmacyControllers.php",
				type: "POST",
				data: function (d) {
					d.action = "inventory";
					d.startDate = $("#inventoryStart").val() || null;
					d.endDate = $("#inventoryEnd").val() || null;
				},
				dataSrc: function (json) {
					// console.log("Inventory JSON:", json);
					if (json.totals) {
						$("#totalStock").text(json.totals.totalStock ?? 0);
						$("#totalValue").text(
							parseFloat(json.totals.totalValue ?? 0).toLocaleString(
								undefined,
								{
									minimumFractionDigits: 2,
								},
							),
						);
						$("#expiredStock").text(json.totals.expiredStock ?? 0);
						$("#expiredValue").text(
							parseFloat(json.totals.expiredValue ?? 0).toLocaleString(
								undefined,
								{
									minimumFractionDigits: 2,
								},
							),
						);
					}
					return json.data || [];
				},
			},
			pageLength: 10,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			order: [[12, "desc"]],
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
					title: "Inventory Report",
					exportOptions: {
						columns: ":visible",
					},
				},
				{
					extend: "csvHtml5",
					text: "CSV",
					className: "btn btn-primary btn-sm",
					title: "Inventory Report",
				},
				{
					extend: "print",
					text: "Print",
					className: "btn btn-secondary btn-sm",
					title: "Inventory Report",
				},
			],
			columns: [
				{
					data: "lot_number",
				},
				{
					data: "drug_description",
				},
				{
					data: "stock_balance",
				},
				{
					data: "beg_balance",
				},
				{
					data: "total_dispensed",
				},
				{
					data: "total_returned",
				},
				{
					data: "net_dispensed",
				},
				{
					data: "adjustment_addition",
				},
				{
					data: "adjustment_deduction",
				},
				{
					data: "calculated_expected_stock",
				},
				{
					data: "selling_price",
				},
				{
					data: "unit_price",
				},
				{
					data: "entry_date",
				},
				{
					data: "expiration_date",
				},
				{
					data: "account_type",
				},
				{
					data: "status",
				},
				{
					data: "id",
					render: function (data, type, row) {
						const isDisabled =
							row.status === "PULLOUT" || row.status === "EXPIRED/PULLOUT";
						return `
                            <a href="#"
                            class="pulloutDrugBtn ${isDisabled ? "disabled-link text-muted" : ""}"
                            ${isDisabled ? 'style="pointer-events:none;"' : ""}
                            data-id="${data}"
                            data-drug="${row.drug_description}">
                            Pull Out
                            </a>
                        `;
					},
				},
			],
			createdRow: function (row, data) {
				if (data.status === "EXPIRED") {
					$(row).addClass("table-danger");
					$("td:eq(15)", row).addClass("text-danger fw-bold");
				} else if (data.status === "NEAR EXPIRE") {
					$(row).addClass("table-warning");
					$("td:eq(15)", row).addClass("text-warning fw-bold");
				} else {
					$("td:eq(15)", row).addClass("text-success fw-bold");
				}

				if (data.stock_balance === "No Stock Balance") {
					$("td:eq(2)", row).addClass("text-danger fw-bold");
				}

				if (data.selling_price === "No Selling Price") {
					$("td:eq(10)", row).addClass("text-danger fw-bold");
				}

				if (data.unit_price === "No Unit Price") {
					$("td:eq(11)", row).addClass("text-danger fw-bold");
				}
			},
		});

		// Filter & Reset
		$("#filterInventory").click(function () {
			inventoryTable.ajax.reload();
		});
		$("#resetInventory").click(function () {
			$("#inventoryStart").val(container.data("start"));
			$("#inventoryEnd").val(container.data("end"));
			inventoryTable.ajax.reload();
		});

		$("#exportInventoryExcel").click(function (e) {
			e.preventDefault();

			let startDate = $("#inventoryStart").val();
			let endDate = $("#inventoryEnd").val();
			let search = $("#inventoryTable_filter input").val();

			let url =
				"modules/Pharmacy/export_inventory.php?startDate=" +
				encodeURIComponent(startDate) +
				"&endDate=" +
				encodeURIComponent(endDate) +
				"&search=" +
				encodeURIComponent(search);

			window.location.href = url;
		});

		$(document).on("click", ".pulloutDrugBtn", function (e) {
			if ($(this).hasClass("disabled-link")) {
				e.preventDefault(); // block the click
				return;
			}

			pullOutId = $(this).data("id");
			$("#modalDrug").text($(this).data("drug"));
			$("#pulledOutRemarks").val(""); // ✅ reset
			$("#pullOutModal").modal("show");
		});

		// Confirm Pull Out
		$("#confirmPullOut").click(function (e) {
			e.preventDefault(); // prevent form submission

			if (pullOutId === null || pullOutId === undefined) {
				// console.log("DEBUG ID:", pullOutId);
				return alert("Invalid drug ID.");
			}
			let remarks = $("#pulledOutRemarks").val().trim();
			if (!remarks) {
				alert("Please enter a reason.");
				return;
			}

			$.ajax({
				url: "Controllers/PharmacyControllers.php",
				type: "POST",
				data: {
					action: "pullout",
					id: pullOutId,
					remarks: remarks,
				},
				success: function (response) {
					let res;
					try {
						res = JSON.parse(response);
					} catch (e) {
						return alert("Invalid response from server");
					}

					if (res.status === "success") {
						alert("Successfully pulled out item.");
						$("#pullOutModal").modal("hide");
						$("#pulledOutRemarks").val("");
						pullOutId = null;
						inventoryTable.ajax.reload(null, false);
					} else {
						alert(res.message || "Failed to pull out the item.");
					}
				},
				error: function () {
					alert("AJAX error. Could not pull out item.");
				},
			});
		});
	}
}

function initPulledOutDMTable() {
	var table = null;
	let container = $("#dmPulledOutTable");

	if ($("#pulledoutDMTable").length) {
		table = $("#pulledoutDMTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "Controllers/PharmacyController.php",
				type: "POST",
				data: function (d) {
					d.action = "pulledOut";
					d.startDate = $("#pulledoutStart").val() || null;
					d.endDate = $("#pulledoutEnd").val() || null;
				},
				dataSrc: function (json) {
					if (json.totals) {
						$("#totalStock").text(json.totals.totalStock ?? 0);
						$("#totalValue").text(
							parseFloat(json.totals.totalValue ?? 0).toLocaleString(
								undefined,
								{
									minimumFractionDigits: 2,
								},
							),
						);
						$("#expiredStock").text(json.totals.expiredStock ?? 0);
						$("#expiredValue").text(
							parseFloat(json.totals.expiredValue ?? 0).toLocaleString(
								undefined,
								{
									minimumFractionDigits: 2,
								},
							),
						);
					}
					return json.data || [];
				},
			},
			pageLength: 10,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			order: [[0, "asc"]],
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
					title: "Pulled Out Drugs and Medicine Report",
					exportOptions: {
						columns: ":visible",
					},
				},
				{
					extend: "csvHtml5",
					text: "CSV",
					className: "btn btn-primary btn-sm",
					title: "Pulled Out Drugs and Medicine Report",
				},
				{
					extend: "print",
					text: "Print",
					className: "btn btn-secondary btn-sm",
					title: "Pulled Out Drugs and Medicine Report",
				},
			],
			columns: [
				{
					data: "lot_number",
				},
				{
					data: "drug_description",
				},
				{
					data: "stock_balance",
				},
				{
					data: "beg_balance",
				},
				{
					data: "total_dispensed",
				},
				{
					data: "selling_price",
				},
				{
					data: "entry_date",
				},
				{
					data: "expiration_date",
				},
				{
					data: "account_type",
				},
				{
					data: "status",
				},
				{
					data: "remarks",
				},
				{
					data: "id",
					render: function (data, type, row) {
						return `
                            <a href="#"
                                class="undoPulloutBtn"
                                data-id="${data}"
                                data-drug="${row.drug_description}">
                                Undo Pull Out
                            </a>
                        `;
					},
				},
			],
			createdRow: function (row, data) {
				if (data.status === "EXPIRED/PULLOUT") {
					$(row).addClass("table-danger");
				} else if (data.status === "NEAR EXPIRE") {
					$(row).addClass("table-warning");
				}
			},
		});

		$("#filterPulledOut").click(function () {
			table.ajax.reload();
		});
		$("#resetPulledOut").click(function () {
			$("#pulledoutStart").val(container.data("start"));
			$("#pulledoutEnd").val(container.data("end"));
			table.ajax.reload();
		});
		$("#exportPulledOutExcel").click(function (e) {
			e.preventDefault();

			let startDate = $("#pulledoutStart").val();
			let endDate = $("#pulledoutEnd").val();
			let search = $(".dataTables_filter input").val(); // get global search

			let url =
				"modules/Pharmacy/export_pulledout.php?startDate=" +
				encodeURIComponent(startDate) +
				"&endDate=" +
				encodeURIComponent(endDate) +
				"&search=" +
				encodeURIComponent(search);

			window.location.href = url;
		});
		// Pull Out modal
		$(document).on("click", ".undoPulloutBtn", function (e) {
			if ($(this).hasClass("disabled-link")) {
				e.preventDefault();
				return;
			}

			pullOutId = $(this).data("id");
			$("#modalDrug").text($(this).data("drug"));
			$("#undoRemarks").val("");
			$("#undoPullOutModal").modal("show");
		});

		// Confirm Pull Out
		$("#confirmUndoPullOut").click(function (e) {
			e.preventDefault(); // prevent form submission

			if (!pullOutId) return alert("Invalid drug ID.");

			let remarks = $("#undoRemarks").val().trim();
			if (!remarks) {
				alert("Please enter a reason.");
				return;
			}

			$.ajax({
				url: "Controllers/PharmacyController.php",
				type: "POST",
				data: {
					action: "undoPullOut",
					id: pullOutId,
					remarks: remarks,
				},
				success: function (response) {
					let res;
					try {
						res = JSON.parse(response);
					} catch (e) {
						return alert("Invalid response from server");
					}

					if (res.status === "success") {
						alert("Successfully restored item.");
						$("#undoPullOutModal").modal("hide");
						$("#undoRemarks").val("");
						table.ajax.reload(null, false);
					} else {
						alert(res.message || "Failed to undo pull out the item.");
					}
				},
				error: function () {
					alert("AJAX error. Could not pull out item.");
				},
			});
		});
	}
}

// ----------------------------- ISSUED TABLE -----------------------------

function initIssuedDMTable() {
	var issuedTable = null;
	let container = $("#issuedFilters");

	if ($("#issuedDMTable").length) {
		issuedTable = $("#issuedDMTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "Controllers/PharmacyController.php",
				type: "POST",
				data: function (d) {
					d.action = "issued";
					d.startDate = $("#issuedStart").val() || null;
					d.endDate = $("#issuedEnd").val() || null;
				},
				dataSrc: function (json) {
					if (json.totals) {
						$("#totalDrugs").html(json.totals.totalDrugs ?? 0);
						$("#totalIssued").html(json.totals.totalIssued ?? 0);
						$("#totalReturned").html(json.totals.totalReturned ?? 0);
					}
					return json.data || [];
				},
			},
			pageLength: 10,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			scrollX: true,
			order: [[0, "desc"]],
			language: {
				processing: "Loading issued records...",
			},
			dom:
				"<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
				"<'row'<'col-12'tr>>" +
				"<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
			buttons: [
				{
					extend: "excelHtml5",
					className: "btn btn-success",
					title: "Issued Drugs and Medicine",
				},
				{
					extend: "csvHtml5",
					className: "btn btn-primary",
				},
				{
					extend: "print",
					className: "btn btn-secondary",
					title: "Issued Drugs and Medicine",
				},
			],
			columns: [
				{
					data: "order_date",
				},
				{
					data: "date_issued",
				},
				{
					data: "turnaround_time",
				},
				{
					data: "lot_number",
				},
				{
					data: "drug_description",
				},
				{
					data: "hpercode",
				},
				{
					data: "patient",
				},
				{
					data: "quantity_issued",
				},
				{
					data: "quantity_returned",
				},
				{
					data: "order_type",
				},
				{
					data: "account_type",
				},
				{
					data: "issued_by",
				},
			],
			createdRow: function (row, data) {
				if (data.lot_number === "No Lot Number") {
					$("td:eq(3)", row).addClass("text-danger fw-bold");
				}
			},
		});
	}
	$("#filterIssued").click(function () {
		if (issuedTable) issuedTable.ajax.reload();
	});
	$("#resetIssued").click(function () {
		$("#issuedStart").val(container.data("start"));
		$("#issuedEnd").val(container.data("end"));
		if (issuedTable) issuedTable.ajax.reload();
	});

	$(document).on("click", "#exportIssuedExcel", function (e) {
		e.preventDefault();

		let startDate = $("#issuedStart").val();
		let endDate = $("#issuedEnd").val();

		let params = new URLSearchParams({
			startDate: startDate || "",
			endDate: endDate || "",
		});

		let url =
			window.location.origin +
			"/modules/Pharmacy/export_issued_drugs.php?" +
			params.toString();

		window.location.href = url;
	});
}
