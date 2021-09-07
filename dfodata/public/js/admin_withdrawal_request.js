(function ($, api_url, Vue, swal, axios, location, moment, _, undefined) {

    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    const client = commissionEngine.createAccessClient('admin/ledger-withdrawal');
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dt = null;
    let $dtHistory = null;
    let $dtPaymentDetails = null;

    const vm = new Vue({
        el: "#withdrawal-request",

        data: {
            selected_ids: [],
            is_processing: 0,
            error: {
                message: null,
                type: null,
            },
            start_date: null,
            end_date: null,
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
            lines: [],
            log_interval: null,
        },
        mounted() {
            this.initializeDataTables();
            this.initializeDatePicker();
            this.initializeJQueryEvents();
        },
        view() {
            this.start_date = $('#start_date').val();
            this.end_date = $('#end_date').val();
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
        methods: {
            initializeDataTables() {

                let _this = this;

                $dt = $("#table-main").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    },
                    paging: false,
                    searching: false,
                    order: [[ 1, 'asc' ]],
                    autoWidth: false,
                    columns     : [
                        {
                            data: 'is_selected',
                            width: '10x',
                            orderable: false,
                            className: 'table__cell--text-center table__cell--align-middle',
                            render: function (data, type, full, meta){

                                if(full.username === null)
                                {
                                    return `<input class="row-selected" disabled readonly type="checkbox" name="id[]" value="1">`
                                }

                                return `<div class="checkbox"><input class="row-selected"${_this.selected_ids.includes(full.ids) ? "checked" : ""} type="checkbox" name="id[]" value="1"><label>&nbsp;</label></div>`;
                            }
                        },
                        {data: 'date'},
                        {data: 'user_id'},
                        {
                            data: 'name',
                            className: "table__cell--align-middle",
                            render: function(data, type, full) {

                                if(full.username === null)
                                {
                                    return `<span class="label label-danger">NO ACCOUNT</span> <span>${data}</span>`;
                                }

                                return data;
                            }
                        },
                        {data: 'amount', className: "table__cell--text-center", render: $.fn.dataTable.render.number( ',', '.', 2, '$' )},
                        {
                            width: '10px',
                            className: "table__cell--align-middle table__cell--text-center",
                            render: function(data, type, full) {
                                return '<button class="btn btn-info btn-reject btn-sm">Reject</button>';
                            }
                        },
                    ],

                });

                $dtHistory = $("#table-history").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    },
                    "processing": true,
                    "serverSide": true,
                    "ajax": {
                        "url" : `${api_url}admin/ledger-withdrawal/payment-history`,
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
                            orderable: false,
                            className: 'text-center',
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
                        "url" : `${api_url}admin/ledger-withdrawal/payment-details`,
                        "data": function(d) {
                            d.history_id = _this.history.id;
                        },
                    },
                    order: [[ 0, 'desc' ]],
                    columns     : [
                        {data    : 'accounting_id'},
                        {data    : 'reference_no'},
                        {data    : 'member'},
                        {data    : 'username'},
                        {data    : 'amount', className: "text-right", render: $.fn.dataTable.render.number( ',', '.', 2, '$' )},
                        {data    : 'status'},
                    ]
                });

            },
            initializeDatePicker() {
                let _this = this;

                $('#start_date').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#end_date').ddatepicker('setStartDate', e.date);

                    if ($('#end_date').ddatepicker('getDate') < e.date) {
                        $('#end_date').ddatepicker('setDate', e.date);
                    }
                });

                $('#end_date').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                });

                $('#start_date').ddatepicker('setDate', new Date());
                $('#end_date').ddatepicker('setDate', new Date());
            },
            initializeJQueryEvents() {
                let _this = this;

                $('#table-main tbody').on('change', '.row-selected', function () {
                    let data = $dt.row($(this).parents('tr')).data();

                    if(data.username === null) return;

                    if($(this).is(':checked')) {
                        _this.selected_ids.push(data.ids);
                    } else {
                        _this.selected_ids = _this.selected_ids.filter(ids => ids !== data.ids);
                    }

                    if(_this.selected_ids.length === 0) {
                        _this.is_check_all = false;
                    }
                });

                $('#table-main tbody').on('click', '.btn-reject', function () {
                    let data = $dt.row($(this).parents('tr')).data();

                    _this.rejectRequest(data.ids);
                });

                $('#table-history tbody').on('click', '.btn-view-details', function () {
                    let data = $dtHistory.row($(this).parents('tr')).data();
                    _this.clearLog();

                    _this.showPayment(data);

                    if(data.status === 'RUNNING')
                    {
                        _this.startLog();
                    }
                });
            },
            toggleCheckAll() {
                if(this.is_check_all) {
                    this.selected_ids = [];
                    $('.row-selected[type="checkbox"]').each((i, c) => {
                        let data = $dt.row($(c).parents('tr')).data();
                        if(!!data.username)  {
                            this.selected_ids.push(data.ids);
                            $(c).prop('checked', true);
                        }

                    });
                } else {
                    this.selected_ids = [];
                    $('.row-selected[type="checkbox"]').prop('checked', false);
                }
            },
            viewRequest() {
                $dt.clear().draw();
                this.selected_ids = [];
                this.is_check_all = false;

                if(this.is_processing) return;

                this.is_processing = 1;

                client.get(`pending?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.is_processing = 0;
                    $dt.rows.add(response.data).draw();
                }).catch(this.axiosErrorHandler);
            },
            approveRequest() {

                if(this.is_processing) return;

                swal({
                    title: "Are you sure you want to approve these requests?",
                    text: "Money will be sent to the member's eWallet.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.is_processing = 1;

                    client.post("start", {
                        ids: this.selected_ids
                    }).then(response => {
                        this.history = response.data;
                        this.clearLog();
                        this.startLog();
                        swal.close();
                        this.showPayment(response.data);

                        client.post("pay", {
                            history_id: this.history.id
                        }).then(response => {
                            this.error.message = null;
                            this.error.type = null;
                            this.fetchLog();
                        }).catch(error => {
                            this.axiosErrorHandler(error);
                            this.stopLog();
                        });

                    }).catch(this.axiosErrorHandler);

                });

            },
            showPayment(history) {
                this.history = history;
                $dtPaymentDetails.draw();
                $('#modal-view-details').modal({backdrop: 'static', keyboard: false});
            },
            startLog() {
                this.fetchLog();
                if(this.log_interval !== null) {
                    this.stopLog();
                }
                this.log_interval = setInterval(this.fetchLog, 1000 * 10);
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

                client.get(`log/${this.history.id}?seek=${this.seek}`)
                    .then(response => {

                        let data = response.data;
                        this.history = data.history;

                        if(this.history.status !== 'RUNNING' && this.history.status !== 'PENDING') {
                            this.processDone();
                        }

                        if(this.seek != data.seek) {
                            this.lines = [...this.lines, ...data.lines];
                            this.seek = data.seek;
                        }
                    });
            },
            stopLog() {
                clearInterval(this.log_interval);
                this.log_interval = null;
            },
            clearLog() {
                this.stopLog();
                this.seek = 0;
                this.lines = [];
            },
            processDone() {
                this.stopLog();
                swal('Success','','success');
                $dtPaymentDetails.draw();
                this.is_processing = 0;
                this.selected_ids = [];
                this.is_check_all = false;
                this.viewRequest();
                $dtHistory.draw();
            },
            rejectRequest(ids) {
                if(this.is_processing === 1) return;

                swal({
                    title: "Reject Withdrawal Request",
                    text: "Are you sure you want to reject this request? The money will be returned to the member's ledger.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {
                    this.is_processing = 1;

                    client.post("reject", {
                        ids: ids
                    }).then(response => {
                        console.log(response.data);
                        this.error.message = null;
                        this.error.type = null;
                        this.is_processing = 0;
                        this.selected_ids = [];
                        this.is_check_all = false;
                        swal('Success','','success');
                        this.viewRequest();

                    }).catch(this.axiosErrorHandler);
                });
            },
            axiosErrorHandler(error) {
                this.is_processing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data)

                this.error = parse.message;

                swal(this.error, "", 'error');
            },

        },
    });

    window.onbeforeunload = function() {
        if (vm.is_processing) {
            return "Do you really want to leave? Pay commission is currently processing";
        } else {
            return;
        }
    };

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment, _));