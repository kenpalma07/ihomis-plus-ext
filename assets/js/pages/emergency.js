$(document).ready(function () {
	initERTurnAroundTimeTable();
});

let erTable = null;

function initERTurnAroundTimeTable() {
	if (!$("#erTable").length) return;

	erTable = $("#erTable").DataTable({
		serverSide: true,
		processing: true,

		ajax: {
			url: "Controllers/EmergencyController.php",
			type: "POST",
			data: function (d) {
				d.action = "erTurnaroundTime";

				d.regStart = $("#regStart").val() || "";
				d.regEnd = $("#regEnd").val() || "";
				d.disStart = $("#disStart").val() || "";
				d.disEnd = $("#disEnd").val() || "";
			},
		},

		columns: [
			{ data: "hpercode" },
			{ data: "patient" },
			{ data: "birthdate" },
			{ data: "registration_date" },
			{ data: "discharged_date" },
			{ data: "turnaround_dhms" },
			{ data: "discharge_by" },
		],

		pageLength: 10,
		lengthMenu: [
			[5, 10, 15, 50, 100, -1],
			[5, 10, 15, 50, 100, "All"],
		],
		scrollX: true,
		order: [[3, "desc"]],
		pagingType: "full_numbers",
		dom:
			"<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
			"<'row'<'col-12'tr>>" +
			"<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",

		buttons: [
			{
				extend: "excelHtml5",
				text: "Excel",
				className: "btn btn-success btn-sm",
				title: "Emergency Turn Around Time Report",
				exportOptions: { columns: ":visible" },
			},
			{
				extend: "csvHtml5",
				text: "CSV",
				className: "btn btn-primary btn-sm",
				title: "Emergency Turn Around Time Report",
			},
			{
				extend: "print",
				text: "Print",
				className: "btn btn-secondary btn-sm",
				title: "Emergency Turn Around Time Report",
			},
		],

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
				$(row).addClass("table-danger");
			} else if (totalHours > 4) {
				$(row).addClass("table-warning");
			}
		},
	});

	// FILTER
	$("#filterBtn")
		.off("click")
		.on("click", function () {
			erTable.ajax.reload();
		});

	// RESET
	$("#resetBtn")
		.off("click")
		.on("click", function () {
			$("#regStart, #regEnd, #disStart, #disEnd").val("");
			erTable.ajax.reload();
		});

	// EXPORT
	$("#exportEmergencyBtn")
		.off("click")
		.on("click", function (e) {
			e.preventDefault();

			let searchValue = $("#erTable_filter input").val() || "";
			let regStart = $("#regStart").val() || "";
			let regEnd = $("#regEnd").val() || "";
			let disStart = $("#disStart").val() || "";
			let disEnd = $("#disEnd").val() || "";

			let url =
				"modules/Emergency/export_erTurnAroundTime.php?" +
				"regStart=" +
				encodeURIComponent($("#regStart").val()) +
				"&regEnd=" +
				encodeURIComponent($("#regEnd").val()) +
				"&disStart=" +
				encodeURIComponent($("#disStart").val()) +
				"&disEnd=" +
				encodeURIComponent($("#disEnd").val()) +
				"&search=" +
				encodeURIComponent($("#erTable_filter input").val() || "");

			window.open(url, "_blank");
		});
}
