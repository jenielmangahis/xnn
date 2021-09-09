print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_transactions_report.css?v=1" />

<div id="transactions-report" class="tool-container tool-container--default">

    <div class="row">
        <div class="col-md-12">
            <h4 class="admin-money-title">Transaction Report</h4>
        </div>
    </div>

    <div class="put-mobile-lr-padding">

        <div class="panel panel-default">

            <div class="panel-body">

                <div class="row margin-top">
                    <div class="col-md-6">
                        <div class="form-horizontal">

                            <div class="form-group">
                                <div class="form-sub-group col-md-6 col-lg-6">
                                    <label for="date-from">From</label>

                                    <div class='input-group start-date' >
                                        <input type='text' class="form-control required" id='date-from' />
                                        <span class="input-group-addon date-from-icon">
                                            <i class="glyphicon glyphicon-calendar"></i>
                                        </span>
                                    </div>
                                    <span class="help-block"></span>
                                </div>
                                <div class="form-sub-group col-md-6 col-lg-6">
                                    <label for="date-to">To</label>
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
                                    <button class="btn btn-primary pull-right flat btn-block" id="button-generate-report">View Report</button>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="col-md-6">
                        <table class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <td colspan="2">Summary</td>
                            </tr>
                            </thead>
                            <tbody class="table__body">
                                <tr>
                                    <td>Credit Card</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Ledger</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Gift Card</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Coupons</td>
                                    <td></td>
                                </tr>
            				</tbody>
                        </table>
                    </div>

                </div>

                <div class="row margin-top margin-bottom padding-left-right-15">

                    <div class="col-md-12 dashes-container-2">
                        <div class="row">

                            <div class="col-md-4 no-padding-left">
                                <div class="form-group" id="download-report-links">
                                    
                                    <a id="link-download-csv" class="btn btn-info btn-block margin-left-minus-30" href="#">
                                        Download Transaction Report
                                    </a>
                                    
                                </div>
                            </div>

                            <div class="col-md-4">
                            </div>

                            <div class="col-md-4">
                                <div class="total-style-controller">
                                    <h5 class="admin-money-total"><strong>Total</strong></h5>
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
                                    <td class="table__cell">Commission Type</td>
                                    <td class="table__cell">Commission Period</td>
                                    <td class="table__cell">Invoice</td>
                                    <td class="table__cell">Purchaser</td>
                                    <td class="table__cell">Order Date</td>
                                    <td class="table__cell">Level</td>
                                    <td class="table__cell">CV</td>
                                    <td class="table__cell">Percentage</td>
                                    <td class="table__cell">Amount</td>
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