(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    const vm = new Vue({
        el: "#personal-retail-sale",
        data: function () {
            return {
                autocompleteUrl: `${api_url}common/autocomplete/members`,
                enrollment: {                    
                    filters: {
                        start_date: null,
                        end_date: null,
                        transaction_start_date : moment().format("YYYY-MM-DD"),
                        transaction_end_date : moment().format("YYYY-MM-DD"),
                        memberId: null,
                        prs_500_above : '',
                    },
                },
                csvPersonalRetail: {
                    filters: {
                        start_date: moment().format("YYYY-MM-DD"),
                        end_date: moment().format("YYYY-MM-DD"),
                        transaction_start_date : moment().format("YYYY-MM-DD"),
                        transaction_end_date : moment().format("YYYY-MM-DD"),
                        memberId: null,
                        prs_500_above : '',
                    },

                    downloadLink: "",
                    downloadLinkState: "loaded",
                },
                today: moment().format("YYYY-MM-DD"),
            }
        },
        mounted() {
            this.initializeDataTables();
            this.initializeDatePicker();
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
                            d.start_date = $('#enrollment-start-date').val();
                            d.end_date   = $('#enrollment-end-date').val();        
                            d.prs_500_above = _this.enrollment.filters.prs_500_above;
                            d.memberId = _this.enrollment.filters.memberId;
                            d.transaction_start_date = $('#transaction-start-date').val();
                            d.transaction_end_date = $('#transaction-end-date').val();                  
                        },
                    },
                    order: [[9, 'desc']],                    
                    columns: [                        
                        {data: 'top', className: "text-center"},
                        {data: 'user_id', className: "text-center"},
                        {data: 'member', className: "text-center"},
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
            initializeDatePicker() {
                let _this = this;

                $('#enrollment-start-date').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#enrollment-end-date').ddatepicker('setStartDate', e.date);

                    if ($('#enrollment-end-date').ddatepicker('getDate') < e.date) {
                        $('#enrollment-end-date').ddatepicker('setDate', e.date);
                    }
                });

                $('#enrollment-end-date').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                });

                $('#transaction-start-date').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#transaction-end-date').ddatepicker('setStartDate', e.date);

                    if ($('#transaction-end-date').ddatepicker('getDate') < e.date) {
                        $('#transaction-end-date').ddatepicker('setDate', e.date);
                    }
                });

                $('#transaction-end-date').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                });

                $('#transaction-start-date').ddatepicker('setDate', new Date());
                $('#transaction-end-date').ddatepicker('setDate', new Date());
            },
            viewPersonalRetail() {
                this.dtEnrollment.clear().draw();
                this.dtEnrollment.responsive.recalc();
            },
            getDownloadPersonalRetail() {

                this.csvPersonalRetail.filters.start_date = $('#enrollment-start-date').val();
                this.csvPersonalRetail.filters.end_date = $('#enrollment-end-date').val(); 
                this.csvPersonalRetail.filters.prs_500_above = this.enrollment.filters.prs_500_above;
                this.csvPersonalRetail.filters.memberId = this.enrollment.filters.memberId;
                this.csvPersonalRetail.filters.transaction_start_date = $('#transaction-start-date').val();
                this.csvPersonalRetail.filters.transaction_end_date = $('#transaction-end-date').val(); 
                
                if (this.csvPersonalRetail.downloadLinkState === "fetching") return;

                this.csvPersonalRetail.downloadLinkState = "fetching";
                this.csvPersonalRetail.downloadLink = "";

                client.get("admin/personal-retail-sales/download-personal-retail", {
                    params: this.csvPersonalRetail.filters
                })
                    .then(response => {
                        this.csvPersonalRetail.downloadLinkState = "loaded";
                        this.csvPersonalRetail.downloadLink = response.data.link;

                        if (!!this.csvPersonalRetail.downloadLink) {
                            window.location = this.csvPersonalRetail.downloadLink;
                        }
                    })
                    .catch(error => {
                        this.csvPersonalRetail.downloadLinkState = "error";
                    })
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));