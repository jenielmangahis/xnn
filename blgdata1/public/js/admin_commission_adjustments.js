(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dt: null,

            commissionTypes: [],
            commissionTypeState: "loaded", // loaded/fetching/error
            commissionType: "",

            commissionPeriods: [],
            commissionPeriodState: "loaded", // loaded/fetching/error
            commissionPeriodIndex: "",
            commissionPeriodView: "",

            purchaserId: null,
            userId: null,
            orderId: null,
            itemId: null,
            amount: null,
            level: null,
            remarks: null,
            autocompleteUrl: `${api_url}common/autocomplete/affiliates`,

            isCreateMode: 0,
            isEditMode: 0,
            isViewMode: 0,
            isProcessing: 0,


            filters: {
                id: null,
                member_id: null,
                commission_type_id: null,
                commission_period_id: null,
                purchaser_id: null,
                order_id: null,
                item_id: null,
                amount: null,
                level: null,
                remarks: null,
            },
            
            error: {
                message: null,
                type: null,
            },
        },
        mounted() {
            this.getCommissionTypes();
            this.initializeDataTables();
            this.initializeJQueryEvents();
            
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-adjustments").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/commission-adjustments`
                    },
                    order: [[0, 'asc']],
                    columns: [
                        {
                            data: 'name',
                            render: function (data, type, row, meta) {
                                let member_id = row.member_id;
                                let member = row.name;
                                return `${member_id}: ${member}`;
                            }
                        },
                        {data: 'commission_type'},
                        {data: 'commission_period', className: "text-center"},
                        {data: 'amount', className: "text-center"},
                        {
                            data: 'actions',
                            className: "text-center",
                            render: function ( data, type, full, meta ) {
                                disabled_btn = '';
                                if(parseInt(full.is_locked) == 1){
                                    disabled_btn = 'disabled="disabled"';
                                }
                                
                                let $btnView = '<button class="btn btn-info btn-sm btn-view">VIEW</button> ';
                                let $btnEdit = '<button class="btn btn-warning btn-sm btn-edit" ' + disabled_btn + '">EDIT</button> ';
                                let $btnDelete = '<button class="btn btn-danger btn-sm btn-delete" ' + disabled_btn + '">DELETE</button> ';

                                let $options = $("<span>")
                                    .addClass("text-center")
                                    .append($btnView)
                                    .append($btnEdit)
                                    .append($btnDelete);

                                return $options[0].outerHTML;
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
                let _this = this;

                $('#table-adjustments tbody').on('click', '.btn-view', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.showViewModal(data);
                });
                $('#table-adjustments tbody').on('click', '.btn-edit', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.showEditModal(data);
                });
                $('#table-adjustments tbody').on('click', '.btn-delete', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.deleteAdjustment(data);
                });
            },
            getCommissionTypes() {

                if (this.commissionTypeState === "fetching") return;

                this.commissionTypeState = "fetching";
                this.commissionTypes = [];

                client.get("common/commission-types/active-cash-manual")
                    .then(response => {
                        this.commissionTypes = response.data;
                        this.commissionTypeState = "loaded";

                    })
                    .catch(error => {
                        this.commissionTypeState = "error";
                    })
            },
            getCommissionPeriods(callback) {

                if (this.commissionPeriodState === "fetching") return;

                this.commissionPeriodState = "fetching";
                this.commissionPeriods = [];
                this.commissionPeriodIndex = "";
                
                this.filters.commission_type_id = this.commissionType;

                client.get(`common/commission-types/${this.filters.commission_type_id}/open-periods`)
                    .then(response => {
                        this.commissionPeriods = response.data;
                        this.commissionPeriodState = "loaded";
                        typeof callback == "function" && callback();
                    })
                    .catch(error => {
                        this.commissionPeriodState = "error";
                    })

            },
            showCreateModal() {

                this.isCreateMode = 1;
                this.isViewMode = 0;
                this.isEditMode = 0;
                this.filters.member_id = "";
                this.filters.purchaser_id = "";
                this.filters.order_id = "";
                this.filters.item_id = "";
                this.filters.amount = "";
                this.filters.level = "";
                this.filters.remarks = "";
                this.$refs.autocompletePurchaser.setDisabled(false);
                this.$refs.autocompleteMember.setDisabled(false);
                $('#modal-create').modal({backdrop: 'static', keyboard: false});

            },
            showViewModal(adjustment) {

                this.isViewMode = 1;
                this.isEditMode = 0;
                this.isCreateMode = 0;
                this.commissionType = adjustment.commission_type_id;
                this.getCommissionPeriods(() => {
                    this.commissionPeriodIndex = adjustment.commission_period_id;
                });
                this.filters.order_id = adjustment.transaction_id;
                this.filters.item_id = adjustment.item_id;
                this.filters.amount = adjustment.amount;
                this.filters.level = adjustment.level;
                this.filters.remarks = adjustment.remarks;
                this.$refs.autocompleteMember.setValue(`#${adjustment.member_id}: ${adjustment.name}`, adjustment.member_id);
                this.$refs.autocompleteMember.setDisabled(true);
                this.$refs.autocompletePurchaser.setValue(`#${adjustment.purchaser_id}: ${adjustment.purchaser_name}`, adjustment.purchaser_id);
                this.$refs.autocompletePurchaser.setDisabled(true);
                $('#modal-create').modal({backdrop: 'static', keyboard: false});
                
            },
            showEditModal(adjustment) {
                
                this.isEditMode = 1;
                this.isViewMode = 0;
                this.isCreateMode = 0;
                this.filters.id = adjustment.id;
                this.commissionType = adjustment.commission_type_id;
                this.getCommissionPeriods(() => {
                    this.commissionPeriodIndex = adjustment.commission_period_id;
                });
                this.filters.order_id = adjustment.transaction_id;
                this.filters.item_id = adjustment.item_id;
                this.filters.amount = adjustment.amount;
                this.filters.level = adjustment.level;
                this.filters.remarks = adjustment.remarks;
                this.$refs.autocompleteMember.setValue(`#${adjustment.member_id}: ${adjustment.name}`, adjustment.member_id);
                this.$refs.autocompleteMember.setDisabled(false);
                this.$refs.autocompletePurchaser.setValue(`#${adjustment.purchaser_id}: ${adjustment.purchaser_name}`, adjustment.purchaser_id);
                this.$refs.autocompletePurchaser.setDisabled(false);
                $('#modal-create').modal({backdrop: 'static', keyboard: false});
                
            },
            saveAdjustment() {
                
                let commissionPeriod = this.commissionPeriods[this.commissionPeriodIndex - 1];
                // this.filters.commission_period_id = commissionPeriod.id;
                this.filters.commission_period_id = this.commissionPeriodIndex;

                if(!this.filters.member_id) {
                    swal("Member is required.", "", "error");
                    return;
                }
                
                if(!this.filters.purchaser_id) {
                    swal("Purchaser is required.", "", "error");
                    return;
                }

                if(!this.filters.commission_period_id) {
                    swal("Period is required.", "", "error");
                    return;
                }

                if(this.isProcessing) return;

                swal({
                    title: "Confirm Commission Adjustments",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.isProcessing = 1;

                    client.post("admin/commission-adjustments/", this.filters).then(response => {
                        this.error.message = null;
                        this.error.type = null;

                        console.log(response.data);

                        this.dt.draw();
                        $('#modal-create').modal('hide');
                        swal('Success','','success');

                    }).catch(this.axiosErrorHandler).finally(()=> {
                        this.isProcessing = 0;
                    });

                });
            },
            updateAdjustment() {
                
                let commissionPeriod = this.commissionPeriods[this.commissionPeriodIndex - 1];
                this.filters.commission_period_id = this.commissionPeriodIndex;

                if(!this.filters.member_id) {
                    swal("Member is required.", "", "error");
                    return;
                }
                
                if(!this.filters.purchaser_id) {
                    swal("Purchaser is required.", "", "error");
                    return;
                }

                if(!this.filters.commission_period_id) {
                    swal("Period is required.", "", "error");
                    return;
                }

                if(this.isProcessing) return;

                swal({
                    title: "Confirm Updates for Commission Adjustments",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.isProcessing = 1;

                    client.post("admin/commission-adjustments/update", this.filters).then(response => {
                        this.error.message = null;
                        this.error.type = null;

                        console.log(response.data);

                        this.dt.draw();
                        $('#modal-create').modal('hide');
                        swal('Success','','success');

                    }).catch(this.axiosErrorHandler).finally(()=> {
                        this.isProcessing = 0;
                    });

                });
            },
            deleteAdjustment(data) {

                if(this.isProcessing) return;

                swal({
                    title: "Delete commission adjustments",
                    text: `Are you sure you want to delete the commission adjusments of ${data.name} (ID ${data.member_id})?`,
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.isProcessing = 1;

                    client.post(`admin/commission-adjustments/${data.id}/delete`, this.filters).then(response => {
                        this.error.message = null;
                        this.error.type = null;

                        this.dt.draw();
                        swal('Success','','success');

                    }).catch(this.axiosErrorHandler).finally(()=> {
                        this.isProcessing = 0;
                    });

                });
            },
            axiosErrorHandler(error) {
                this.isProcessing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data)

                this.error = parse.message;

                swal(this.error, "", 'error');
            },
        },
        watch: {
            commissionType: function (newType, oldType) {

                if (!!newType) {
                    this.commissionType = newType;
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

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));