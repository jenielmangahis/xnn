(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#rank-history",
        data: {
            filters: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
                selectedStatus: "Select an option"
            },

            today: moment().format("YYYY-MM-DD"),
            dtEnergyAccount: null,
            dtStatusHistory: null,
            statuses: [],
            approvePendingFlowingCount: 0,
            cancelledCount: 0,
            flowingCount: 0,
            flowingPendingCancellation: 0,
            pendingApprovalCount: 0,
            pendingConfirmationCount: 0,
            pendingRejectionCount: 0,
            referenceId: null
        },
        mounted() {
            this.loadStatuses();
            this.initializeDataTables();
            this.initializeJQueryEvents();
            this.loadStatusCount();

            this.dtEnergyAccount.responsive.recalc();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtEnergyAccount = $("#table-energy-accounts").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/energy-accounts`,
                        data: function (d) {
                            d.start_date = _this.filters.start_date;
                            d.end_date = _this.filters.end_date;
                            d.status_id = _this.filters.selectedStatus;
                        },
                    },
                    order: [[0, 'desc']],
                    columns: [
                        {data: 'customer_id'},
                        {data: 'customer'},
                        {data: 'associate'},
                        {
                            data: 'associate_id',
                            className: 'text-center'
                        },
                        {data: 'associate_sponsor'},
                        {data: 'account'},
                        {data: 'reference_id'},
                        {
                            data: 'date_accepted',
                            render: function (data, type, row, meta) {
                                let date = row.date_accepted;

                                if(date === null) {
                                    return 'N/A';
                                }

                                return moment(date).format('YYYY-MM-DD');
                            },
                            className: 'text-center'
                        },
                        {
                            data: 'date_started_flowing',
                            render: function (data, type, row, meta) {
                                let date = row.date_started_flowing;

                                if(date === null) {
                                    return 'N/A';
                                }

                                return moment(date).format('YYYY-MM-DD');
                            },
                            className: 'text-center'
                        },
                        {data: 'status'},
                        {
                            data: null,
                            render: function (data, type, row, meta) {
                                // return `<a href="#" class="show-status-history" data-toggle="modal" data-target="#modal-status-history" data-reference-id="${row.reference_id}">View</a>`;
                                return `<a href="#" class="show-status-history" data-energy-id="${row.energy_id}">View</a>`;
                            },
                            className: 'text-center'
                        },
                        {data: 'energy_id'},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: 2},
                        {
                            "targets": [ 11 ],
                            "visible": false,
                        },

                    ]
                });

                this.dtStatusHistory = $("#table-status-history").DataTable({
                    processing: true,
                    serverSide: false,
                    responsive: true,
                    data: [],
                    order: [[0, 'desc']],
                    columns: [
                        {data: 'type'},
                        {
                            data: 'created_date',
                            render: function (data, type, row, meta) {
                                return moment(row.created_date).format('YYYY-MM-DD');
                            }
                        },
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                    ]
                });
            },
            initializeJQueryEvents() {
                let _this = this;

/*                 $('#modal-status-history').on('shown.bs.modal', function () {
                    // this.showStatusHistory(this.referenceId);
                    // console.log('TRIGGERED');
                }); */

                $('#table-energy-accounts tbody').on('click', '.show-status-history', function() {
                    _this.referenceId = $(this).data('energy-id');
                    _this.showStatusHistory();
                    $('#modal-status-history').modal('show');
                });
            },
            filter() {
                this.dtEnergyAccount.clear().draw();
                this.loadStatusCount();
            },
            loadStatuses() {
                client.get(`${api_url}admin/energy-accounts/status`)
                    .then(response => {
                        this.statuses = response.data;
                    })
                    .catch(error => {
                        console.error(error);
                    })
            },

            loadStatusCount() {
                client.get(`${api_url}admin/energy-accounts/status-count?start_date=${this.filters.start_date}&end_date=${this.filters.end_date}&status_id=${this.filters.selectedStatus}`)
                    .then(response => {
                        this.approvePendingFlowingCount = response.data.approved_pending_flowing_count;
                        this.cancelledCount =  response.data.cancelled_count;
                        this.flowingCount =  response.data.flowing_count;
                        this.flowingPendingCancellation =  response.data.flowing_pending_cancellation;
                        this.pendingApprovalCount =  response.data.pending_approval_count;
                        this.pendingConfirmationCount =  response.data.pending_confirmation_count;
                        this.pendingRejectionCount =  response.data.pending_rejection_count;
                    })
                    .catch(error => {
                        console.error(error);
                    });
            },

            showStatusHistory() {
                client.get(`${api_url}admin/energy-accounts/status/${this.referenceId}`)
                    .then(response => {
                        this.dtStatusHistory.clear().draw();
                        this.dtStatusHistory.rows.add(response.data);
                        this.dtStatusHistory.columns.adjust().draw();
                        this.dtStatusHistory.responsive.recalc();
                    })
                    .catch(error => {
                        console.error(error);
                    })
            }, 
            
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));