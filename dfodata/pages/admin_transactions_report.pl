print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_transactions_report.css?v=1" />
<style>
.summary-header{
    background-color:#007398;
    color:#ffffff;
    padding:10px;
    display:block;
    font-size:14px;
}
</style>
<div id="transactions-report" class="tool-container tool-container--default">

    <div class="row">
        <div class="col-md-12">
            <h4 class="admin-money-title">Transaction Report</h4>
        </div>
    </div>

    <div class="put-mobile-lr-padding">

        <div class="card with-border-no-radius">

            <div class="card-body">

                <div class="row margin-top">
                    
                    <div class="col-md-6">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                            
                                <label for="date-from">From</label>
                                <div class="input-group start-date">
                                    <input type='text' class="form-control" id='date-from' >
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon2">
                                            <i class="bi bi-calendar"></i>
                                        </span>
                                    </div>
                                </div>

                                <span class="help-block"></span>
                            </div>

                            <div class="col-md-6">

                                <label for="date-to">To</label>
                                <div class="input-group end-date">
                                    <input type='text' class="form-control" id='date-to' >
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon2">
                                            <i class="bi bi-calendar"></i>
                                        </span>
                                    </div>
                                </div>

                                <span class="help-block"></span>
                            </div> 

                        </div>
                        <div class="row margin-top-bottom">

                            <div class="col-md-6">
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

                            <div class="col-md-6">
                                <label for="status">&nbsp;</label>
                                <button class="btn btn-primary pull-right flat btn-block" id="button-generate-report">View Report</button>
                            </div>

                        </div>

                    </div>

                    <div class="col-md-6">
                        <div class="col-md-12">
                            <table id="table-admin-totals" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell" colspan="6">Summary</th>
                                </tr>
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

                <div class="row margin-top margin-bottom padding-left-right-15">

                    <div class="col-md-12 use-content-balance">
                        <div class="row dashes-container-2">

                            <div class="col-md-4 no-padding-left">
                                <div class="" id="download-report-links">
                                    
                                    <a id="link-download-csv" class="btn btn-info btn-block margin-left-minus-30" href="#">
                                        Download Transaction Report
                                    </a>
                                    
                                </div>
                            </div>

                            <div class="col-md-4">
                            </div>

                            <div class="col-md-4">
                                <div class="total-style-controller">
                                    <h5 class="admin-money-total"><strong>Total: 0.00 </strong></h5>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

                <div class="row put-mobile-lr-padding">
                    <div class="table-responsive">
                        <div class="col-md-12">
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