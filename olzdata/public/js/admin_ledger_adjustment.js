(function ($, api_url, Vue, swal, axios, location, moment, _, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dt: null,

            autocompleteUrl: `${api_url}common/autocomplete/affiliates`,

            adjustment: {
                user_id: "",
                amount: 0,
                notes: "",
                type: "",
            },

            isProcessing: 0,

            error: {
                message: null,
                type: null,
            },
        },
        mounted() {

            this.initializeDataTables();
            this.initializeJQueryEvents();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-ledger-adjustment").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/ledger-adjustment`,
                    },
                    order: [[4, 'desc']],
                    columns: [
                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let member = row.member;
                                return `${user_id}: ${member}`;
                            }
                        },
                        {data: 'notes'},
                        {data: 'amount', className: "text-center"},
                        {
                            data: 'created_by_id',
                            render: function (data, type, row, meta) {
                                let created_by_id = row.created_by_id;
                                let created_by = row.created_by;
                                return `${created_by_id}: ${created_by}`;
                            }
                        },
                        {data: 'updated_at', className: "text-center"},
                        {
                            data: null,
                            width: '50px',
                            className: "table__cell--align-middle text-center",
                            orderable: false,
                            render: function (data, type, row, meta) {
                                return `
                                    <button class="btn btn-danger btn-sm btn-delete">UNDO</button>
                                `;
                            }
                        },
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: 2},
                    ]
                });
            },
            initializeJQueryEvents() {
                let _this = this;
                $('#table-ledger-adjustment tbody').on('click', '.btn-delete', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.deleteAdjustment(data);
                });
            },

            showAddModal() {

                this.adjustment.notes = "";
                this.adjustment.user_id = "";
                this.adjustment.type = "";
                this.adjustment.amount = 0;

                $('#modal-ledger-adjustment').modal({ backdrop: 'static', keyboard: false });
            },

            saveAdjustment(type) {

                if(!this.adjustment.user_id) {
                    swal("Member is required.", "", "error");
                    return;
                }

                if(this.adjustment.amount <= 0) {
                    swal("Amount must be greater than 0.", "", "error");
                    return;
                }

                if(isNaN(this.adjustment.amount)) {
                    swal("Amount must be a number", "", "error");
                    return;
                }

                this.adjustment.notes += "";

                if(this.adjustment.notes.length < 3) {
                    swal("Note is required (Min. 3 character).", "", "error");
                    return;
                }

                this.adjustment.type = type;

                if(this.isProcessing) return;

                let title = `Ledger adjustment`;
                let text = `Are you sure you want to ${type} ${this.adjustment.amount} amount to User ID ${this.adjustment.user_id}?`;

                swal({
                    title: title,
                    text: text,
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "Cancel",
                            value: null,
                            visible: false,
                            className: "",
                            closeModal: true,
                        },
                        confirm: {
                            text: "Confirm",
                            value: true,
                            visible: true,
                            className: "btn-success",
                            closeModal: true
                        }
                    },
                    closeModal: false,
                })
                .then((result) => {
                    if( result ){
                        this.isProcessing = 1;

                        client.post("admin/ledger-adjustment", this.adjustment).then(response => {
                            this.error.message = null;
                            this.error.type = null;

                            console.log(response.data);

                            this.dt.draw();
                            $('#modal-ledger-adjustment').modal('hide');
                            swal('Success','','success');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });
                    }
                });
            },
            deleteAdjustment(data) {

                if(this.isProcessing) return;

                swal({
                    title: "Undo Adjustment",
                    text: `Are you sure you want to undo this ledger adjustment of User ID ${data.user_id} (${data.notes})?`,
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "Cancel",
                            value: null,
                            visible: false,
                            className: "",
                            closeModal: true,
                        },
                        confirm: {
                            text: "Confirm",
                            value: true,
                            visible: true,
                            className: "btn-success",
                            closeModal: true
                        }
                    },
                    closeModal: false,
                })
                .then((result) => {
                    if( result ){
                        this.isProcessing = 1;

                        client.post(`admin/ledger-adjustment/${data.id}/delete`).then(response => {
                            this.error.message = null;
                            this.error.type = null;

                            this.dt.draw();
                            swal('Success','','success');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });
                    }
                });
            },
            axiosErrorHandler(error) {

                let data = commissionEngine.parseAxiosErrorData(error.response.data);

                this.error.message = data.message;
                this.error.type = data.type;
                this.error.data = data.data;

                swal(this.error.message, "", "error");
            },

        },

    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment, _));