(function ($, api_url, Vue, swal, axios, location, moment, _, undefined) {

    // Initialize datepicker
    $.fn.ddaterangepicker = $.fn.daterangepicker; // jquery-ui is overriding the bootstrap-daterangepicker

    const client = commissionEngine.createAccessClient('member/party-manager');
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dtOpenEvents = null;
    let $dtPastEvents = null;
    let $dtTopHostesses = null;
    let $dtOrders = null;

    const vm = new Vue({
        el: ".tool-container",
        data: {
            isProcessing: 0,
            is_fetching: 0,
            error: null,

            autocompleteUrl: `${api_url}common/autocomplete/enroller-customer-downline`,
            
            event: {
                hostess_id: "",
                period: "",
            },

            top_hostess: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                }
            },

            today: moment().format("YYYY-MM-DD"),

            error: {
                message: null,
                type: null,
            },
        },
        mounted() {
            this.member_id = $('#member').val();
            this.initializeDataTables();
            this.initializeJQueryEvents();
            
        },
        methods: {
            initializeDataTables() {
                
                let _this = this;

                $dtOpenEvents = $("#table-open-events").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 20,
                    ajax: {
                        url : `${api_url}member/party-manager/open-events`,
                    },
                    columns: [
                        {data: 'hostess'},
                        {data: 'sharing_link'},
                        {data: 'start_date', className: "text-center"},
                        {data: 'end_date', className: "text-center"},
                        {data: 'time_left', className: "text-center"},
                        {data: 'total_sales', className: "text-center", render: $.fn.dataTable.render.number( ',', '.', 2, '$' )},
                        {
                            data: 'hostess_program_id',
                            className: "text-center",
                            render: function ( data, type, row, meta ) {
                                return `<a class="btn-view-orders">View</a>`;
                            }
                        },
                        // {
                        //     data: null,
                        //     width: '50px',
                        //     className: "table__cell--align-middle text-center",
                        //     orderable: false,
                        //     render: function (data, type, row, meta) {
                        //         return `
                        //             <button class="btn btn-danger btn-sm btn-delete">DELETE</button>
                        //         `;
                        //     }
                        // },
                        {
                            data: 'is_active',
                            width: '50px',
                            className: "table__cell--align-middle text-center",
                            orderable: false,
                            render: function ( data, type, full, meta ) {

                                if (data == 1) 
                                    return '<button class="btn btn-danger btn-sm btn-delete">DELETE</button>';
                                else 
                                    return '<button class="btn btn-danger btn-sm btn-delete" disabled>DELETE</button>';
                    
                            }
                        },
                    ],
                    order: [[ 4, 'asc' ]],
                });

                $dtPastEvents = $("#table-past-events").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 20,
                    ajax: {
                        url : `${api_url}member/party-manager/past-events`,
                    },
                    columns: [
                        {data: 'hostess'},
                        {data: 'sharing_link'},
                        {data: 'start_date'},
                        {data: 'end_date'},
                        {data: 'total_sales', className: "text-center", render: $.fn.dataTable.render.number( ',', '.', 2, '$' )},
                        {
                            data: 'hostess_program_id',
                            className: "text-center",
                            render: function ( data, type, row, meta ) {
                                return `<a class="btn-view-orders">View</a>`;
                            }
                        },
                    ],
                    order: [[ 3, 'desc' ]]
                });

                $dtTopHostesses = $("#table-top-hostesses").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 20,
                    ajax: {
                        url : `${api_url}member/party-manager/top-hostesses`,
                        data: function (d) {
                            d.start_date = _this.top_hostess.filters.start_date;
                            d.end_date = _this.top_hostess.filters.end_date;
                        },
                    },
                    columns: [
                        {data: 'hostess'},
                        {data: 'sharing_link'},
                        {data: 'total_sales', className: "text-center", render: $.fn.dataTable.render.number( ',', '.', 2, '$' )},
                    ],
                    order: [[ 2, 'desc' ]]
                });

                $dtOrders = $("#table-view-orders").DataTable({
                    columns: [
                        {data: 'customer'},
                        {data: 'order_id', className: "text-center"},
                        {data: 'description'},
                        {data: 'amount', className: "text-center"},
                    ]
                });
            },
            initializeJQueryEvents() {
                let _this = this;

                $('#table-open-events tbody').on('click', '.btn-view-orders', function ()  {
                    let data = $dtOpenEvents.row($(this).parents('tr')).data();
                    _this.viewOrders(data);
                });

                $('#table-open-events tbody').on('click', '.btn-delete', function ()  {
                    let row = $(this).parents('tr');
                    if (row.hasClass('child')) {
                        row = row.prev();
                    }

                    let data = $dtOpenEvents.row(row).data();
                    _this.deleteEvent(data);
                });

                $('#table-past-events tbody').on('click', '.btn-view-orders', function ()  {
                    let data = $dtPastEvents.row($(this).parents('tr')).data();
                    _this.viewOrders(data);
                });
                
            },
            initDateRangePickers(start_date, end_date) {
                var startDate = moment(start_date).add(1, 'days').format('MM/DD/YYYY');
                var endDate = moment(end_date).add(1, 'days').format('MM/DD/YYYY');
                var nowDate = new Date();
                var today = new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate(), 0, 0, 0, 0);
                $('input#dt-promo-period').ddaterangepicker({
                    "startDate" : startDate,
                    "endDate" : endDate,
                    "minDate" : startDate,
                    "maxSpan": {
                        "days": 13
                    },
                    "locale" : {"format": "MM/DD/YYYY"}
                });
            },
            destroyDateRangePickers() {
                if ($('input#dt-promo-period').data('daterangepicker') !== undefined) {
                    $('input#dt-promo-period').data('daterangepicker').remove();
                }
            },
            showCreateModal() {
                this.event.hostess_id = "";
                this.$refs.autocompleteMember.setDisabled(false);
                this.initDateRangePickers(this.event.start_date, this.event.end_date);
                $('#modal-create').modal({ backdrop: 'static', keyboard: false });
            },
            saveEvent() {
                if(!this.event.hostess_id) {
                    swal("Hostess is required.", "", "error");
                    return;
                }

                if(this.isProcessing) return;

                this.event.period = $('input[name="dt-promo-period"]').val();

                swal({
                    title: "Create Event",
                    text: "Are you sure you want to create this event?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["Cancel", "Confirm"],
                    closeModal: true,
                }).then((event) => {

                    if(event) {
                        this.isProcessing = 1;

                        client.post("create", this.event).then(response => {

                            this.error.message = null;
                            this.error.type = null;

                            $dtOpenEvents.clear().draw();
                            $dtOpenEvents.responsive.recalc();
                            $('#modal-create').modal('hide');
                            swal('Success','','success');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });
                    }
                });
            },     
            deleteEvent(data) {
                if(this.isProcessing) return;

                swal({
                    title: "Delete Event",
                    text: "Are you sure you want to delete this event?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["Cancel", "Confirm"],
                    closeModal: true,
                }).then((deleteEvent) => {

                    if(deleteEvent) {
                        this.isProcessing = 1;

                        client.post(`${data.hostess_program_id}/delete`).then(response => {
                            
                            this.error.message = null;
                            this.error.type = null;

                            $dtOpenEvents.draw();
                            swal('Success','','success');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });
                    }

                });
            },      
            viewOrders(data) {
                $dtOrders.clear().draw();
                $('#modal-view-orders').modal({backdrop: 'static', keyboard: false});

                client.get(`orders/${data.hostess_program_id}`).then(response => {
                    $dtOrders.rows.add(response.data);
                    $dtOrders.columns.adjust().draw();
                });
            },       
            viewTopHostesses() {

                this.top_hostess.filters.start_date = this.top_hostess.start_date;
                this.top_hostess.filters.end_date = this.top_hostess.end_date;

                $dtTopHostesses.clear().draw();
                $dtTopHostesses.responsive.recalc();
            },  
            axiosErrorHandler(error) {
                
                this.isProcessing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data)

                this.error = parse.message;

                swal(this.error, "", 'error');
            },        
        },

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment, _));