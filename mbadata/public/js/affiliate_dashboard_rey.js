(function ($, api_url, Vue, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dtCurrentPeriodOrders: null,

            currentRankDetailsState: "loaded", // loaded/fetching/error
            currentRankDetails: {
                highestAchievedRank: "Coach",
                paidAsRank: "Coach",
                currentRank: "Coach",
                isActive: 0,
                businessVolume: 0,
                nextRank: "Coach",
                needs: [],
            },
            binaryVolume: {
                leftLeg: {
                    volume: 0,
                    today: 0,
                    carryOver: 0
                },
                rightLeg: {
                    volume: 0,
                    today: 0,
                    carryOver: 0
                },
            },
            lastEarnings: {
                lifeTime: 0,
                weekly: 0,
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
            this.initializeDataTables();
            // this.initializeJQueryEvents();
            // this.getTitleAchievementBonus();
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
                            // render: function (data, type, row) {
                            //     return row.amount;
                            // }
                        },
                        {data: 'transaction_date', className: "text-center"},
                        // {data: 'cv', className: "text-right", render: $.fn.dataTable.render.number(',', '.', 2, '$')},
                        {data: 'bv', className: "text-right"},
                    ],
                });
            },
            initializeJQueryEvents() {
                // let _this = this;
                // $('#modal-referral-points').on('shown.bs.modal', function () {
                //     _this.dtReferralPointsDetails.responsive.recalc();
                //     _this.dtReferralPointsDetails.columns.adjust().draw();

                // })
            },
            getCurrentRankDetails() {
                console.log("get details");
                if (this.currentRankDetailsState === "fetching") return;

                this.currentRankDetailsState = "fetching";

                this.currentRankDetails.highestAchievedRank = "Coach";
                this.currentRankDetails.paidAsRank = "Coach";
                this.currentRankDetails.currentRank = "Coach";
                this.currentRankDetails.nextRank = "Coach";
                this.currentRankDetails.isActive = 0;
                this.currentRankDetails.businessVolume = 0;
                this.currentRankDetails.needs = [];

                // this.dtReferralPointsDetails.clear().draw();

                client.get("member/dashboard/current-rank-details")
                    .then(response => {
                        let details = response.data;
                        console.log("details", details);
                        this.currentRankDetails.highestAchievedRank = typeof details.highest_rank !== "undefined" ? details.highest_rank : "Coach";
                        this.currentRankDetails.paidAsRank = typeof details.paid_as_rank !== "undefined" ? details.paid_as_rank : "Coach";
                        this.currentRankDetails.currentRank = typeof details.current_rank !== "undefined" ? details.current_rank : "Coach";
                        this.currentRankDetails.nextRank = typeof details.next_rank !== "undefined" ? details.next_rank : "Coach";
                        this.currentRankDetails.isActive = typeof details.is_active !== "undefined" ? details.is_active : 0;
                        this.currentRankDetails.businessVolume = typeof details.business_volume !== "undefined" ? details.business_volume : 0;

                        
                        this.currentRankDetails.needs = typeof details.needs !== "undefined" ? details.needs : [];

                        if(details.binary_volume.length > 0) {
                            this.binaryVolume.leftLeg.volume = details.binary_volume.left_leg;
                            this.binaryVolume.rightLeg.volume = details.binary_volume.right_leg;
                        }

                        if(details.binary_volume_details.length > 0) {
                            this.binaryVolume.leftLeg.today = details.binary_volume_details.left_leg_today;
                            this.binaryVolume.leftLeg.carryOver = details.binary_volume_details.left_leg_carry_over;

                            this.binaryVolume.rightLeg.today = details.binary_volume_details.right_leg_today;
                            this.binaryVolume.rightLeg.carryOver = details.binary_volume_details.right_leg_carry_over;
                        }

                        this.lastEarnings.lifeTime = typeof details.earnings.lifetime !== "undefined" ?details.earnings.lifetime : 0;
                        this.lastEarnings.weekly = typeof details.earnings.weekly !== "undefined" ?details.earnings.weekly : 0;

                        this.currentRankDetailsState = "loaded";
                    })
                    .catch(error => {
                        this.currentRankDetailsState = "error";
                    })
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

}(jQuery, window.commissionEngine.API_URL, Vue, axios, window.location));