(function ($, api_url, Vue, swal, axios, location, moment, _, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dt: null,

            autocompleteUrl: `${api_url}common/autocomplete/affiliates`,
            ranks: [],
            rankState: "loaded", // loaded/fetching/error

            minimumRank: {
                rank_id: "",
                user_id: "",
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
            },
            isEditMode: 0,
            today: moment().format("YYYY-MM-DD"),
            isProcessing: 0,

            error: {
                message: null,
                type: null,
            },
        },
        mounted() {
            this.getRanks();
            this.initializeDataTables();
            this.initializeJQueryEvents();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-minimum-rank").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search...",
                        paginate: {
                          next: 'Next',
                          previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/minimum-rank`,
                    },
                    order: [[0, 'asc']],
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
                            data: 'rank_id',
                            render: function (data, type, row, meta) {
                                return row.minimum_rank;
                            }
                        },
                        {data: 'start_date', className: "text-center"},
                        {data: 'end_date', className: "text-center"},
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
                                    <button class="btn btn-danger btn-sm btn-delete">DELETE</button>
                                    <button class="btn btn-info btn-sm btn-edit">EDIT</button>
                                `;
                            }
                        },
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                    ]
                });
            },
            initializeJQueryEvents() {
                let _this = this;
                $('#table-minimum-rank tbody').on('click', '.btn-delete', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.deleteMinimumRank(data);
                });
                $('#table-minimum-rank tbody').on('click', '.btn-edit', function ()  {
                    let data = _this.dt.row($(this).parents('tr')).data();
                    _this.showEditModal(data);
                });
            },
            getRanks() {

                if (this.rankState === "fetching") return;

                this.rankState = "fetching";
                this.ranks = [];

                client.get("common/ranks")
                    .then(response => {
                        this.ranks = response.data;
                        this.rankState = "loaded";

                    })
                    .catch(error => {
                        this.rankState = "error";
                    })

            },
            showAddModal() {
                this.isEditMode = 0;
                this.minimumRank.rank_id = "";
                this.minimumRank.user_id = "";
                this.minimumRank.start_date = moment().format("YYYY-MM-DD");
                this.minimumRank.end_date = moment().format("YYYY-MM-DD");
                this.$refs.autocompleteMember.setDisabled(false);
                $('#modal-minimum-rank').modal({ backdrop: 'static', keyboard: false });
            },
            showEditModal(data) {
                this.isEditMode = 1;
                this.minimumRank.rank_id = data.rank_id;
                this.minimumRank.user_id = data.user_id;
                this.minimumRank.start_date = data.start_date;
                this.minimumRank.end_date = data.end_date;
                this.$refs.autocompleteMember.setValue(`#${data.user_id}: ${data.member}`, data.user_id);
                this.$refs.autocompleteMember.setDisabled(true);
                $('#modal-minimum-rank').modal({ backdrop: 'static', keyboard: false });
            },
            saveMinimumRank() {

                if(!this.minimumRank.user_id) {
                    swal("Member is required.", "", "error");
                    return;
                }

                if(!this.minimumRank.rank_id) {
                    swal("Rank is required.", "", "error");
                    return;
                }

                if(this.isProcessing) return;

                this.getMinimumRank(this.minimumRank.user_id, data => {


                    let hasMinimumRank = false;

                    if(!_.isEmpty(data)) {
                        hasMinimumRank = true;
                    }

                    let title = "Set Minimum Rank";
                    let text = `Are you sure you want to set a minimum rank to User ID ${this.minimumRank.user_id}?`;

                    if(this.isEditMode) {
                        title = "Edit Minimum Rank";
                        text = `Are you sure you want to edit the minimum rank of User ID ${this.minimumRank.user_id}?`
                    } else if(hasMinimumRank) {
                        title = "Overwrite Minimum Rank";
                        text = `A minimum rank is already set to User ID ${this.minimumRank.user_id}. Do you want to overwrite it?`
                    }

                    swal({
                        title: title,
                        text: text,
                        type: "warning",
                        confirmButtonClass: "btn-success",
                        confirmButtonText: "Confirm",
                        cancelButtonText: "Cancel",
                        showCancelButton: true,
                        closeOnConfirm: false,
                        showLoaderOnConfirm: true,
                    }, () => {

                        this.isProcessing = 1;

                        client.post("admin/minimum-rank", this.minimumRank).then(response => {
                            this.error.message = null;
                            this.error.type = null;

                            console.log(response.data);

                            this.dt.draw();
                            $('#modal-minimum-rank').modal('hide');
                            swal('Success','','success');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            this.isProcessing = 0;
                        });

                    });
                });

            },
            deleteMinimumRank(data) {

                if(this.isProcessing) return;

                swal({
                    title: "Delete minimum rank",
                    text: `Are you sure you want to delete the minimum rank of ${data.member} (ID ${data.user_id})?`,
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.isProcessing = 1;

                    client.post(`admin/minimum-rank/${data.user_id}/delete`, this.minimumRank).then(response => {
                        this.error.message = null;
                        this.error.type = null;

                        this.dt.draw();
                        swal('Success','','success');

                    }).catch(this.axiosErrorHandler).finally(()=> {
                        this.isProcessing = 0;
                    });

                });
            },
            getMinimumRank(user_id, callback) {

                this.isProcessing = 1;

                client.get(`admin/minimum-rank/${user_id}`, this.minimumRank).then(response => {
                    callback(response.data);
                    this.isProcessing = 0;
                }).catch(error => {
                    callback({});
                    this.isProcessing = 0;
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