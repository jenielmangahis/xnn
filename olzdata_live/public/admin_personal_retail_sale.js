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
                            d.rank_id = _this.enrollment.filters.rank_id;
                        },
                    },
                    order: [[0, 'asc']],
                    columns: [
                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let member = row.member;
                                return `${user_id}: ${member}`;
                            }
                        },
                        {data: 'current_rank', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'paid_as_rank', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'prs', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'grs', className: "text-center"},
                        {data: 'sponsored_qualified_representatives_count', className: "text-center"},
                        {data: 'sponsored_leader_or_higher_count', className: "text-center"},                        
                        {data: 'level', className: "text-center"},
                        {data: 'rank_date', className: "text-center"},
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -3},
                        {responsivePriority: 4, targets: -4},
                    ]
                });
            },
            initializeJQueryEvents() {

                $('#nav-tab-report a[data-toggle="tab"]').on('shown.bs.tab',  (e) => {

                    let tab = $(e.target).attr("href");

                    if(tab === "#tree") {
                        this.dtEnrollment.responsive.recalc();
                    } else if(tab === "#new-highest-rank") {
                        this.dtHighest.responsive.recalc();
                    }

                    this.dtEnrollment.columns.adjust();

                })
            },
            viewEnrollment() {

                this.enrollment.filters.start_date = this.enrollment.start_date;
                this.enrollment.filters.rank_id = this.enrollment.rank_id;

                this.dtEnrollment.clear().draw();
                this.dtEnrollment.responsive.recalc();
            },
            viewHighest() {

                this.highest.filters.start_date = this.highest.start_date;
                this.highest.filters.end_date = this.highest.end_date;
                this.highest.filters.rank_id = this.highest.rank_id;
                this.highest.filters.is_all = this.highest.is_all;

                this.dtHighest.clear().draw();
                this.dtHighest.responsive.recalc();
            },
            getRanks() {

                if (this.rankState === "fetching") return;

                this.rankState = "fetching";
                this.ranks = [];
                this.enrollment.rank_id = "";
                this.highest.rank_id = "";

                client.get("common/ranks")
                    .then(response => {
                        this.ranks = response.data;
                        this.rankState = "loaded";

                    })
                    .catch(error => {
                        this.rankState = "error";
                    })

            },
            getDownloadHighestLink() {
                if (this.highest.downloadLinkState === "fetching") return;

                this.highest.downloadLinkState = "fetching";
                this.highest.downloadLink = "";

                client.get("admin/rank-history/download-highest", {
                    params: this.highest.filters
                })
                    .then(response => {
                        this.highest.downloadLinkState = "loaded";
                        this.highest.downloadLink = response.data.link;

                        if (!!this.highest.downloadLink) {
                            window.location = this.highest.downloadLink;
                        }
                    })
                    .catch(error => {
                        this.highest.downloadLinkState = "error";
                    })
            },
            getDownloadEnrollmentLink() {
                if (this.enrollment.downloadLinkState === "fetching") return;

                this.enrollment.downloadLinkState = "fetching";
                this.enrollment.downloadLink = "";

                client.get("admin/rank-history/download-enrollment", {
                    params: this.enrollment.filters
                })
                    .then(response => {
                        this.enrollment.downloadLinkState = "loaded";
                        this.enrollment.downloadLink = response.data.link;

                        if (!!this.enrollment.downloadLink) {
                            window.location = this.enrollment.downloadLink;
                        }
                    })
                    .catch(error => {
                        this.enrollment.downloadLinkState = "error";
                    })
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));