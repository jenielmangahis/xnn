print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_transactions_report.css?v=1" />

<div id="transactions-report" class="tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">Transactions</h4>
        </div>
    </div>
<div class="mba-money-border">    
    <div class="row">
        <div class="col-md-6">
            <div class="form-horizontal">
               <div class="form-row font-weight-bold">
                     <div class="form-group col-md-6">
                        <label for="date-from">Date From</label>
                          <div class="input-group start-date">
                                <input type='text' class="form-control required" id='date-from' />
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-th"></span>
                                </div>
                            </div>
                        <span class="help-block"></span>
                    </div>
                     <div class="form-group col-md-6">
                        <label for="date-to">Date To</label>
                          <div class="input-group end-date" data-provide="datepicker">
                            <input type='text' class="form-control required" id='date-to' />
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-th"></span>
                            </div>
                            </div>
                        <span class="help-block"></span>
                    </div>
                </div>
               <div class="form-row font-weight-bold">
                    <div class="form-group col-md-6 mt-1">
                        <label for="status">Status:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="All" selected>All</option>
                            <option value="Approved">Approved</option>
                            <option value="Declined">Declined</option>
                            <option value="Error">Error</option>
                            <option value="Failed">Failed</option>
                        </select>
                        <span class="help-block"></span>
                    </div>
                    <div class="form-group col-md-6 mt-1">
                        <label>&nbsp;</label><br>
                        <button class="new-btn-mba btn btn-primary pull-right flat btn-block" id="button-generate-report">GO</button>
                    </div>
                </div>
                <div class="form-group" id="download-report-links" style="display: none;">

                    <div class="form-sub-group col-md-6 col-lg-6">
                        <a style="margin-bottom:10px!important;" id="link-download-csv" class="btn btn-primary flat btn-block" href="#">
                            <span class="glyphicon glyphicon-th-list" aria-hidden="true">  </span> Download CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-4 pt-3">
           <div class="table-responsive"> 
                <table id="table-admin-totals" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Totals </th>
                        <th class="table__cell">All </th>
                        <th class="table__cell">Approved </th>
                        <th class="table__cell">Declined </th>
                        <th class="table__cell">Error </th>
                        <th class="table__cell">Failed </th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
           </div> 
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="table-responsive">   
                <table id="table-admin-transactions" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">ORDER ID </th>
                        <th class="table__cell">Purchaser </th>
                        <th class="table__cell">Sponsor </th>
                        <th class="table__cell">Product </th>
                        <th class="table__cell">CV </th>
                        <th class="table__cell">Volume </th>
                        <th class="table__cell">Date
                            <br>Purchase </th>
                        <th class="table__cell">Price
                            <br>Paid </th>
                        <th class="table__cell">Credited </th>
                        <th class="table__cell">Status </th>
                        <th class="table__cell">Order
                            <br>Type </th>
                        <th class="table__cell">Payment
                            <br>Type </th>
                        <th class="table__cell">Phone </th>
                        <th class="table__cell">Gift Card </th>
                        <th class="table__cell">Coupon </th>
                        <th class="table__cell">Ledger </th>
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

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    jQuery.fn.ddatepicker = jQuery.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/admin_transactions_report.js?v=1.3"></script>

EOS
1;