document.addEventListener("DOMContentLoaded", () => {
	initDashboard();
});

function initDashboard() {
	const filterBtn = document.getElementById("filterBtn");
	const startEl = document.getElementById("start_date");
	const endEl = document.getElementById("end_date");

	// Initial load
	loadDashboard();

	// Button click
	if (filterBtn) {
		filterBtn.addEventListener("click", () => {
			loadDashboard();
		});
	}

	// Optional: press ENTER to filter
	[startEl, endEl].forEach((input) => {
		if (input) {
			input.addEventListener("keypress", (e) => {
				if (e.key === "Enter") {
					e.preventDefault();
					loadDashboard();
				}
			});
		}
	});
}

async function loadDashboard() {
	const start = document.getElementById("start_date")?.value;
	const end = document.getElementById("end_date")?.value;
	const btn = document.getElementById("filterBtn");

	if (!start || !end) {
		console.warn("Start or End date missing");
		return;
	}

	try {
		// 🔄 UI loading state
		if (btn) {
			btn.disabled = true;
			btn.innerHTML = "Loading...";
		}

		const res = await fetch(
			`../../Controllers/DashboardController.php?action=loadDashboard&start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}`,
		);

		const result = await res.json();

		if (result.status === "success") {
			renderDashboard(result.data);
		} else {
			console.error("Server error:", result.message);
		}
	} catch (err) {
		console.error("Fetch error:", err);
	} finally {
		// 🔁 Restore button
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
