<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        // Only initialize the table if it exists
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                pageLength: 15,
                lengthMenu: [
                    [5, 10, 15, -1],
                    [5, 10, 15, "All"]
                ],
                order: [
                    [5, 'desc']
                ],
                scrollX: true,
                dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success'
                    },
                    {
                        extend: 'csvHtml5',
                        className: 'btn btn-primary'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary'
                    }
                ]
            });
        }

        var erTable = $('#erTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: "Controllers/EmergencyController.php",
                data: function(d) {
                    d.regStart = $('#regStart').val();
                    d.regEnd = $('#regEnd').val();
                    d.disStart = $('#disStart').val();
                    d.disEnd = $('#disEnd').val();
                }
            },
            columns: [{
                    data: 0
                }, // HPERCODE
                {
                    data: 1
                }, // PATIENT
                {
                    data: 2
                }, // BIRTHDATE
                {
                    data: 3
                }, // REG DATE
                {
                    data: 4
                }, // DISCHARGE DATE
                {
                    data: 5
                }, // TURNAROUND
                {
                    data: 6
                } // DISCHARGED BY
            ],
            pageLength: 10,
            lengthMenu: [
                [5, 10, 15, 50, 100, -1],
                [5, 10, 15, 50, 100, "All"]
            ],
            order: [
                [0, 'asc']
            ],
            scrollX: true,
            dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'excelHtml5',
                    className: 'btn btn-success',
                    title: 'Emergency - Turnaround Time Report'
                },
                {
                    extend: 'csvHtml5',
                    className: 'btn btn-primary',
                    title: 'Emergency - Turnaround Time Report'
                },
                {
                    extend: 'print',
                    className: 'btn btn-secondary',
                    title: 'Emergency - Turnaround Time Report'
                }
            ],
            language: {
                processing: "Loading emergency records..."
            },

            // ✅ ROW COLOR LOGIC
            rowCallback: function(row, data) {

                if (!data[5]) return;

                let text = data[5].toLowerCase();
                let totalHours = 0;

                // ✅ Extract DAYS
                let dayMatch = text.match(/(\d+)\s*day/);
                if (dayMatch) {
                    totalHours += parseInt(dayMatch[1]) * 24;
                }

                // ✅ Extract TIME (HH:MM or HH:MM:SS)
                let timeMatch = text.match(/(\d+):(\d+)(?::(\d+))?/);
                if (timeMatch) {
                    let h = parseInt(timeMatch[1]) || 0;
                    let m = parseInt(timeMatch[2]) || 0;
                    let s = parseInt(timeMatch[3]) || 0;

                    totalHours += h + (m / 60) + (s / 3600);
                }

                // ✅ APPLY COLORS
                if (totalHours > 8) {
                    $(row).addClass('table-danger');
                    $('td:eq(5)', row).addClass('text-danger fw-bold');
                } else if (totalHours > 4) {
                    $(row).addClass('table-warning');
                    $('td:eq(5)', row).addClass('text-success fw-bold');
                }
            }
        });
        // Filter button
        $('#filterBtn').on('click', function() {
            erTable.ajax.reload();
        });
        // Reset button
        $('#resetBtn').on('click', function() {
            $('#regStart, #regEnd, #disStart, #disEnd').val('');
            erTable.ajax.reload();
        });
        $('#exportEmergencyBtn').click(function(e) {
            e.preventDefault();

            var regStart = $('#regStart').val();
            var regEnd = $('#regEnd').val();
            var disStart = $('#disStart').val();
            var disEnd = $('#disEnd').val();

            // DataTable search value
            var search = $('#emergencyTable_filter input').val();

            var url = "modules/Emergency/export_EmergencyTurnAroundTime.php?" +
                "regStart=" + encodeURIComponent(regStart) +
                "&regEnd=" + encodeURIComponent(regEnd) +
                "&disStart=" + encodeURIComponent(disStart) +
                "&disEnd=" + encodeURIComponent(disEnd) +
                "&search=" + encodeURIComponent(search);

            window.open(url, '_blank');
        });

        if ($('#patientTable').length) {

            $('#patientTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "controllers/PatientController.php",

                pageLength: 15,
                lengthMenu: [
                    [5, 10, 15, 50, 100, -1],
                    [5, 10, 15, 50, 100, "All"]
                ],

                order: [
                    [0, 'asc']
                ],

                scrollX: true,

                dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",

                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success'
                    },
                    {
                        extend: 'csvHtml5',
                        className: 'btn btn-primary'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary'
                    }
                ],

                columns: [{
                        data: 0
                    },
                    {
                        data: 1
                    },
                    {
                        data: 2
                    },
                    {
                        data: 3
                    },
                    {
                        data: 4
                    },
                    {
                        data: 5
                    },
                    {
                        data: 6
                    }
                ],
                language: {
                    processing: "Loading patients..."
                }
            });
        }
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>

<script>
    $(document).ready(function() {
        var table = $('#labTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "Controllers/LaboratoryController.php",
                data: function(d) {
                    d.reqStart = $('#reqStart').val();
                    d.reqEnd = $('#reqEnd').val();
                    d.chgStart = $('#chgStart').val();
                    d.chgEnd = $('#chgEnd').val();
                }
            },
            pageLength: 10,
            lengthMenu: [
                [5, 10, 15, 50, 100, -1],
                [5, 10, 15, 50, 100, "All"]
            ],
            order: [
                [4, 'desc']
            ],
            scrollX: true,
            dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'excelHtml5',
                    className: 'btn btn-success'
                },
                {
                    extend: 'csvHtml5',
                    className: 'btn btn-primary'
                },
                {
                    extend: 'print',
                    className: 'btn btn-secondary'
                }
            ],
            language: {
                processing: "Loading..."
            },
            createdRow: function(row, data, dataIndex) {
                var turnaround = data[6];
                if (turnaround) {
                    var hours = 0;
                    var match = turnaround.match(/(\d+)\s*days\s*-\s*(\d+):(\d+):(\d+)/);
                    if (match) {
                        var days = parseInt(match[1]);
                        var h = parseInt(match[2]);
                        var m = parseInt(match[3]);
                        var s = parseInt(match[4]);
                        hours = (days * 24) + h + (m / 60) + (s / 3600);
                    }
                    if (hours > 8) {
                        $(row).addClass('table-danger');
                    } else if (hours > 4) {
                        $(row).addClass('table-warning');
                    }
                }
            }
        });
        // FILTER BUTTON
        $('#filterBtn').click(function() {
            table.ajax.reload();
        });
        // RESET BUTTON
        $('#resetBtn').click(function() {
            $('#reqStart').val('');
            $('#reqEnd').val('');
            $('#chgStart').val('');
            $('#chgEnd').val('');
            table.ajax.reload();
        });
        $('#exportBtn').click(function(e) {
            e.preventDefault();

            var reqStart = $('#reqStart').val();
            var reqEnd = $('#reqEnd').val();
            var chgStart = $('#chgStart').val();
            var chgEnd = $('#chgEnd').val();

            // get DataTable search value
            var search = $('#labTable_filter input').val();

            var url = "modules/Laboratory/export_TurnAroundTime.php?" +
                "reqStart=" + encodeURIComponent(reqStart) +
                "&reqEnd=" + encodeURIComponent(reqEnd) +
                "&chgStart=" + encodeURIComponent(chgStart) +
                "&chgEnd=" + encodeURIComponent(chgEnd) +
                "&search=" + encodeURIComponent(search);

            window.open(url, '_blank');
        });
    });
</script>

<script>
    $(document).ready(function() {
        var table = $('#radTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "Controllers/RadiologyController.php",
                data: function(d) {

                    d.reqStart = $('#reqStart').val();
                    d.reqEnd = $('#reqEnd').val();
                    d.chgStart = $('#chgStart').val();
                    d.chgEnd = $('#chgEnd').val();

                }
            },
            pageLength: 10,
            lengthMenu: [
                [5, 10, 15, 50, 100, -1],
                [5, 10, 15, 50, 100, "All"]
            ],
            order: [
                [4, 'desc']
            ],
            scrollX: true,
            dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'excelHtml5',
                    className: 'btn btn-success'
                },
                {
                    extend: 'csvHtml5',
                    className: 'btn btn-primary'
                },
                {
                    extend: 'print',
                    className: 'btn btn-secondary'
                }
            ],
            language: {
                processing: "Loading..."
            },
            createdRow: function(row, data, dataIndex) {
                var turnaround = data[6];
                if (turnaround) {
                    var hours = 0;
                    var match = turnaround.match(/(\d+)\s*days\s*-\s*(\d+):(\d+):(\d+)/);
                    if (match) {
                        var days = parseInt(match[1]);
                        var h = parseInt(match[2]);
                        var m = parseInt(match[3]);
                        var s = parseInt(match[4]);
                        hours = (days * 24) + h + (m / 60) + (s / 3600);
                    }
                    if (hours > 8) {
                        $(row).addClass('table-danger');
                    } else if (hours > 4) {
                        $(row).addClass('table-warning');
                    }
                }
            }
        });
        // FILTER BUTTON
        $('#filterBtn').click(function() {
            table.ajax.reload();
        });
        // RESET BUTTON
        $('#resetBtn').click(function() {
            $('#reqStart').val('');
            $('#reqEnd').val('');
            $('#chgStart').val('');
            $('#chgEnd').val('');
            table.ajax.reload();
        });
        $('#exportRadiologyBtn').click(function(e) {
            e.preventDefault();

            var reqStart = $('#reqStart').val();
            var reqEnd = $('#reqEnd').val();
            var chgStart = $('#chgStart').val();
            var chgEnd = $('#chgEnd').val();

            var search = $('#radTable_filter input').val();

            var url = "modules/Radiology/export_radTurnAroundTime.php?" +
                "reqStart=" + encodeURIComponent(reqStart) +
                "&reqEnd=" + encodeURIComponent(reqEnd) +
                "&chgStart=" + encodeURIComponent(chgStart) +
                "&chgEnd=" + encodeURIComponent(chgEnd) +
                "&search=" + encodeURIComponent(search);

            window.open(url, '_blank');
        });
    });
</script>

<script>
    var table;

    $(document).ready(function() {

        if ($('#admLogTable').length) {
            table = $('#admLogTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "Controllers/MedicalRecordsController.php",
                    type: "POST",
                    data: function(d) {
                        d.action = "admissionLog";
                    }
                },
                pageLength: 15,
                lengthMenu: [
                    [5, 10, 15, 50, 100, -1],
                    [5, 10, 15, 50, 100, "All"]
                ],
                scrollX: true,
                order: [
                    [3, 'desc'] // admission_count
                ],
                dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success',
                        title: 'Admission Log'
                    },
                    {
                        extend: 'csvHtml5',
                        className: 'btn btn-primary'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary'
                    }
                ],
                columns: [{
                        data: 'hpercode'
                    },
                    {
                        data: 'patient'
                    },
                    {
                        data: 'birthdate'
                    },
                    {
                        data: 'admission_count'
                    },
                    {
                        data: 'hpercode',
                        render: function(data) {
                            return `<a href="index.php?page=viewAdmissionLog&hpercode=${data}">
                                    View History
                                </a>`;
                        }
                    }
                ]
            });
        }
    });
</script>

<script>
    $(document).ready(function() {

        if ($('#viewAdmissionLogTable').length) {
            $('#viewAdmissionLogTable').DataTable({
                pageLength: 15,
                lengthMenu: [
                    [5, 10, 15, -1],
                    [5, 10, 15, "All"]
                ],
                order: [
                    [1, 'desc']
                ], // fixed
                scrollX: true,
                dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success'
                    },
                    {
                        extend: 'csvHtml5',
                        className: 'btn btn-primary'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary'
                    }
                ]
            });
        }
    });
</script>

<script>
    var issuedTable;

    $(document).ready(function() {
        if ($('#issuedDMTable').length) {
            issuedTable = $('#issuedDMTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "Controllers/PharmacyController.php",
                    type: "POST",
                    data: function(d) {
                        d.action = "issued";
                        d.startDate = $('#issuedStart').val() || null;
                        d.endDate = $('#issuedEnd').val() || null;
                    },
                    dataSrc: function(json) {
                        if (json.totals) {
                            $('#totalDrugs').html(json.totals.totalDrugs ?? 0);
                            $('#totalIssued').html(json.totals.totalIssued ?? 0);
                            $('#totalReturned').html(json.totals.totalReturned ?? 0);
                        }
                        return json.data;
                    }
                },
                pageLength: 10,
                lengthMenu: [
                    [5, 10, 15, 50, 100, -1],
                    [5, 10, 15, 50, 100, "All"]
                ],
                scrollX: true,
                order: [
                    [9, 'desc']
                ],
                language: {
                    processing: "Loading issued records..."
                },
                dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success',
                        title: 'Issued Drugs and Medicine'
                    },
                    {
                        extend: 'csvHtml5',
                        className: 'btn btn-primary'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary',
                        title: 'Issued Drugs and Medicine'
                    }
                ],
                columns: [{
                        data: 'lot_number'
                    },
                    {
                        data: 'drug_description'
                    },
                    {
                        data: 'hpercode'
                    },
                    {
                        data: 'patient'
                    },
                    {
                        data: 'quantity_issued'
                    },
                    {
                        data: 'quantity_returned'
                    },
                    {
                        data: 'order_type'
                    },
                    {
                        data: 'account_type'
                    },
                    {
                        data: 'issued_by'
                    },
                    {
                        data: 'date_issued'
                    }
                ]
            });
        }
        $('#filterIssued').click(function() {
            if (issuedTable) issuedTable.ajax.reload();
        });
        $('#resetIssued').click(function() {
            $('#issuedStart').val('<?= $startDate ?>');
            $('#issuedEnd').val('<?= $endDate ?>');
            if (issuedTable) issuedTable.ajax.reload();
        });

        $('#exportIssuedExcel').click(function(e) {
            e.preventDefault();
            let startDate = $('#issuedStart').val();
            let endDate = $('#issuedEnd').val();
            let url = "modules/Pharmacy/export_issued_drugs.php?" +
                "startDate=" + encodeURIComponent(startDate) +
                "&endDate=" + encodeURIComponent(endDate);
            window.open(url, '_blank');
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        var inventoryTable = $('#inventoryTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "Controllers/PharmacyController.php",
                type: "POST",
                data: function(d) {
                    d.action = "inventory";
                    d.startDate = $('#inventoryStart').val() || null;
                    d.endDate = $('#inventoryEnd').val() || null;
                },
                dataSrc: function(json) {
                    if (json.totals) {
                        $('#totalStock').text(json.totals.totalStock ?? 0);
                        $('#totalValue').text(
                            parseFloat(json.totals.totalValue ?? 0)
                            .toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        );
                        $('#expiredStock').text(json.totals.expiredStock ?? 0);
                        $('#expiredValue').text(
                            parseFloat(json.totals.expiredValue ?? 0)
                            .toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        );
                    }
                    return json.data;
                }
            },
            pageLength: 10,
            lengthMenu: [
                [5, 10, 15, 50, 100, -1],
                [5, 10, 15, 50, 100, "All"]
            ],
            order: [
                [0, 'asc']
            ],
            scrollX: true,
            dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Inventory Report',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'btn btn-primary btn-sm',
                    title: 'Inventory Report'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Inventory Report'
                }
            ],
            columns: [{
                    data: 'lot_number'
                },
                {
                    data: 'drug_description'
                },
                {
                    data: 'stock_balance'
                },
                {
                    data: 'beg_balance'
                },
                {
                    data: 'total_dispensed'
                },
                {
                    data: 'selling_price'
                },
                {
                    data: 'entry_date'
                },
                {
                    data: 'expiration_date'
                },
                {
                    data: 'account_type'
                },
                {
                    data: 'status'
                },
                {
                    data: "id",
                    render: function(data, type, row) {
                        const isDisabled = row.status === "PULLOUT" || row.status === "EXPIRED/PULLOUT";
                        return `
                            <a href="#"
                            class="pulloutDrugBtn ${isDisabled ? "disabled-link text-muted" : ""}"
                            ${isDisabled ? 'style="pointer-events:none;"' : ''}
                            data-id="${data}"
                            data-drug="${row.drug_description}">
                            Pull Out
                            </a>
                        `;
                    }
                }
            ],
            createdRow: function(row, data) {
                if (data.status === "EXPIRED") {
                    $(row).addClass('table-danger');
                } else if (data.status === "NEAR EXPIRE") {
                    $(row).addClass('table-warning');
                }
            }
        });

        // Filter & Reset
        $('#filterInventory').click(function() {
            inventoryTable.ajax.reload();
        });
        $('#resetInventory').click(function() {
            $('#inventoryStart').val('<?= $startDate ?>');
            $('#inventoryEnd').val('<?= $endDate ?>');
            inventoryTable.ajax.reload();
        });

        $('#exportInventoryExcel').click(function(e) {
            e.preventDefault();

            let startDate = $('#inventoryStart').val();
            let endDate = $('#inventoryEnd').val();
            let search = $('#inventoryTable_filter input').val();

            let url = "modules/Pharmacy/export_inventory.php?startDate=" + encodeURIComponent(startDate) +
                "&endDate=" + encodeURIComponent(endDate) +
                "&search=" + encodeURIComponent(search);

            window.location.href = url;
        });

        $(document).on("click", ".pulloutDrugBtn", function(e) {
            if ($(this).hasClass("disabled-link")) {
                e.preventDefault(); // block the click
                return;
            }

            pullOutId = $(this).data("id");
            $("#modalDrug").text($(this).data("drug"));
            $("#pulledOutRemarks").val(""); // ✅ reset
            $("#pullOutModal").modal("show");
        });

        // Confirm Pull Out
        $("#confirmPullOut").click(function(e) {
            e.preventDefault(); // prevent form submission

            if (pullOutId === null || pullOutId === undefined) {
                console.log("DEBUG ID:", pullOutId);
                return alert("Invalid drug ID.");
            }
            let remarks = $("#pulledOutRemarks").val().trim();
            if (!remarks) {
                alert("Please enter a reason.");
                return;
            }

            $.ajax({
                url: "Controllers/PharmacyController.php",
                type: "POST",
                data: {
                    action: "pullout",
                    id: pullOutId,
                    remarks: remarks
                },
                success: function(response) {
                    let res;
                    try {
                        res = JSON.parse(response);
                    } catch (e) {
                        return alert("Invalid response from server");
                    }

                    if (res.status === "success") {
                        alert("Successfully pulled out item.");
                        $("#pullOutModal").modal("hide");
                        $("#pulledOutRemarks").val("");
                        pullOutId = null;
                        inventoryTable.ajax.reload(null, false);
                    } else {
                        alert(res.message || "Failed to pull out the item.");
                    }
                },
                error: function() {
                    alert("AJAX error. Could not pull out item.");
                }
            });
        });
    });
</script>

<script>
    $(document).ready(function() {
        var table = $('#pulledoutDMTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "Controllers/PharmacyController.php",
                type: "POST",
                data: function(d) {
                    d.action = "pulledOut";
                    d.startDate = $('#pulledoutStart').val() || null;
                    d.endDate = $('#pulledoutEnd').val() || null;
                },
                dataSrc: function(json) {
                    if (json.totals) {
                        $('#totalStock').text(json.totals.totalStock ?? 0);
                        $('#totalValue').text(
                            parseFloat(json.totals.totalValue ?? 0)
                            .toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        );
                        $('#expiredStock').text(json.totals.expiredStock ?? 0);
                        $('#expiredValue').text(
                            parseFloat(json.totals.expiredValue ?? 0)
                            .toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        );
                    }
                    return json.data;
                }
            },
            pageLength: 10,
            lengthMenu: [
                [5, 10, 15, 50, 100, -1],
                [5, 10, 15, 50, 100, "All"]
            ],
            order: [
                [0, 'asc']
            ],
            scrollX: true,
            dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Pulled Out Drugs and Medicine Report',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'btn btn-primary btn-sm',
                    title: 'Pulled Out Drugs and Medicine Report'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Pulled Out Drugs and Medicine Report'
                }
            ],
            columns: [{
                    data: 'lot_number'
                },
                {
                    data: 'drug_description'
                },
                {
                    data: 'stock_balance'
                },
                {
                    data: 'beg_balance'
                },
                {
                    data: 'total_dispensed'
                },
                {
                    data: 'selling_price'
                },
                {
                    data: 'entry_date'
                },
                {
                    data: 'expiration_date'
                },
                {
                    data: 'account_type'
                },
                {
                    data: 'status'
                },
                {
                    data: 'remarks'
                },
                {
                    data: "id",
                    render: function(data, type, row) {
                        return `
                            <a href="#"
                                class="undoPulloutBtn"
                                data-id="${data}"
                                data-drug="${row.drug_description}">
                                Undo Pull Out
                            </a>
                        `;
                    }
                }
            ],
            createdRow: function(row, data) {
                if (data.status === "EXPIRED/PULLOUT") {
                    $(row).addClass('table-danger');
                } else if (data.status === "NEAR EXPIRE") {
                    $(row).addClass('table-warning');
                }
            }
        });

        $('#filterPulledOut').click(function() {
            table.ajax.reload();
        });
        $('#resetPulledOut').click(function() {
            $('#pulledoutStart').val('<?= $startDate ?>');
            $('#pulledoutEnd').val('<?= $endDate ?>');
            table.ajax.reload();
        });
        $('#exportPulledOutExcel').click(function(e) {
            e.preventDefault();

            let startDate = $('#pulledoutStart').val();
            let endDate = $('#pulledoutEnd').val();
            let search = $('.dataTables_filter input').val(); // get global search

            let url = "modules/Pharmacy/export_pulledout.php?startDate=" + encodeURIComponent(startDate) +
                "&endDate=" + encodeURIComponent(endDate) +
                "&search=" + encodeURIComponent(search);

            window.location.href = url;
        });
        // Pull Out modal
        let pullOutId = null;
        $(document).on("click", ".undoPulloutBtn", function(e) {
            if ($(this).hasClass("disabled-link")) {
                e.preventDefault();
                return;
            }

            pullOutId = $(this).data("id");
            $("#modalDrug").text($(this).data("drug"));
            $("#undoRemarks").val("");
            $("#undoPullOutModal").modal("show");
        });

        // Confirm Pull Out
        $("#confirmUndoPullOut").click(function(e) {
            e.preventDefault(); // prevent form submission

            if (!pullOutId) return alert("Invalid drug ID.");

            let remarks = $("#undoRemarks").val().trim();
            if (!remarks) {
                alert("Please enter a reason.");
                return;
            }

            $.ajax({
                url: "Controllers/PharmacyController.php",
                type: "POST",
                data: {
                    action: "undoPullOut",
                    id: pullOutId,
                    remarks: remarks
                },
                success: function(response) {
                    let res;
                    try {
                        res = JSON.parse(response);
                    } catch (e) {
                        return alert("Invalid response from server");
                    }

                    if (res.status === "success") {
                        alert("Successfully restored item.");
                        $("#undoPullOutModal").modal("hide");
                        $("#undoRemarks").val("");
                        table.ajax.reload(null, false);
                    } else {
                        alert(res.message || "Failed to undo pull out the item.");
                    }
                },
                error: function() {
                    alert("AJAX error. Could not pull out item.");
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });

    $('#inventoryTable tbody tr').each(function() {
        var statusCell = $(this).find('td').eq(7); // index 7 = STATUS
        var status = statusCell.text();

        if (status === 'EXPIRED') {
            statusCell.css('color', 'red').css('font-weight', 'bold');
        } else if (status === 'NEAR EXPIRE') {
            statusCell.css('color', 'orange').css('font-weight', 'bold');
        } else {
            statusCell.css('color', 'green');
        }
    });
</script>

<!--------------------------- CENTRAL SUPPLY --------------------------->
<script>
    $(document).ready(function() {
        var inventoryCSTable = $('#inventorySuppliesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "Controllers/CentralSupplyController.php",
                type: "POST",
                data: function(d) {
                    d.action = "supplyInventory";
                    d.startDate = $('#inventorySupplyStart').val() || null;
                    d.endDate = $('#inventorySupplyEnd').val() || null;
                },
                dataSrc: function(json) {
                    if (json.totals) {
                        $('#totalStock').text(json.totals.totalStock ?? 0);
                        $('#totalValue').text(
                            parseFloat(json.totals.totalValue ?? 0)
                            .toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        );
                        $('#expiredStock').text(json.totals.expiredStock ?? 0);
                        $('#expiredValue').text(
                            parseFloat(json.totals.expiredValue ?? 0)
                            .toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        );
                    }
                    return json.data;
                }
            },
            pageLength: 10,
            lengthMenu: [
                [5, 10, 15, 50, 100, -1],
                [5, 10, 15, 50, 100, "All"]
            ],
            order: [
                [0, 'asc']
            ],
            scrollX: true,
            dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                "<'row'<'col-12'tr>>" +
                "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                    extend: 'excelHtml5',
                    text: 'Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Central Supply Inventory Report',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'btn btn-primary btn-sm',
                    title: 'Central Supply Inventory Report'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Central Supply Inventory Report'
                }
            ],
            columns: [{
                    data: 'lot_number'
                },
                {
                    data: 'supply_name'
                },
                {
                    data: 'stock_balance'
                },
                {
                    data: 'beg_balance'
                },
                {
                    data: 'total_dispensed'
                },
                {
                    data: 'selling_price'
                },
                {
                    data: 'entry_date'
                },
                {
                    data: 'expiry_date'
                },
                {
                    data: 'date_modified'
                },
                {
                    data: 'account_type'
                },
                {
                    data: 'status'
                },
                {
                    data: 'cost_center'
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        const isDisabled = row.status === "PULLOUT" || row.status === "EXPIRED/PULLOUT";

                        return `
                            <a href="#" class="pulloutSupplyBtn ${isDisabled ? "disabled-link text-muted" : ""}" ${isDisabled ? 'style="pointer-events:none;"' : ''}
                                data-cl2dtmd="${row.cl2dtmd}"
                                data-itemcode="${row.itemcode}"
                                data-supply="${row.supply_name}">
                                Pull Out
                            </a>
                        `;
                    }
                }
            ],
            columnDefs: [{
                    targets: 8,
                    visible: false
                },
                {
                    targets: 11,
                    visible: false
                }
            ],
            createdRow: function(row, data) {
                if (data.status === "EXPIRED") {
                    $(row).addClass('table-danger');
                } else if (data.status === "NEAR EXPIRE") {
                    $(row).addClass('table-warning');
                }

                if (data.stock_balance === "No Stock Balance") {
                    $('td:eq(2)', row).addClass('text-danger fw-bold');
                }

                if (data.selling_price === "No Selling Price") {
                    $('td:eq(5)', row).addClass('text-danger fw-bold');
                }
            }
        });

        // Filter & Reset
        $('#filterSupplyInventory').click(function() {
            inventoryCSTable.ajax.reload();
        });

        $('#resetSupplyInventory').click(function() {
            $('#inventorySupplyStart').val('<?= $startDate ?>');
            $('#inventorySupplyEnd').val('<?= $endDate ?>');
            inventoryCSTable.ajax.reload();
        });

        $('#exportSupplyInventoryExcel').click(function(e) {
            e.preventDefault();

            let startDate = $('#inventorySupplyStart').val();
            let endDate = $('#inventorySupplyEnd').val();
            let search = $('#inventorySuppliesTable_filter input').val();

            let url = "modules/CentralSupply/export_csInventory.php?startDate=" + encodeURIComponent(startDate) +
                "&endDate=" + encodeURIComponent(endDate) +
                "&search=" + encodeURIComponent(search);

            window.location.href = url;
        });

        let pullOutData = {};
        $('#inventorySuppliesTable').on("click", ".pulloutSupplyBtn", function(e) {
            if ($(this).hasClass("disabled-link")) {
                e.preventDefault();
                return;
            }

            pullOutData = {
                cl2dtmd: $(this).data("cl2dtmd"),
                itemcode: $(this).data("itemcode")
            };

            $("#modalDrug").text($(this).data("supply"));
            $("#pulledOutCSRemarks").val("");
            $("#pullOutModal").modal("show");
        });

        $("#confirmCSPullOut").click(function(e) {
            e.preventDefault();

            if (pullOutData.cl2dtmd === null || pullOutData.cl2dtmd === undefined ||
                pullOutData.itemcode === null || pullOutData.itemcode === undefined) {
                return alert("Invalid item reference.");
            }

            let remarks = $("#pulledOutCSRemarks").val().trim();
            if (!remarks) {
                alert("Please enter a reason.");
                return;
            }

            $.ajax({
                url: "Controllers/CentralSupplyController.php",
                type: "POST",
                data: {
                    action: "pullout",
                    cl2dtmd: pullOutData.cl2dtmd,
                    itemcode: pullOutData.itemcode,
                    remarks: remarks
                },
                success: function(response) {
                    let res;
                    try {
                        res = JSON.parse(response);
                    } catch (e) {
                        return alert("Invalid response from server");
                    }

                    if (res.status === "success") {
                        alert("Successfully pulled out item.");
                        $("#pullOutModal").modal("hide");
                        $("#pulledOutCSRemarks").val("");
                        pullOutData = {}; // reset
                        inventoryCSTable.ajax.reload(null, false);
                    } else {
                        alert(res.message || "Failed to pull out the item.");
                    }
                },
                error: function() {
                    alert("AJAX error. Could not pull out item.");
                }
            });
        });
    });
</script>