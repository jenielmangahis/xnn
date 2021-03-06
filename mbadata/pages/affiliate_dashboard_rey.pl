print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">

<div class="dashboard tool-container" >

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="text-xs font-weight-bold text-uppercase mb-2">Titles</div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Highest Achieved rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.highestAchievedRank }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Paid-as Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.paidAsRank }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Current Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.currentRank }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Active:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ +currentRankDetails.isActive ? 'Yes' : 'No' }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Business Volume:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.business_volume }}</span>
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
        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="text-xs font-weight-bold text-uppercase mb-2">Binary Volume</div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                LEFT LEG VOLUME:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ binaryVolume.leftLeg.volume }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                TODAY:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ binaryVolume.leftLeg.today }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                CARRY OVER:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ binaryVolume.leftLeg.carryOver }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                RIGHT LEG VOLUME:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ binaryVolume.rightLeg.volume }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                TODAY:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ binaryVolume.rightLeg.today }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                CARRY OVER:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ binaryVolume.rightLeg.carryOver }}</span>
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
                            <div class="text-xs font-weight-bold text-uppercase mb-2">LAST EARNINGS</div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                LIFE TIME EARNINGS:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ lastEarnings.lifeTime }}</span>
                            </div>
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                LAST WEEKS'S EARNINGS:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ lastEarnings.weekly }}</span>
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

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_dashboard_rey.js?v=1.1&app=$app_js_version"></script>


EOS
1;