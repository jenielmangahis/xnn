(function ($, api_url, Vue, swal, axios, location, moment, undefined) {
    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    const client = commissionEngine.createAccessClient('admin/dashboard');
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dt = null;
    let $dtCustomers = null;
    let $dtCustomersWithSubscription = null;
    let $dtIBO = null;
    let $dtIBOWithSubscription = null;
    let $pack_sales = null;
    let $gold_sales = null;
    let $platinum_sales = null;
    let $ibo_sales = null;
    let $endorsers = null;

    const vm = new Vue({
        el: '#dashboard',
        data: {
            new_customer_count: null,
            new_customer_with_product_subscription_count: null,
            new_ibo_count: null,
            new_ibo_with_product_subscription_count: null,
            platinum_package_sales: null,
            gold_package_sales: null,
            ibo_sales_only: null,
            average_reorder: null,
            error: null,
            start_date: null,
            end_date: null,
            is_downloading: false,
            report_type: "",
            is_downloading_top_endorser: false,
            top_endorser_count: 0,
            user_id: 0,
            pack_count: 0,
        },
        mounted() {
            let _this = this;
            $dt = $("#table-top-endorsers").DataTable({
                responsive: true,
                searching: false,
                ordering: false,
                lengthChange: false,
                columns: [
                    {data: 'user_id', className: "text-center"},
                    {data: 'ranking', className: "text-center"},
                    {data: 'endorser'},
                    {data: 'volume', className: "text-right"},
                ]
            });

            $("#table-top-endorsers").on('click', '.btn-view-endorsers', function () {
                let data = $dt.row($(this).parents('tr')).data();
                vm.viewEndorsers(+data.user_id);
            });

            $dtCustomers = $("#table-new-customers").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: `${api_url}admin/dashboard/new-customers`,
                    data: function (d) {
                        d.start_date = _this.start_date;
                        d.end_date = _this.end_date;
                    },
                },
                columns: [
                    {data: 'member_id', className: "text-center"},
                    {data: 'member'},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'sponsor_type'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'has_subscription'},
                    {data: 'cellphone'},
                ]
            });

            // $dtCustomersWithSubscription = $("#table-new-customers-with-subscription").DataTable({
            //     processing: true,
            //     serverSide: true,
            //     responsive: true,
            //     ajax: {
            //         url: `${api_url}admin/dashboard/new-customers-with-product-subscription`,
            //         data: function (d) {
            //             d.start_date = _this.start_date;
            //             d.end_date = _this.end_date;
            //         },
            //     },
            //     columns: [
            //         {data: 'member_id', className: "text-center"},
            //         {data: 'member'},
            //         {data: 'sponsor_id', className: "text-center"},
            //         {data: 'sponsor'},
            //         {data: 'sponsor_type'},
            //         {data: 'order_id'},
            //         {data: 'cv'},
            //         {data: 'amount_paid'},
            //         {data: 'has_subscription'},
            //         {data: 'cellphone'},
            //     ]
            // });

            $dtIBO = $("#table-new-ibo").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: `${api_url}admin/dashboard/new-ibo`,
                    data: function (d) {
                        d.start_date = _this.start_date;
                        d.end_date = _this.end_date;
                    },
                },
                columns: [
                    {data: 'member_id', className: "text-center"},
                    {data: 'member'},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'sponsor_type'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'has_subscription'},
                    {data: 'cellphone'},
                ]
            });

            $dtIBOWithSubscription = $("#table-new-ibo-with-subscription").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: `${api_url}admin/dashboard/new-ibo-with-product-subscription`,
                    data: function (d) {
                        d.start_date = _this.start_date;
                        d.end_date = _this.end_date;
                    },
                },
                columns: [
                    {data: 'member_id', className: "text-center"},
                    {data: 'member'},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'sponsor_type'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'has_subscription'},
                    {data: 'cellphone'},
                ]
            });

            $gold_sales = $("#table-gold-sales").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: `${api_url}admin/dashboard/sales/gold-package`,
                    data: function (d) {
                        d.start_date = _this.start_date;
                        d.end_date = _this.end_date;
                    },
                },
                columns: [
                    {data: 'purchaser_id', className: "text-center"},
                    {data: 'purchaser'},
                    {data: 'invoice', className: "text-center"},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'sponsor_type'},
                    {data: 'is_clawback'},
                ]
            });

            $platinum_sales = $("#table-platinum-sales").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: `${api_url}admin/dashboard/sales/platinum-package`,
                    data: function (d) {
                        d.start_date = _this.start_date;
                        d.end_date = _this.end_date;
                    },
                },
                columns: [
                    {data: 'purchaser_id', className: "text-center"},
                    {data: 'purchaser'},
                    {data: 'invoice', className: "text-center"},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'sponsor_type'},
                    {data: 'is_clawback'},
                ]
            });

            $ibo_sales = $("#table-ibo-sales").DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: `${api_url}admin/dashboard/sales/ibo-only`,
                    data: function (d) {
                        d.start_date = _this.start_date;
                        d.end_date = _this.end_date;
                    },
                },
                columns: [
                    {data: 'purchaser_id', className: "text-center"},
                    {data: 'purchaser'},
                    {data: 'invoice', className: "text-center"},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'sponsor_type'},
                    {data: 'is_clawback'},
                ]
            });

            $endorsers = $("#table-modal-view-endorsers").DataTable({
                responsive: true,
                columns: [
                    {data: 'member_id', className: "text-center"},
                    {data: 'member'},
                    {data: 'invoice', className: "text-center"},
                    {data: 'description'},
                    {data: 'transaction_date'},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'shipping_city'},
                    {data: 'shipping_state'},
                    {data: 'sponsor_type'},
                    {data: 'cellphone'},
                ]
            });

            $('#start-date').ddatepicker({
                "setDate" : new Date(),
                "format": "yyyy-mm-dd"
            }).on('changeDate', function(e){
                $('#end-date').ddatepicker('setStartDate' , e.date);

                if($('#end-date').ddatepicker('getDate') < e.date) {
                    $('#end-date').ddatepicker('setDate', e.date);
                }
            });

            $('#end-date').ddatepicker({
                "setDate" : new Date(),
                "startDate" : new Date(),
                "format": "yyyy-mm-dd"
            });

            $('#start-date').ddatepicker('setDate', new Date());
            $('#end-date').ddatepicker('setDate', new Date());

            this.view();
        },
        methods: {
            initializeJQueryEvents() {
                let _this = this;

                $('#new-ibo').on('click', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.showViewModal(data);
                });
            },
            getNewCustomerCount() {
                this.new_customer_count = null;
                client.get(`new-customer-count?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.new_customer_count = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },
            getNewCustomerWithProductSubscriptionCount() {
                this.new_customer_with_product_subscription_count = null;
                client.get(`new-customer-with-product-subscription-count?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.new_customer_with_product_subscription_count = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },
            getNewIBOCount() {
                this.new_ibo_count = null;
                client.get(`new-ibo-count?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.new_ibo_count = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },
            getNewIBOWithProductSubscriptionCount() {
                this.new_ibo_with_product_subscription_count = null;
                client.get(`new-ibo-with-product-subscription-count?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.new_ibo_with_product_subscription_count = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },
            getPlatinumPackageSales() {
                this.platinum_package_sales = null;
                client.get(`total-sales/platinum-package?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.platinum_package_sales = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getGoldPackageSales() {
                this.gold_package_sales = null;
                client.get(`total-sales/gold-package?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.gold_package_sales = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getIBOSalesOnly() {
                this.ibo_sales_only = null;
                client.get(`total-sales/ibo-only?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.ibo_sales_only = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getAverageReorder() {
                this.average_reorder = null;
                client.get(`average-reorder?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.average_reorder = +response.data.average;
                }).catch(this.axiosErrorHandler);
            },
            getTopEndorsers() {
                $dt.clear().draw();
                this.top_endorser_count = 0;
                client.get(`top-endorsers?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.top_endorser_count = response.data.length;
                    $dt.rows.add(response.data);
                    $dt.columns.adjust().draw();
                }).catch(this.axiosErrorHandler);
            },
            view() {
                this.start_date = $('#start-date').val();
                this.end_date = $('#end-date').val();

                this.getNewCustomerCount();
                //this.getNewCustomerWithProductSubscriptionCount();
                this.getNewIBOCount();
                this.getNewIBOWithProductSubscriptionCount();
                this.getPlatinumPackageSales();
                this.getGoldPackageSales();
                this.getIBOSalesOnly();
                this.getAverageReorder();
                this.getTopEndorsers();
            },
            viewNewCustomers() {
                this.report_type = "NEW_CUSTOMERS";
                $dtCustomers.clear().draw();
                $('#modal-view-new-customers-title').text("New Customers");
                $('#modal-view-new-customers').modal({backdrop: 'static', keyboard: false});

            },
            // viewNewCustomersWithProductSubscription() {
            //     this.report_type = "NEW_CUSTOMERS_PS";
            //     $dtCustomersWithSubscription.clear().draw();
            //     $('#modal-view-new-customers-with-subscription-title').text("New Customers with Product Subscription");
            //     $('#modal-view-new-customers-with-subscription').modal({backdrop: 'static', keyboard: false});
            //
            // },
            viewNewIBO() {
                this.report_type = "NEW_IBO";
                $dtIBO.clear().draw();
                $('#modal-view-new-ibo-title').text("New IBO");
                $('#modal-view-new-ibo').modal({backdrop: 'static', keyboard: false});
            },
            viewNewIBOWithProductSubscription() {
                this.report_type = "NEW_IBO_PS";
                $dtIBOWithSubscription.clear().draw();
                $('#modal-view-new-ibo-with-subscription-title').text("New IBO with Product Subscription");
                $('#modal-view-new-ibo-with-subscription').modal({backdrop: 'static', keyboard: false});
            },
            viewPlatinumPackage() {
                this.report_type = "PLATINUM_PACKAGE";
                $platinum_sales.clear().draw();
                $('#modal-view-platinum-sales-title').text("Platinum Package Sales");
                $('#modal-view-platinum-sales').modal({backdrop: 'static', keyboard: false});
            },
            viewGoldPackageSales() {
                this.report_type = "GOLD_PACKAGE";
                $gold_sales.clear().draw();
                $('#modal-view-gold-sales-title').text("Gold Package Sales");
                $('#modal-view-gold-sales').modal({backdrop: 'static', keyboard: false});
            },
            viewIBOSalesOnly() {
                this.report_type = "IBO_SALES";
                $ibo_sales.clear().draw();
                $('#modal-view-ibo-sales-title').text("IBO Sales (No Product)");
                $('#modal-view-ibo-sales').modal({backdrop: 'static', keyboard: false});
            },
            viewEndorsers(user_id) {
                this.report_type = "TOP_IBO";
                this.user_id = user_id;
                $endorsers.clear().draw();
                $endorsers.responsive.recalc();
                $('#modal-view-endorsers').modal({backdrop: 'static', keyboard: false});

                client.get(`endorsers/${user_id}/endorsers?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $endorsers.rows.add(response.data);
                    $endorsers.columns.adjust().draw();
                    $endorsers.responsive.recalc();
                });
            },
            download() {
                if(this.is_downloading) return;

                this.is_downloading = true;
                client.get(`download?report_type=${this.report_type}&start_date=${this.start_date}&end_date=${this.end_date}&user_id=${this.user_id}`).then(response => {
                    let link = response.data.link;
                    this.is_downloading = false;

                    if(!!link) {
                        window.location = link;
                    }
                });
            },
            downloadTopEndorser() {
                if(this.is_downloading_top_endorser) return;

                this.is_downloading_top_endorser = true;
                client.get(`download?report_type=TOP_IBO&start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    let link = response.data.link;
                    this.is_downloading_top_endorser = false;
                    window.location = link;
                });
            },
            loadOverlay() {
                var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
                $('body').append(overlay);
            },
            removeOverlay() {
                $('#overlay').remove();
            },
            axiosErrorHandler(error) {
                this.is_processing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data)

                this.error = parse.message;

                swal(this.error, "", 'error');
            },
        }
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));
