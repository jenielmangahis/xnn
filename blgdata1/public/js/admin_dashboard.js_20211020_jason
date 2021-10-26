(function ($, api_url, Vue, swal, axios, location, moment, undefined) {
    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    const client = commissionEngine.createAccessClient('admin/dashboard');
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dt = null;
    let $new_members = null;
    let $pack_sales = null;
    let $endorsers = null;

    const vm = new Vue({
        el: '#dashboard',
        data: {
            new_customer_count: null,
            new_customer_with_product_subscription_count: null,
            new_endorser_count: null,
            new_endorser_with_product_subscription_count: null,
            customer_transformation_pack_total_sales: null,
            transformation_pack_total_sales: null,
            elite_pack_total_sales: null,
            family_elite_pack_total_sales: null,
            average_reorder: null,
            viral_index: null,
            viral_index_start_date: null,
            viral_index_end_date: null,
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
            $dt = $("#table-top-endorsers").DataTable({
                searching: false,
                ordering: false,
                lengthChange: false,
                columns: [
                    {data: 'user_id', className: "text-center"},
                    {data: 'endorser'},
                    {
                        data: 'endorser_count',
                        className: "text-center",
                        render: function ( data, type, row, meta ) {
                            return `<a class="btn-link btn-view-endorsers">${data}</a>`;
                        }
                    },
                    {data: 'volume', className: "text-right"},
                ]
            });

            $("#table-top-endorsers").on('click', '.btn-view-endorsers', function () {
                let data = $dt.row($(this).parents('tr')).data();
                vm.viewEndorsers(+data.user_id);
            });

            $new_members = $("#table-new-members").DataTable({
                responsive: true,
                columns: [
                    {data: 'member_id', className: "text-center"},
                    {data: 'member'},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'sponsor_type'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'has_coupon'},
                    {data: 'has_gift_card'},
                    {data: 'shipping_city'},
                    {data: 'shipping_state'},
                    {data: 'has_subscription'},
                    {data: 'cellphone'},

                ]
            });

            $pack_sales = $("#table-pack-sales").DataTable({
                responsive: true,
                columns: [
                    {data: 'purchaser_id', className: "text-center"},
                    {data: 'purchaser'},
                    {data: 'invoice', className: "text-center"},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor'},
                    {data: 'order_id'},
                    {data: 'cv'},
                    {data: 'amount_paid'},
                    {data: 'has_coupon'},
                    {data: 'has_gift_card'},
                    {data: 'shipping_city'},
                    {data: 'shipping_state'},
                    {data: 'sponsor_type'},
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
                    {data: 'has_coupon'},
                    {data: 'has_gift_card'},
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
            getNewEndorserCount() {
                this.new_endorser_count = null;
                client.get(`new-endorser-count?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.new_endorser_count = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },
            getNewEndorserWithProductSubscriptionCount() {
                this.new_endorser_with_product_subscription_count = null;
                client.get(`new-endorser-with-product-subscription-count?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.new_endorser_with_product_subscription_count = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },
            getCustomerTransformationPackTotalSales() {
                this.customer_transformation_pack_total_sales = null;
                client.get(`total-sales/customer-transformation-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.customer_transformation_pack_total_sales = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getTransformationPackTotalSales() {
                this.transformation_pack_total_sales = null;
                client.get(`total-sales/transformation-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.transformation_pack_total_sales = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getElitePackTotalSales() {
                this.elite_pack_total_sales = null;
                client.get(`total-sales/elite-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.elite_pack_total_sales = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getFamilyElitePackTotalSales() {
                this.family_elite_pack_total_sales = null;
                client.get(`total-sales/family-elite-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    this.family_elite_pack_total_sales = +response.data.total_sales;
                }).catch(this.axiosErrorHandler);
            },
            getAveragaReorder() {
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
            getViralIndex() {
                this.viral_index = null;
                this.viral_index_start_date = null;
                this.viral_index_end_date = null;

                client.get(`viral-index?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    let data = response.data;
                    this.viral_index = data.viral_index;
                    this.viral_index_start_date = data.this_month.start_date;
                    this.viral_index_end_date = data.this_month.end_date;
                });
            },
            view() {
                this.start_date = $('#start-date').val();
                this.end_date = $('#end-date').val();

                this.getNewCustomerCount();
                this.getNewCustomerWithProductSubscriptionCount();
                this.getNewEndorserCount();
                this.getNewEndorserWithProductSubscriptionCount();
                this.getCustomerTransformationPackTotalSales();
                this.getTransformationPackTotalSales();
                this.getElitePackTotalSales();
                this.getFamilyElitePackTotalSales();
                this.getAveragaReorder();
                this.getTopEndorsers();
                this.getViralIndex();
            },
            viewNewCustomers() {
                this.report_type = "NEW_CUSTOMERS";
                $new_members.clear().draw();
                $('#modal-view-new-members-title').text("New Customers");
                $('#modal-view-new-members').modal({backdrop: 'static', keyboard: false});
                $new_members.responsive.recalc();

                client.get(`new-customers?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $new_members.rows.add(response.data);
                    $new_members.columns.adjust().draw();
                    $new_members.responsive.recalc();
                });

            },
            viewNewCustomersWithProductSubscription() {
                this.report_type = "NEW_CUSTOMERS_PS";
                $new_members.clear().draw();
                $('#modal-view-new-members-title').text("New Customers with Product Subscription");
                $('#modal-view-new-members').modal({backdrop: 'static', keyboard: false});
                $new_members.responsive.recalc();

                client.get(`new-customers-with-product-subscription?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $new_members.rows.add(response.data);
                    $new_members.columns.adjust().draw();
                    $new_members.responsive.recalc();
                });

            },
            viewNewEndorsers() {
                this.report_type = "NEW_ENDORSERS";
                $new_members.clear().draw();
                $('#modal-view-new-members-title').text("New Endorsers");
                $('#modal-view-new-members').modal({backdrop: 'static', keyboard: false});
                $new_members.responsive.recalc();

                client.get(`new-endorsers?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $new_members.rows.add(response.data);
                    $new_members.columns.adjust().draw();
                    $new_members.responsive.recalc();
                });
            },
            viewNewEndorsersWithProductSubscription() {
                this.report_type = "NEW_ENDORSERS_PS";
                $new_members.clear().draw();
                $('#modal-view-new-members-title').text("New Endorsers with Product Subscription");
                $('#modal-view-new-members').modal({backdrop: 'static', keyboard: false});
                $new_members.responsive.recalc();

                client.get(`new-endorsers-with-product-subscription?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $new_members.rows.add(response.data);
                    $new_members.columns.adjust().draw();
                    $new_members.responsive.recalc();
                });
            },
            viewCustomerTransformationPackSales() {
                this.report_type = "CUSTOMER_TRANSFORMATION_PACK";
                $pack_sales.clear().draw();
                $('#modal-view-pack-sales-title').text("Customer Transformation Pack Sales");
                $('#modal-view-pack-sales').modal({backdrop: 'static', keyboard: false});

                client.get(`sales/customer-transformation-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $pack_sales.rows.add(response.data);
                    $pack_sales.columns.adjust().draw();
                    $pack_sales.responsive.recalc();
                });
            },
            viewTransformationPackSales() {
                this.report_type = "TRANSFORMATION_PACK";
                $pack_sales.clear().draw();
                $('#modal-view-pack-sales-title').text("Transformation Pack Sales");
                $('#modal-view-pack-sales').modal({backdrop: 'static', keyboard: false});

                client.get(`sales/transformation-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $pack_sales.rows.add(response.data);
                    $pack_sales.columns.adjust().draw();
                    $pack_sales.responsive.recalc();
                });
            },
            viewElitePackSales() {
                this.report_type = "ELITE_PACK";
                $pack_sales.clear().draw();
                $('#modal-view-pack-sales-title').text("Elite Pack Sales");
                $('#modal-view-pack-sales').modal({backdrop: 'static', keyboard: false});

                client.get(`sales/elite-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $pack_sales.rows.add(response.data);
                    $pack_sales.columns.adjust().draw();
                    $pack_sales.responsive.recalc();
                });
            },
            viewFamilyElitePackSales() {
                this.report_type = "FAMILY_PACK";
                $pack_sales.clear().draw();
                $('#modal-view-pack-sales-title').text("Family Elite Pack Sales");
                $('#modal-view-pack-sales').modal({backdrop: 'static', keyboard: false});

                client.get(`sales/family-elite-pack?start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    $pack_sales.rows.add(response.data);
                    $pack_sales.columns.adjust().draw();
                    $pack_sales.responsive.recalc();
                });
            },
            viewEndorsers(user_id) {
                this.report_type = "TOP_ENDORSERS_ENDORSER";
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
                client.get(`download?report_type=TOP_ENDORSERS&start_date=${this.start_date}&end_date=${this.end_date}`).then(response => {
                    let link = response.data.link;
                    this.is_downloading_top_endorser = false;
                    window.location = link;
                });
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
