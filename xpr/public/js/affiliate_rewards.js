(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#rewards",
        data: {
            dtGiftCards: null,
            dtCoupons: null,

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
            
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtGiftCards = $("#table-gift-cards").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/rewards/gift-cards`,
                    },
                    order: [[3, 'desc']],
                    columns: [
                        {data: 'code', className: "text-center"},
                        {data: 'amount', className: "text-center"},
                        {data: 'balance', className: "text-center"},
                        {data: 'earned_date', className: "text-center"},
                        {data: 'expiration_date', className: "text-center"},
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: 2},
                    ]
                });

                this.dtCoupons = $("#table-coupons").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/rewards/coupons`,
                    },
                    order: [[2, 'desc']],
                    columns: [
                        {data: 'coupon_name', className: "text-center"},
                        {data: 'coupon_count', className: "text-center"},
                        {data: 'expiration_date', className: "text-center"},
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                    ]
                });
            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));