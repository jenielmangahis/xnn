(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient('member/receipt-details');
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
        el: "#receipts-details",
        data: {
            start_date: moment().format("YYYY-MM-DD"),
            end_date: moment().format("YYYY-MM-DD"),
            options:{
              language:'it'  
            }, 
           

            filters: {
                start_date: moment().format("YYYY-MM-DD"),
                end_date: moment().format("YYYY-MM-DD"),
            },

            today: moment().format("YYYY-MM-DD"),
            dtReceipt: null,
            is_downloading: false,
            // language:'en'
        },
        mounted() {
            this.initializeJQueryEvents();
            this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                this.dtReceipt = $("#table-receipts-details").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/receipt-details`,
                        data: function (d) {
                            d.start_date = _this.filters.start_date;
                            d.end_date = _this.filters.end_date;
                        },
                    },
                    data: [],
                    order: [[0, 'desc']],
                    columns: [
                        {
                            data: 'actual_date',
                            className: "text-center",
                            render: function (data, type, row, meta) {
                                // var [before, after] = data.split("-") // split the number and date so I can translate
                                // return before+`-<span class=" text-center `+after+`-month">`+after+`</span>`;

                                const d = new Date(data);
                                return d.getDate()+'/'+(d.getMonth()+1) +'/'+d.getFullYear();
                            }
                        },
                        {
                            data: 'receipt_number',
                            className: "text-center",
                        },
                        {
                            data: 'bank_reference',
                            className: "text-center",
                        },
                        {
                            data: 'month_reference',
                            className: "text-center",
                        },
                        {
                            data: 'id',
                            className: "text-center",
                            render: function ( data, type, row, meta ) {
                                return `<button class="btn btn-download-receipt"><i class="fa fa-download"></i></button>`;
                            }
                        },
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

            },
            initializeJQueryEvents() {

                let _this = this;

                $("#table-receipts-details").on('click', '.btn-download-receipt', function () {
                    // let data = this.dtReceipt.row($(this).parents('tr')).data();
                    let row = $(this).parents('tr');
                    if (row.hasClass('child')) {
                        row = row.prev();
                    }
                    let data = _this.dtReceipt.row(row).data()
                    console.log(data);
                    _this.download(+data.id);
                });

            },
            viewReceipts() {

                this.filters.start_date = this.start_date;
                this.filters.end_date = this.end_date;

                this.dtReceipt.clear().draw();
                this.dtReceipt.responsive.recalc();
            },
            download(id) {
                // client.get(`download/${id}`).then(response => {
                //     $distributorsByPeriod.rows.add(response.data);
                //     $distributorsByPeriod.columns.adjust().draw();
                // });
                // if(this.is_downloading) return;

                this.is_downloading = true;
                client.get(`download/${id}`).then(response => {
                    let link = response.data;
                    this.is_downloading = false;

                    if(!!link) {
						window.open(link, '_blank'); 
                    }
                });
            },
        }

    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));

