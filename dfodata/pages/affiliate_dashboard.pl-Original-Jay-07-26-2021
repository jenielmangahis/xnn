print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datatables.css?v=$app_css_version" />
<!--<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">-->
<style>
    /*TODO: arrange the css*/
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid #e3e6f0;
        border-radius: .35rem;
    }

    .tool-container {
        color: #000 !important;
    }

    .card-body {
        flex: 1 1 auto;
        padding: 2.25rem;
    }

    .card-body .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -.75rem;
        margin-left: -.75rem;
    }

    .no-gutters {
        margin-right: 0;
        margin-left: 0;
    }

    .align-items-center {
        align-items: center!important;
    }

    .justify-content-between {
        justify-content: space-between!important;
    }

    .card-header:first-child {
        border-radius: calc(.35rem - 1px) calc(.35rem - 1px) 0 0;
    }
    .card-header {
        padding: .75rem 1.25rem;
        margin-bottom: 0;
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
    }

    .font-weight-bold {
        font-weight: 700!important;
    }

    .mb-4, .my-4 {
        margin-bottom: 1.5rem!important;
    }

    .text-gray-800, .card-details span {
        color: #5a5c69!important;
    }

    .text-gray-300 {
        color: #dddfeb!important;
    }

    .no-gutters>.col, .no-gutters>[class*=col-] {
        padding-right: 0;
        padding-left: 0;
    }

    .col-auto {
        flex: 0 0 auto;
        width: auto;
        max-width: 100%;
    }

    .text-xs {
        font-size: 1rem;
    }

    .d-flex {
        display: flex!important;
    }

    .flex-row {
        flex-direction: row!important;
    }

    .bg-pending-bonus {
        background-color: #f5f5f5 !important;
        color: #000 !important;
    }

    .bg-achieved-bonus {
        background-color: #6c757d !important;
        color: #fff !important;
    }

    .referral-points {
        color: #17a2b8!important;
        font-weight: bolder !important;
    }
</style>

<div class="dashboard tool-container" v-cloak>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="text-xs font-weight-bold text-uppercase mb-2">Titles</div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Highest Achieved Title:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.highestAchievedRank }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Paid-as Title:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.paidAsRank }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Current Title:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.currentRank }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Active:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ +currentRankDetails.isActive ? 'Yes' : 'No' }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Coach Points:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.coachPoints }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Referral Points:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else> <a href="#" class="text-success" style="text-decoration: underline" v-on:click.prevent="showReferralPoints">{{ currentRankDetails.referralPoints }}</a></span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Organization Points:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.organizationPoints }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Team Group Points:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.teamGroupPoints }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Preferred Customers:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.preferredCustomerCount }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Influencers:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.influencerCount }}</span>
                            </div>

                            <div class="h5 mb-0 mt-4 font-weight-bold">
                                Next Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.nextRank }}</span>
                            </div>

                            <div class="h5 mb-0 mt-1 font-weight-bold">
                                Needs:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <div v-else>
                                    <ul class="list-unstyled">
                                        <li v-for="(n, index) in currentRankDetails.needs" v-bind:key="n.description">{{n.description}}: <span>{{n.value}}</span> </li>
                                    </ul>
                                </div>
                            </div>

                        </div>
                        <div class="col-auto">
                             <!-- <i class="fa fa fa-trophy fa-4x text-gray-300"></i> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col-md-12 mr-2 text-success card-details">
                            <div class="font-weight-bold text-uppercase mb-2">
                                Next Bonus:
                                <span v-if="!isAchievementLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ titleAchievementBonus.nextBonus | money }}</span>
                            </div>

                            <div class="progress" style="height:60px">

                                <div v-for="(r, index) in titleAchievementBonus.ranks"
                                     v-bind:key="r.id"
                                     class="progress-bar overflow-auto"
                                     v-bind:class="{
                                         'bg-achieved-bonus': r.id <= titleAchievementBonus.highestRankId,
                                         'bg-pending-bonus': r.id > titleAchievementBonus.highestRankId,
                                     }"
                                     style="width:20%;height:60px"
                                     v-bind:style="{
                                         width: (100/titleAchievementBonus.ranks.length) + '%'
                                     }"
                                >{{ r.name }}</div>
                            </div>

                            <div v-show="isAchievementLoaded && titleAchievementBonus.doubleBonus.rank !== null"
                                 class="font-weight-bold text-uppercase mb-2">
                                <p v-if="titleAchievementBonus.doubleBonus.days > 0">
                                    <span>{{ titleAchievementBonus.doubleBonus.days }}</span> days remaining for <span>{{ titleAchievementBonus.doubleBonus.rank }}</span> Double Fast Start
                                </p>
                                <p v-else><span>{{ titleAchievementBonus.doubleBonus.hours }}</span> hours remaining for <span>{{ titleAchievementBonus.doubleBonus.rank }}</span> Double Fast Start</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-4 d-flex flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase">Current Period Orders</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtCurrentPeriodOrders.draw()">Refresh</button>
                        </div>
                    </div>

                    <table id="table-current-period-orders" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header">
                        <tr class="table__row">
                            <th class="table__cell">Invoice</th>
                            <th class="table__cell">Purchaser</th>
                            <th class="table__cell">Enroller</th>
                            <th class="table__cell">Products</th>
                            <th class="table__cell">Amount Paid</th>
                            <th class="table__cell">Date</th>
                            <th class="table__cell">CV</th>
                        </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-4 d-flex flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase">Beyond Bucks</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtGiftCards.draw()">Refresh</button>
                        </div>
                    </div>

                    <table id="table-gift-cards" class="table table-striped table-bordered dt-responsive nowrap table--align-middle" style="width:100%">
                        <thead class="table__header">
                        <tr class="table__row">
                            <th class="table__cell">Code</th>
                            <th class="table__cell">Validation Code</th>
                            <th class="table__cell">Amount</th>
                            <th class="table__cell">Balance</th>
                            <th class="table__cell">Expiration Date</th>
                            <th class="table__cell">Created Date</th>
                        </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-referral-points" role="dialog" aria-labelledby="modal-referral-points-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-referral-points-title">
                        Referral Points
                    </h4>
                </div>
                <div class="modal-body">

                    <table id="table-referral-points" class="table table-striped table-bordered dt-responsive nowrap table--align-middle" style="width:100%">
                        <thead class="table__header">
                        <tr class="table__row">
                            <th class="table__cell">Member</th>
                            <th class="table__cell">Points</th>
                            <th class="table__cell">Type</th>
                            <th class="table__cell">Details</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_dashboard.js?v=1.4"></script>

EOS
1;