print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datatables.css?v=$app_css_version" />
<!--<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">-->
<link rel="stylesheet" href="$commission_engine_api_url/css/member-menu.css" />

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
        padding: 7px 1.25rem;
        margin-bottom: 0;
    background-color: #9aa1a5;
    color: #fff;
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
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">Next Rank Requirements</h5>
                </div> 
                <div class="card-body">
                    <div class="row">
                            <div class="h5 mb-0 mt-4 font-weight-bold" style="display:block; width:100%">
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
                            <ul class="list-unstyled" style="margin-left: 20px;">
                                    <li>
                                        <div class="h5 mb-0 mt-1 font-weight-bold">
                                            <!-- Static Area I created -->
                                                Personal Volume
                                                <span>500</span>
                                        </div> 
                                    </li>
                                        <div class="h5 mb-0 mt-1 font-weight-bold">
                                        <!-- Static Area I created -->
                                            Group Volume
                                            <span>5000</span>
                                        </div>                                     
                                    <li>
                                        <div class="h5 mb-0 mt-1 font-weight-bold">
                                        <!-- Static Area I created -->
                                            Legs
                                            <span>1 BGS</span>
                                        </div>   
                                    </li>
                                </ul>                                
                            </div>                                                                                                                               
                    </div>                
                </div>                           
            </div>
        </div>      

        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">Rank</h5>
                </div> 
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Paid-as Rank:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.paidAsRank }}</span>
                            </div> 
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Current Title:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.currentRank }}</span>
                            </div> 
                            <div class="clearfix"></div>   
                            <div class="h5 mb-0 mt-1 font-weight-bold" style="display:block; width:100%">
                            <!-- Static Area I created -->
                                Personal Volume
                                <span>100</span>
                            </div>  
                            <div class="clearfix"></div>   
                            <div class="h5 mb-0 mt-1 font-weight-bold" style="display:block; width:100%">
                            <!-- Static Area I created -->
                                Group Volume
                                <span>20000</span>
                            </div> 
                            <div class="clearfix"></div>   
                            <div class="h5 mb-0 mt-1 font-weight-bold" style="display:block; width:100%">
                            <!-- Static Area I created -->
                                Legs
                                <span>1 BGS</span>
                            </div>                                                                                                                             
                    </div>                
                </div>                           
            </div>
        </div>   

        <div class="col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">Earnings</h5>
                </div> 
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Last Weekly Earnings:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.highestAchievedRank }}</span>
                            </div> 
                            <div class="h5 mb-0 mt-2 font-weight-bold">
                                Last Monthly Earnings:
                                <span v-if="!isRankLoaded"><i class="fa fa-spinner fa-spin"></i></span>
                                <span v-else>{{ currentRankDetails.highestAchievedRank }}</span>
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
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">14 Day Run Bonus</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtCurrentPeriodOrders.draw()">Refresh</button>
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
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">60-Day Run Bonus</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtCurrentPeriodOrders.draw()">Refresh</button>
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
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">Personal and Enrolled Orders</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtCurrentPeriodOrders.draw()">Refresh</button>
                        </div>
                    </div>
                <div class="table-responsive">
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

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-4 flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-uppercase text-center">Beyond Bucks</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtGiftCards.draw()">Refresh</button>
                        </div>
                    </div>
                <div class="table-responsive">
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