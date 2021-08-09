(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    let $dtLogs;
    let $dt;

    const vm = new Vue({
        el: "#app-clawback",
        data: function () {
            return {

                autocompleteUrl: `${api_url}common/autocomplete/members`,

                filters: {
                    startDate: null,
                    endDate: null,
                    memberId: null,
                },
                products: [],
                transaction_id: null,
                is_clawback: 0,

                order: {
                    order_id: null,
                    purchaser_id: null,
                    purchaser: null,
                    sponsor_id: null,
                    sponsor: null,
                    sub_total: null,
                    transaction_date: null,
                    tax: null,
                    shipping_fee: null,
                    total: null,
                    percentage_off: null,
                    amount_off: null,
                    commission_value: null,
                    is_full_order: null,
                    new_purchaser_id: null,
                    is_clawback: 0
                },

                error: {
                    message : null,
                },

                is_saving: 0,

            };
        },
        mounted() {
            this.initializeDataTables();
            this.initializeDatePicker();
            this.initializeJQueryEvents();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                $dt = $("#table-orders").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 25,
                    ajax: {
                        "url": api_url + 'admin/clawback/',
                        "data": function (d) {
                            d.startDate = $("#start-date").val();
                            d.endDate = $("#end-date").val();
                            d.memberId = _this.filters.memberId;
                        },
                    },
                    columns: [
                        {data: 'order_id'},
                        {data: 'invoice'},
                        {data: 'purchaser', className: 'text-left text-capitalize'},
                        {data: 'description', className: 'text-left text-capitalize'},
                        {data: 'transaction_date'},
                        {data: 'commission_value'},
                        {data: 'amount_paid'},
                        {data: 'set_by', className: 'text-left'},
                        {
                            data: 'action',
                            width: '200px',
                            render: function (data, type, row, meta) {
                                return '<div class="btn-group-xs" role="group" aria-label="...">' +
                                    '<button ' + ((+row.is_clawback && +row.is_per_product) ? 'disabled' : '') + ' type="button" class="btn btn-danger btn-order-refund" style="margin-right: 5px;">Refund</button>' +
                                    '<button ' + ((+row.is_clawback && !+row.is_per_product) ? 'disabled' : '') + ' type="button" class="btn btn-info btn-view-items" style="margin-right: 5px;">Refund By Items</button>' +
                                    '<button type="button" class="btn btn-warning btn-order-move">Move</button>' +
                                    '</div>';
                            }
                        },
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                    ]
                });

                $dtLogs = $("#table-logs").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url : api_url + 'admin/move-order/logs',
                        data: function(d) {

                        },
                    },
                    order: [[ 5, 'desc' ]],
                    columns     : [
                        {data    : 'order_id'},
                        // {data    : 'new_purchaser'},
                        {
                            data: 'new_purchaser',
                            render: function ( data, type, row, meta ) {

                                if(row.new_user_id == row.old_user_id)
                                {
                                    return '<span class="label label-info">No changes</span>';
                                }

                                return data;
                            }
                        },
                        // {data    : 'new_sponsor'},
                        {data    : 'old_purchaser'},
                        // {data    : 'old_sponsor'},
                        // {data    : 'new_transaction_date'},
                        {
                            data: 'new_transaction_date',
                            render: function ( data, type, row, meta ) {

                                if(row.old_transaction_date == row.new_transaction_date)
                                {
                                    return '<span class="label label-info">No changes</span>';
                                }

                                return data;
                            }
                        },
                        {data    : 'old_transaction_date'},
                        {data    : 'created_at'},
                        {data    : 'changed_by'},
                    ]
                });
            },
            initializeDatePicker() {
                let _this = this;

                $('#start-date').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#end-date').ddatepicker('setStartDate', e.date);

                    if ($('#end-date').ddatepicker('getDate') < e.date) {
                        $('#end-date').ddatepicker('setDate', e.date);
                    }
                });

                $('#end-date').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                });

                $('#start-date').ddatepicker('setDate', new Date());
                $('#end-date').ddatepicker('setDate', new Date());

                // Transaction Date
                $('#transaction-date').datetimepicker({
                    defaultDate: moment(),
                    format: 'YYYY-MM-DD HH:mm:ss',
                });

                $('#transaction-date').on('dp.change', e => {_this.order.transaction_date = $('#transaction-date').val()});
            },
            initializeJQueryEvents() {

                let _this = this;

                $('#button-view-orders').on('click', function () {
                    $dt.clear().draw();
                    $dt.responsive.recalc();
                });

                $('#table-orders tbody').on('click', '.btn-order-refund', function () {
                    let data = $dt.row($(this).parents('tr')).data();
                    _this.showOrder(data);
                });

                $('#table-orders tbody').on('click', '.btn-view-items', function () {
                    let data = $dt.row($(this).parents('tr')).data();
                    _this.getProducts(data.order_id, data.is_clawback, data);
                });

                $('#table-orders tbody').on('click', '.btn-order-move', function () {
                    let data = $dt.row($(this).parents('tr')).data();
                    _this.showMoveOrder(data);
                });
            },
            readonlyProduct: function(order) {
                return +order.is_clawback && !+order.is_per_product;
            },
            formatMoney: function(value) {
                if(value == null || value == undefined) return 0.00;

                return parseFloat(value).toFixed(2);
            },
            changeIsFullOrder: function(event) {
                if(this.order.is_full_order) {
                    this.order.amount_off = 0;
                    this.order.percentage_off = 0;
                }
            },
            keyupAmount: function(event) {
                this.order.percentage_off = null;
            },
            keyupPercent: function(event) {
                this.order.amount_off = null;
            },
            showOrder: function(data) {
                this.error.message = null;

                this.order = JSON.parse(JSON.stringify(data));
                this.order.amount_off = data.amount_off ? data.amount_off : data.commission_value;
                // this.order.is_full_order = data.is_full_order;
                $('#modal-refund-order').modal('show');
            },
            getProducts: function (transaction_id, is_clawback, order) {

                this.order = JSON.parse(JSON.stringify(order));
                this.transaction_id = transaction_id;

                client.get(`admin/clawback/order-products/${transaction_id}`).then(response => {
                    this.products = response.data;
                    this.is_clawback = is_clawback;
                    this.error.message = null;
                    $('#modal-order-items').modal('show');
                }).catch(error => {
                    swal('Unable to fetch!','','error');
                });
            },

            p: function (index, name) {
                return 'products[' + index + '].' + name;
            },
            onSubmit: function (type) {

                // if(this.is_clawback) return;

                let _this = this;
                swal({
                    title: "Are you sure you want to clawback/refund Order ID " + this.transaction_id + " - Items? " + (type == 'commission' ? "(Commission Only)" :"(Merchant & Commission)"),
                    text: "You cannot undo this.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Confirm Refund",
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, function () {

                    client.post(`admin/clawback/refund-order-products`, {
                        products : _this.products,
                        transaction_id: _this.transaction_id,
                        set_user_id: $('#member').val(),
                        type: type
                    }).then(response => {

                        $dt.clear().draw();
                        $dt.responsive.recalc();

                        _this.products = [];
                        _this.transaction_id = null;

                        $('#modal-order-items').modal('hide');
                        swal("Successfully added for clawback/refund!", "", "success");
                    }).catch(this.axiosErrorHandler).finally(()=> {

                    });

                });
            },
            onSubmitRefund: function (type){

                if(this.order.is_clawback) return;

                var $this = this;
                let $form = $('#form-refund-order');

                swal({
                        title: "Are you sure you want to clawback/refund Order ID " + $('#form-refund-order [name="transaction_id"]').val() + "? " + (type == 'commission' ? "(Commission Only)" :"(Merchant & Commission)"),
                        text: "You cannot undo this.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Confirm Refund",
                        closeOnConfirm: false,
                        showLoaderOnConfirm: true,
                    },
                    function () {

                        // if (!proceed) {
                        //     swal.close();
                        //     return;
                        // }
                        console.log($form.serialize() + "&set_user_id=" + $('#member').val() + "&type=" + type);
                        $.post(api_url + 'admin/clawback/refund-order', $form.serialize() + "&set_user_id=" + $('#member').val() + "&type=" + type, function (result) {
                            console.log(result);
                            $('#modal-refund-order').modal('hide');
                            $dt.clear().draw();
                            $dt.responsive.recalc()
                            $this.error.message = null;
                            $this.order = {
                                order_id: null,
                                purchaser: null,
                                sub_total: null,
                                tax: null,
                                shipping_fee: null,
                                total: null,
                                percentage_off: null,
                                amount_off: null,
                                commission_value: null,
                                is_full_order: null,
                                is_clawback: 0
                            };
                            swal("Successfully added for clawback/refund!", "", "success");
                        }, 'json').fail(function (xhr, status, error) {
                            if(xhr.responseJSON.message != undefined && xhr.responseJSON.message == "Validation error") {
                                let errors = xhr.responseJSON.errors;
                                $this.error.message = errors[Object.keys(errors)[0]][0];
                            } else if(xhr.responseJSON.error != undefined && typeof xhr.responseJSON.error === "string") {
                                $this.error.message = xhr.responseJSON.error;
                            } else if(xhr.responseJSON.message != undefined) {
                                $this.error.message = xhr.responseJSON.message;
                            } else {
                                $this.error.message = 'Something went wrong!';
                            }
                            swal.close();
                        });

                    });
            },
            showMoveOrder(order) {
                this.order.invoice = order.invoice;
                this.order.order_id = order.order_id;
                this.order.purchaser = order.purchaser;
                this.order.purchaser_id = order.purchaser_id;
                this.order.sponsor = order.sponsor;
                this.order.sponsor_id = order.sponsor_id;
                this.order.transaction_date = order.transaction_date;
                this.order.new_purchaser_id = null;
                this.error.message = null;

                $('#modal-move').modal({backdrop: 'static', keyboard: false});
            },
            save() {
                swal({
                    title: `Are you sure you want to update Order ID ${this.order.order_id}?`,
                    // text: "You cannot undo this.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, function () {

                    this.is_saving = 1;

                    client.post(`admin/move-order/orders/${this.order.order_id}/change`, {modified: $('#member').val(), ...this.order}).then(response => {

                        $('#modal-move').modal('hide');
                        // swal.close();
                        swal("Successfully updated!", "", "success");
                        $dt.clear().draw();
                        $dt.responsive.recalc();
                        $dtLogs.clear().draw();
                        $dtLogs.responsive.recalc();

                    }).catch(this.axiosErrorHandler).finally(()=> {

                        this.is_saving = 0;
                    });

                });
            },
            axiosErrorHandler(error) {

                let data = commissionEngine.parseAxiosErrorData(error.response.data);

                this.error.message = data.message;
                this.error.type = data.type;
                this.error.data = data.data;

                swal(this.error.message, "", "error");
            },
        },
        computed: {
            displayPurchaser() {
                return this.order.purchaser_id + ": " + this.order.purchaser;
            },
            displaySponsor() {
                return this.order.sponsor_id + ": " + this.order.sponsor;
            }
        }
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));