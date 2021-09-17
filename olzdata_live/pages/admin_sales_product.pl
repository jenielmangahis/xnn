print <<EOS; 
<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_sales_product.css?v=1" />



<div class="sales-product tool-container tool-container--default">
    <div class="row">
        <div class="col-md-10">
            <h4>Sales By Product</h4>
        </div>

        <div class="col-md-2">
            <div class="pull-right">
                <button type="button" class="btn btn-excel ">
                    <i class="bi bi-file-earmark-ruled-fill"></i> Export to Excel
                </button>
            </div>    
        </div>
    </div>
    <hr />
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
                        <input type="text" class="form-control" placeholder="SKU" />
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <select class="form-control form-control-sm">
                            <option selected>Country</option>
                            <option value="US">United States</option>
                        </select>
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
     <hr />
    <div class="row">
        <div class="col-lg-2">
            <button type="button" class="btn btn-primary btn-block">Hide Summary</button>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-6">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Total Quantity</th>
                            <th class="table__cell">Total Price</th>
                            <th class="table__cell">Total Cost Price</th>

                        </tr>
                    </thead>
                    <tbody class="table__body">
                        <tr>
                            <td>2</td>
                            <td>\$20.00</td>
                            <td>\$220.00</td>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Order Number</th>
                            <th class="table__cell">SKU</th>
                            <th class="table__cell">Rep ID</th>
                            <th class="table__cell">Name</th>
                            <th class="table__cell">Ship To Name</th>
                            <th class="table__cell">Name</th>
                            <th class="table__cell">Order Date</th>
                            <th class="table__cell">Order Status</th>
                            <th class="table__cell">Currency</th>
                            <th class="table__cell">Quantity</th>
                            <th class="table__cell">Total Price</th>
                            <th class="table__cell">Cost Price Total</th>
                        </tr>
                    </thead>
                    <tbody class="table__body">
                         <tr>
                            <td>1001</td>
                            <td>OLZ123</td>
                            <td>E1143</td>
                            <td>123</td>
                            <td>Jo Carison</td>
                            <td>Jo Carison</td>
                            <td>Jay Ho</td>
                            <td>09-11-21</td>
                            <td>Completed</td>
                            <td>USD</td>
                            <td>\$20.00</td>
                            <td>\$20.001</td>
                        </tr>
                        <tr>
                            <td>1001</td>
                            <td>OLZ123</td>
                            <td>E1143</td>
                            <td>123</td>
                            <td>Jo Carison</td>
                            <td>Jo Carison</td>
                            <td>Jay Ho</td>
                            <td>09-11-21</td>
                            <td>Completed</td>
                            <td>USD</td>
                            <td>\$20.00</td>
                            <td>\$20.001</td>
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


EOS
1;