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
                        period: null,
                        memberId: null                        
                    },
                },
                csvQualifiedRecruits: {
                    filters: {
                        period: null,
                        memberId: null,
                    },

                    downloadLink: "",
                    downloadLinkState: "loaded",
                },
                userReps: [],
                user_id: null,
                listRep:{
                    filters:{
                        period: null,
                        userId: null,
                    },                    
                },
                today: moment().format("YYYY-MM-DD"),
            }
        },
        mounted() {
            this.initializeDataTables();
            this.initializeDatePicker();
            this.initializeJQueryEvents();
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
                            d.period = $('#report-date').val();  
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
                        {
                            data: 'action',
                            render: function (data, type, row, meta) {
                                return '<a href="javascript:void(0);" class="btn-view-reps" data-id="'+row.user_id+'">'+row.total_reps+'</a>';
                            }
                        },
                        {
                            data: 'action',
                            render: function (data, type, row, meta) {
                                return '<a href="javascript:void(0);" class="btn-view-qualified-reps" data-id="'+row.user_id+'">'+row.sponsored_qualified_representatives_count+'</a>';
                            }
                        },
                    ]
                    /*
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -3},
                        {responsivePriority: 4, targets: -4},
                    ]
                    */
                });

                this.dtListReps = $("#table-reps-list").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    searching: false,
                    ajax: {
                        url: `${api_url}admin/qualified-recruits/user-representative-list`,
                        data: function (d) {
                            d.period = $('#report-date').val();  
                            d.userId = _this.listRep.filters.userId; 
                        },
                    },
                    order: [[0, 'asc']],                    
                    columns: [                         
                        {data: 'user_id', className: "text-left", width: '100px'},
                        {data: 'member_name', className: "text-left"},
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                    ]
                });

                this.dtQualifiedListReps = $("#table-qualified-reps-list").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    searching: false,
                    ajax: {
                        url: `${api_url}admin/qualified-recruits/user-qualified-representative-list`,
                        data: function (d) {
                            d.period = $('#report-date').val();  
                            d.userId = _this.listRep.filters.userId; 
                        },
                    },
                    order: [[0, 'asc']],                    
                    columns: [   
                        {data: 'user_id', className: "text-left", width: '80px'},
                        {data: 'member_name', className: "text-left"},
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                    ]
                });
            },
            initializeJQueryEvents() {

                let _this = this;

                $('#table-qualified-recruits').on('click', '.btn-view-reps', function () {
                    let row_user_id = $(this).attr('data-id');
                    _this.getRepsList(row_user_id);
                });

                $('#table-qualified-recruits').on('click', '.btn-view-qualified-reps', function () {
                    let row_user_id = $(this).attr('data-id');
                    _this.getQualifiedRepsList(row_user_id);
                });
            },
            initializeDatePicker() {
                let _this = this;

                /*
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
                */

                $('#report-date').ddatepicker({
                    "setDate" : new Date(),
                    "format": "yyyy-mm",
                    "autoclose": true,
                    "startView": 1,
                    viewMode: "months",
                    minViewMode: "months",
                    "endDate" : new Date()
                });
            },
            viewQualifiedRecruits() {
                this.dtQualifiedRecruits.clear().draw();
                this.dtQualifiedRecruits.responsive.recalc();
            },
            getDownloadQualifiedRecruits() {

                this.csvQualifiedRecruits.filters.period = $('#report-date').val();
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
            getRepsList: function (user_id) {
                this.listRep.filters.period = $('#report-date').val();
                this.listRep.filters.userId = user_id;
                this.user_id = user_id;

                this.dtListReps.clear().draw();
                this.dtListReps.responsive.recalc();

                $('#modal-user-representative-list').modal('show');
            },
            getQualifiedRepsList: function (user_id) {
                this.listRep.filters.period = $('#report-date').val();
                this.listRep.filters.userId = user_id;
                this.user_id = user_id;

                this.dtQualifiedListReps.clear().draw();
                this.dtQualifiedListReps.responsive.recalc();

                $('#modal-qualified-user-representative-list').modal('show');
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));