(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dt;

    let $dtHistory;
    let $dtPending;
    let $dtPaymentDetails;

    const vm = window.VVVVVV = new Vue({
        el: '.tool-container',
        data() {
            return {
                selectedIds: [],
                period_ids: [],
                is_processing: 0,
                error: {
                    message: null,
                    type: null,
                    data: null,
                },
                period_id: null,
                history: {
                    created_at: "",
                    id: 0,
                    period: "",
                    prepared_by: "",
                    pay_count: 0,
                    status: null,
                    csv_file: null,
                },
                is_check_all: false,
                seek: 0,
                lastSeek: 0,
                lines: [],
                logInterval: null,
            };
        },
        mounted() {
            // this.initializeSelect2();
            this.initializeDataTables();
            this.initializeDataTablesEvents();
            this.viewPayouts();
        },
        methods: {
            initializeSelect2() {

                $("#commission-type, #commission-period").select2({
                    theme: "bootstrap",
                    multiple: true,
                    width: "100%",
                    placeholder: "Select one or more",
                });

                this.getCommissionTypes();

                $("#commission-type").on('change', () => {

                    let type = $("#commission-type").val();

                    this.getCommissionPeriods(type);

                });
            },
            initializeDataTables() {
                let $this = this;

                $dt = $("#table-main").DataTable({
                    paging: false,
                    responsive: true,
                    order: [[ 1, 'asc' ]],
                    columns     : [
                        {
                            data: 'is_selected',
                            width: '10x',
                            orderable: false,
                            className: 'text-center',
                            render: function (data, type, full, meta){

                                if(full.username === null)
                                {
                                    return `<input class="row-selected" disabled readonly type="checkbox" name="id[]" value="1">`
                                }

                                return `<input class="row-selected" ${vm.selectedIds.includes(full.user_id) ? "checked" : ""} type="checkbox" name="id[]" value="1">`;
                            }
                        },
                        {
                            data: 'name',
                            width: '30%',
                        },
                        {
                            data: 'username',
                            render: function(data, type, full) {
                                if(full.username === null)
                                {
                                    return '<span class="label label-danger">NO ACCOUNT</span> ';
                                }

                                return data;
                            }
                        },
                        {data    : 'commission_type'},
                        {data    : 'amount', className: "text-right"},
                        {data    : 'currency'},
                        {
                            data: null,
                            orderable: false,
                            width: '10px',
                            render: function(data, type, full) {
                                return '<button class="btn btn-info btn-mark-as-paid btn-sm">Mark as paid</button>';
                            }
                        },
                    ]
                });

                $dtHistory = $("#table-history").DataTable({
                    "processing": true,
                    "serverSide": true,
                    "responsive": true,
                    "ajax": {
                        "url" : api_url + 'admin/pay-commission/payment-history',
                        "data": function(d) {
                        },
                    },
                    order: [[ 0, 'desc' ]],
                    columns     : [
                        {data    : 'id'},
                        {data    : 'prepared_by'},
                        {data    : 'created_at'},
                        {data    : 'status'},
                        // {data    : 'receipt_num'},
                        {
                            data: null,
                            orderable: false,
                            width: '10px',
                            render: function(data, type, full) {
                                return '<button title="View Details" type= "button" class="btn btn-view-details btn-action btn-sm btn-primary">View Details</button>';
                            }
                        },
                    ]
                });
                $dtPaymentDetails = $("#table-view-details").DataTable({
                    "processing": true,
                    "serverSide": true,
                    "responsive": true,
                    "ajax": {
                        "url" : api_url + 'admin/pay-commission/payment-details',
                        "data": function(d) {
                            d.history_id = $this.history.id;
                        },
                    },
                    order: [[ 0, 'desc' ]],
                    columns     : [
                        {data    : 'payment_id'},
                        {data    : 'reference_no'},
                        {data    : 'member'},
                        {data    : 'username'},
                        {data    : 'amount'},
                        {data    : 'status'},
                    ]
                });
                $dtPending = $("#table-pending").DataTable({
                    "processing": true,
                    "serverSide": true,
                    "responsive": true,
                    "ajax": {
                        "url" : api_url + 'admin/pay-commission/payment-pending',
                        "data": function(d) {
                        },
                    },
                    order: [[ 0, 'desc' ]],
                    columns     : [
                        {data    : 'id'},
                        {data    : 'prepared_by'},
                        {data    : 'created_at'},
                        {data    : 'status'},
                        {
                            data: null,
                            orderable: false,
                            width: '10px',
                            render: function(data, type, full) {

                                let link = vm.download(data.id);

                                return `
                                    <button style="margin-bottom: 5px;" title="View Details" type= "button" class="btn btn-view-details btn-action btn-sm btn-primary">View</button>
                                    <!-- <button style="margin-bottom: 5px;" title="Undo Payment" type= "button" class="btn btn-undo btn-action btn-sm btn-danger">Undo</button> -->
                                    <a style="margin-bottom: 5px; text-transform: none;" title="Download CSV" target="_blank" href="${link}" class="btn btn-download btn-action btn-sm btn-warning">Download</a>
                                    <button style="margin-bottom: 5px;" title="Upload CSV" type= "button" class="btn btn-upload-csv btn-action btn-sm btn-success">Upload</button>
                                `;
                            }

                        },
                    ]
                });
            },
            initializeDataTablesEvents() {

                $('#table-main tbody').on('change', '.row-selected', function () {
                    let data = $dt.row($(this).parents('tr')).data();

                    if(data.username === null) return;

                    if($(this).is(':checked')) {
                        vm.selectedIds.push(data.user_id);
                    } else {
                        vm.selectedIds = vm.selectedIds.filter(ids => ids !== data.user_id);
                    }
                });

                $('#table-history tbody').on('click', '.btn-view-details', function () {
                    let data = $dtHistory.row($(this).parents('tr')).data();
                    vm.clearLog();

                    if(data.status === 'RUNNING')
                    {
                        vm.startLog();
                        console.log("Started");
                    }

                    vm.showPayment(data);
                });

                $('#table-pending tbody').on('click', '.btn-view-details', function () {
                    let data = $dtPending.row($(this).parents('tr')).data();
                    vm.showPayment(data);
                });

                $('#table-main tbody').on('click', '.btn-mark-as-paid', function () {
                    let data = $dt.row($(this).parents('tr')).data();
                    vm.markAsPaid(data.user_id);
                });

                $('#table-pending tbody').on('click', '.btn-upload-csv', function () {
                    let data = $dtPending.row($(this).parents('tr')).data();
                    vm.showCsvUpload(data);
                });

                $('#table-pending tbody').on('click', '.btn-undo', function () {
                    let data = $dtPending.row($(this).parents('tr')).data();
                    vm.cancelUpload(data);
                });

            },
            getCommissionTypes() {

                $("#commission-type").prop('disabled', true);
                $("#commission-type").html(null).trigger('change');

                client.get("common/commission-types/active-cash-manual").then(response => {

                    let data = response.data;

                    for (let i = 0; i < data.length; i++) {
                        let option = new Option(data[i].name, data[i].id, false, false);
                        $("#commission-type").append(option).trigger('change');
                    }

                    $("#commission-type").removeAttr('disabled');
                })
                    .catch(error => {

                    })
            },
            getCommissionPeriods(type) {

                if(!type) return;

                $("#commission-period").prop('disabled', true);
                $("#commission-period").html(null).trigger('change');

                client.get(`admin/pay-commission/locked-periods?ids=${type.join(',')}`).then(response => {
                    let data = response.data;
                    $("#commission-period").html(null).trigger('change');

                    for (let i = 0; i < data.length; i++) {
                        let option = new Option(data[i].text, data[i].id, false, false);
                        $("#commission-period").append(option).trigger('change');
                    }

                    $("#commission-period").removeAttr('disabled');

                }).catch(error => {

                });
            },
            viewPayouts() {
                // $('#new-generated-link').html("");
                $dt.clear().draw();

                this.selectedIds = [];
                this.is_check_all = false;
                // this.period_ids = $("#commission-period").val() === null ? [] : $("#commission-period").val();

                // if(this.period_ids.length == 0) return;

                this.getPayouts();
            },
            toggleCheckAll() {
                if(this.is_check_all) {
                    this.selectedIds = [];
                    let v = this;
                    $('.row-selected[type="checkbox"]').each((i, c) => {
                        let data = $dt.row($(c).parents('tr')).data();
                        if(!!data.username)  {
                            v.selectedIds.push(data.user_id);
                            $(c).prop('checked', true);
                        }
                    });
                } else {
                    this.selectedIds = [];
                    $('.row-selected[type="checkbox"]').prop('checked', false);
                }
            },
            getTotal(callback) {
                
                this.is_processing = 1

                client.post('admin/pay-commission/total', {ids: this.selectedIds.join(",")}).then(response => {
                    let data = response.data;

                    callback({
                        succeeded: true,
                        total: data.total
                    });
                }).catch(error => {
                    callback({
                        succeeded: false,
                    });
                }).finally(() => {
                    this.is_processing = 0;
                });
            },
            pay() {

                if(this.is_processing === 1 || this.selectedIds.length === 0) return;

                this.getTotal(data => {

                    if(!data.succeeded) {
                        return;
                    }

                    swal({
                        title: "Generate CSV",
                        text: "Total: " + data.total,
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "Confirm",
                        cancelButtonText: "Cancel",
                        showCancelButton: true,
                        closeOnConfirm: false,
                        showLoaderOnConfirm: true,
                    }, () => {

                        this.is_processing = 1;

                        client.post('admin/pay-commission/start', {
                            ids: this.selectedIds.join(","),
                            user_id: $('#member').val(),
                            period_ids: this.period_ids.join(",")
                        }).then(response => {
                            this.history = response.data;

                            this.clearLog();
                            this.startLog();

                            swal.close();
                            this.showPayment(response.data);

                            client.post('admin/pay-commission/pay', {
                                ids: this.selectedIds.join(","),
                                user_id: $('#member').val(),
                                period_ids: this.period_ids.join(","),
                                history_id: this.history.id
                            }).then(response => {
                                this.clearError();

                            }).catch(error => {
                                this.stopLog();
                                this.axiosErrorHandler(error); //swal('Something went wrong!','','error');
                            })

                        }).catch(error => {
                            this.stopLog();
                            this.axiosErrorHandler(error); //swal('Something went wrong!','','error');
                        });

                    });
                });

            },
            showPayment(history) {
                this.history = history;
                $dtPaymentDetails.draw();
                $('#modal-view-details').modal({backdrop: 'static', keyboard: false});
            },
            showCsvUpload(history) {
                this.history = history;
                this.$refs.csvFile.value = null;
                $('#modal-csv-upload').modal({backdrop: 'static', keyboard: false});
            },
            upload() {
                let files = this.$refs.csvFile.files;

                if(!files.length) {
                    swal("Payout file is required.", "", "error");
                    return;
                }

                let formData = new FormData();
                formData.append('csv_file', files[0]);

                if(this.is_processing === 1) return;

                swal({
                    title: "Upload Payout file",
                    text: "Are you sure you want to upload this payout file?",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.is_processing = 1;

                    client.post(`admin/pay-commission/upload-csv/${this.history.id}`, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    }).then(response => {
                        console.log(response.data);
                        this.is_processing = 0;

                        $dtPending.draw();
                        $dtHistory.draw();
                        this.$refs.csvFile.value = null;
                        $('#modal-csv-upload').modal('hide');
                        swal('Success','','success');
                    }).catch(this.axiosErrorHandler);

                });

            },
            cancelUpload(history) {
                this.history = history;

                if(this.is_processing === 1) return;

                swal({
                    title: "Cancel Upload",
                    text: "Are you sure you want to UNDO the upload process?",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.is_processing = 1;

                    client.post(`admin/pay-commission/cancel-upload/${this.history.id}`).then(response => {
                        console.log(response.data);
                        this.is_processing = 0;

                        this.getPayouts();
                        $dtPending.draw();
                        $dtHistory.draw();

                        swal('Success','','success');
                    }).catch(this.axiosErrorHandler);

                });

            },
            getPayouts() {
                // $('#new-generated-link').html('<p class="h4"><i class="fa fa-spinner fa-pulse fa-fw"></i> Generating CSV...</p>');

                client.get(`admin/pay-commission/payouts?ids=${this.period_ids.join(",")}`).then(response => {
                    let data = response.data;
                    $dt.clear().draw();

                    // if(!+data.has_report) {
                    //     $('#new-generated-link').html("");
                    //     return;
                    // }

                    // $('#new-generated-link').html(`<a class="btn btn-success" href="${data.link}">Download</a>`);
                    $dt.rows.add(data.payouts).draw();

                }).catch(error => {
                    // $('#new-generated-link').html("");
                });
            },
            startLog() {
                this.fetchLog();
                if(this.logInterval !== null) {
                    this.stopLog();
                }
                this.logInterval = setInterval(this.fetchLog, 1000 * 10);
            },
            fetchLog() {
                if(this.history == null || this.history.id === undefined) {
                    this.clearLog();
                    return;
                }

                if(this.history.status !== 'RUNNING' && this.history.status !== 'PENDING') {
                    this.processDone();
                    return;
                }

                // if(this.seek !== 0 && this.seek === this.lastSeek) return;

                // this.lastSeek = this.seek;

                client.get(`admin/pay-commission/log/${this.history.id}?seek=${this.seek}`).then(response => {
                    let result = response.data;

                    this.history = result.history;

                    if(this.history.status !== 'RUNNING' && this.history.status !== 'PENDING') {
                        this.processDone();
                    }

                    if(this.seek != result.seek) {
                        this.lines = [...this.lines, ...result.lines];
                        this.seek = result.seek;
                    }
                });
            },
            stopLog() {
                clearInterval(this.logInterval);
                this.logInterval = null;
            },
            clearLog() {
                this.stopLog();
                this.seek = 0;
                this.lines = [];
            },
            processDone() {
                this.stopLog();
                $dtPaymentDetails.draw();
                this.is_processing = 0;
                swal('Success','','success');
                this.selectedIds = [];
                this.is_check_all = false;
                this.getPayouts();
                $dtHistory.draw();
                $dtPending.draw();
            },
            axiosErrorHandler(error) {

                this.is_processing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                this.error.message = parse.message;
                this.error.type = parse.type;
                this.error.data = parse.data;

                swal(this.error.message, "", "error");
            },
            clearError() {
                this.error.message = null;
                this.error.type = null;
                this.error.data = null;
            },
            markAsPaid(user_id) {
                if(this.is_processing === 1) return;

                swal({
                    title: "Mark as paid",
                    text: "Are you sure you want to manually mark this as paid? The payout will NOT be included in the CSV file.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.is_processing = 1;

                    client.post(`admin/pay-commission/mark-as-paid`, {
                        ids: user_id,
                        user_id: $('#member').val()
                    }).then(response => {
                        this.selectedIds = this.selectedIds.filter(ids => ids !== user_id);
                        
                        this.getPayouts();
                        this.clearError();
                        this.is_processing = 0;
                        swal('Success','','success');
                        
                    }).catch(this.axiosErrorHandler);

                });
            },
            download(id) {
                return `${api_url}admin/pay-commission/download/${id}?token=${commissionEngine.ACCESS_TOKEN}`
            }
        },
        computed: {
            progress() {
                return this.lines.filter(l => l.indexOf("          ") !== -1).length;
            },
            progressPercentage() {
                if(this.history === null) return '0%';

                if(this.history.status === 'COMPLETED' || this.history.status === 'CANCELLED' || this.history.status === 'PENDING_UPLOAD') return '100%';

                let percentage = (this.progress / this.history.pay_count) * 100;

                if(percentage >= 99.99) {
                    percentage = 99.99;
                }

                return percentage.toFixed(2) + '%';
            }
        },
    });

    window.onbeforeunload = function() {
        if (vm.is_processing) {
            return "Do you really want to leave? Pay commission is currently processing";
        } else {
            return;
        }
    };

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));