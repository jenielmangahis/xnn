print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_party_report.css?v=1" />


<div class="party-report tool-container tool-container--default">
    <div class="row">
        <div class="col-md-10">
            <h4>Parties List</h4>
        </div>

        <div class="col-md-2">
            <div class="pull-right">
                <button type="button" class="btn btn-excel">
                    <i class="bi bi-file-earmark-ruled-fill"></i> Export to Excel
                </button>
            </div>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-11">
            <form class="form-horizontal ">
                <div class="form-row">
                     <div class="form-group col-lg-2 col-md-3 col-6">
                        <input  type="text" class="form-control flat"  placeholder="Party ID" >
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="start-date" type="text" class="form-control flat">
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="end-date" type="text" class="form-control flat">
                    </div>
                    <div class="form-group col-lg-1 col-md-3 col-6">
                         <select class="form-control form-control-sm">
                            <option selected="Status">Status</option>
                            <option value="Status">Status</option>
                            <option value="Status">Status</option>
                            <option value="Status">Status</option>
                         </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input  type="text" class="form-control flat"  placeholder="Rep ID or Name" >
                    </div>
                    <div class="form-group col-lg-1 col-md-3 col-6">
                        <button type="button" class="btn btn-primary btn-block">Search</button>
                    </div>
                    <div class="form-group col-lg-2 col-md-4 col-6">
                        <div class="checkbox mt-2">
                            <input type="checkbox">
                            <label>Show only Rep Party</label>
                        </div>
                    </div>
                </div>

            </form>
        </div>
        <div class="col-md-1 ">
            <div class="pull-right">
                <div class="form-group">
                    <select class="form-control form-control-sm">
                        <option selected="">100</option>
                        <option value="75">75</option>
                        <option value="50">50</option>
                        <option value="25">25</option>
                    </select>
                </div>
            </div>
        </div>

    </div>
     <hr />
    <div class="row">
        <div class="col-md-12">
            <p class="text-primary mb-0"><i class="bi bi-caret-down-fill"></i> Advanced Filter & Search
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-4">
            <button type="button" id="show-hide-summary" class="btn btn-primary " data-toggle="collapse" href="#collapseSummary" aria-controls="collapseSummary">Show Summary</button>
       
            <div class="collapse " id="collapseSummary">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <tbody class="table__body">
                            <tr>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Total Number of Parties</td>
                                <td>211</td>
                            
                            </tr>
                            <tr>
                                <td>Total Number of Reps</td>
                                <td>120</td>
                            </tr>
                            <tr>
                                <td>Total Number of Order</td>
                                <td>1201</td>
                            </tr>
                            <tr>
                                <td>Party Average</td>
                                <td>10.80</td>
                            </tr>
                            <tr>
                                <td>Average Guest</td>
                                <td>4.80</td>
                            </tr>
                            <tr>
                                <td>Average Items Bought per Order</td>
                                <td>1.80</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Party ID Action</th>
                            <th class="table__cell">Rep ID</th>
                            <th class="table__cell">Name</th>
                            <th class="table__cell">Hostess Name</th>
                            <th class="table__cell">Hostess Email</th>
                            <th class="table__cell">Start Date</th>
                            <th class="table__cell">End Date</th>
                            <th class="table__cell">Close Date</th>
                            <th class="table__cell">Title</th>
                            <th class="table__cell">Guest Count</th>
                            <th class="table__cell">Orders Count</th>
                            <th class="table__cell">Total Retail Sales</th>
                            <th class="table__cell">Total Sales</th>
                            <th class="table__cell">Reward</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <tr>
                            <td>OLZ22</td>
                            <td>21123</td>
                            <td>Ann Renk</td>
                            <td>Nancy</td>
                            <td>annren\@gmail.com</td>
                            <td>09-11-21 10:00 AM</td>
                            <td>09-12-21 10:00 AM</td>
                            <td></td>
                            <td>Nancy' End of Summer Party</td>
                            <td>1</td>
                            <td>2</td>
                            <td>1</td>
                            <td>\$120.00</td>
                            <td>2</td>
                        </tr>
                        <tr>
                            <td>OLZ22</td>
                            <td>21123</td>
                            <td>Ann Renk</td>
                            <td>Nancy</td>
                            <td>annren\@gmail.com</td>
                            <td>09-11-21 10:00 AM</td>
                            <td>09-12-21 10:00 AM</td>
                            <td></td>
                            <td>Nancy' End of Summer Party</td>
                            <td>1</td>
                            <td>2</td>
                            <td>1</td>
                            <td>\$120.00</td>
                            <td>2</td>
                        </tr>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
    integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_party_report.js?v=1.1"></script>


EOS
1;