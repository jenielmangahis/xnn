(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();
    
    const vm = new Vue({
        el: ".tool-container",
        data: {
            dtCurrentPeriodOrders: null,
            dtGiftCards: null,
            dtStatusHistory: null,

            currentRankDetailsState: "loaded", // loaded/fetching/error
            current_rank: {
                current_rank: "Spark",
                paid_as_rank: "Spark",
                qta: "Spark",
                pea: 0,
                ta: 0,
                mar: 0,

                next_rank: "Spark",
				needs: [],
				current_rank_deets: []
            },

            titleAchievementBonusState: "loaded", // loaded/fetching/error
            titleAchievementBonus: {
                ranks: [],
                highestRankId: 0,
                nextBonus: 0,
                doubleBonus: {
                    days: 0,
                    hours: 0,
                    rank: null,
                }
			},
			
			qualified: {
				is_qualified: '',
				requirements: ''
			},
            cookieValue: ''

        },
        mounted() {
            this.cookieValue = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
            this.initializeDataTables();
            this.initializeJQueryEvents();
        },
        methods: {

            initializeDataTables() {
                let _this = this;
				
                _this.dtCurrentPeriodOrders = $("#table-members").DataTable({
                    searching: false,
                    // lengthChange: true,
                    
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/tech-fee`,
                        data: function (d) {
                            d.memberid = $('input[name="hidden_user_id"]').val()
                        },
                    },
                    columns: [
                        {data: 'tdate'},
                        {data: 'invoice'},
                        {data: 'pay_desc'},
                        {data: 'amount'},
                        {data: 'status'},
                        {
                            data: 'id',
                            className: "text-center",
                            render: function ( data, type, row, meta ) {
                                return `<button class="btn btn-download-receipt"><i class="fa fa-download"></i></button>`;
                            }
                        },

                    ],
                    order: []
                });

            },
            initializeJQueryEvents() {
                let _this = this;
                $("#table-members").on('click', '.btn-download-receipt', function () {

                    let row = $(this).parents('tr');
                    let data = _this.dtCurrentPeriodOrders.row(row).data();

                    _this.download(data.payment_type, data.receipt_id);
                });
            },
            download(type, id) {

                this.is_downloading = true;
                client.get(`member/tech-fee/download/?receipt_type=${type}&receipt_id=${id}`).then(response => {
                    let link = response.data;
                    this.is_downloading = false;

                    if(!!link) {
                        window.open(link, '_blank');
                    }
                });
            },
        }
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));

