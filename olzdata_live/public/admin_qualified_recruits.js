(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    $.fn.ddatepicker = $.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker

    const vm = new Vue({
        el: "#qualified-recruits",
        data: function () {
            return {
                autocompleteUrl: `${api_url}common/autocomplete/members`,
                qualifiedRecruits: {                    
                    filters: {
                        start_date: null,
                        end_date: null,
                        memberId: null                        
                    },
                },
                csvQualifiedRecruits: {
                    filters: {
                        start_date: null,
                        end_date: null,
                        memberId: null,
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

                this.dtQualifiedRecruits = $("#table-qualified-recruits").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    ajax: {
                        url: `${api_url}admin/qualified-recruits`,
                        data: function (d) {
                            d.start_date = $('#start-date').val();
                            d.end_date   = $('#end-date').val();     
                            d.memberId = _this.qualifiedRecruits.filters.memberId;       
                        },
                    },
                    order: [[0, 'asc']],                    
                    columns: [   
                        {data: 'user_id', className: "text-center"},
                        {data: 'member', className: "text-center"},
                        {data: 'enrolled_date', className: "text-center"},
                        {data: 'affiliated_date', className: "text-center"},
                        {data: 'email', className: "text-center"},
                        {data: 'country', className: "text-center"},
                        {data: 'sponsor_id', className: "text-center"},                        
                        {data: 'sponsor', className: "text-center"},
                        {data: 'total_reps', className: "text-center"},
                        {data: 'sponsored_qualified_representatives_count', className: "text-center"},
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

                $('#start-date').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#end-date').ddatepicker('setStartDate', e.date);

                    if ($('#end-date').ddatepicker('getDate') < e.date) {
                        $('#end-date').ddatepicker('setDate', e.date);
                    }
                });

                $('#end-date').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                });
            },
            viewQualifiedRecruits() {
                this.dtQualifiedRecruits.clear().draw();
                this.dtQualifiedRecruits.responsive.recalc();
            },
            getDownloadQualifiedRecruits() {

                this.csvQualifiedRecruits.filters.start_date = $('#start-date').val();
                this.csvQualifiedRecruits.filters.end_date = $('#end-date').val(); 
                this.csvQualifiedRecruits.filters.memberId = this.qualifiedRecruits.filters.memberId;
                
                if (this.csvQualifiedRecruits.downloadLinkState === "fetching") return;

                this.csvQualifiedRecruits.downloadLinkState = "fetching";
                this.csvQualifiedRecruits.downloadLink = "";

                client.get("admin/qualified-recruits/download-qualified-recruits", {
                    params: this.csvQualifiedRecruits.filters
                })
                    .then(response => {
                        this.csvQualifiedRecruits.downloadLinkState = "loaded";
                        this.csvQualifiedRecruits.downloadLink = response.data.link;

                        if (!!this.csvQualifiedRecruits.downloadLink) {
                            window.location = this.csvQualifiedRecruits.downloadLink;
                        }
                    })
                    .catch(error => {
                        this.csvQualifiedRecruits.downloadLinkState = "error";
                    })
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));