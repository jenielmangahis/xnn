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
            this.getCurrentRankDetails();
			//this.getTitleAchievementBonus();
			this.getLastThreeMonthsEarnings();
			this.getQualificationRequirementDetails();
        },
        methods: { 
            
                triggerTranslate(){
                    if(this.cookieValue == "italian"){
                        $('#italian').trigger('click');
                    }else{
                        $('#english').trigger('click');
                    }
                },
                translateQualification(quaStatus){

                  

                    if(this.cookieValue == 'italian'){
                      
                        console.log($('#qualified-text-2'));

                        var quaTrans = '';	
                        if( quaStatus !== ''){
                            quaTrans = quaStatus.replace("Yes until", "Si, fino al");  // the yes and no translation
                            quaTrans = quaTrans.replace("/01/", " Gennaio ");
                            quaTrans = quaTrans.replace("/02/", " Febbraio ");
                            quaTrans = quaTrans.replace("/03/", " Marzo ");
                            quaTrans = quaTrans.replace("/04/", " Aprile ");
                            quaTrans = quaTrans.replace("/05/", " Maggio ");
                            quaTrans = quaTrans.replace("/06/", " Giugno ");
                            quaTrans = quaTrans.replace("/07/", " Luglio ");
                            quaTrans = quaTrans.replace("/08/", " Agosto ");
                            quaTrans = quaTrans.replace("/09/", " Settembre ");
                            quaTrans = quaTrans.replace("/10/", " Ottobre ");
                            quaTrans = quaTrans.replace("/11/", " Novembre ");
                            quaTrans = quaTrans.replace("/12/", " Dicembre ");
                        }
                        $('#italian').trigger('click');
                    }else{

                        var quaTrans = '';	
                        if( quaStatus !== ''){
                            quaTrans = quaStatus.replace("Si, fino al", "Yes until");  // the yes and no translation
                            quaTrans = quaTrans.replace(" Gennaio ", "/01/");
                            quaTrans = quaTrans.replace(" Febbraio ", "/02/");
                            quaTrans = quaTrans.replace(" Marzo ", "/03/");
                            quaTrans = quaTrans.replace(" Aprile ", "/04/");
                            quaTrans = quaTrans.replace(" Maggio ", "/05/");
                            quaTrans = quaTrans.replace(" Giugno ", "/06/");
                            quaTrans = quaTrans.replace(" Luglio ", "/07/");
                            quaTrans = quaTrans.replace(" Agosto ", "/08/");
                            quaTrans = quaTrans.replace(" Settembre ", "/09/");
                            quaTrans = quaTrans.replace(" Ottobre ", "/10/");
                            quaTrans = quaTrans.replace(" Novembre ", "/11/");
                            quaTrans = quaTrans.replace(" Dicembre ", "/12/");
                        }
                        $('#english').trigger('click');
                    }

                    $('#qualified-text-2').text(quaTrans);
                },
            initializeDataTables() {
                let _this = this;
                _this.dtStatusHistory = $("#table-status-history").DataTable({
                    responsive: true,
                    data: [
                        {
                            status: 'Approved, Peeding Flowing',
                            date: 5/1/2020
                        },
                        {
                            status: 'Flowing',
                            date: 5/20/2020
                        },
                        {
                            status: 'Date Cancelled',
                            date: 6/10/2020
                        },
                    ],
                    columns: [
                        { 
                            data: 'status',
                            render: function (data, type, row, meta) {
                                return `<span class="`+data.replace(/,/g, '')+`">`+data+`</span>`;
                            }
                        
                        },
                        { data: 'date' }
                    ]
				});
				
                _this.dtCurrentPeriodOrders = $("#table-members").DataTable({
                    // searching: false,
                    // lengthChange: true,
                    
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/dashboard/my-pea`,
                    },
                    columns: [
                        {data: 'por'},
                        {data: 'customer'},
                        {
                            data: 'account',
                            render: function (data, type, row, meta) {
                                return `<span class="`+data.replace(/,/g, '')+`">`+data+`</span>`;
                            }
                        },
                        {data: 'date_accepted',
                            render:function(data){
                                if(data == '' || data == null)
                                {
                                    return '';
                                }
                                else{
                                    let d = new Date(data);
                                    return moment(d).format('DD/MM/YYYY');
                                }
                            }

                        },
                        {data: 'date_started_flowing',
                            render:function(data, type, row, meta){
                                if(data == '' || data == null)
                                {
                                    if(row.flowing_date == '' || row.flowing_date == null) {
                                        return '';
                                    } else {
                                        let fd = new Date(row.flowing_date);
                                        return moment(fd).format('DD/MM/YYYY');
                                    }
                                }
                                else{
                                    let d = new Date(data);
                                    return moment(d).format('DD/MM/YYYY');
                                }
                            }},
                        {
                            data: 'status',
                            render: function (data, type, row, meta) {
                                return `<span class="`+data.replace(/,/g, '')+`">`+data+`</span>`;
                            }
                        },
                        {
                            data: 'energy_account_id',
                            render: function (data, type, row, meta) {
                                return `<a class="btn-status-history th-status-history-label" href="#" data-toggle="modal" data-target="#modal-status-history" data-id="`+data+`">Status History</a>`;
                            }
                        }
                    ],
                    order: [],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: -2},
					],
					drawCallback: function( settings ) {
                        let cookieValue = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
                        Pace.on("done", function(){
                            if(cookieValue == "italian"){
                                $('#italian').trigger('click');
                            }else{
                                $('#english').trigger('click');
                            }
                        });
                        
						$('.btn-status-history').on('click', function () {
							_this.dtStatusHistory.ajax.url(`${api_url}member/dashboard/my-pea-history?energy_account_id=${$(this).data('id')}`).load();
							//_this.dtStatusHistory.ajax.url(`${api_url}member/dashboard/my-pea-history?user_id=50`).load();
							_this.dtStatusHistory.responsive.recalc();
                    		_this.dtStatusHistory.columns.adjust().draw();
                            let cookieValue = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
                            Pace.on("done", function(){
                                if(cookieValue == "italian"){
                                    $('#italian').trigger('click');
                                }else{
                                    $('#english').trigger('click');
                                }
                            });
 
						});
					}
                });

            },
            initializeJQueryEvents() {
				let _this = this;
				/*
                $('.status-history').on('click', function () {
					alert($(this).attr('id'));
                    _this.dtStatusHistory.responsive.recalc();
                    _this.dtStatusHistory.columns.adjust().draw();
				});
				*/
            },
            getCurrentRankDetails() {

                if (this.currentRankDetailsState === "fetching") return;

                this.currentRankDetailsState = "fetching";

                this.current_rank.current_rank = "Spark";
                this.current_rank.paid_as_rank = "Spark";
                this.current_rank.next_rank = "Spark";
                this.current_rank.pea = 0;
                this.current_rank.ta = 0;
                this.current_rank.qta = 0;
                this.current_rank.mar = 0;
                this.current_rank.needs = [];

                this.dtStatusHistory.clear().draw();

                client.get("member/dashboard/current-rank-details")
                    .then(response => {
                        let details = response.data;
                        let _this = this; 
                        this.current_rank.paid_as_rank = typeof details.paid_as_rank !== "undefined" ? details.paid_as_rank : "Spark";
                        this.current_rank.current_rank = typeof details.current_rank !== "undefined" ? details.current_rank : "Spark";
                        this.current_rank.next_rank = typeof details.next_rank !== "undefined" ? details.next_rank : "Spark";

                        this.current_rank.pea = typeof details.pea !== "undefined" ? details.pea : 0;
                        this.current_rank.ta = typeof details.ta !== "undefined" ? details.ta : 0;
                        this.current_rank.qta = typeof details.qta !== "undefined" ? details.qta : 0;
                        this.current_rank.mar = typeof details.mar !== "undefined" ? details.mar : 0;
                        this.current_rank.needs = typeof details.needs !== "undefined" ? details.needs : [];
                        this.current_rank.current_rank_deets = typeof details.current_rank_deets !== "undefined" ? details.current_rank_deets : [];

                        $.each(this.current_rank.needs, function(index, value) {
                            $('#needs').append('<div class="d-flex">'+
                                                    '<label class="text-info '+value.label+'">'+value.label+':</label>'+  
                                                    '<div class="col-md-4">'+
                                                        '<div class="value">'+value.value+'</div>'+
                                                    '</div>'+
                                                '</div>');
						});

                        $.each(this.current_rank.current_rank_deets, function(index, value) {
                            $('#current-rank-deets').append('<div class="d-flex">'+
                                                    '<label class="text-info '+value.label+'">'+value.label+':</label>'+  
                                                    '<div class="col-md-4">'+
                                                        '<div class="value">'+value.value+'</div>'+
                                                    '</div>'+
                                                '</div>');
						});

                        //this.dtStatusHistory.rows.add(typeof details.referral_points_details !== "undefined" ? details.referral_points_details : []);
                        //this.dtStatusHistory.columns.adjust().draw();
                        //this.dtStatusHistory.responsive.recalc();

                        this.currentRankDetailsState = "loaded";
                        _this.triggerTranslate();
                    })
                    .catch(error => {
                        this.currentRankDetailsState = "error";
                    })


            },
            getTitleAchievementBonus() {

                if (this.titleAchievementBonusState === "fetching") return;

                this.titleAchievementBonusState = "fetching";

                this.titleAchievementBonus.ranks = [];
                this.titleAchievementBonus.highestRankId = 0;
                this.titleAchievementBonus.nextBonus = 0;

                this.titleAchievementBonus.doubleBonus.days = 0;
                this.titleAchievementBonus.doubleBonus.hours = 0;
                this.titleAchievementBonus.doubleBonus.rank = null;

                client.get("member/dashboard/title-achievement-bonus-details")
                    .then(response => {
                        let details = response.data;

                        this.titleAchievementBonus.ranks = details.ranks;
                        this.titleAchievementBonus.highestRankId = details.highest_rank_id;
                        this.titleAchievementBonus.nextBonus = details.next_bonus;

                        this.titleAchievementBonus.doubleBonus.days = details.double_bonus.days;
                        this.titleAchievementBonus.doubleBonus.hours = details.double_bonus.hours;
                        this.titleAchievementBonus.doubleBonus.rank = details.double_bonus.next_double_rank_name;

                        this.titleAchievementBonusState = "loaded";
                    })
                    .catch(error => {
                        this.titleAchievementBonusState = "error";
                    })
			},
			getLastThreeMonthsEarnings() {
                client.get("member/dashboard/three-month-earning")
                    .then(response => {
						let details = response.data;
						

                        $.each(details, function(key, value) {
                            $('#last-three-months').append('<div class="d-flex">'+
												'<label class="text-info month-'+key+'">'+key+':</label>'+
													'<div class="col-md-6">'+
													'<div class="value">'+value.earnings+'</div>'+
												'</div>'+
											'</div>');
                        });
                    })
                    .catch(error => {
                    })
			},
			getQualificationRequirementDetails() {
                let _this = this; 
                client.get("member/dashboard/my-requirements")
                    .then(response => {
						let details = response.data;
                        console.log(details);
						this.qualified.is_qualified = details.qualified_text;
						this.qualified.requirements = details.qualified_requirement;
                       _this.translateQualification(details.qualified_requirement);

                    })
                    .catch(error => {
                    })
			},
            showStatusHistory() {
                $('#modal-status-history').modal({backdrop: 'static', keyboard: false});
            },
        },
        computed: {
            isRankLoaded() {
                return this.currentRankDetailsState === 'loaded';
            },
            isAchievementLoaded() {
                return this.titleAchievementBonusState === "loaded";
            },
        }
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));

