document.addEventListener("DOMContentLoaded", () => {
	initZBBDashboard();
	initZBBPatientTable();
});

function initZBBDashboard() {
	const filterBtn = document.getElementById("filterBtn");
	const startEl = document.getElementById("zbb_start_date");
	const endEl = document.getElementById("zbb_end_date");

	loadZBBDashboard();

	if (filterBtn) {
		filterBtn.addEventListener("click", loadZBBDashboard);
	}

	[startEl, endEl].forEach((input) => {
		if (!input) return;

		input.addEventListener("keypress", (e) => {
			if (e.key === "Enter") {
				e.preventDefault();
				loadZBBDashboard();
			}
		});
	});
}

async function loadZBBDashboard() {
	const start = document.getElementById("zbb_start_date")?.value;
	const end = document.getElementById("zbb_end_date")?.value;
	const btn = document.getElementById("filterBtn");

	if (!start || !end) return;

	try {
		if (btn) {
			btn.disabled = true;
			btn.innerHTML = "Loading...";
		}

		const params = new URLSearchParams({
			action: "loadZBBDashboard",
			start_date: start,
			end_date: end,
		});

		const res = await fetch(
			`../../Controllers/ZBBController.php?${params.toString()}`,
		);

		const result = await res.json();

		if (result.status === "success") {
			renderZBBDashboard(result.data);
		} else {
			console.error(result.message);
		}
	} catch (err) {
		console.error("Error loading dashboard:", err);
	} finally {
		if (btn) {
			btn.disabled = false;
			btn.innerHTML = "Filter";
		}
	}
}

function renderZBBDashboard(data) {
	setValue("total_zbb_patients", data.total_zbb_patients);
	setValue("total_actual_charges", data.total_actual_charges);
	setValue("total_philhealth_charges", data.total_philhealth_charges);
	setValue("total_balance", data.total_balance);
}

function setValue(id, value) {
	const el = document.getElementById(id);

	if (!el) return;

	if (
		id === "total_actual_charges" ||
		id === "total_philhealth_charges" ||
		id === "total_balance"
	) {
		el.textContent = Number(value).toLocaleString(undefined, {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		});
		return;
	}

	el.textContent = Number(value).toLocaleString();
}

function initZBBPatientTable() {
	var zbbPatientTable = null;
	let container = $("#filterBtn");

	if ($("#zbbPatientTable").length) {
		zbbPatientTable = $("#zbbPatientTable").DataTable({
			processing: true,
			serverSide: true,
			ajax: {
				url: "../../Controllers/ZBBController.php",
				type: "POST",
				data: function (d) {
					d.action = "loadZBBPatientTable";
					d.startDate = $("#zbb_start_date").val() || null;
					d.endDate = $("#zbb_end_date").val() || null;
				},
			},
			pageLength: 10,
			lengthMenu: [
				[5, 10, 15, 50, 100, -1],
				[5, 10, 15, 50, 100, "All"],
			],
			order: [[5, "desc"]],
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
					data: "hpercode",
				},
				{
					data: "patient",
				},
				{
					data: "total_actual_charges",
					render: $.fn.dataTable.render.number(",", ".", 2, ""),
				},
				{
					data: "total_philhealth_charges",
					render: $.fn.dataTable.render.number(",", ".", 2, ""),
				},
				{
					data: "total_balance",
					render: $.fn.dataTable.render.number(",", ".", 2, ""),
				},
				{
					data: "entry_date",
				},
			],
		});

		$("#filterBtn").click(function () {
			zbbPatientTable.ajax.reload();
		});
	}
}
