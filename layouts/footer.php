<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<!-- <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script> -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="/assets/js/pages/emergency.js"></script>
<script src="/assets/js/pages/patient.js"></script>
<script src="/assets/js/pages/laboratory.js"></script>
<script src="/assets/js/pages/radiology.js"></script>
<script src="/assets/js/pages/medical_records.js"></script>
<script src="/assets/js/pages/pharmacy.js"></script>
<script src="/assets/js/pages/central_supply.js"></script>

<!--------------------------- CENTRAL SUPPLY --------------------------->

<script>
    $(document).ready(function() {

        var csPulledOutTable = $('#pulledoutSuppliesTable').DataTable({
            processing: true,
            serverSide: true, // ✅ FIXED

            ajax: {
                url: "Controllers/CentralSupplyController.php",
                type: "POST",
                data: function(d) {
                    d.action = "csPullOutHistory"; // ✅ FIXED
                    d.startDate = $('#pulledoutCSStart').val() || null;
                    d.endDate = $('#pulledoutCSEnd').val() || null;
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

                    return json.data || []; // ✅ SAFE
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
                    title: 'Pulled Out Non-Drugs and Supply Report',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    className: 'btn btn-primary btn-sm',
                    title: 'Pulled Out Non-Drugs and Supply Report'
                },
                {
                    extend: 'print',
                    text: 'Print',
                    className: 'btn btn-secondary btn-sm',
                    title: 'Pulled Out Non-Drugs and Supply Report'
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
                }, // ⚠️ ensure exists in PHP
                {
                    data: 'total_dispensed'
                }, // ⚠️ ensure exists in PHP
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
                    data: 'date_modified' // hidden number 8
                },
                {
                    data: 'account_type'
                },
                {
                    data: 'status'
                },
                {
                    data: 'cost_center' // hidden number 11
                },
                {
                    data: 'cs_remarks'
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <a href="#" class="undoPullOutSupplyBtn"
                            data-cl2dteas="${row.entry_date || ''}"
                            data-itemcode="${row.itemcode || ''}"
                            data-supply="${row.supply_name}">
                            Undo Pull Out
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
                } else if (data.status === "EXPIRED/PULLOUT") {
                    $(row).addClass('table-danger');
                }

                if (data.stock_balance === "No Stock Balance") {
                    $('td:eq(2)', row).addClass('text-danger fw-bold');
                }

                if (data.selling_price === "No Selling Price") {
                    $('td:eq(5)', row).addClass('text-danger fw-bold');
                }
            }
        });

        $('#filterPulledOutCS').click(function() {
            csPulledOutTable.ajax.reload(); // ✅ FIXED
        });
        $('#resetPulledOutCS').click(function() {
            $('#pulledoutCSStart').val('<?= $startDate ?? '' ?>');
            $('#pulledoutCSEnd').val('<?= $endDate ?? '' ?>');
            csPulledOutTable.ajax.reload(); // ✅ FIXED
        });

        $('#exportUndoPulledOutCSExcel').click(function(e) {
            e.preventDefault();

            let startDate = $('#pulledoutCSStart').val();
            let endDate = $('#pulledoutCSEnd').val();
            let search = $('#pulledoutSuppliesTable_filter input').val();

            let url = "modules/CentralSupply/export_csPulledOut.php?startDate=" + encodeURIComponent(startDate) +
                "&endDate=" + encodeURIComponent(endDate) +
                "&search=" + encodeURIComponent(search);

            window.location.href = url;
        });

        let undoPullOutData = {};
        $('#pulledoutSuppliesTable').on("click", ".undoPullOutSupplyBtn", function(e) {
            if ($(this).hasClass("disabled-link")) {
                e.preventDefault();
                return;
            }

            undoPullOutData = {
                cl2dteas: $(this).data("cl2dteas"),
                itemcode: $(this).data("itemcode")
            };

            $("#modalUndoCS").text($(this).data("supply"));
            $("#undoPulledOutCSRemarks").val("");
            $("#undoPulledOutCSModal").modal("show");
        });

        $("#confirmUndoPulledOutCS").click(function(e) {
            e.preventDefault();

            if (!undoPullOutData.cl2dteas || !undoPullOutData.itemcode) {
                return alert("Invalid item reference.");
            }

            let remarks = $("#undoPulledOutCSRemarks").val().trim();
            if (!remarks) {
                alert("Please enter a reason.");
                return;
            }

            $.ajax({
                url: "Controllers/CentralSupplyController.php",
                type: "POST",
                data: {
                    action: "undoPullOutCS",
                    cl2dteas: undoPullOutData.cl2dteas,
                    itemcode: undoPullOutData.itemcode,
                    remarks: remarks
                },
                success: function(response) {
                    console.log(response);
                    let res;
                    try {
                        res = JSON.parse(response);
                    } catch (e) {
                        return alert("Invalid response from server");
                    }

                    if (res.status === "success") {
                        alert("Successfully restored item.");
                        $("#undoPulledOutCSModal").modal("hide");
                        $("#undoPulledOutCSRemarks").val("");
                        undoPullOutData = {};
                        csPulledOutTable.ajax.reload(null, false);
                    } else {
                        alert(res.message || "Failed to undo pull out the item.");
                    }
                },
                error: function() {
                    alert("AJAX error. Could not restore item.");
                }
            });
        });
    });
</script>

<script>
    var issuedCSTable;

    $(document).ready(function() {
        if ($('#issuedCSTable').length) {
            issuedCSTable = $('#issuedCSTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "Controllers/CentralSupplyController.php",
                    type: "POST",
                    data: function(d) {
                        d.action = "issuedSupplies";
                        d.startDate = $('#issuedCSStart').val() || null;
                        d.endDate = $('#issuedCSEnd').val() || null;
                    },
                    dataSrc: function(json) {
                        if (json.totals) {
                            $('#totalSupplies').text(json.totals.totalSupplies ?? 0);
                            $('#totalIssued').text(json.totals.totalIssued ?? 0);
                            $('#totalReturned').text(json.totals.totalReturned ?? 0);
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
                    [0, 'desc']
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
                },
                dom: "<'row px-md-1 mb-2'<'col-md-2'l><'col-md-4'B><'col-md-6'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row px-md-4'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                        extend: 'excelHtml5',
                        className: 'btn btn-success',
                        title: 'Issued Non-Drugs and Supplies Report'
                    },
                    {
                        extend: 'csvHtml5',
                        className: 'btn btn-primary',
                        title: 'Issued Non-Drugs and Supplies Report'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary',
                        title: 'Issued Non-Drugs and Supplies Report'
                    }
                ],
                columns: [{
                        data: 'order_date'
                    },
                    {
                        data: 'issued_date'
                    },
                    {
                        data: 'turnaround_time'
                    },
                    {
                        data: 'lot_number'
                    },
                    {
                        data: 'supply_name'
                    },
                    {
                        data: 'hpercode'
                    },
                    {
                        data: 'patient'
                    },
                    {
                        data: 'charge_code'
                    },
                    {
                        data: 'request_quantity'
                    },
                    {
                        data: 'issued_quantity'
                    },
                    {
                        data: 'selling_price'
                    },
                    {
                        data: 'total_amount'
                    },
                    {
                        data: 'returned_quantity'
                    },
                    {
                        data: 'account_type'
                    },
                    {
                        data: 'issued_by'
                    },
                ]
            });
        }
        $('#filterCSForm').on('submit', function(e) {
            e.preventDefault(); // 🚨 VERY IMPORTANT
            if (issuedCSTable) issuedCSTable.ajax.reload();
        });
        $('#resetIssuedCS').click(function() {
            $('#issuedCSStart').val('<?= $startDate ?? '' ?>');
            $('#issuedCSEnd').val('<?= $endDate ?? '' ?>');
            if (issuedCSTable) issuedCSTable.ajax.reload();
        });
        $('#exportIssuedCSExcel').click(function(e) {
            e.preventDefault();

            let startDate = $('#issuedCSStart').val();
            let endDate = $('#issuedCSEnd').val();
            let search = $('#issuedCSTable_filter input').val();

            let url = "modules/CentralSupply/export_issuedSupplies.php?startDate=" + encodeURIComponent(startDate) +
                "&endDate=" + encodeURIComponent(endDate) +
                "&search=" + encodeURIComponent(search);

            window.open(url, '_blank');
        });
    });
</script>