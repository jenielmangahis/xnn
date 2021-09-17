print <<EOS; 
<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_group_sales.css?v=1" />

<div class="group-sales tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4>Group Sales</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-admin-tab-report'>
                <li class="nav-item">
                    <a class="nav-link active" href="#groupsales" aria-controls="groupsales" role="tab"
                        data-toggle="tab" style="color: black !important;">Group Sales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#alldata" aria-controls="alldata" role="tab" data-toggle="tab"
                        style="color: black !important;">All Data</a>
                </li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="groupsales">
                    <div class="row">
                        <div class="col-md-10">
                           
                        </div>

                        <div class="col-md-2">
                            <div class="pull-right">
                                <button type="button" class="btn btn-excel">
                                    <i class="bi bi-file-earmark-ruled-fill"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-10">
                            <form class="form-horizontal ">
                                <div class="form-row">
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input id="start-date" type="text" class="form-control flat">
                                    </div>
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input id="end-date" type="text" class="form-control flat">
                                    </div>
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input type="text" class="form-control" placeholder="Rep ID or Name" />
                                    </div>
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <button type="button" class="btn btn-primary btn-block">Search</button>
                                    </div>
                                </div>

                            </form>
                        </div>
                        <div class="col-md-2 ">
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
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered dt-responsive nowrap"
                                    style="width:100%">
                                    <thead class="table__header table__header--bg-primary">
                                        <tr class="table__row">
                                            <th class="table__cell">ID</th>
                                            <th class="table__cell">Full Name</th>
                                            <th class="table__cell">Enrollment Date</th>
                                            <th class="table__cell">Upgrade Date</th>
                                            <th class="table__cell">Email</th>
                                            <th class="table__cell">Country</th>
                                            <th class="table__cell">Sponsor ID</th>
                                            <th class="table__cell">Sponsor Name</th>
                                            <th class="table__cell">PRS</th>
                                            <th class="table__cell">Psrs</th>
                                            <th class="table__cell">Grs</th>
                                        </tr>
                                    </thead>
                                    <tbody class="table__body">
                                        <tr>
                                            <td>1001</td>
                                            <td>Ann Renk</td>
                                            <td>09-11-21</td>
                                            <td>09-11-21</td>
                                            <td>annren\@gmail.com</td>
                                            <td>United States</td>
                                            <td>12345680</td>
                                            <td>Ben</td>
                                            <td>422</td>
                                            <td>344</td>
                                            <td>34</td>

                                        </tr>
                                        <tr>
                                            <td>1002</td>
                                            <td>Ann Renk</td>
                                            <td>09-11-21</td>
                                            <td>09-11-21</td>
                                            <td>annren\@gmail.com</td>
                                            <td>United States</td>
                                            <td>12345680</td>
                                            <td>Ben</td>
                                            <td>422</td>
                                            <td>344</td>
                                            <td>34</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="alldata">
                    <div class="row">
                        <div class="col-md-10">
                           
                        </div>

                        <div class="col-md-2">
                            <div class="pull-right">
                                <button type="button" class="btn btn-excel">
                                    <i class="bi bi-file-earmark-ruled-fill"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-11">
                            <form class="form-horizontal ">
                                <div class="form-row">
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input id="start-date" type="text" class="form-control flat">
                                    </div>
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input id="end-date" type="text" class="form-control flat">
                                    </div>
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input type="text" class="form-control" placeholder="Start Enrollment Date" />
                                    </div>
                                    <div class="form-group col-lg-2 col-md-3 col-6">
                                        <input type="text" class="form-control" placeholder="Rep ID or Name" />
                                    </div>
                                    <div class="form-group col-lg-1 col-md-3 col-6">
                                        <button type="button" class="btn btn-primary btn-block">Search</button>
                                    </div>
                                    <div class="form-group col-lg-1 col-md-3 col-6">
                                        <select class="form-control form-control-sm">
                                            <option selected="GRS">GRS</option>
                                            <option value="GRS">GRS</option>
                                            <option value="GRS">GRS</option>
                                            <option value="GRS">GRS</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-lg-2 col-md-4 col-6">
                                        <div class="checkbox mt-2">
                                            <input type="checkbox">
                                            <label>PRS of \$500 or above</label>
                                        </div>
                                    </div>

                                </div>

                            </form>
                        </div>
                        <div class="col-md-1">
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
                    <div class="row">
                            <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered dt-responsive nowrap"
                                    style="width:100%">
                                    <thead class="table__header table__header--bg-primary">
                                        <tr class="table__row">
                                            <th class="table__cell">Order Count: 563</th>
                                            <th class="table__cell">Total Order Amount: 3222.22</th>
                                            <th class="table__cell">Grs Total: 65431</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class=" table-responsive">
                                <table class="table table-striped table-bordered dt-responsive nowrap"
                                    style="width:100%">
                                    <thead class="table__header table__header--bg-primary">
                                        <tr class="table__row">
                                            <th class="table__cell">Level</th>
                                            <th class="table__cell">ID</th>
                                            <th class="table__cell">Full Name</th>
                                            <th class="table__cell">Enrollment Date</th>
                                            <th class="table__cell">Upgrade Date</th>
                                            <th class="table__cell">Order Total</th>
                                            <th class="table__cell">PRS</th>
                                            <th class="table__cell">Order Count</th>
                                            <th class="table__cell">Sponsor ID</th>
                                            <th class="table__cell">Sponsor Name</th>

                                        </tr>
                                    </thead>
                                    <tbody class="table__body">
                                        <tr>
                                            <td>1</td>
                                            <td>21123</td>
                                            <td>Ann Renk</td>
                                            <td>09-11-21 </td>
                                            <td>09-12-21 </td>
                                            <td>3212</td>
                                            <td>212</td>
                                            <td>32</td>
                                            <td>12122</td>
                                            <td>West</td>
                                        </tr>
                                        <tr>
                                            <td>1</td>
                                            <td>21123</td>
                                            <td>Ann Renk</td>
                                            <td>09-11-21 </td>
                                            <td>09-12-21 </td>
                                            <td>3212</td>
                                            <td>212</td>
                                            <td>32</td>
                                            <td>12122</td>
                                            <td>West</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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


EOS
1;