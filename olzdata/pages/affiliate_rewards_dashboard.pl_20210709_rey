print <<EOS;

<link rel="stylesheet" href="https://cdn.datatables.net/rowreorder/1.2.5/css/rowReorder.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tooltipster/3.3.0/css/themes/tooltipster-shadow.min.css" integrity="sha256-OIlyDunILjraKXlyZTIBuWVxBPzw3DvDhjbUUYgoxEo=" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.css" integrity="sha256-VxlXnpkS8UAw3dJnlJj8IjIflIWmDUVQbXD9grYXr98=" crossorigin="anonymous" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_rewards_dashboard.css?v=$app_css_version">

<div class="container panel rewards-dashboard">
	<input type="hidden" id="member_id" value="$uid">

	<div class="row">
	    <div class="col-md-12 text-center">
        <p>You Sharing Link: <a href="https://www.opulenzadesigns.com/ben">https://www.opulenzadesigns.com/ben</a></p>
            <a href="javascript:void(0)" class="btn btn-info"  data-clipboard-text="https://www.opulenzadesigns.com/ben"><i class="bi bi-clipboard"></i> Copy</a>
            <a href="https://www.facebook.com/sharer/sharer.php?s=100&p[url]=https://www.opulenzadesigns.com&p[images][0]=&p[title]=Opulenza Designs&p[summary]=LEAVE A LITTLE SPARKLE
WHEREVER YOU GO" target="_blank" onclick="window.open(this.href,'targetWindow','toolbar=no,location=0,status=no,menubar=no,scrollbars=yes,resizable=yes,width=600,height=250'); return false" class="btn btn-info"><i class="bi bi-facebook"></i> Share</a>
            <a href="https://www.instagram.com/opulenzadesigns/" target="_blank" class="btn btn-info"><i class="bi bi-instagram"></i> Instagram</a>
            <a href="https://www.pinterest.ph/opulenzadesigns2021/" target="_blank" class="btn btn-info"><i class="bi bi-pin-angle-fill"></i> Pin It</a>            
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
    </div>
<h4>Your progress: 785usd worth of Total Sales So Far</h4>
<div class="progress" style="height: 40px; width:100%">
  <div class="progress-bar bg-success" role="progressbar" style="width: 22%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
</div>
<div class="container" style="margin-bottom:30px; text-align:center">
  <div class="row">
    <div class="col col1">500usd</div>
    <div class="col col2">1000usd</div>
    <div class="col col3">1500usd</div>
    <div class="col col4">2000usd</div>
    <div class="col col5">2500usd+</div>
  </div>
</div>    
    <div class="row affiliate-rewards-dashboard__pending-orders-table--wrap table-responsive">
        <div class="col-md-12">
            <h3 class="rewards-dashboard__h2 text-uppercase">
                Your Open Event
	        </h3>        
            <table class="table table-bordered rewards-dashboard__table affiliate-rewards-dashboard__pending-orders-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Invoice</th>
                        <th>Order Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
    <div class="row affiliate-rewards-dashboard__top-hostess-table--wrap table-responsive">
        <div class="col-md-12">
            <h3 class="rewards-dashboard__h2 text-uppercase">
                Your Daily Rewards
	        </h3>
            <table class="table table-bordered rewards-dashboard__table affiliate-rewards-dashboard__top-hostess-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Sales</th>
                        <th>Product Credits</th>
                        <th>Orders</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="row mtop5 table-responsive">
        <div class="col-md-12">
            <h3 class="rewards-dashboard__h2 text-uppercase">
                Your Product Credits
            </h3>
            <table class="table table-bordered rewards-dashboard__table affiliate-rewards-dashboard__coupon-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Validation Code</th>
                        <th>Period Earned</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Expiration Date</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="row mtop5 table-responsive">
        <div class="col-md-12">
            <h3 class="rewards-dashboard__h2 text-uppercase">
                Your Coupons
            </h3>
            <table class="table table-bordered rewards-dashboard__table affiliate-rewards-dashboard__gift-cards-history-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Period Earned</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>    

    <!-- TOOLTIPSTER TABLE -->
    <div class="tooltip-affiliate-pending-orders-breakdown__template">
        <div class="container" id="tooltip-affiliate-pending-orders-breakdown">
            <div class="row">
                <div class="col-md-12">
                    <span class="tooltipster-close float-right">
                        <i class="fa fa-times-circle" aria-hidden="true"></i>
                    </span>

                    <table class="table table-bordered rewards-dashboard__table affiliate-rewards-dashboard__pending-orders-table--breakdown display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Order Number</th>
                                <th>Order</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        <div>
    </div>
</div>

<script src="https://cdn.datatables.net/rowreorder/1.2.5/js/dataTables.rowReorder.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="$commission_engine_api_url/js/jquery.countdown.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.4/jquery-confirm.min.js" integrity="sha256-Ka8obxsHNCz6H9hRpl8X4QV3XmhxWyqBpk/EpHYyj9k=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/_pages.config.js"></script>
<script src="$commission_engine_api_url/js/affiliate_rewards_dashboard_fn.js"></script>
<script src="$commission_engine_api_url/js/affiliate_rewards_dashboard.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.5.10/clipboard.min.js"></script>

EOS
1;
