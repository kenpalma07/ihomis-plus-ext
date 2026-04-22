$(document).ready(function () {
	initStockRoomInventory();
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
