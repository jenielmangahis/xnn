print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_personal_retail_sales.css?v=1" />

<div id="personal-retail-sale" class="personal-retail-sales tool-container tool-container--default">
    <div class="row">
        <div class="col-md-10">
            <h4>Personal Retail Sales</h4>
        </div>

        <div class="col-md-2">
            <div class="pull-right">
                <button
                        type="button"
                        v-on:click.prevent="getDownloadPersonalRetail"
                        class="btn btn-excel"
                        v-bind:disabled="csvPersonalRetail.downloadLinkState === 'fetching'"
                >
                    <span v-if="csvPersonalRetail.downloadLinkState !== 'fetching'"><i class="bi bi-file-earmark-ruled-fill"></i> Export to Excel</span>
                    <span v-else>
                    <i class="bi bi-file-earmark-ruled-fill"></i> Generating <i class="fa fa-spinner fa-spin"></i>
                </span>
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
                        <input id="transaction-start-date" type="text" class="form-control flat" placeholder="Start Date" v-model="enrollment.filters.transaction_start_date">
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="transaction-end-date" type="text" class="form-control flat" placeholder="End Date" v-model="enrollment.filters.transaction_end_date">                        
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <select2-autocomplete-member id="member-id" :url="autocompleteUrl" v-model="enrollment.filters.memberId"></select2-autocomplete-member>
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <button type="button" class="btn btn-primary btn-block" v-on:click.prevent="viewPersonalRetail">Search</button>
                    </div>
                    <div class="form-group col-lg-4 col-md-3 col-6">
                        <div class="checkbox mt-2">
                            <input type="checkbox" name="prs-500-above" v-model="enrollment.filters.prs_500_above" />
                            <label>PRS of \$500 or above</label>
                        </div>
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="enrollment-start-date" type="text" class="form-control flat" placeholder="Start Enrollment Date" v-model="enrollment.filters.start_date">
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="enrollment-end-date" type="text" class="form-control flat" placeholder="End Enrollment Date" v-model="enrollment.filters.end_date">
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
                <table id="table-personal-retail-sales" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Top</th>
                            <th class="table__cell">ID</th>
                            <th class="table__cell">Full Name</th>
                            <th class="table__cell">Enrollment Date</th>
                            <th class="table__cell">Upgrade Date</th>
                            <th class="table__cell">Email</th>
                            <th class="table__cell">Country</th>
                            <th class="table__cell">Sponsor ID</th>
                            <th class="table__cell">Sponsor Name</th>
                            <th class="table__cell">PRS</th>
                        </tr>
                    </thead>
                    <tbody class="table__body"></tbody>
                </table>
            </div>
        </div>

    </div>



</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
<script src="$commission_engine_api_url/js/admin_personal_retail_sale.js?v=1.1"></script>

EOS
1;