(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dt;

    let $dtHistory;
    let $dtPaymentDetails;

    const vm = new Vue({
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
                },
                is_check_all: false,
                seek: 0,
                lastSeek: 0,
                lines: [],
                logInterval: null,
            };
        },
        mounted() {
            this.initializeSelect2();
            this.initializeDataTables();
            this.initializeDataTablesEvents();
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
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search",
                        paginate: {
                            next: 'Next',
                            previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    paging: true,
                    columns     : [
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
                        {data    : 'amount'},
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
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search",
                        paginate: {
                            next: 'Next',
                            previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    "processing": true,
                    "serverSide": true,
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

            },
            initializeDataTablesEvents() {

                $('#table-main tbody').on('change', '.row-selected', function () {
                    let data = $dt.row($(this).parents('tr')).data();

                    if(data.username === null) return;

                    if($(this).is(':checked')) {
                        vm.selectedIds.push(data.ids);
                    } else {
                        vm.selectedIds = vm.selectedIds.filter(ids => ids !== data.ids);
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

                $('#table-main tbody').on('click', '.btn-mark-as-paid', function () {
                    let data = $dt.row($(this).parents('tr')).data();
                    vm.markAsPaid(data.ids);
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
                $('#new-generated-link').html("");
                $dt.clear().draw();

                this.selectedIds = [];
                this.is_check_all = false;
                this.period_ids = $("#commission-period").val() === null ? [] : $("#commission-period").val();

                if(this.period_ids.length == 0) return;

                this.getPayouts();
            },
            toggleCheckAll() {
                if(this.is_check_all) {
                    this.selectedIds = [];
                    let v = this;
                    $('.row-selected[type="checkbox"]').each((i, c) => {
                        let data = $dt.row($(c).parents('tr')).data();
                        if(!!data.username)  {
                            v.selectedIds.push(data.ids);
                            $(c).prop('checked', true);
                        }
                    });
                } else {
                    this.selectedIds = [];
                    $('.row-selected[type="checkbox"]').prop('checked', false);
                }
            },
            getTotal(callback) {
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
                });
            },
            pay() {

                if(this.is_processing === 1 || this.selectedIds.length === 0) return;

                this.getTotal(data => {
                    if(!data.succeeded) {
                        // swal('Something went wrong!','','error');
                        return;
                    }

                    swal({
                        title: "Confirm Commission Pay",
                        text: "Total: $ " + data.total,
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
            getPayouts() {
                $('#new-generated-link').html('<p class="h4"><i class="fa fa-spinner fa-pulse fa-fw"></i> Generating CSV...</p>');

                client.get(`admin/pay-commission/payouts?ids=${this.period_ids.join(",")}`).then(response => {
                    let data = response.data;
                    $dt.clear().draw();

                    if(!+data.has_report) {
                        $('#new-generated-link').html("");
                        return;
                    }

                    $('#new-generated-link').html(`<a class="btn btn-success" href="${data.link}">Download</a>`);
                    $dt.rows.add(data.payouts).draw();

                }).catch(error => {
                    $('#new-generated-link').html("");
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
            },
            axiosErrorHandler(error) {

                this.is_processing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data)

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
            markAsPaid(payout_ids) {
                if(this.is_processing === 1) return;

                swal({
                    title: "Mark as paid",
                    text: "Are you sure you want to manually mark this as paid? Payment will NOT be sent to member's PayQuicker account.",
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
                        ids: payout_ids,
                        user_id: $('#member').val()
                    }).then(response => {
                        this.clearError();
                        this.is_processing = 0;
                        swal('Success','','success');
                        this.selectedIds = this.selectedIds.filter(ids => ids !== payout_ids);
                        this.getPayouts();
                    }).catch(this.axiosErrorHandler);

                });
            },
        },
        computed: {
            progress() {
                return this.lines.filter(l => l.indexOf("          ") !== -1).length;
            },
            progressPercentage() {
                if(this.history === null) return '0%';

                if(this.history.status === 'COMPLETED' || this.history.status === 'CANCELLED') return '100%';

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