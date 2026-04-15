$(document).ready(function () {
  initERTurnAroundTimeTable();
});

function initERTurnAroundTimeTable() {
  var erTable;
  // DATA TABLE
  if ($("#erTable").length) {
    erTable = $("#erTable").DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: "Controllers/EmergencyController.php",
        data: function (d) {
          d.regStart = $("#regStart").val();
          d.regEnd = $("#regEnd").val();
          d.disStart = $("#disStart").val();
          d.disEnd = $("#disEnd").val();
        },
      },
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
      ],
      language: {
        emptyTable: "No records found",
        processing: "Loading...",
      },
      pageLength: 10,
      scrollX: true,

      rowCallback: function (row, data) {
        if (!data[5]) return;

        let text = data[5].toLowerCase();
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
    $("#filterBtn").click(function () {
      erTable.ajax.reload();
    });

    // RESET
    $("#resetBtn").click(function () {
      $("#regStart, #regEnd, #disStart, #disEnd").val("");
      erTable.ajax.reload();
    });

    // EXPORT
    $("#exportEmergencyBtn").click(function (e) {
      e.preventDefault();

      let url =
        "modules/Emergency/export_EmergencyTurnAroundTime.php?" +
        "regStart=" +
        encodeURIComponent($("#regStart").val()) +
        "&regEnd=" +
        encodeURIComponent($("#regEnd").val()) +
        "&disStart=" +
        encodeURIComponent($("#disStart").val()) +
        "&disEnd=" +
        encodeURIComponent($("#disEnd").val()) +
        "&search=" +
        encodeURIComponent($("#emergencyTable_filter input").val());

      window.open(url, "_blank");
    });
  }

  // PATIENT TABLE
  if ($("#patientTable").length) {
    $("#patientTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: "controllers/PatientController.php",
      pageLength: 15,
      scrollX: true,
    });
  }

  // TOOLTIP
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]'),
  );

  tooltipTriggerList.map(function (el) {
    return new bootstrap.Tooltip(el);
  });
}
