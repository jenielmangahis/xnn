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
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search",
                        paginate: {
                            next: 'Next',
                            previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    responsive: true
                });

                this.dtHighest = $("#table-rank-history-highest").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search",
                        paginate: {
                            next: 'Next',
                            previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    responsive: true
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