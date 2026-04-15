$(document).ready(function () {
	initAdmissionLogTable();
	initViewAdmissionLogTable();
});

function initAdmissionLogTable() {
	var admLogTable;

	if ($("#admLogTable").length) {
		admLogTable = $("#admLogTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "Controllers/MedicalRecordsController.php",
				type: "POST",
				data: function (d) {
					d.action = "admissionLog";
				},
			},
			pageLength: 15,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			scrollX: true,
			order: [
				[3, "desc"], // admission_count
			],
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
					title: "Admission Log",
				},
				{
					extend: "csvHtml5",
					className: "btn btn-primary",
				},
				{
					extend: "print",
					className: "btn btn-secondary",
				},
			],
			columns: [
				{
					data: "hpercode",
				},
				{
					data: "patient",
				},
				{
					data: "birthdate",
				},
				{
					data: "admission_count",
				},
				{
					data: "hpercode",
					render: function (data) {
						return `<a href="index.php?page=viewAdmissionLog&hpercode=${data}">
                                    View History
                                </a>`;
					},
				},
			],
		});
	}
}

function initViewAdmissionLogTable() {
	if ($("#viewAdmissionLogTable").length) {
		$("#viewAdmissionLogTable").DataTable({
			pageLength: 15,
			lengthMenu: [
				[5, 10, 15, -1],
				[5, 10, 15, "All"],
			],
			order: [[1, "desc"]], // fixed
			scrollX: true,
			dom:
				"<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
				"<'row'<'col-12'tr>>" +
				"<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
			buttons: [
				{
					extend: "excelHtml5",
					className: "btn btn-success",
				},
				{
					extend: "csvHtml5",
					className: "btn btn-primary",
				},
				{
					extend: "print",
					className: "btn btn-secondary",
				},
			],
		});
	}
}
