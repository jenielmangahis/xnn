(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dtCurrentPeriodOrders: null,
            dtGiftCards: null,
            dtReferralPointsDetails: null,

            currentRankDetailsState: "loaded", // loaded/fetching/error
            currentRankDetails: {
                highestAchievedRank: "Coach",
                paidAsRank: "Coach",
                currentRank: "Coach",
                isActive: 0,
                coachPoints: 0,
                preferredCustomerCount: 0,
                referralPoints: 0,
                organizationPoints: 0,
                teamGroupPoints: 0,
                influencerCount: 0,

                nextRank: "Coach",
                needs: [],
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
           // this.initializeDataTables();
            this.initializeJQueryEvents();
            this.getCurrentRankDetails();
            this.getTitleAchievementBonus();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dtCurrentPeriodOrders = $("#table-current-period-orders").DataTable({
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
                        {data: 'cv', className: "text-right"},
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: 1},
                        {responsivePriority: 3, targets: -2},
                    ]
                });

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

                this.dtReferralPointsDetails = $("#table-referral-points").DataTable({
                    responsive: true,
                    columns: [
                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let name = row.name;
                                return `${user_id}: ${name}`;
                            }
                        },
                        {data: 'points'},
                        {data: 'type'},
                        {data: 'other_details'},
                    ]
                });
            },
            initializeJQueryEvents() {
                let _this = this;
                $('#modal-referral-points').on('shown.bs.modal', function () {
                    _this.dtReferralPointsDetails.responsive.recalc();
                    _this.dtReferralPointsDetails.columns.adjust().draw();

                })
            },
            getCurrentRankDetails() {

                if (this.currentRankDetailsState === "fetching") return;

                this.currentRankDetailsState = "fetching";

                this.currentRankDetails.highestAchievedRank = "Coach";
                this.currentRankDetails.paidAsRank = "Coach";
                this.currentRankDetails.currentRank = "Coach";
                this.currentRankDetails.nextRank = "Coach";
                this.currentRankDetails.isActive = 0;
                this.currentRankDetails.preferredCustomerCount = 0;
                this.currentRankDetails.coachPoints = 0;
                this.currentRankDetails.referralPoints = 0;
                this.currentRankDetails.organizationPoints = 0;
                this.currentRankDetails.teamGroupPoints = 0;
                this.currentRankDetails.influencerCount = 0;
                this.currentRankDetails.needs = [];

                this.dtReferralPointsDetails.clear().draw();

                client.get("member/dashboard/current-rank-details")
                    .then(response => {
                        let details = response.data;

                        this.currentRankDetails.highestAchievedRank = typeof details.highest_rank !== "undefined" ? details.highest_rank : "Coach";
                        this.currentRankDetails.paidAsRank = typeof details.paid_as_rank !== "undefined" ? details.paid_as_rank : "Coach";
                        this.currentRankDetails.currentRank = typeof details.current_rank !== "undefined" ? details.current_rank : "Coach";
                        this.currentRankDetails.nextRank = typeof details.next_rank !== "undefined" ? details.next_rank : "Coach";
                        this.currentRankDetails.isActive = typeof details.is_active !== "undefined" ? details.is_active : 0;

                        this.currentRankDetails.coachPoints = typeof details.coach_points !== "undefined" ? details.coach_points : 0;
                        this.currentRankDetails.referralPoints = typeof details.referral_points !== "undefined" ? details.referral_points : 0;
                        this.currentRankDetails.organizationPoints = typeof details.organization_points !== "undefined" ? details.organization_points : 0;
                        this.currentRankDetails.preferredCustomerCount = typeof details.preferred_customer_count !== "undefined" ? details.preferred_customer_count : 0;
                        this.currentRankDetails.teamGroupPoints = typeof details.team_group_points !== "undefined" ? details.team_group_points : 0;
                        this.currentRankDetails.influencerCount = typeof details.influencer_count !== "undefined" ? details.influencer_count : 0;
                        this.currentRankDetails.needs = typeof details.needs !== "undefined" ? details.needs : [];

                        this.dtReferralPointsDetails.rows.add(typeof details.referral_points_details !== "undefined" ? details.referral_points_details : []);
                        this.dtReferralPointsDetails.columns.adjust().draw();
                        this.dtReferralPointsDetails.responsive.recalc();

                        this.currentRankDetailsState = "loaded";
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
            showReferralPoints() {
                $('#modal-referral-points').modal({backdrop: 'static', keyboard: false});
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