print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_dashboard.css?v=1" />

<div class="dashboard tool-container" v-cloak>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="h4 font-weight-bold text-capitalize mb-3">Titles</div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Highest Achieved rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.highestAchievedRank }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Paid-as Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.paidAsRank }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Current Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.currentRank }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Active:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.isActive }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Personal Volume:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.businessVolume }}</span>
                            </div>

                            <div class="h5 mb-0 mt-4 font-weight-bold">
                                Next Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.nextRank }}</span>
                            </div>

                            <div class="h5 mb-0 mt-1 font-weight-bold">
                                Needs:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <div class="mba-text-color" v-else>
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
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="h4 font-weight-bold text-capitalize mb-3">Binary Volume</div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                LEFT LEG VOLUME:
                                <span v-if="!isBinaryVolumeLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentBinaryVolumeDetails.leftLegVolume }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold" style="padding-left: 15px;">
                                TODAY:
                                <span v-if="!isBinaryVolumeLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentBinaryVolumeDetails.leftLegVolumeToday }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold" style="padding-left: 15px;">
                                CARRY OVER:
                                <span v-if="!isBinaryVolumeLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentBinaryVolumeDetails.leftLegVolumeCarryOver }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                RIGHT LEG VOLUME:
                                <span v-if="!isBinaryVolumeLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentBinaryVolumeDetails.rightLegVolume }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold" style="padding-left: 15px;">
                                TODAY:
                                <span v-if="!isBinaryVolumeLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentBinaryVolumeDetails.rightLegVolumeToday }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold" style="padding-left: 15px;">
                                CARRY OVER:
                                <span v-if="!isBinaryVolumeLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentBinaryVolumeDetails.rightLegVolumeCarryOver }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="h4 font-weight-bold text-capitalize mb-3">LAST EARNINGS</div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                LIFE TIME EARNINGS:
                                <span v-if="!isEarningsLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ lastEarningsDetails.lifeTimeEarnings | money }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                LAST WEEKS'S EARNINGS:
                                <span v-if="!isEarningsLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span class="mba-text-color" v-else>{{ currentRankDetails.lastWeekEarnings | money }}</span>
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
                            <th class="table__cell">BV</th>
                        </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_dashboard.js?v=1.2&app=$app_js_version"></script>


EOS
1;