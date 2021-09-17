(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#personal-retail-sale",
        data: {
            enrollment: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                },
            },
            today: moment().format("YYYY-MM-DD"),
        },
        mounted() {
            this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtEnrollment = $("#table-personal-retail-sales").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    ajax: {
                        url: `${api_url}admin/personal-retail-sales/enrollment`,
                        data: function (d) {
                            d.start_date = _this.enrollment.filters.start_date;
                            d.end_date   = _this.enrollment.filters.end_date;                            
                        },
                    },
                    order: [[0, 'asc']],
                    columns: [                        
                        {data: 'top', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'user_id', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'member', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'enrolled_date', className: "text-center"},
                        {data: 'affiliated_date', className: "text-center"},
                        {data: 'email', className: "text-center"},
                        {data: 'country', className: "text-center"},
                        {data: 'sponsor_id', className: "text-center"},                        
                        {data: 'sponsor', className: "text-center"},
                        {data: 'prs', className: "text-center"},
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -3},
                        {responsivePriority: 4, targets: -4},
                    ]
                });
            },
            viewPersonalRetail() {

                this.enrollment.filters.start_date = this.enrollment.start_date;
                this.enrollment.filters.end_date = this.enrollment.end_date
                //this.highest.filters.is_all = this.highest.is_all;

                this.dtEnrollment.clear().draw();
                this.dtEnrollment.responsive.recalc();
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));