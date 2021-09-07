(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#top-earners",
        data: {
            
            top_earners: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
                is_all: 0,

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                    is_all: 0,
                }
            },

            today: moment().format("YYYY-MM-DD"),
            dtTopEarner: null,
            
        },
        mounted() {
            this.initializeDataTables();

        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtTopEarner = $("#table-top_earners").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search..."
                    },
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/top-earner/`,
                        data: function (d) {
                            d.start_date = _this.top_earners.filters.start_date;
                            d.end_date = _this.top_earners.filters.end_date;
                            d.is_all = +_this.top_earners.filters.is_all;
                        },
                    },
                    order: [[2, 'desc']],
                    columns: [
                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let member = row.member;
                                return `${user_id}: ${member}`;
                            }
                        },
                        {data: 'site', className: "text-center"},
                        {data: 'earnings', className: "text-center"},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: 2},
                    ]
                });
            },
            viewTopEarners() {
                
                this.top_earners.filters.start_date = this.top_earners.start_date;
                this.top_earners.filters.end_date = this.top_earners.end_date;
                this.top_earners.filters.is_all = this.top_earners.is_all;

                this.dtTopEarner.clear().draw();
                this.dtTopEarner.responsive.recalc();

            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));