print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/hostess_dashboard.css?v=$app_css_version">

<div id="hostess-dashboard" class="hostess-dashboard tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-12 text-center">
                <p>
                    You Sharing Link: <a href="https://www.opulenzadesigns.com/$site" id="sharing_link">https://www.opulenzadesigns.com/$site</a>
                </p>

                <a href="javascript:void(0);" class="btn btn-default rewards-dashboard__a-btn-ss rewards-dashboard__a-btn-ss-copy" data-clipboard-target="#sharing_link">
                    <i class="fa fa-files-o" aria-hidden="true"></i>
                    <span class="rewards-dashboard__a-btn-txt">
                        Copy
                    </span>
                </a>

                <a href="$site" class="btn btn-default rewards-dashboard__a-btn-ss rewards-dashboard__a-btn-ss-fb rewards-dashboard__a-social-media" title="Facebook share" target="_blank">
                    <i class="fa fa-facebook" aria-hidden="true"></i>
                    <span class="rewards-dashboard__a-btn-txt">
                        Share
                    </span>
                </a>

                <a href="$site" class="btn btn-default rewards-dashboard__a-btn-ss rewards-dashboard__a-btn-ss-instagram rewards-dashboard__a-social-media">
                    <i class="fa fa-instagram" aria-hidden="true"></i>
                    <span class="rewards-dashboard__a-btn-txt">
                        Instagram
                    </span>
                </a>

                <a href="$site" class="btn btn-default rewards-dashboard__a-btn-ss rewards-dashboard__a-btn-ss-pinterest rewards-dashboard__a-social-media" title="Pinterest share" target="_blank">
                    <i class="fa fa-pinterest-p" aria-hidden="true"></i>
                    <span class="rewards-dashboard__a-btn-txt">
                        Pin it
                    </span>
                </a>

                <!-- COUNTDOWN TIMER -->
                <ul id="rewards-dashboard__countdown-timer">
                    <li>
                        <span class="days">00</span><p class="days_text">Days</p>
                    </li>
                    <li class="separator">:</li>
                    <li>
                        <span class="hours">00</span><p class="hours_text">Hours</p>
                    </li>
                    <li class="separator">:</li>
                    <li>
                        <span class="minutes">00</span><p class="minutes_text">Minutes</p>
                    </li>
                </ul>
                <!-- END COUNTDOWN TIMER -->

                <h2 class="rewards-dashboard__h2">
                    Until Reward Time!
                </h2>
            </div>

            <!-- PROGRESS BAR -->
            <div class="col-md-12">
                <div id="countdown-wrap">
                    <h4 id="progress-value">
                        <i class="fa fa-circle-o-notch fa-spin text-danger"></i>
                    </h4>
                    <div id="glass">
                        <div id="progress"></div>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-number">\$500</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-number">\$1000</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-number">\$1500</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-number">\$2000</span>
                    </div>
                    <div class="goal-stat">
                        <span class="goal-number">\$2500+</span>
                    </div>
                </div><!-- /#countdown-wrap -->
            </div><!-- /.col-md-12 -->
            <!-- END PROGRESS BAR -->
        </div><!-- /.col-md-12 -->
    </div><!-- /.row -->

    <div class="row">
        <div class="col-md-12">
            <!-- OPEN EVENT LIST -->
            <h3>
                YOUR OPEN EVENT
            </h3>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-12 bottom-padding">
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-open-events" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Customer</th>
                    <th class="table__cell">Invoice</th>
                    <th class="table__cell">Order Date</th>
                    <th class="table__cell">Description</th>
                    <th class="table__cell">Amount</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
            <!-- END OPEN EVENT TABLE -->
        </div>
            
        <div class="col-md-12 bottom-padding">
            <div class="clearfix"></div>

            <!-- DAILY REWARDS TABLE -->
            <h3>
                YOUR DAILY REWARDS
            </h3>
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-daily-rewards" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Date</th>
                    <th class="table__cell">Total Sales</th>
                    <th class="table__cell">Product Credits</th>
                    <th class="table__cell">Orders</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
            <!-- END DAILY REWARDS TABLE -->
        </div>
            
        <div class="col-md-12 bottom-padding">
            <div class="clearfix"></div>

            <!-- ORDERS GIFT CARDS TABLE -->
            <h3>
                YOUR PRODUCT CREDITS
            </h3>
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-gift-cards" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Code</th>
                    <th class="table__cell">Validation Code (GVC)</th>
                    <th class="table__cell">Period Earned</th>
                    <th class="table__cell">Amount</th>
                    <th class="table__cell">Balance</th>
                    <th class="table__cell">Expiration Date</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
            <!-- END GIFT CARDS TABLE -->
        </div>

        <div class="col-md-12 bottom-padding">
            <div class="clearfix"></div>

            <!-- COUPON TABLE -->
            <h3>
                YOUR COUPONS
            </h3>
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-coupon" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Code</th>
                    <th class="table__cell">Period Earned</th>
                    <th class="table__cell">Description</th>
                    <th class="table__cell">Status</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
            <!-- COUPON TABLE -->
        </div>
    </div>

    <div class="modal fade" id="modal-view-orders" role="dialog" aria-labelledby="modal-view-orders-label">
        <div class="modal-dialog modal-lg" style="padding-top: 60px;" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Order List</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="table-view-orders" class="table table table-striped" style="width:100%" cellspacing="0" width="100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Customer</th>
                            <th class="table__cell">Order ID</th>
                            <th class="table__cell">Description</th>
                            <th class="table__cell">Amount</th>
                        </tr>
                        </thead>
                        <tbody class="table__body"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.tool-container -->

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="$commission_engine_api_url/js/jquery.countdown.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.4/clipboard.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js" integrity="sha256-S1J4GVHHDMiirir9qsXWc8ZWw74PHHafpsHp5PXtjTs=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/hostess_dashboard.js?v=1.9"></script>

EOS
1;