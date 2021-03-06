(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();
    const lang = cookieValue = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
    $.fn._datepicker = jQuery.fn.datepicker;

    
    if(lang == 'english'){
        $.fn._datepicker.dates['en'] = {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
            months: ["Januarys", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            today: "Today",
            clear: "Clear",
            format: "YYYY-MM-DD",
            titleFormat: "MM yyyy", /* Leverages same syntax as 'format' */
            weekStart: 0
    };
     }
    else{
        $.fn._datepicker.dates['en'] = {
            days: ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"],
            daysShort: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"],
            daysMin: ["Do", "Lu", "Ma", "Me", "Gi", "Ve", "Sa"],
            months: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"],
            monthsShort: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
            today: "Oggi",
            monthsTitle: "Mesi",
            clear: "Cancella",
            format: "YYYY-MM-DD",
            titleFormat: "MM yyyy", /* Leverages same syntax as 'format' */
            weekStart: 0
    };
    
    }


    const vm = new Vue({
        el: "#rank-history",
        data: {
            enrollment: {
                start_date: moment().format("YYYY-MM-DD"),
                rank_id: "",

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    rank_id: "",
                }
            },
            personal: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
                rank_id: "",

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                    rank_id: "",
                }
            },
            highest: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
                is_all: 0,
                rank_id: "",

                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                    end_date: moment().format("YYYY-MM-DD"),
                    is_all: 0,
                    rank_id: "",
                }
            },

            ranks: [],
            rankState: "loaded", // loaded/fetching/error

            today: moment().format("YYYY-MM-DD"),
            dtEnrollment: null,
            dtPersonal: null,
            dtHighest: null,
        },
        mounted() {
            this.getRanks();
            this.initializeJQueryEvents();
            this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtEnrollment = $("#table-rank-history-enrollment").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/rank-history/enrollment`,
                        data: function (d) {
                            d.start_date = _this.enrollment.filters.start_date;
                            d.rank_id = _this.enrollment.filters.rank_id;
                        },
                    },
                    data: [],
                    order: [[0, 'asc']],
                    columns: [
                        {data: 'user_id'},
                        {
                            data: 'member'
                        },
                        {data: 'career_title'},
                        {data: 'pea'},
                        {data: 'ta'},
                        {data: 'mar'},
                        {data: 'qta'},
                        {
                            data: 'is_active',
                            className: "text-center",
                            render: function (data, type, row, meta) {

                                if (+row.is_active) {
                                    return `<span class="label label-success qua-yes">Yes</span>`;
                                }

                                return `<span class="label label-warning  qua-no">No</span>`;
                            }
                        },
                        {
                            data: 'is_system_active',
                            className: "text-center",
                            render: function (data, type, row, meta) {

                                if (+row.is_system_active) {
                                    return `<span class="label label-success active-yes">Yes</span>`;
                                }

                                return `<span class="label label-warning active-no">No</span>`;
                            }
                        },
                        {data: 'level', className: "text-center"},
                        {data: 'rank_date', className: "text-center"},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -3},
                        {responsivePriority: 4, targets: -4},
                    ]
                });

                this.dtPersonal = $("#table-rank-history-personal").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/rank-history/personal`,
                        data: function (d) {
                            d.start_date = _this.personal.filters.start_date;
                            d.end_date = _this.personal.filters.end_date;
                            d.rank_id = _this.personal.filters.rank_id;
                        },
                    },
                    data: [],
                    order: [[0, 'desc']],
                    columns: [
                        {data: 'paid_as_rank'},
                        {data: 'pea'},
                        {data: 'ta'},
                        {data: 'mar'},
                        {data: 'qta'},
                        {
                            data: 'is_active',
                            className: "text-center",
                            render: function (data, type, row, meta) {

                                if (+row.is_active) {
                                    return `<span class="label label-success qua-yes">Yes</span>`;
                                }

                                return `<span class="label label-warning qua-no">No</span>`;
                            }
                        },
                        {
                            data: 'is_system_active',
                            className: "text-center",
                            render: function (data, type, row, meta) {

                                if (+row.is_system_active) {
                                    return `<span class="label label-success qua-yes">Yes</span>`;
                                }

                                return `<span class="label label-warning qua-no">No</span>`;
                            }
                        },
                        {data: 'rank_date', className: "text-center"},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

                this.dtHighest = $("#table-rank-history-highest").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/rank-history/highest`,
                        data: function (d) {
                            d.start_date = _this.highest.filters.start_date;
                            d.end_date = _this.highest.filters.end_date;
                            d.is_all = +_this.highest.filters.is_all;
                            d.rank_id = +_this.highest.filters.rank_id;
                        },
                    },
                    order: [[1, 'desc']],
                    columns: [
                        {
                            data: 'user_id'
                        },

                        {
                            data: 'member'
                        },
                        {data: 'highest_rank', className: "text-center"},
                        {data: 'date_achieved', className: "text-center"},
                        {data: 'level', className: "text-center"},
                        {
                            data: 'sponsor'
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

                $('#nav-tab-report a[data-toggle="tab"]').on('shown.bs.tab show.bs.tab',  (e) => {

                    let tab = $(e.target).attr("href");

                    if(tab === "#personal") {
                        this.dtPersonal.responsive.recalc();
                    } else if(tab === "#tree") {
                        this.dtEnrollment.responsive.recalc();
                    } else if(tab === "#new-highest-rank") {
                        this.dtHighest.responsive.recalc();
                    }

                })


            },
            viewEnrollment() {

                this.enrollment.filters.start_date = this.enrollment.start_date;
                this.enrollment.filters.rank_id = this.enrollment.rank_id;

                this.dtEnrollment.clear().draw();
                this.dtEnrollment.responsive.recalc();
            },
            viewPersonal() {

                this.personal.filters.start_date = this.personal.start_date;
                this.personal.filters.end_date = this.personal.end_date;
                this.personal.filters.rank_id = this.personal.rank_id;

                this.dtPersonal.clear().draw();
                this.dtPersonal.responsive.recalc();
            },
            viewHighest() {

                this.highest.filters.start_date = this.highest.start_date;
                this.highest.filters.end_date = this.highest.end_date;
                this.highest.filters.rank_id = this.highest.rank_id;
                this.highest.filters.is_all = this.highest.is_all;

                this.dtHighest.clear().draw();
                this.dtHighest.responsive.recalc();
            },
            getRanks() {

                if (this.rankState === "fetching") return;

                this.rankState = "fetching";
                this.ranks = [];
                this.enrollment.rank_id = "";
                this.personal.rank_id = "";
                this.highest.rank_id = "";

                client.get("common/ranks")
                    .then(response => {
                        this.ranks = response.data;
                        this.rankState = "loaded";

                    })
                    .catch(error => {
                        this.rankState = "error";
                    })

            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));

$(function(){
    // $('table.dataTable').parent().css('overflow','hidden');
})