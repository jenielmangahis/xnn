(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#personal-retail-sale",
        data: function () {
            return {
                autocompleteUrl: `${api_url}common/autocomplete/members`,
                enrollment: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                    prs_500_above : '',
                    filters: {
                        start_date: moment().format("YYYY-MM-DD"),
                        end_date: moment().format("YYYY-MM-DD"),
                        memberId: null,
                        prs_500_above : '',
                    },
                },
                today: moment().format("YYYY-MM-DD"),
            }
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
                            d.prs_500_above = _this.enrollment.filters.prs_500_above;
                            d.memberId = _this.filters.memberId;                   
                        },
                    },
                    order: [[0, 'asc']],
                    columns: [                        
                        {data: 'rownum', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
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
                this.enrollment.filters.prs_500_above = this.enrollment.prs_500_above;
                this.enrollment.filters.memberId = this.enrollment.memberId;

                this.dtEnrollment.clear().draw();
                this.dtEnrollment.responsive.recalc();
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));