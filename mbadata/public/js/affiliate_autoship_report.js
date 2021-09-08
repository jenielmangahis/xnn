(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient('member/autoship');
    commissionEngine.setupAccessTokenJQueryAjax();

    $.fn.ddatepicker = $.fn.datepicker;
    const vm = new Vue({
        el: "#autoship",
        data: {
            generateCsv:{
                downloadLinkState: "loaded",
                downloadLink: "",
            },
            yearMonth: moment().format("YYYY-MM"),

            filters: {
                yearMonth: moment().format("YYYY-MM"),
            },


            metrics: {

                pendingAutoshipAmount: 0,
                successfulAutoshipAmount: 0,
                failedAutoshipAmount: 0,

                memberCount: 0,
                activeMemberOnAutoshipCount: 0,
                cancelledAutoshipCount: 0,
                averageOrderValue: 0,
                personallyEnrolledRetentionRate: 0,
                organizationalRetentionRate: 0,
            },
            csvUrl: null,
            activeTable: null,
            dtPendingAutoship: null,
            dtSuccessfulAutoship: null,
            dtFailedAutoship: null,
            dtCancelledAutoship: null,
            dtActiveMembersOnAutoship: null,
        },
        mounted() {
            this.initializeDataTables();
            this.view();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dtPendingAutoship = $("#table-pending-autoship").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    ajax: {
                        url: `${api_url}member/autoship/pending-autoship`,
                        data: function (d) {
                            d.year_month = _this.filters.yearMonth;
                        },
                    },
                    order: [[1, 'asc']],
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
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {data: 'account_type'},
                        {
                            data: 'price',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'processing_date'},

                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

                this.dtSuccessfulAutoship = $("#table-successful-autoship").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    ajax: {
                        url: `${api_url}member/autoship/successful-autoship`,
                        data: function (d) {
                            d.year_month = _this.filters.yearMonth;
                        },
                    },
                    order: [[1, 'asc']],
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
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {data: 'account_type'},
                        {
                            data: 'price',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {
                            data: 'cv',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'processing_date'},

                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

                this.dtFailedAutoship = $("#table-failed-autoship").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    ajax: {
                        url: `${api_url}member/autoship/failed-autoship`,
                        data: function (d) {
                            d.year_month = _this.filters.yearMonth;
                        },
                    },
                    order: [[1, 'asc']],
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
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {data: 'account_type'},
                        {
                            data: 'price',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {
                            data: 'cv',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'processing_date'},

                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

                this.dtCancelledAutoship = $("#table-cancelled-autoship").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    ajax: {
                        url: `${api_url}member/autoship/cancelled-autoship`,
                        data: function (d) {
                            d.year_month = _this.filters.yearMonth;
                        },
                    },
                    order: [[1, 'asc']],
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
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {data: 'account_type'},
                        {
                            data: 'price',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {
                            data: 'cv',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'processing_date'},

                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

                this.dtActiveMembersOnAutoship = $("#table-active-members-on-autoship").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    ajax: {
                        url: `${api_url}member/autoship/active-members-on-autoship`,
                        data: function (d) {
                            d.year_month = _this.filters.yearMonth;
                        },
                    },
                    order: [[1, 'asc']],
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
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {data: 'account_type'},
                        {
                            data: 'price',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'processing_date'},

                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });
            },
            showTable(table) {
                this.activeTable = table;

                switch (this.activeTable) {
                    case 'pending_autoship':
                        this.dtPendingAutoship.clear().draw();
                        break;
                    case 'successful_autoship':
                        this.dtSuccessfulAutoship.clear().draw();
                        break;
                    case 'failed_autoship':
                        this.dtFailedAutoship.clear().draw();
                        break;
                    case 'cancelled_autoship':
                        this.dtCancelledAutoship.clear().draw();
                        break;
                    case 'active_members_on_autoship':
                        this.dtActiveMembersOnAutoship.clear().draw();
                        break;
                    default:
                        this.activeTable = null;
                }
            },
            generateCSV(table) {
                this.activeTable = table;

                switch (this.activeTable) {
                    case 'pending_autoship':
                        this.csvUrl = 'csv-pending-autoship';
                        break;
                    case 'successful_autoship':
                        this.csvUrl = 'csv-successful-autoship';
                        break;
                    case 'failed_autoship':
                        this.csvUrl = 'csv-failed-autoship';
                        break;
                    case 'cancelled_autoship':
                        this.csvUrl = 'csv-cancelled-autoship';
                        break;
                    case 'active_members_on_autoship':
                        this.csvUrl = 'csv-active-members-on-autoship';
                        break;
                    default:
                        this.csvUrl = 'csv-active-members-on-autoship';
                }

                if (this.generateCsv.downloadLinkState === "fetching") return;

                this.generateCsv.downloadLinkState = "fetching";
                this.generateCsv.downloadLink = "";

                client.get(`${this.csvUrl}?year_month=${this.filters.yearMonth}`)
                .then(response => {
                    this.generateCsv.downloadLinkState = "loaded";
                    this.generateCsv.downloadLink = response.data.link;

                    if (!!this.generateCsv.downloadLink) {
                        window.location = this.generateCsv.downloadLink;
                    }
                })
                .catch(error => {
                    this.generateCsv.downloadLinkState = "error";
                })
            },
            view() {

                this.activeTable = null;
                this.filters.yearMonth = this.yearMonth;
                this.getPendingAutoshipAmount();
                this.getSuccessfulAutoshipAmount();
                this.getFailedAutoshipAmount();
                this.getMembersCount();
                this.getActiveMembersOnAutoshipCount();
                this.getCancelledAutoshipCount();
                this.getAverageOrderValue();
                this.getPersonallyEnrolledRetentionRate();
                this.getOrganizationalRetentionRate();
            },

            getPendingAutoshipAmount() {
                this.metrics.pendingAutoshipAmount = null;
                client.get(`pending-autoship-amount?year_month=${this.filters.yearMonth}`).then(response => {
                    console.log(response.data);
                    this.metrics.pendingAutoshipAmount = +response.data.amount;
                }).catch(this.axiosErrorHandler);
            },

            getSuccessfulAutoshipAmount() {
                this.metrics.successfulAutoshipAmount = null;
                client.get(`successful-autoship-amount?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.successfulAutoshipAmount = +response.data.amount;
                }).catch(this.axiosErrorHandler);
            },

            getFailedAutoshipAmount() {
                this.metrics.failedAutoshipAmount = null;
                client.get(`failed-autoship-amount?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.failedAutoshipAmount = +response.data.amount;
                }).catch(this.axiosErrorHandler);
            },

            getMembersCount() {
                this.metrics.memberCount = null;
                client.get(`members-count?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.memberCount = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },

            getActiveMembersOnAutoshipCount() {
                this.metrics.activeMemberOnAutoshipCount = null;
                client.get(`active-members-on-autoship-count?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.activeMemberOnAutoshipCount = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },

            getCancelledAutoshipCount() {
                this.metrics.cancelledAutoshipCount = null;
                client.get(`cancelled-autoship-count?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.cancelledAutoshipCount = +response.data.count;
                }).catch(this.axiosErrorHandler);
            },

            getAverageOrderValue() {
                this.metrics.averageOrderValue = null;
                client.get(`average-order-value?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.averageOrderValue = +response.data.amount;
                }).catch(this.axiosErrorHandler);
            },

            getPersonallyEnrolledRetentionRate() {
                this.metrics.personallyEnrolledRetentionRate = null;
                client.get(`personally-enrolled-retention-rate?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.personallyEnrolledRetentionRate = +response.data.rate;
                }).catch(this.axiosErrorHandler);
            },

            getOrganizationalRetentionRate() {
                this.metrics.organizationalRetentionRate = null;
                client.get(`organizational-retention-rate?year_month=${this.filters.yearMonth}`).then(response => {
                    this.metrics.organizationalRetentionRate = +response.data.rate;
                }).catch(this.axiosErrorHandler);
            },

            axiosErrorHandler(error) {
                this.is_processing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data)

                this.error = parse.message;

                swal(this.error, "", 'error');
            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));