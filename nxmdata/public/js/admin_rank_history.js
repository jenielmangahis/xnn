(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#rank-history",
        data: {
            enrollment: {
                start_date: moment().format("YYYY-MM-DD"),
                rank_id: "",

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    rank_id: "",
                }
            },
            highest: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
                is_all: 0,
                rank_id: "",

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                    is_all: 0,
                    rank_id: "",
                },

                downloadLink: "",
                downloadLinkState: "loaded",
            },

            ranks: [],
            rankState: "loaded", // loaded/fetching/error

            today: moment().format("YYYY-MM-DD"),
            dtEnrollment: null,
            dtHighest: null,
        },
        mounted() {
            this.getRanks();
            this.initializeDataTables();
            this.initializeJQueryEvents();

            this.dtEnrollment.responsive.recalc();
            this.dtHighest.responsive.recalc();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtEnrollment = $("#table-rank-history-enrollment").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    ajax: {
                        url: `${api_url}admin/rank-history/enrollment`,
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
                        {
                            data: 'rank_id',
                            render: function (data, type, row, meta) {
                                return row.current_rank;
                            }
                        },
                        {
                            data: 'paid_as_rank_id',
                            render: function (data, type, row, meta) {
                                return row.paid_as_rank;
                            }
                        },
                        {data: 'referral_points', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'coach_points', className: "text-center"},
                        {data: 'organization_points', className: "text-center"},
                        {data: 'team_group_points', className: "text-center"},
                        {data: 'preferred_customer_count', className: "text-center"},
                        {data: 'influencer_count', className: "text-center"},
                        {data: 'silver_influencer_count', className: "text-center"},
                        {data: 'gold_influencer_count', className: "text-center"},
                        {data: 'platinum_influencer_count', className: "text-center"},
                        {data: 'diamond_influencer_count', className: "text-center"},
                        {
                            data: 'is_active',
                            className: "text-center",
                            render: function (data, type, row, meta) {

                                if (+row.is_active) {
                                    return `<span class="label label-success">Yes</span>`;
                                }

                                return `<span class="label label-warning">No</span>`;
                            }
                        },
                        {data: 'level', className: "text-center"},
                        {
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {data: 'rank_date', className: "text-center"},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -3},
                        {responsivePriority: 4, targets: -4},
                    ]
                });

                this.dtHighest = $("#table-rank-history-highest").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/rank-history/highest`,
                        data: function (d) {
                            d.start_date = _this.highest.filters.start_date;
                            d.end_date = _this.highest.filters.end_date;
                            d.is_all = +_this.highest.filters.is_all;
                            d.rank_id = +_this.highest.filters.rank_id;
                        },
                    },
                    order: [[1, 'desc']],
                    columns: [

                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let member = row.member;
                                return `${user_id}: ${member}`;
                            }
                        },
                        {
                            data: 'rank_id',
                            render: function (data, type, row, meta) {
                                return row.highest_rank;
                            }
                        },

                        {data: 'date_achieved', className: "text-center"},
                        {data: 'level', className: "text-center"},
                        {
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: 2},
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