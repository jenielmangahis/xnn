print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_transactions_report.css?v=1" />
<link rel="stylesheet" href="$commission_engine_api_url/css/money-admin-menu.css" />

<style>
    div.dataTables_wrapper div.dataTables_length select 
    {
        margin-left: 14px !important;
        width:102px !important;
    }
    .dataTables_scrollHeadInner[style], .dataTable[style] { width:100% !important; }
</style>

<div id="transactions-report" class="tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4>Transactions</h4>
            <hr />
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="form-sub-group col-md-6 col-lg-6">
                        <label for="date-from">Date From</label>

                        <div class='input-group start-date' >
                            <input type='text' class="form-control required" id='date-from' />
                            <span class="input-group-addon date-from-icon">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </span>
                        </div>
                        <span class="help-block"></span>
                    </div>
                    <div class="form-sub-group col-md-6 col-lg-6">
                        <label for="date-to">Date To</label>
                        <div class='input-group end-date' >
                            <input type='text' class="form-control required" id='date-to' />
                            <span class="input-group-addon date-to-icon">
                                <i class="glyphicon glyphicon-calendar"></i>
                            </span>
                        </div>
                        <span class="help-block"></span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-sub-group col-md-6 col-lg-6">
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
                    <div class="form-sub-group col-md-6 col-lg-6">
                        <button class="btn btn-primary pull-right flat btn-block" id="button-generate-report">GO</button>
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
        <div class="col-md-6">
            <div class="table-responsive">
                <table id="table-admin-totals" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100% !important;">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Totals</th>
                        <th class="table__cell">All</th>
                        <th class="table__cell">Approved</th>
                        <th class="table__cell">Declined</th>
                        <th class="table__cell">Error</th>
                        <th class="table__cell">Failed</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>    
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table id="table-admin-transactions" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">ORDER ID</th>
                        <th class="table__cell">Purchaser</th>
                        <th class="table__cell">Sponsor</th>
                        <th class="table__cell">Product</th>
                        <th class="table__cell">CV</th>
                        <th class="table__cell">Volume</th>
                        <th class="table__cell">Date
                            <br>Purchase</th>
                        <th class="table__cell">Price
                            <br>Paid</th>
                        <th class="table__cell">Credited</th>
                        <th class="table__cell">Status</th>
                        <th class="table__cell">Order
                            <br>Type</th>
                        <th class="table__cell">Payment
                            <br>Type</th>
                        <th class="table__cell">Phone</th>
                        <th class="table__cell">Gift Card</th>
                        <th class="table__cell">Coupon</th>
                        <th class="table__cell">Ledger</th>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    jQuery.fn.ddatepicker = jQuery.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/admin_transactions_report.js?v=1.3"></script>

EOS
1;