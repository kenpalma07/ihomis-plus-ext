$(document).ready(function () {
	initIssuedDMTable();
});

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
					$("td:eq(2)", row).addClass("text-danger fw-bold");
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
