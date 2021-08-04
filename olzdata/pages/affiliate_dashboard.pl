print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_dashboard.css?v=1" />

<style>
.goal-stat{
    display:inline-block;
    margin:20px;
}
.bash-goal{
    margin-right:171px;
}
</style>

<div class="dashboard tool-container" v-cloak>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h3 class="m-0 font-weight-bold text-primary text-uppercase">RANKS</h3>
                </div>
                <div class="card-body">
                    <div class="no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Paid As Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.paidAsRank }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.currentRank }}</span>
                            </div>                            
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Lifetime Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.currentRank }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Qualified:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.isQualified }}</span>
                            </div>  
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Current Volumes:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else></span>
                            </div>      
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                PRS:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.volumePRS }}</span>
                            </div>                                                   
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                GRS:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.volumeGRS }}</span>
                            </div> 
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Sponsored Qualified Representatives:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.sponsoredQualifiedRepresentativesCount }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Sponsored Leader or higher:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.sponsoredLeaderHigher }}</span>
                            </div> 
                            <div class="h6 mb-0 mt-2 mb-3 font-weight-bold">
                                Level 1 Leader 1:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else></span>
                            </div>                                                                                     

                            <div class="h6 mb-0 mt-4 font-weight-bold">
                                Next Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.nextRank }}</span>
                            </div>
                            <div class="h6 mb-0 mt-1 font-weight-bold">
                                Needs:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                PRS:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.needsPRS }}</span>
                            </div>  
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                GRS:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.needsGRS }}</span>
                            </div>   
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Sponsored Qualified Representatives:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.sponsoredQualifiedRepresentativesCount }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Sponsored Leader or higher:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.sponsoredLeaderHigher }}</span>
                            </div> 
                            <div class="h6 mb-0 mt-2 mb-3 font-weight-bold">
                                Level 1 Leader 1:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else></span>
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
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h3 class="m-0 font-weight-bold text-primary text-uppercase">QUALIFICATIONS</h3>
                </div>            
                <div class="card-body">
                    <div class="no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Direct Profit:
                                <span v-if="!isQualificationLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentQualificationDetails.isQualifiedForWeeklyDirectProfit }}</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Personal Sales Bonus:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentQualificationDetails.isQualifiedPersonalSalesBonus }}</span>
                            </div>                            
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Level Commission:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>Not Qualified</span>
                            </div>  
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Rank Advancement Bonus:
                                <span v-if="!isQualificationLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentQualificationDetails.isQualifiedForRankAdvancementBonus }}</span>
                            </div> 
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Rank Consistency Bonus:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>Not Qualified</span>
                            </div>
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Silver Start Up Program:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentQualificationDetails.isQualifiedSilverStartup }}</span>
                            </div>         
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Sparkle Start Up Program:
                                <span v-if="!isQualificationLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentQualificationDetails.isQualifiedForSparkleStartProgram }}</span>
                            </div>    
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Monthly Free Jewelry Incentive:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentQualificationDetails.isQualifiedFreeJewelryIncentive }}</span>
                            </div>                                                                                                                                                                
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h3 class="m-0 font-weight-bold text-primary text-uppercase">EARNINGS</h3>
                </div>              
                <div class="card-body">
                    <div class="no-gutters align-items-center">
                        <div class="col mr-2 text-info card-details">
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Last Weekly Earnings:
                                <span v-if="!isEarningsLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.lastWeekEarnings }}</span>
                                
                            </div>      
                            <div class="h6 mb-0 mt-2 font-weight-bold">
                                Last Monthly Earnings:
                                <span v-if="!isEarningsLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.lastMonthEarnings }}</span>
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
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase">Current Period Orders</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <!-- <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtCurrentPeriodOrders.draw()">Refresh</button>
                        </div>
                    </div> -->

                    <div class="form-inline mb-3">
                        <div class="form-group">
                            <label for="period-order-type" class="sr-only">Type</label> 
                                <select name="period-order-filter" id="period-order-type" class="form-control">.
                                    <option value="">Personal</option> 
                                    <option value="team">Team</option>
                                </select>
                        </div>
                    </div>                  

                    <div class="table-responsive">
                        <table id="table-current-period-orders" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header">
                            <tr  >
                                <th class="table__cell">Order #</th>
                                <th class="table__cell">Purchaser ID #</th>
                                <th class="table__cell">Purchaser</th>
                                <th class="table__cell">Sponsor</th>
                                <th class="table__cell">Product</th>
                                <th class="table__cell">Amount Paid</th>
                                <th class="table__cell">Transaction Date</th>
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
    
    <div class="col-md-12" style="padding: 40px 0px;">
        <div id="countdown-wrap">
            <div class="progress" style="height:2rem;">
              <div class="progress-bar" role="progressbar" aria-valuenow="100"
              aria-valuemin="0" aria-valuemax="100" style="width:100%;text-align:left;padding-left:14px;">
                <span v-if="!isSilverStartupProgramLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                <span v-else><b>Silver Start Up Program</b> Progress {{ silverStartUpDetails.silverTotalPRS }} worth of Gift Cards so far</span>
              </div>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$500</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$1000</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$1500</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$2000</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$2500</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$2500</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$3000</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$3500</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
            <div class="goal-stat">
                <span class="goal-number">\$4000</span><br/>
                <span class="goal-label">\$50 Gift Card</span>
            </div>
        </div>
    </div>

    <div class="col-md-12" style="padding: 40px 0px;">
        <div id="countdown-wrap">
            <span v-if="!isSparkleStartupProgramLoaded"><i class="fa fa-spinner fa-spin"></i></span>
            <span v-else>{{ sparkleStartUpDetails.sparkleNotice }} </span>
            <div class="progress" style="height:2rem;">
              <div class="progress-bar" role="progressbar" aria-valuenow="100"
              aria-valuemin="0" aria-valuemax="100" style="width:100%;text-align:left;padding-left:14px;">   
                <span v-if="!isSparkleStartupProgramLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                <span v-else>{{ sparkleStartUpDetails.sparkleTotalPRS }} / 500.00 PRS</span>            
              </div>
            </div>
        </div>
    </div>

    <div class="col-md-12" style="padding: 40px 0px;">
        <div id="countdown-wrap">
            <span v-if="!isBashStartupProgramLoaded"><i class="fa fa-spinner fa-spin"></i></span>
            <span v-else>{{ bashStartUpDetails.bashNotice }} </span>
            <div class="progress" style="height:2rem;">
              <div class="progress-bar" role="progressbar" aria-valuenow="100"
              aria-valuemin="0" aria-valuemax="100" style="width:{{ bashStartUpDetails.bashPercentage }}%;text-align:left;padding-left:14px;">   
                <span v-if="!isBashStartupProgramLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                <span v-else>{{ bashStartUpDetails.bashTotalPRS }} / 3,600.00 PRS</span>            
              </div>
            </div>

            <div class="goal-stat bash-goal">
                <span class="goal-number">\$6000</span>                
            </div>
            <div class="goal-stat bash-goal">
                <span class="goal-number">\$18,000</span>
            </div>
            <div class="goal-stat bash-goal">
                <span class="goal-number">\$24,000</span>
            </div>
            <div class="goal-stat bash-goal">
                <span class="goal-number">\$30,000</span>
            </div>
            <div class="goal-stat bash-goal">
                <span class="goal-number">\$36,000</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <!-- Card Header -->
                <div class="card-header py-4 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary text-uppercase">Gift Cards</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;"> 
                    <div class="table-responsive">
                        <table id="table-gift-cards" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header">
                            <tr>
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
        </div><!-- /.col-md-12 -->
    </div><!-- /.row -->    

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_dashboard.js?v=1.2&app=$app_js_version"></script>


EOS
1;