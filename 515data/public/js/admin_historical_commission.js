(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dt: null,
            frequencies: [],
            frequencyState: "loaded", // loaded/fetching/error
            frequency: "",

            commissionTypes: [],
            commissionType: "",

            commissionPeriods: [],
            commissionPeriodState: "loaded", // loaded/fetching/error
            commissionPeriodIndex: "",

            invoice: null,

            total: 0,
            totalState: "loaded",

            downloadLink: "",
            downloadLinkState: "loaded",

            userId: null,
            autocompleteUrl: `${api_url}common/autocomplete/affiliates`,

            filters: {
                start_date: null,
                end_date: null,
                commission_type_id: null,
                frequency: null,
                invoice: null,
                user_id: null,
            }
        },
        mounted() {
            this.getFrequencies();
            this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-historical-commission").DataTable({
                    // searching: false,
                    // lengthChange: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/historical-commission`,
                        data: function (d) {
                            d.start_date = _this.filters.start_date;
                            d.end_date = _this.filters.end_date;
                            d.commission_type_id = _this.filters.commission_type_id;
                            d.frequency = _this.filters.frequency;
                            d.invoice = _this.filters.invoice;
                            d.user_id = _this.filters.user_id;
                        },
                    },
                    order: [[0, 'asc']],
                    columns: [
                        {data: 'commission_type'},
                        {data: 'commission_period'},
                        {data: 'reference_id'},
                        {data: 'purchaser'}, 
                        {data: 'sponsor_name'},
                        {data: 'payee'},
                        {data: 'account_type'},
                        {data: 'amount_earned'},
                        {data: 'level'},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 7},
                        {responsivePriority: 3, targets: 1},
                    ]
                });
            },
            getFrequencies() {

                if (this.frequencyState === "fetching") return;

                this.frequencyState = "fetching";
                this.frequencies = [];
                this.frequency = "";
                this.commissionTypes = [];

                client.get("common/commission-types/frequencies")
                    .then(response => {
                        this.frequencies = response.data;
                        this.frequencyState = "loaded";

                    })
                    .catch(error => {
                        this.frequencyState = "error";
                    })
            },
            getCommissionPeriods() {

                if (this.commissionPeriodState === "fetching") return;

                this.commissionPeriodState = "fetching";
                this.commissionPeriods = [];
                this.commissionPeriodIndex = "";

                client.get("common/commission-periods/locked-dates", {
                    params: {
                        frequency: this.frequency
                    }
                })
                    .then(response => {
                        this.commissionPeriods = response.data;
                        this.commissionPeriodState = "loaded";

                    })
                    .catch(error => {
                        this.commissionPeriodState = "error";
                    })

            },
            getTotalAmount() {

                if (this.totalState === "fetching") return;

                this.totalState = "fetching";
                this.total = 0;

                client.get("admin/historical-commission/total", {
                    params: this.filters
                })
                    .then(response => {
                        this.totalState = "loaded";
                        this.total = response.data.total;
                    })
                    .catch(error => {
                        this.totalState = "error";
                    })
            },
            getDownloadLink() {
                if (this.downloadLinkState === "fetching") return;

                this.downloadLinkState = "fetching";
                this.downloadLink = "";

                client.get("admin/historical-commission/download", {
                    params: this.filters
                })
                    .then(response => {
                        this.downloadLinkState = "loaded";
                        this.downloadLink = response.data.link;

                        if (!!this.downloadLink) {
                            window.location = this.downloadLink;
                        }
                    })
                    .catch(error => {
                        this.downloadLinkState = "error";
                    })
            },
            view() {

                if (!this.commissionPeriodIndex) return;

                let commissionPeriod = this.commissionPeriods[this.commissionPeriodIndex - 1];

                this.filters.start_date = commissionPeriod.start_date;
                this.filters.end_date = commissionPeriod.end_date;
                this.filters.commission_type_id = this.commissionType;
                this.filters.frequency = this.frequency;
                this.filters.invoice = this.invoice;
                this.filters.user_id = this.userId;

                this.dt.clear().draw();
                // this.dt.responsive.recalc();
                this.getTotalAmount();
            },
        },
        watch: {
            frequency: function (newFrequency, oldFrequency) {

                if (!!newFrequency) {
                    this.commissionTypes = _.find(this.frequencies, ['name', newFrequency]).commission_types;
                    this.getCommissionPeriods();
                } else {
                    this.commissionTypes = [];
                    this.commissionType = "";
                    this.commissionPeriods = [];
                    this.commissionPeriodIndex = "";
                }

                console.log(this.commissionTypes);
            }
        },
    });

    window.onbeforeunload = function () {
        if (vm.downloadLinkState === "fetching") {
            return "Do you really want to leave? Download will be cancelled.";
        } else {
            return;
        }
    };

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));