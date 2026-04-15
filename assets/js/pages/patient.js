$(document).ready(function () {
  initMasterPatientTable();
});

function initMasterPatientTable() {
  let patientTable = null;

  if ($.fn.DataTable.isDataTable("#patientTable")) {
    $("#patientTable").DataTable().destroy();
  }

  if ($("#patientTable").length) {
    patientTable = $("#patientTable").DataTable({
      processing: true,
      serverSide: true,
      ajax: "controllers/PatientController.php",

      pageLength: 15,
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
        { extend: "excelHtml5", className: "btn btn-success" },
        { extend: "csvHtml5", className: "btn btn-primary" },
        { extend: "print", className: "btn btn-secondary" },
      ],

      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 },
        { data: 6 },
      ],
    });
  }
}
