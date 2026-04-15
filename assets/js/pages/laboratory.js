$(document).ready(function () {
  initLaboratoryTurnAroundTimeTable();
});

function initLaboratoryTurnAroundTimeTable() {
  var table;

  if ($("#labTable").length) {
    table = $("#labTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "Controllers/LaboratoryController.php",
        data: function (d) {
          d.reqStart = $("#reqStart").val();
          d.reqEnd = $("#reqEnd").val();
          d.chgStart = $("#chgStart").val();
          d.chgEnd = $("#chgEnd").val();
        },
      },
      pageLength: 10,
      lengthMenu: [
        [5, 10, 15, 50, 100, -1],
        [5, 10, 15, 50, 100, "All"],
      ],
      order: [[4, "desc"]],
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
      language: {
        processing: "Loading...",
      },
      createdRow: function (row, data, dataIndex) {
        var turnaround = data[6];
        if (turnaround) {
          var hours = 0;
          var match = turnaround.match(/(\d+)\s*days\s*-\s*(\d+):(\d+):(\d+)/);
          if (match) {
            var days = parseInt(match[1]);
            var h = parseInt(match[2]);
            var m = parseInt(match[3]);
            var s = parseInt(match[4]);
            hours = days * 24 + h + m / 60 + s / 3600;
          }
          if (hours > 8) {
            $(row).addClass("table-danger");
          } else if (hours > 4) {
            $(row).addClass("table-warning");
          }
        }
      },
    });
    // FILTER BUTTON
    $("#filterBtn").click(function () {
      table.ajax.reload();
    });
    // RESET BUTTON
    $("#resetBtn").click(function () {
      $("#reqStart").val("");
      $("#reqEnd").val("");
      $("#chgStart").val("");
      $("#chgEnd").val("");
      table.ajax.reload();
    });
    $("#exportBtn").click(function (e) {
      e.preventDefault();

      var reqStart = $("#reqStart").val();
      var reqEnd = $("#reqEnd").val();
      var chgStart = $("#chgStart").val();
      var chgEnd = $("#chgEnd").val();

      // get DataTable search value
      var search = $("#labTable_filter input").val();

      var url =
        "modules/Laboratory/export_TurnAroundTime.php?" +
        "reqStart=" +
        encodeURIComponent(reqStart) +
        "&reqEnd=" +
        encodeURIComponent(reqEnd) +
        "&chgStart=" +
        encodeURIComponent(chgStart) +
        "&chgEnd=" +
        encodeURIComponent(chgEnd) +
        "&search=" +
        encodeURIComponent(search);

      window.open(url, "_blank");
    });
  }
}
