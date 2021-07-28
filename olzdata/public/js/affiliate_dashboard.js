(function ($, api_url, Vue, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dtCurrentPeriodOrders: null,
            defaultRank: 'Customer',

            currentRankDetailsState: "loaded", // loaded/fetching/error
            currentBinaryVolumeDeatilsState: "loaded",
            currentEarningsDeatilsState: "loaded",
            currentRankDetails: {
                highestAchievedRank: "",
                paidAsRank: "",
                currentRank: "",
                businessVolume: 0,
                nextRank: "",
                needs: [],
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
            // this.getCurrentBinaryVolumeDetails();
            // this.getLastEarningsDetails();
            // this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dtCurrentPeriodOrders = $('#table-current-period-orders').DataTable({
                    // searching: false,
                    // lengthChange: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        // url: `${api_url}member/dashboard/current-period-orders`,
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
                });
            },
            getCurrentRankDetails() {
                if (this.currentRankDetailsState === "fetching") return;

                this.currentRankDetailsState = "fetching";

                client.get("member/dashboard/current-rank-details")
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

                client.get("member/dashboard/current-binary-details")
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

                client.get("member/dashboard/current-earnings-details")
                    .then(response => {
                        let details = response.data;

                        this.currentEarningsDeatilsState = "loaded";

                        // earnings
                        this.lastEarningsDetails.lifeTimeEarnings = typeof details.lifetime_earnings !== "undefined" ? details.lifetime_earnings : 0;
                        this.currentRankDetails.lastWeekEarnings = typeof details.last_week_earnings !== "undefined" ? details.last_week_earnings : 0;

                    })
                    .catch(error => {
                        this.currentEarningsDeatilsState = "error";
                        console.log("last ernings error", error);
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
        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, axios, window.location));