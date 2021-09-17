(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#hostess-dashboard",
        data: {
            openEvents: null,
            dailyRewards: null,
            productCredits: null,
            coupons: null,
            orders: null,
            totalSales: null,

            isProcessing: 0,

            member_id: null,
            
            error: {
                message: null,
                type: null,
            },
        },
        mounted() {
            this.member_id = $('#member').val();
            this.initializeDataTables();
            this.initializeJQueryEvents();
            this.getTotalSales();
            this.getCountdown();
            
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.openEvents = $("#table-open-events").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: `${api_url}member/hostess-dashboard/open-events`,
                    },
                    order: [[2, 'desc']],
                    columns: [
                        {data: 'customer'},
                        {data: 'invoice', className: "text-center"},
                        {data: 'order_date', className: "text-center"},
                        {data: 'description'},
                        {data: 'amount', className: "text-center"},
                    ]
                });

                this.dailyRewards = $("#table-daily-rewards").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: `${api_url}member/hostess-dashboard/daily-rewards`,
                    },
                    order: [[1, 'desc']],
                    columns: [
                        {data: 'date', className: "text-center"},
                        {data: 'total_sales', className: "text-center"},
                        {data: 'product_credits', className: "text-center"},
                        {
                            data: 'rewards_id',
                            className: "text-center",
                            render: function ( data, type, row, meta ) {
                                return `<a class="btn-view-orders">View</a>`;
                            }
                        },
                    ]
                });

                this.productCredits = $("#table-gift-cards").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: `${api_url}member/hostess-dashboard/product-credits`,
                    },
                    order: [[3, 'desc']],
                    columns: [
                        {data: 'code', className: "text-center"},
                        {data: 'validation_code', className: "text-center"},
                        {data: 'period_earned', className: "text-center"},
                        {data: 'amount', className: "text-center"},
                        {data: 'balance', className: "text-center"},
                        {data: 'expiration_date', className: "text-center"},
                    ]
                });

                this.coupons = $("#table-coupon").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: `${api_url}member/hostess-dashboard/coupons`,
                    },
                    order: [[2, 'desc']],
                    columns: [
                        {data: 'code', className: "text-center"},
                        {data: 'period_earned', className: "text-center"},
                        {data: 'description'},
                        {data: 'status', className: "text-center"},
                    ]
                });
                
                this.orders = $("#table-view-orders").DataTable({
                    columns: [
                        {data: 'customer'},
                        {data: 'order_id', className: "text-center"},
                        {data: 'description'},
                        {data: 'amount', className: "text-center"},
                    ]
                });
            },
            initializeJQueryEvents() {
                let _this = this;

                let clipboard = new ClipboardJS('.rewards-dashboard__a-btn-ss-copy');
    
                clipboard.on('success', function(e) {
                    swal('Copied!','You have copied the Sharing Link', 'info');
                });

                clipboard.on('error', function(e) {
                    swal('Error!','Cannot copy link', 'error');
                });

                $("#table-daily-rewards").on('click', '.btn-view-orders', function () {
                    // let data = this.dailyRewards.row($(this).parents('tr')).data();
                    let data = _this.dailyRewards.row($(this).parents('tr')).data();
                    console.log(data.rewards_id);
                    _this.viewOrders(data.rewards_id);
                });

            },
            getSharingLink() {
                this.sharingLink = null;
                client.get(`member/hostess-dashboard/sharing-link`).then(response => {
                    this.sharingLink = +response.data.link;
                }).catch(this.axiosErrorHandler);
            },
            getTotalSales() {
                this.totalSales = null;
                client.get(`member/hostess-dashboard/rewards`).then(response => {
                    this.totalSales = parseFloat(response.data.total_sales);
                    console.log(this.totalSales);
                    $('#progress-value').text("Your progress: $" + this.totalSales + " worth of Total Sales So Far");
                    widthPercentage = ((this.totalSales.toFixed(0)/2500)*100);
                    $("#progress").css("width",widthPercentage+"%");
                }).catch(this.axiosErrorHandler);
            },
            getCountdown() {

                var countdownNamespace = {
                    countDownTimerObj : '#rewards-dashboard__countdown-timer',
                }

                client.get(`member/hostess-dashboard/countdown`).then(response => {

                    if(response.data != '0') {
                        $(countdownNamespace.countDownTimerObj).countdown({
                            date: response.data,
                            day: 'Day',
                            days: 'Days',
                            offset: -7
                        }, function () {
                            console.log('Done!');
                        });
                    }
                    
                }).catch(this.axiosErrorHandler);
            },
            viewOrders(id) {
                this.orders.clear().draw();
                $('#modal-view-orders').modal({backdrop: 'static', keyboard: false});

                client.get(`member/hostess-dashboard/orders/${id}`).then(response => {
                    this.orders.rows.add(response.data);
                    this.orders.columns.adjust().draw();
                });
            },
            axiosErrorHandler(error) {
                this.is_processing = 0;
                console.log(error);
                let parse = commissionEngine.parseAxiosErrorData(error.response)



                this.error = parse.message;

                swal(this.error, "", 'error');
            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));