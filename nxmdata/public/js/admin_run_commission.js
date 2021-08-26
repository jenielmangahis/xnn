(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data() {
            return {
                commissionType: "",
                commissionTypes: [],
                commissionPeriod: "",
                commissionPeriods: [],
                background: null,
                processes: [],
                is_generating: 0,
                is_cancelling: 0,
                is_viewing_previous_run: 0,
                is_locking: 0,
                error: {
                    message: null,
                    type: null,
                },
                seek: 0,
                lastSeek: 0,
                lines: [],
                logInterval: null,
                detailsInterval : null,
                logSecondsInterval: 10,
                detailsSecondsInterval : 10,
            };
        },
        mounted() {
            this.getCommissionTypes();
        },
        methods: {
            getCommissionTypes() {

                client.get('common/commission-types/active-cash-manual').then(response => {
                    this.commissionTypes = response.data;
                }).catch(error => {
                    let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                    swal(parse.message,'','error')
                })
            },
            clearError() {
                this.error.message = null;
                this.error.type = null;
            },
            onChangeCommissionTypes() {
                if(this.commissionType === "") return;
                this.commissionPeriod = "";
                this.commissionPeriods = [];

                this.clearError();
                this.clearLog();
                this.clearDetails();

                client.get(`common/commission-types/${this.commissionType.id}/open-periods`).then(response => {
                    this.commissionPeriods = response.data;
                }).catch(error => {
                    let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                    swal(parse.message,'','error')
                });
            },
            onChangeCommissionPeriods() {

                this.clearError();
                this.clearLog();
                this.clearDetails();
            },
            run() {
                if(this.commissionPeriod === "" || this.is_generating === 1) return;

                let $this = this;

                swal({
                    title: "Are you sure you want to run the commission?",
                    // text: "",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: true,
                    closeOnCancel: true
                }, isConfirm => {

                    if (!isConfirm) return;
                    this.is_generating = 1;
                    client.post(`admin/run-commission/commission-periods/${this.commissionPeriod.id}/run`).then(response => {
                        this.error.message = null;
                        this.error.type = null;
                        this.showBackgroundWorker(response.data);
                        this.is_generating = 0;
                    }).catch(error => {
                        let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                        if(!!parse.data && !!parse.data.background) {
                            this.showBackgroundWorker(parse.data);
                        }

                        this.gotoError();
                        this.error.message = parse.message;
                        this.error.type = parse.type;
                        this.is_generating = 0;
                    });

                });
            },
            cancelRun() {

                if(this.is_cancelling === 1) return;

                swal({
                    title: "Are you sure you want to CANCEL the commission run?",
                    // text: "",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "No",
                    closeOnConfirm: true,
                    closeOnCancel: true
                }, isConfirm => {

                    if (!isConfirm) return;

                    this.is_cancelling = 1;

                    client.post(`admin/run-commission/background-worker/${this.background.id}/cancel`).then(response => {
                        let data = response.data;

                        this.stopLog();
                        this.stopDetails();
                        this.processes = data.processes;
                        this.background = data.background;
                        this.is_cancelling = 0;
                    }).catch(error => {
                        this.is_cancelling = 0;
                    });

                });
            },
            showBackgroundWorker(data) {
                this.background = data.background;
                this.processes = data.processes;

                /*if(+this.commissionType.id === 5 || +this.commissionType.id === 1) {
                    this.logSecondsInterval = 10;
                    this.detailsSecondsInterval = 10;
                } else {
                    this.logSecondsInterval= 60;
                    this.detailsSecondsInterval = 60;
                }*/

                this.clearLog();
                this.startDetails();
                this.startLog();
            },
            startLog() {
                this.fetchLog();
                if(this.logInterval !== null) {
                    this.stopLog();
                }
                this.logInterval = setInterval(this.fetchLog, 1000 * this.logSecondsInterval);
            },
            fetchLog() {

                if(this.background == null || this.background.id === undefined) {
                    this.clearLog();
                    return;
                }

                if(this.background.is_running !== 'YES') {
                    this.stopLog();
                    return;
                }

                // if(this.seek !== 0 && this.seek === this.lastSeek) return;

                // this.lastSeek = this.seek;

                client.get(`admin/run-commission/background-worker/${this.background.id}/log?seek=${this.seek}`).then(response => {
                    let data = response.data;
                    if(this.seek !== data.seek) {
                        this.lines = [...data.lines.reverse(), ...this.lines];
                        this.seek = data.seek;
                    }
                });

            },
            stopLog() {
                clearInterval(this.logInterval);
                this.logInterval = null;
            },
            clearLog() {
                this.stopLog();
                this.seek = 0;
                this.lines = [];
            },
            startDetails() {
                this.fetchDetails();
                this.detailsInterval = setInterval(this.fetchDetails, 1000 * this.detailsSecondsInterval);
            },
            fetchDetails() {
                if(this.background == null || this.background.id === undefined) {
                    this.clearDetails();
                    return;
                }

                if(this.background.is_running !== 'YES') {
                    this.stopDetails();
                    return;
                }

                client.get(`admin/run-commission/background-worker/${this.background.id}/details`).then(response => {
                    let data = response.data;

                    this.processes = data.processes;
                    this.background = data.background;

                    let remaining = +this.background.total_task - +this.background.total_task_done;

                    if(remaining === 0 && this.background.is_running === 'YES') {
                        this.stopLog();
                        this.completed();
                    }
                })
            },
            stopDetails() {
                clearInterval(this.detailsInterval);
                this.detailsInterval = null;
            },
            clearDetails() {
                this.stopDetails();
                this.background = null;
                this.processes = [];
            },
            completed() {

                client.post(`admin/run-commission/background-worker/${this.background.id}/completed`).then(response => {
                    let data = response.data;
                    this.processes = data.processes;
                    this.background = data.background;
                    this.stopDetails();
                });
            },
            lockCommissionPeriod() {

                if(this.is_locking === 1) return;

                swal({
                    title: "Are you sure you want to lock this commission period?",
                    text: "Make sure to REVIEW the payout first before locking the period. You cannot undo this action.",
                    type: "warning",
                    confirmButtonClass: "btn-success",
                    confirmButtonText: "Confirm",
                    cancelButtonText: "Cancel",
                    showCancelButton: true,
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true,
                }, () => {

                    this.is_locking = 1;

                    client.post(`${api_url}admin/run-commission/commission-periods/${this.commissionPeriod.id}/lock`).then(response => {
                        let period = response.data;

                        if(+period.is_locked === 1) {
                            location.reload(false);
                        } else {
                            swal.close();
                        }

                        this.is_locking = 0;
                    }).catch(error => {
                        let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                        swal.close();
                        this.gotoError();

                        this.error.message = parse.message;
                        this.error.type = parse.type;
                        this.is_locking = 0;
                    })

                });
            },
            viewPreviousRun() {
                if(this.is_viewing_previous_run === 1) return;

                this.is_viewing_previous_run = 1;

                client.post(`${api_url}admin/run-commission/commission-periods/${this.commissionPeriod.id}/view-previous-run`).then(response => {
                    this.is_viewing_previous_run = 0;
                    this.error.message = null;
                    this.error.type = null;

                    let data = response.data;

                    if(data.background.is_running === 'YES') {
                        this.showBackgroundWorker(data);
                    } else {
                        this.background = data.background;
                        this.processes = data.processes;
                        this.seek = 0;
                        this.lines = [];

                        /*if(+this.commissionType.id === 5 || +this.commissionType.id === 1) {
                            this.logSecondsInterval = 10;
                            this.detailsSecondsInterval = 10;
                        } else {
                            this.logSecondsInterval= 60;
                            this.detailsSecondsInterval = 60;
                        }*/

                        client.get(`${api_url}admin/run-commission/background-worker/${this.background.id}/log?seek=${this.seek}`).then(response => {
                            let data = response.data;

                            this.lines = [...data.lines.reverse(), ...this.lines];
                            this.seek = data.seek;
                        });
                    }
                }).catch(error => {
                    this.gotoError();
                    let parse = commissionEngine.parseAxiosErrorData(error.response.data);

                    this.error.message = parse.message;
                    this.error.type = parse.type;
                    this.is_viewing_previous_run = 0;
                })
            },
            gotoError() {
                location.hash = "";
                location.hash = "#tool-container__header"
                // swal(this.error.message,'', this.error.type === 'danger' ? 'error' : this.error.type);
            },
        },
        computed: {
            progress() {
                return this.lines.filter(l => l.indexOf("          ") !== -1).length;
            },
            progressPercentage() {
                if(this.background === null) return '0%';

                if(this.background.is_running === 'COMPLETED' || this.background.is_running === 'CANCELLED') return '100%';

                let percentage = (this.progress / this.background.loop) * 100;

                if(percentage >= 99.99) {
                    percentage = 99.99;
                }

                return percentage.toFixed(2) + '%';
            }
        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));