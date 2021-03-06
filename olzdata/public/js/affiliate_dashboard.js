(function ($, api_url, Vue, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            //dtCurrentPeriodOrders: null,
            dtGiftCards: null,
            defaultRank: 'Customer',

            currentRankDetailsState: "loaded", // loaded/fetching/error
            currentQualificationState: "loaded",
            currentBinaryVolumeDeatilsState: "loaded",
            currentEarningsDeatilsState: "loaded",
            silverStartUpDetailsState: "loaded",
            sparkleStartUpDetailsState: "loaded",            
            bashStartUpDetailsState: "loaded",
            currentRankDetails: {
                highestAchievedRank: "",
                paidAsRank: "",
                currentRank: "",
                businessVolume: 0,
                nextRank: "",
                needs: [],
            },
            currentQualificationDetails:{
                isQualifiedForWeeklyDirectProfit: "",
                isQualifiedForMonthlyLevelCommission: "",
                isQualifiedForSparkleStartProgram: "",
                isQualifiedForRankAdvancementBonus: "",
            },
            silverStartUpDetails: {
                silverNotice: "",
                silverDaysDiffAffiliatedDate: 0,
                silverTotalPRS: 0,
                silverTotalGiftCards: 0,
                silverPercentage: 0,
            },
            sparkleStartUpDetails: {
                sparkleNotice: "",
                sparkleDaysDiffAffiliatedDate: 0,
                daysDiff: 0,
                sparkleTotalPRS: 0,
                sparkleMemberId: 0,
                sparklePercentage: 0,
            },
            bashStartUpDetails:{
                bashNotice: "",
                bashTotalPRS: 0,
                daysLeft: 0,
                bashPercentage: 0,
            },
            currentBinaryVolumeDetails: {
                leftLegVolume:'0.00',
                leftLegVolumeToday: '0.00',
                leftLegVolumeCarryOver: '0.00',
                rightLegVolume: '0.00',
                rightLegVolumeToday: '0.00',
                rightLegVolumeCarryOver: '0.00'
            },
            lastEarningsDetails: {
                lifeTimeEarnings: 0,
                lastWeekEarnings: 0,
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
            }

        },
        mounted() {
            this.getCurrentRankDetails();
            this.getQualificationDetails();
            // this.getCurrentBinaryVolumeDetails();
            this.getLastEarningsDetails();
            this.initializeDataTables();
            this.getSilverStartupProgram();
            this.getSparkleStartupProgram();
            this.get925BashProgram();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                /*this.dtCurrentPeriodOrders = $('#table-current-period-orders').DataTable({
                    // searching: false,
                    // lengthChange: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                         url: `${api_url}member/dashboard/current-period-orders`,
                    },
                    order: [[5, 'desc']],
                    columns: [
                        {data: 'invoice'},
                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let purchaser = row.purchaser;
                                return `${user_id}: ${purchaser}`;
                            }
                        },
                        {
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {
                            data: 'products',
                            orderable: false,
                            render: function (data, type, row) {

                                if (data === null) return null;

                                let products = JSON.parse(data);

                                let list = ``;

                                for (let i = 0; i < products.length; i++) {
                                    let p = products[i];
                                    list += `<li><strong>${p.quantity}x</strong> - ${p.product}</li>`
                                }

                                return `<ul class="list-unstyled">${list}</ul>`;
                            }
                        },
                        {
                            data: 'amount',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'transaction_date', className: "text-center"},
                        // {data: 'cv', className: "text-right", render: $.fn.dataTable.render.number(',', '.', 2, '$')},
                        {data: 'bv', className: "text-right"},
                    ],
                });*/
                this.dtGiftCards = $("#table-gift-cards").DataTable({
                    // searching: false,
                    // lengthChange: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/dashboard/gift-cards`,
                    },
                    order: [[5, 'desc']],
                    columns: [
                        {data: 'code'},
                        {data: 'validation_code'},
                        {
                            data: 'amount',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {
                            data: 'balance',
                            className: "text-right",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        },
                        {data: 'end_date'},
                        {data: 'created_date'},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: -3},
                    ]
                });
            },
            getQualificationDetails() {
                if (this.currentQualificationState === "fetching") return;

                this.currentQualificationState = "fetching";

                client.get(`${api_url}member/dashboard/current-qualification-details`)
                    .then(response => {
                        let details = response.data;

                        this.currentQualificationState = "loaded";

                      
                        this.currentQualificationDetails.isQualifiedForWeeklyDirectProfit = typeof details.is_qualified_weekly_direct_profit !== "undefined" ? details.is_qualified_weekly_direct_profit : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedForMonthlyLevelCommission = typeof details.is_qualified_monthly_level_commission !== "undefined" ? details.is_qualified_monthly_level_commission : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedForSparkleStartProgram = typeof details.is_qualified_sparkle_start_program !== "undefined" ? details.is_qualified_sparkle_start_program : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedForRankAdvancementBonus = typeof details.is_qualified_rank_advancement_bonus !== "undefined" ? details.is_qualified_rank_advancement_bonus : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedFreeJewelryIncentive = typeof details.is_qualified_free_jewelry_incentive !== "undefined" ? details.is_qualified_free_jewelry_incentive : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedPersonalSalesBonus = typeof details.is_qualified_personal_sales_bonus !== "undefined" ? details.is_qualified_personal_sales_bonus : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedSilverStartup = typeof details.is_qualified_silver_startup !== "undefined" ? details.is_qualified_silver_startup : 'Not Qualified';
                        this.currentQualificationDetails.isQualifiedRankConsistency = typeof details.is_qualified_for_rank_consistency !== "undefined" ? details.is_qualified_for_rank_consistency : 'Not Qualified';

                    })
                    .catch(error => {
                        this.currentQualificationState = "error";
                        console.log("current details error", error);
                    }
                )
            },
            getCurrentRankDetails() {
                if (this.currentRankDetailsState === "fetching") return;

                this.currentRankDetailsState = "fetching";

                client.get(`${api_url}member/dashboard/current-rank-details`)
                    .then(response => {
                        let details = response.data;

                        this.currentRankDetailsState = "loaded";


                        this.currentRankDetails.highestAchievedRank = typeof details.highest_rank !== "undefined" ? details.highest_rank : this.defaultRank;
                        this.currentRankDetails.paidAsRank = typeof details.paid_as_rank !== "undefined" ? details.paid_as_rank : this.defaultRank;
                        this.currentRankDetails.currentRank = typeof details.current_rank !== "undefined" ? details.current_rank : this.defaultRank;
                        this.currentRankDetails.nextRank = typeof details.next_rank !== "undefined" ? details.next_rank : this.defaultRank;
                        this.currentRankDetails.isActive = typeof details.is_active !== "undefined" ? details.is_active : 'No';
                        this.currentRankDetails.businessVolume = typeof details.business_volume !== "undefined" ? details.business_volume : 0;
                        this.currentRankDetails.volumePRS = typeof details.volume_prs !== "undefined" ? details.volume_prs : 0;
                        this.currentRankDetails.volumeGRS = typeof details.volume_grs !== "undefined" ? details.volume_grs : 0;
                        this.currentRankDetails.sponsoredQualifiedRepresentativesCount = typeof details.sponsored_qualified_representatives !== "undefined" ? details.sponsored_qualified_representatives : 0;
                        this.currentRankDetails.sponsoredLeaderHigher = typeof details.sponsored_leader_or_higher !== "undefined" ? details.sponsored_leader_or_higher : 0;
                        this.currentRankDetails.needsPRS = typeof details.needs_prs !== "undefined" ? details.needs_prs : 0;
                        this.currentRankDetails.needsGRS = typeof details.needs_grs !== "undefined" ? details.needs_grs : 0;
                        this.currentRankDetails.isQualified = typeof details.is_qualified !== "undefined" ? details.is_qualified : 'No';

                        
                        this.currentRankDetails.needs = typeof details.needs !== "undefined" ? details.needs : [];
                    })
                    .catch(error => {
                        this.currentRankDetailsState = "error";
                        console.log("current details error", error);
                    }
                )
            },            
            getCurrentBinaryVolumeDetails() {
                if (this.currentBinaryVolumeDeatilsState === "fetching") return;

                this.currentBinaryVolumeDeatilsState = "fetching";

                client.get(`${api_url}member/dashboard/current-binary-details`)
                    .then(response => {
                        let details = response.data;

                        this.currentBinaryVolumeDeatilsState = "loaded";
                        //left leg binary volume
                        this.currentBinaryVolumeDetails.leftLegVolume = typeof details.left_leg_volume !== 'undefined' ? details.left_leg_volume : '0.00';
                        this.currentBinaryVolumeDetails.leftLegVolumeToday = typeof details.left_leg_volume_today !== 'undefined' ? details.left_leg_volume_today : '0.00';
                        this.currentBinaryVolumeDetails.leftLegVolumeCarryOver = typeof details.left_leg_rollover !== 'undefined' ? details.left_leg_rollover : '0.00';


                        //right leg binary volume
                        this.currentBinaryVolumeDetails.rightLegVolume = typeof details.right_leg_volume !== 'undefined' ? details.right_leg_volume : '0.00';
                        this.currentBinaryVolumeDetails.rightLegVolumeToday = typeof details.right_leg_volume_today !== 'undefined' ? details.right_leg_volume_today : '0.00';
                        this.currentBinaryVolumeDetails.rightLegVolumeCarryOver = typeof details.right_leg_rollover !== 'undefined' ? details.right_leg_rollover : '0.00';

                    })
                    .catch(error => {
                        this.currentBinaryVolumeDeatilsState = "error";
                        console.log("binary volume error", error);
                    }
                )  
            },
            getLastEarningsDetails() {
                if (this.currentEarningsDeatilsState === "fetching") return;

                this.currentEarningsDeatilsState = "fetching";

                client.get(`${api_url}member/dashboard/current-earnings-details`)
                    .then(response => {
                        let details = response.data;

                        this.currentEarningsDeatilsState = "loaded";

                        // earnings
                        this.currentRankDetails.lastMonthEarnings = typeof details.last_month_earnings !== "undefined" ? details.last_month_earnings : 0;
                        this.currentRankDetails.lastWeekEarnings = typeof details.last_week_earnings !== "undefined" ? details.last_week_earnings : 0;

                    })
                    .catch(error => {
                        this.currentEarningsDeatilsState = "error";
                        console.log("last ernings error", error);
                    }
                )
            },
            getSilverStartupProgram() {
                if (this.silverStartUpDetailsState === "fetching") return;

                this.silverStartUpDetailsState = "fetching";

                client.get(`${api_url}member/dashboard/silver-startup-details`)
                    .then(response => {
                        let details = response.data;

                        this.silverStartUpDetailsState = "loaded";
                        
                        this.silverStartUpDetails.silverTotalPRS = typeof details.silver_total_prs !== "undefined" ? details.silver_total_prs : 0;
                        this.silverStartUpDetails.silverTotalGiftCards = typeof details.total_gift_cards !== "undefined" ? details.total_gift_cards : 0;
                        this.silverStartUpDetails.silverNotice = "Silver Start Up Program Progress " + parseFloat(this.silverStartUpDetails.silverTotalGiftCards).toFixed(2) + " worth of Gift Cards so far";
                        this.silverStartUpDetails.silverPercentage = (this.silverStartUpDetails.silverTotalPRS / 4000) * 100;
                        this.silverStartUpDetails.silverDaysDiffAffiliatedDate = typeof details.diff_affiliated_date !== "undefined" ? details.diff_affiliated_date : 0;
                    })
                    .catch(error => {
                        this.silverStartUpDetailsState = "error";
                        console.log("current details error", error);
                    }
                )
            },
            getSparkleStartupProgram() {
                if (this.sparkleStartUpDetailsState === "fetching") return;

                this.sparkleStartUpDetailsState = "fetching";

                client.get(`${api_url}member/dashboard/sparkle-startup-details`)
                    .then(response => {
                        let details = response.data;

                        this.sparkleStartUpDetailsState = "loaded";

                        this.sparkleStartUpDetails.sparkleMemberId = typeof details.muser_id !== "undefined" ? details.muser_id : 0;
                        this.sparkleStartUpDetails.sparkleTotalPRS = typeof details.sparkle_total_prs !== "undefined" ? details.sparkle_total_prs : 0;
                        this.sparkleStartUpDetails.daysDiff = typeof details.days_diff !== "undefined" ? details.days_diff : 0;
                        this.sparkleStartUpDetails.sparkleDaysDiffAffiliatedDate = typeof details.diff_affiliated_date !== "undefined" ? details.diff_affiliated_date : 0;

                        if( details.sparkle_total_prs >= 500 ){
                            this.sparkleStartUpDetails.sparkleNotice = "Sparkle Start Program Progress : You have reached your goal of having $500.00 PRS"; 
                        }else{
                            let x_days = this.sparkleStartUpDetails.daysDiff;
                            this.sparkleStartUpDetails.sparkleNotice = "Sparkle Start Program Progress : You only have " + x_days + " days left to reach $500.00 PRS"; 
                        }

                        this.sparkleStartUpDetails.sparklePercentage = (this.sparkleStartUpDetails.sparkleTotalPRS / 500) * 100;

                    })
                    .catch(error => {
                        this.sparkleStartUpDetailsState = "error";
                        console.log("current details error", error);
                    }
                )
            },
            get925BashProgram() {
                if (this.bashStartUpDetailsState === "fetching") return;

                this.bashStartUpDetailsState = "fetching";

                client.get(`${api_url}member/dashboard/bash-925-startup-details`)
                    .then(response => {
                        let details = response.data;

                        this.bashStartUpDetailsState = "loaded";
                        
                        this.bashStartUpDetails.bashTotalPRS = typeof details.bash_total_prs !== "undefined" ? details.bash_total_prs : 0;
                        this.bashStartUpDetails.daysLeft = typeof details.days_left !== "undefined" ? details.days_left : 0;

                        if( details.bash_total_prs >= 36000 ){
                            this.bashStartUpDetails.bashNotice = "925 Bash Progress : You have reached your goal of having $36,000.00 PRS"; 
                        }else{
                            let x_days = this.bashStartUpDetails.daysLeft;
                            this.bashStartUpDetails.bashNotice = "925 Bash Progress : You only have " + x_days + " days left to reach $36,000.00 PRS"; 
                        }

                        if( this.bashStartUpDetails.bashTotalPRS > 0 ){
                            this.bashStartUpDetails.bashPercentage = Math.round((this.bashStartUpDetails.bashTotalPRS / 36000) * 100);
                        }else{
                            this.bashStartUpDetails.bashPercentage = 0;
                        }
                        

                    })
                    .catch(error => {
                        this.bashStartUpDetailsState = "error";
                        console.log("current details error", error);
                    }
                )
            },
        },
        computed: {
            isRankLoaded() {
                return this.currentRankDetailsState === 'loaded';
            },
            isBinaryVolumeLoaded() {
                return this.currentBinaryVolumeDeatilsState === 'loaded';
            },
            isEarningsLoaded() {
                return this.currentEarningsDeatilsState === 'loaded';
            },
            isAchievementLoaded() {
                return this.titleAchievementBonusState === "loaded";
            },
            isSilverStartupProgramLoaded() {
                return this.silverStartUpDetailsState === 'loaded';
            },
            isSparkleStartupProgramLoaded() {
                return this.sparkleStartUpDetailsState === 'loaded';
            },
            isBashStartupProgramLoaded() {
                return this.bashStartUpDetailsState === 'loaded';
            },
            isQualificationLoaded() {
                return this.currentQualificationState === 'loaded';
            },
        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, axios, window.location));