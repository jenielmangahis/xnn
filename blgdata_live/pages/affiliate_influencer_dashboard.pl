print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=1.0&app=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/member-menu.css" />


<style>
.tool-container--default {
    margin-bottom: 20px;
    background-color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
    padding: 15px;}

.table__header { background-color:#9aa1a5; color:#fff; }

.bg-success {background-color: #28a745 !important; }

.col-xs-1-10,
.col-sm-1-10 {
  position: relative;
  min-height: 1px;
}

.col-xs-1-10 {
  width: 10%;
  float: left;
}

</style>

<div class="tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4>Influencer's Dashboard</h4>
            <hr />
        </div>
    </div>

    <div class="row">
		<div class="col-md-3"> 
			<p><strong>Influencer Level:</strong></p>
		</div>	
        <div class="col-md-9">
            <p>A-Lister</p>
        </div>		
    </div>
	
    <div class="row">
		<div class="col-md-3"> 
			 <p><strong>Coupon Discount</strong></p>
		</div>	
        <div class="col-md-9">
            <p>10%</p>
        </div>		
    </div>	
	
    <div class="row">
		<div class="col-md-3"> 
			 <p><strong>Coupon Code</strong></p>
		</div>	
        <div class="col-md-9">
            <p>GET10</p>
        </div>		
    </div>	
	
    <div class="row">
		<div class="col-md-3"> 
			 <p><strong>Commission</strong></p>
		</div>	
        <div class="col-md-9">
            <p>15%</p>
        </div>		
    </div>		
	
    <div class="row">
		<div class="col-md-3"> 
			 <p><strong>Lifetime Sales</strong></p>
		</div>	
        <div class="col-md-9">
            <p>90,000.00usd</p>
        </div>		
    </div>		

     <div class="row">    
        <div class="col-md-12">    
            <div class="progress" style="margin-bottom:10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
            </div> 
        </div>
    </div>

     <div class="row mob-hide" style="margin-bottom:20px;">    
        <div class="col-md-12">    
            <div class="col-xs-1-10">
              0usd
            </div>
            <div class="col-xs-1-10">
                25,000usd
            </div>
            <div class="col-xs-1-10">
                50,000usd
            </div>
            <div class="col-xs-1-10">
                75,000usd
            </div>
            <div class="col-xs-1-10">
                100,000usd
            </div>
            <div class="col-xs-1-10">
                125,000usd
            </div>
                <div class="col-xs-1-10">
                150,000usd
            </div>
            <div class="col-xs-1-10">
                200,000usd
            </div>
            <div class="col-xs-1-10">
                225,000usd
            </div>
            <div class="col-xs-1-10">
                250,000usd
            </div>        
        </div>
    </div>

     <div class="row">    
        <div class="col-md-12">    
            <h4>Customer Orders</h4>
            <hr />          
        </div>
    </div>

     <div class="row">    
        <div class="col-md-12">
            <div class="row"> 
                <div class="col-md-8">
                    <form class="form-horizontal">
                        <div class="form-group" v-show="+highest.is_all === 0">
                            <div class="row">
								<div class="col-md-12">
                                    <div class="col-md-4">
                                        <label>From</label>
                                        <datepicker v-model="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>	
                                    <div class="col-sm-4">
                                        <label>To</label>
                                        <datepicker v-model="highest.end_date" v-bind:start-date="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>  
                                    <div class="col-sm-4">
                                        <button
                                                type="button"
                                                class="btn btn-primary"
                                                v-on:click.prevent="viewPersonal">
                                            Filter
                                        </button>
                                    </div>  
								</div>	
                            </div>
                        </div>
                    </form>						
                 </div>
				<div class="col-md-4">
            <div class="pull-right">
                <h5><strong>Total Commissions {{ total | money }}</strong></h5>
            </div>					
				</div>	
            </div>               
        </div>
    </div>        
   

    <div class="row">
        <div class="col-md-12">
<div class="table-responsive">
            <table id="affiliate-influencer" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead class="table__header">
                <tr class="table__row">
                    <th class="table__cell">Order ID</th>
                    <th class="table__cell">Purchaser</th>
                    <th class="table__cell">Description</th>
                    <th class="table__cell">Order Date</th>
                    <th class="table__cell">Amount Paid</th>
                    <th class="table__cell">BV</th>
                    <th class="table__cell">Commission %</th>
                    <th class="table__cell">Commisision</th>
                    <th class="table__cell">Status</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
</div>            
        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>

EOS
1;