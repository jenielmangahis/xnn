(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient('member/ledger');
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dtLedger = null;
    let $dtWithdrawals = null;

    const vm = new Vue({
        el: ".tool-container",
        mounted() {
            this.hadSignup(had_signup => {
                this.had_signup = had_signup;
                if(! this.had_signup) {
                    // window.location.replace("https://office.stg1-mydefinelife.xyz/nxm_money.cgi?p=affiliate_payquicker");
                    return;
                }
            });
            this.getTotalBalance();
            this.initializeDataTables();
        },
        data: {
            is_processing: 0,
            is_fetching: 0,
            error: null,
            total_balance: 0,
            transfer: {
                member_id: null,
                amount: 0,
            },
            withdraw: {
                amount: 0,
            },
            had_signup: -1,
            autocomplete_url: `${api_url}common/autocomplete/enroller-downline`,
            error: {
                message: null,
                type: null,
            },
        },
        methods: {
            initializeDataTables() {

                $dtLedger = $("#table-ledger").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 50,
                    ajax: {
                        url : `${api_url}member/ledger`,
                    },
                    columns: [
                        {data: 'date',},
                        {data: 'notes',  orderable: false,},
                        {data: 'amount', render: $.fn.dataTable.render.number( ',', '.', 2, '$' ), },
                    ],
                    order: [[ 0, 'desc' ]],
                    // columnDefs: [
                    //     {responsivePriority: 1, targets: 0},
                    //     {responsivePriority: 2, targets: -1},
                    // ]
                });

                $dtWithdrawals = $("#table-withdraw").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 50,
                    ajax: {
                        url : `${api_url}member/ledger/withdrawal`,
                    },
                    columns: [
                        {data: 'date'},
                        {data: 'amount', render: $.fn.dataTable.render.number( ',', '.', 2, '$' )},
                        {data: 'status'},
                    ],
                    order: [[ 0, 'desc' ]]
                });
            },
            initializeJQueryEvents() {

                $('#nav-tab-report a[data-toggle="tab"]').on('shown.bs.tab',  (e) => {

                    let tab = $(e.target).attr("href");

                    if(tab === "#ledger") {
                        $dtLedger.responsive.recalc();
                    } else if(tab === "#withdrawals") {
                        $dtWithdrawals.responsive.recalc();
                    }

                })
            },
            hadSignup(callback) {
                client.get('had-signup').then(response => {
                    this.is_fetching = 0;
                    callback( +response.data.had_signup);
                }).catch(error => {
                    this.is_fetching = 0;
                    console.error("Something went wrong");
                });
            },
            getTotalBalance() {
                if(this.is_fetching === 1) return;

                this.is_fetching = 1;

                client.get('total-balance').then(response => {
                    this.is_fetching = 0;
                    this.total_balance = +response.data.total_balance;
                }).catch(error => {
                    this.is_fetching = 0;
                    swal("Unable to fetch total balance.", "", "error")
                });
            },
            refresh() {
                this.getTotalBalance();
                $dtLedger.draw();
                $dtWithdrawals.draw();
                $dtLedger.responsive.recalc();
                $dtWithdrawals.responsive.recalc();
            },
            clearError() {
                this.error.message = null;
                this.error.type = null;
                this.error.data;
            },
            showTransfer() {
                this.transfer.amount = 0;
                this.transfer.member_id = null;
                this.clearError();
                $('#modal-transfer').modal({ backdrop: 'static', keyboard: false });
            },
            showWithdraw() {
                this.withdraw.amount = this.total_balance;
                this.clearError();
                $('#modal-withdraw').modal({ backdrop: 'static', keyboard: false });
            },
            axiosErrorHandler(error) {
                this.is_processing = 0;

                let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                this.error.message = parse.message;
                this.error.type = parse.type;
                this.error.data = parse.data;

                swal(this.error.message, "", 'error');
            },
            transferFund() {
                if(this.is_processing === 1) return;

                swal({
                    title: "Are you sure you want to proceed?",
                    text: "You cannot undo this.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm Transfer",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {
                    this.is_processing = 1;

                    client.post("transfer", this.transfer).then(response => {
                        this.is_processing = 0;
                        $dtLedger.draw();
                        this.getTotalBalance();
                        $('#modal-transfer').modal('hide');
                        swal('Success!','','success');

                    }).catch(this.axiosErrorHandler);

                });
            },
            withdrawFund() {
                if(this.is_processing === 1) return;

                swal({
                    title: "Are you sure you want to proceed?",
                    text: "You cannot undo this.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm Withdraw",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {
                    this.is_processing = 1;

                    client.post("withdraw", this.withdraw).then(response => {
                        this.is_processing = 0;
                        this.refresh();
                        $('#modal-withdraw').modal('hide');
                        swal('Success!','','success');
                    }).catch(this.axiosErrorHandler);

                });
            }
        },

    });



}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));