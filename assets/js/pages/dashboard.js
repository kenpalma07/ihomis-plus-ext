document.addEventListener("DOMContentLoaded", () => {
	initDashboard();
});

function initDashboard() {
	const filterBtn = document.getElementById("filterBtn");
	const startEl = document.getElementById("start_date");
	const endEl = document.getElementById("end_date");

	loadDashboard();

	if (filterBtn) {
		filterBtn.addEventListener("click", loadDashboard);
	}

	[startEl, endEl].forEach((input) => {
		if (!input) return;

		input.addEventListener("keypress", (e) => {
			if (e.key === "Enter") {
				e.preventDefault();
				loadDashboard();
			}
		});
	});
}

async function loadDashboard() {
	const start = document.getElementById("start_date")?.value;
	const end = document.getElementById("end_date")?.value;
	const btn = document.getElementById("filterBtn");

	if (!start || !end) return;

	try {
		if (btn) {
			btn.disabled = true;
			btn.innerHTML = "Loading...";
		}

		const params = new URLSearchParams({
			action: "loadDashboard",
			start_date: start,
			end_date: end,
		});

		const res = await fetch(
			`../../Controllers/DashboardController.php?${params.toString()}`,
		);

		const text = await res.text();
		// console.log(text);

		const result = JSON.parse(text);

		if (result.status === "success") {
			renderDashboard(result.data);
		} else {
			console.error(result.message);
		}
	} catch (err) {
		console.error(err);
	} finally {
		if (btn) {
			btn.disabled = false;
			btn.innerHTML = "Filter";
		}
	}
}

function renderDashboard(data) {
	setValue("total_admissions", data.total_admissions);
	setValue("current_inpatients", data.current_inpatients);
	setValue("new_patients", data.new_patients);
	setValue("old_patients", data.old_patients);
	setValue("readmitted_patients", data.readmitted_patients);
	setValue("total_discharges", data.total_discharges);
	setValue("total_deaths", data.total_deaths);
	setValue("total_er_visits", data.total_er_visits);
	setValue("total_er_discharges", data.total_er_discharges);
	setValue("current_er_patients", data.current_er_patients);
	setValue("total_er_deaths", data.total_er_deaths);
	setValue("er_new_patients", data.er_new_patients);
	setValue("er_old_patients", data.er_old_patients);
	setValue("er_readmitted_patients", data.er_readmitted_patients);
	setValue("er_readmission_rate", data.er_readmission_rate + "%");
	setValue("total_opd_visits", data.total_opd_visits);
	setValue("current_opd_patients", data.current_opd_patients);
	setValue("total_opd_discharges", data.total_opd_discharges);
	setValue("new_opd_patients", data.new_opd_patients);
	setValue("old_opd_patients", data.old_opd_patients);
	setValue("readmitted_opd_patients", data.readmitted_opd_patients);
	setValue("total_opd_deaths", data.total_opd_deaths);
	setValue("readmission_opd_rate", data.readmission_opd_rate + "%");

	// Special handling for percentage
	const rate = data.readmission_rate ?? 0;
	setValue("readmission_rate", rate + "%");
}

function setValue(id, value) {
	const el = document.getElementById(id);

	if (!el) return;

	// Format numbers properly
	if (!isNaN(value) && value !== null && value !== "") {
		el.textContent = Number(value).toLocaleString();
	} else {
		el.textContent = value;
	}
}

let viewPatient = null;

$(document).on("click", ".dashboard-card", function () {
	const metric = $(this).data("metric");
	const type = $(this).data("type");

	$("#patientModal").modal("show");

	loadPatientTable(metric, type);
});

function loadPatientTable(metric, type) {
	const start = $("#start_date").val();
	const end = $("#end_date").val();

	// wait modal to fully render
	setTimeout(() => {
		if ($.fn.DataTable.isDataTable("#viewPatient")) {
			$("#viewPatient").DataTable().destroy();
		}

		viewPatient = $("#viewPatient").DataTable({
			processing: true,
			serverSide: true,
			autoWidth: false,
			ajax: {
				url: "Controllers/DashboardController.php",
				type: "POST",
				data: function (d) {
					d.action = "getPatientsByMetric";
					d.metric = metric;
					d.type = type;
					d.start_date = start;
					d.end_date = end;
				},
			},
			pageLength: 10,
			order: [[2, "desc"]],
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
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
					title: "Patient List",
					exportOptions: {
						columns: ":visible",
					},
				},
				{
					extend: "csvHtml5",
					text: "CSV",
					className: "btn btn-primary btn-sm",
					title: "Patient List",
				},
				{
					extend: "print",
					text: "Print",
					className: "btn btn-secondary btn-sm",
					title: "Patient List",
				},
			],
			columns: [
				{ data: "hpercode" },
				{ data: "patient" },
				{ data: "date_registered" },
				{ data: "date_registered2", visible: false },
			],
		});
	}, 150);
}
