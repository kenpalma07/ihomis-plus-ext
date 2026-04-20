$(document).ready(function () {
	initCSInventoryTable();
	initCSPulledOutTable();
	initCSIssuedTable();
});

function initCSInventoryTable() {
	var inventoryCSTable = null;
	let container = $("#csInventoryFilters");

	if ($("#inventorySuppliesTable").length) {
		inventoryCSTable = $("#inventorySuppliesTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "Controllers/CentralSupplyController.php",
				type: "POST",
				data: function (d) {
					d.action = "supplyInventory";
					d.startDate = $("#inventorySupplyStart").val() || null;
					d.endDate = $("#inventorySupplyEnd").val() || null;
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
					return json.data;
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
					title: "Central Supply Inventory Report",
					exportOptions: {
						columns: ":visible",
					},
				},
				{
					extend: "csvHtml5",
					text: "CSV",
					className: "btn btn-primary btn-sm",
					title: "Central Supply Inventory Report",
				},
				{
					extend: "print",
					text: "Print",
					className: "btn btn-secondary btn-sm",
					title: "Central Supply Inventory Report",
				},
			],
			columns: [
				{
					data: "lot_number",
				},
				{
					data: "supply_name",
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
					data: "expiry_date",
				},
				{
					data: "date_modified", // hidden number 8
				},
				{
					data: "account_type",
				},
				{
					data: "status",
				},
				{
					data: "cost_center", // hidden number 11
				},
				{
					data: "cs_remarks",
				},
				{
					data: null,
					render: function (data, type, row) {
						const isDisabled =
							row.status === "PULLOUT" || row.status === "EXPIRED/PULLOUT";

						return `
                            <a href="#" class="pullOutCSModal ${isDisabled ? "disabled-link text-muted" : ""}" ${isDisabled ? 'style="pointer-events:none;"' : ""}
                                data-cl2dteas="${row.entry_date}"
                                data-itemcode="${row.itemcode}"
                                data-supply="${row.supply_name}">
                                Pull Out
                            </a>
                        `;
					},
				},
			],
			columnDefs: [
				{
					targets: 8,
					visible: false,
				},
				{
					targets: 11,
					visible: false,
				},
			],
			createdRow: function (row, data) {
				if (data.status === "EXPIRED") {
					$(row).addClass("table-danger");
					$("td:eq(9)", row).addClass("text-danger fw-bold");
				} else if (data.status === "NEAR EXPIRE") {
					$(row).addClass("table-warning");
					$("td:eq(9)", row).addClass("text-warning fw-bold");
				} else {
					$("td:eq(9)", row).addClass("text-success fw-bold");
				}

				if (data.stock_balance === "No Stock Balance") {
					$("td:eq(2)", row).addClass("text-danger fw-bold");
				}

				if (data.selling_price === "No Selling Price") {
					$("td:eq(5)", row).addClass("text-danger fw-bold");
				}
			},
		});

		// Filter & Reset
		$("#filterSupplyInventory").click(function () {
			inventoryCSTable.ajax.reload();
		});

		$("#resetSupplyInventory").click(function () {
			$("#inventorySupplyStart").val(container.data("start"));
			$("#inventorySupplyEnd").val(container.data("end"));
			inventoryCSTable.ajax.reload();
		});

		$("#exportSupplyInventoryExcel").click(function (e) {
			e.preventDefault();

			let startDate = $("#inventorySupplyStart").val();
			let endDate = $("#inventorySupplyEnd").val();
			let search = $("#inventorySuppliesTable_filter input").val();

			let url =
				"modules/CentralSupply/export_csInventory.php?startDate=" +
				encodeURIComponent(startDate) +
				"&endDate=" +
				encodeURIComponent(endDate) +
				"&search=" +
				encodeURIComponent(search);

			window.location.href = url;
		});

		let pullOutData = {};
		$("#inventorySuppliesTable").on("click", ".pullOutCSModal", function (e) {
			if ($(this).hasClass("disabled-link")) {
				e.preventDefault();
				return;
			}

			pullOutData = {
				cl2dteas: $(this).data("cl2dteas"),
				itemcode: $(this).data("itemcode"),
			};

			$("#modalDrug").text($(this).data("supply"));
			$("#pulledOutCSRemarks").val("");
			$("#pullOutCSModal").modal("show");
		});

		$("#confirmCSPullOut").click(function (e) {
			e.preventDefault();

			if (!pullOutData.cl2dteas || !pullOutData.itemcode) {
				alert("Invalid item reference.");
				return;
			}

			let remarks = $("#pulledOutCSRemarks").val().trim();
			if (!remarks) {
				alert("Please enter a reason.");
				return;
			}

			$.ajax({
				url: "Controllers/CentralSupplyController.php",
				type: "POST",
				data: {
					action: "pullout",
					cl2dteas: pullOutData.cl2dteas,
					itemcode: pullOutData.itemcode,
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
						$("#pullOutCSModal").modal("hide");
						$("#pulledOutCSRemarks").val("");
						pullOutData = {}; // reset
						inventoryCSTable.ajax.reload(null, false);
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
