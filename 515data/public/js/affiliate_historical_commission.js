(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dt: null,
            autocomplete_url: `${api_url}common/autocomplete/affiliate-downline`,
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

            enroller: null,
            enrollerBody: null,

            filters: {
                start_date: null,
                end_date: null,
                commission_type_id: null,
                frequency: null,
                downline_id: null,
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
                        url: `${api_url}member/historical-commission`,
                        data: function (d) {
                            d.start_date = _this.filters.start_date;
                            d.end_date = _this.filters.end_date;
                            d.commission_type_id = _this.filters.commission_type_id;
                            d.frequency = _this.filters.frequency;
                            d.invoice = _this.filters.invoice;
                            d.downline_id = _this.filters.downline_id;
                        },
                    },
                    order: [[0, 'asc']],
                    columns: [
                        {
                            data: 'commission_type',
                            render: function (data, type, row, meta) {
                                return `<span class="`+data.replace(/,/g, '')+`">`+data+`</span>`;
                            }
                        
                        },
                        {data: 'commission_period'},
                        {data: 'reference_id'},
                        {
                            data: 'account_type', 
                            render: function (data, type, row, meta) {
                                return `<span class=" text-center `+data.replace(/,/g, '')+`">`+data+`</span>`;
                            }
                    },
                        {data: 'sponsor_id', className: "text-center "},
                        {data: 'sponsor_name', className: "text-center"},
                        {data: 'amount_earned', className: "text-center"},
                        {data: 'current_rank', className: "text-center"},
                        {data: 'level', className: "text-center"},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 6},
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

                client.get("member/historical-commission/total", {
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

                client.get("member/historical-commission/download", {
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

                this.dt.clear().draw();
                // this.dt.responsive.recalc();
                this.getTotalAmount();
            },
            selectionChange(value) {
                if (!!value) {
                    this.root_id = value;
                } else {
                    this.root_id = this.owner_id;
                }

                this.setupTree(this.root_id);

            },
            setupTree(parentID) {
                let _this = this;
                _this.cancel_previous_request = true;

                if (_this.enroller.data('treetable') !== undefined) {
                    _this.enroller.treetable('destroy');
                    _this.enrollerBody.html(`<tr class="table__row"><td class="table__row" colspan="${_this.colspan}" align="center"><i class="fa fa-spin fa-spinner"></i> Generating data...</td></tr>`);
                }

                _this.getParent(parentID, function (data) {

                    data.level = 0;
                    let html = _this.rowTemplate(data);

                    _this.enrollerBody.empty();
                    _this.enrollerBody.append(html);

                    _this.enroller.treetable({
                        expandable: true,
                        onNodeExpand: function () {
                            if (this.children.length > 0) return; // DO NOT FETCH CHILDREN
                            _this.nodeExpand(this);
                        },
                    });

                    // Copy additional info from xen

                    _this.enroller.treetable("reveal", parentID);
                    _this.enrollerBody.append(`<tr class="table__row" data-tt-id="paganation-${parentID}"><td class="table__cell" colspan="${_this.colspan}" align="center"><i class="fa fa-spin fa-spinner"></i> Generating data...</td></tr>`);

                })

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