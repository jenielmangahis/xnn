(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    let $dtLogs;
    let $dt;
	let $payouts;
	
	let boxes_checked = 0;

    const vm = new Vue({
        el: "#app-clawback",
        data: function () {
            return {

                autocompleteUrl: `${api_url}common/autocomplete/members`,
                autocompletePodUrl: `${api_url}common/autocomplete/pods`,

                filters: {
                    startDate: null,
                    endDate: null,
                    memberId: null,
                    pod: null,
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


				dtPayouts: null,
				
				boxes_checked: 0
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
					/*
                     ajax: {
                         "url": api_url + 'admin/clawback/pea',
                         "data": function (d) {
                             d.startDate = $("#start-date").val();
                             d.endDate = $("#end-date").val();
                             d.memberId = _this.filters.memberId;
                         },
					},
					*/
                    ajax: {
						"url": api_url + 'admin/clawback/pea',
						"data": function (d) {
							d.startDate = $("#start-date").val();
							d.endDate = $("#end-date").val();
							d.memberId = _this.filters.memberId;
							d.pod = _this.filters.pod;
						},
                    },
                    columns: [
                        {data: 'reference_id'},
                        {data: 'associate_id'},
                        {data: 'associate_name'},
                        {data: 'customer_name'},
                        {data: 'account_type'},
                        {data: 'date_accepted'},
                        {data: 'date_started_flowing'},
                        {data: 'status'},
                        {
                            width: '200px',
                            render: function (data, type, row, meta) {
                                return '<div class="btn-group-xs" role="group" aria-label="...">' +
                                    '<button ' + ((+row.is_clawback && +row.is_per_product) ? 'disabled' : '') + ' type="button" class="btn btn-danger btn-order-refund" data-userid="'+row.transaction_id+'" style="margin-right: 5px;">Clawback</button>' +
                                    '</div>';
                            }
                        },
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
					],
					drawCallback: function( settings ) {
						$('.btn-order-refund').on('click', function () {
							$payouts.ajax.url(api_url + 'admin/clawback/payouts?user_id='+$(this).data('userid')+'&startDate='+$("#start-date").val()+'&endDate='+$("#end-date").val()).load(); 
							$('#modal-refund-order').modal('show');
							//_this.showOrder(data);
						});
					}
				});
				
				$payouts = $("#user-payouts").DataTable({
					ajax: {
						"url": api_url + 'admin/clawback/payouts?user_id=&startDate=&endDate=',
					   },
					columns     : [
						/*
						{
							data    : 'associate',
							width: '33px',
							sortable: false,
							render: function ( data, type, row ) {
								return '<div class="custom-control custom-checkbox">'+
									'<input type="checkbox" class="custom-control-input" item-id="'+data+'" data-userid="'+data+'">'+
									'<span class="custom-control-indicator"></span>'+
									'</div>';
							}
						},
						*/
						{data    : 'associate'},
						{data    : 'associate_name'},
						{data    : 'commission_type'},
						{data    : 'commission_period'},
						{data    : 'commission_value'},
						{data    : 'amount'},
						{data    : 'date_clawed'},
						{
							data    : 'amount',
							render: function (data, type, row, meta) {
								return '<input disabled type="number" id="row-'+row.commission_payout_id+'-amount" value="'+row.commission_value+'">';
							}
						},
						{
							render: function (data, type, row, meta) {
								return '<button type="button" class="btn btn-primary btn-refund" data-id="'+row.commission_payout_id+'" data-transactionid="'+row.transaction_id+'">Clawback</button>';
							}
						}
					],
					drawCallback: function( settings ) {
						$('#user-payouts td').on('click', '.custom-checkbox', function (event) {
							var checkbox = $(this).find('.custom-control-input');
							if (checkbox.is(":checked")) {
								checkbox.prop('checked', true);
								boxes_checked++;
							} else {
								checkbox.prop('checked', false);
								boxes_checked--;
							}
							if (boxes_checked === 1) {
								$('#btn-save-refund-commission').removeClass('disabled');
							} else if (boxes_checked === 2 || boxes_checked > 2) {
								$('#btn-save-refund-commission').removeClass('disabled');
							} else {
								$('#btn-save-refund-commission').addClass('disabled');
							}
						});

						$('.btn-refund').on('click', function () {
							_this.error.message = '';
							_this.onSubmitRefund($(this).attr('data-id'), $(this).attr('data-transactionid'));
						});
					}
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
            onSubmitRefund: function (commission_payout_id, transaction_id){

                var $this = this;

                swal({
                        title: "Are you sure you want to clawback/refund Payout ID " + commission_payout_id + "?",
                        text: "You cannot undo this.",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Confirm Refund",
                        closeOnConfirm: false,
                        showLoaderOnConfirm: true,
                    },
                    function () {

						$.post(api_url + 'admin/clawback/refund-order', {
							payout_id : commission_payout_id,
							amount: $('#row-' + commission_payout_id + '-amount').val(),
							type: 'commission',
							set_user_id: $('#member').val(),
							transaction_id: transaction_id
						}, function (result) {
                            console.log(result);
                            $('#modal-refund-order').modal('hide');
                            swal("Successfully added for clawback/refund!", "", "success");
                        }, 'json').fail(function (xhr, status, error) {
							console.log(xhr.responseJSON);
                            if(xhr.responseJSON.message != undefined && xhr.responseJSON.message == "Validation error") {
                                let errors = xhr.responseJSON.errors;
                                $this.error.message = errors[Object.keys(errors)[0]][0];
                            } else if(xhr.responseJSON.error != undefined && typeof xhr.responseJSON.error === "string") {
                                $this.error.message = xhr.responseJSON.error;
                            } else if(xhr.responseJSON.message != undefined) {
                                $this.error.message = xhr.responseJSON.message;
                            } else {
                                $this.error.message = xhr.responseJSON.error.message;
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
                }, () => {

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