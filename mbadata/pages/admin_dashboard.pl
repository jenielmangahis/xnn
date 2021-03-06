print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_dashboard.css?v=1" />


<div id="dashboard" class="dashboard tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5"> Dashboard</h4>
        </div>
    </div>
<div class="mba-money-border">
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal">
               <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="start-date">From</label>
                        <input type="text" id="start-date" class="form-control" />
                    </div>
                     <div class="form-group col-md-4">
                        <label for="end-date">To</label>
                        <input type="text" id="end-date" class="form-control" />
                    </div>

                    <div class="form-group col-md-4">
                         <label>&nbsp;</label><br>
                         <button type="button" class="new-btn-mba generate-width btn btn-primary" id="btn-view"  v-on:click="view">View</button>
                    </div>

                </div>

            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th>Description </i></th>
                        <th>Amount </i></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="table__row">
                        <td>New Customers</td>
                        <td class="text-number">
                            <i v-if="new_customer_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewCustomers">{{new_customer_count}}</a>
                        </td>
                    </tr>
                    <!-- <tr class="table__row">
                        <td>New Customers with $autoship</td>
                        <td class="text-number">
                            <i v-if="new_customer_with_product_subscription_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewCustomersWithProductSubscription">{{new_customer_with_product_subscription_count}}</a>
                        </td>
                    </tr> -->
                    <tr class="table__row">
                        <td>New IBO</td>
                        <td class="text-number">
                            <i v-if="new_ibo_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewIBO">{{new_ibo_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>New IBO with $autoship</td>
                        <td class="text-number">
                            <i v-if="new_ibo_with_product_subscription_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewIBOWithProductSubscription">{{new_ibo_with_product_subscription_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>Platinum Package</td>
                        <td class="text-number">
                            <i v-if="platinum_package_sales === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewPlatinumPackage">{{platinum_package_sales | money}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>Gold Package</td>
                        <td class="text-number">
                            <i v-if="gold_package_sales === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewGoldPackageSales">{{gold_package_sales | money}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>IBO Only (No Product)</td>
                        <td class="text-number">
                            <i v-if="ibo_sales_only === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewIBOSalesOnly">{{ibo_sales_only | money}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>Average Reorder</td>
                        <td class="text-number">
                            <i v-if="average_reorder === null" class="fa fa-spinner fa-spin"></i>
                            <span v-else>{{average_reorder}}%</span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-8">
            <table id="table-top-endorsers" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th>ID </i></th>
                        <th>Top </i></th>
                        <th>IBO</i></th>
                        <th>Volume </i></th>
                    </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
            <button type="button" v-if="top_endorser_count > 0" v-on:click="downloadTopEndorser" :disabled="is_downloading_top_endorser" class="btn btn-primary" style="margin-bottom: 10px;">
                <span v-if="is_downloading_top_endorser">Generating&hellip;</span>
                <span v-else>Download Top IBO</span>
            </button>
        </div>
    </div>

    <div class="modal fade" id="modal-view-new-customers" role="dialog" aria-labelledby="modal-view-new-customers-title">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="modal-view-new-customers-title">
                            New Customers
                        </h4>
                    </div>
                    <div class="modal-body">

                        <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                            <span v-if="is_downloading">Generating&hellip;</span>
                            <span v-else>Download</span>
                        </button>

                        <table id="table-new-customers" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th>ID </i></th>
                                <th class="table-new-member-type">Member </i></th>
                                <th>Sponsor ID </i></th>
                                <th>Sponsor </i></th>
                                <th>Sponsor Type </i></th>
                                <th>Order # </i></th>
                                <th>CV </i></th>
                                <th>Total \$ paid </i></th>
                                <th>Product Subscription </i></th>
                                <th>Phone Number </i></th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                    </div>
                </div>
            </div>
        </div>

    <div class="modal fade" id="modal-view-new-customers-with-subscription" role="dialog" aria-labelledby="modal-view-new-customers-with-subscription-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-view-new-customers-with-subscription-title">
                        New Customers
                    </h4>
                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-new-customers-with-subscription" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>ID </i></th>
                            <th class="table-new-member-type">Member </i></th>
                            <th>Sponsor ID </i></th>
                            <th>Sponsor </i></th>
                            <th>Sponsor Type </i></th>
                            <th>Order # </i></th>
                            <th>CV </i></th>
                            <th>Total \$ paid </i></th>
                            <th>Product Subscription </i></th>
                            <th>Phone Number </i></th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-view-new-ibo" role="dialog" aria-labelledby="modal-view-new-ibo-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-view-new-ibo-title">
                        New IBO
                    </h4>
                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-new-ibo" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>ID </i></th>
                            <th class="table-new-member-type">Member </i></th>
                            <th>Sponsor ID </i></th>
                            <th>Sponsor </i></th>
                            <th>Sponsor Type </i></th>
                            <th>Order # </i></th>
                            <th>CV </i></th>
                            <th>Total \$ paid </i></th>
                            <th>Product Subscription </i></th>
                            <th>Phone Number </i></th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-view-new-ibo-with-subscription" role="dialog" aria-labelledby="modal-view-new-ibo-with-subscription-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-view-new-ibo-with-subscription-title">
                        New IBO
                    </h4>
                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-new-ibo-with-subscription" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>ID </i></th>
                            <th class="table-new-member-type">Member </i></th>
                            <th>Sponsor ID </i></th>
                            <th>Sponsor </i></th>
                            <th>Sponsor Type </i></th>
                            <th>Order # </i></th>
                            <th>CV </i></th>
                            <th>Total \$ paid </i></th>
                            <th>Product Subscription </i></th>
                            <th>Phone Number </i></th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-view-platinum-sales" role="dialog" aria-labelledby="modal-view-platinum-sales-title">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                         <h4 class="modal-title" id="modal-view-platinum-sales-title">
                            Pack Sales
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

                    </div>
                    <div class="modal-body">

                        <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                            <span v-if="is_downloading">Generating&hellip;</span>
                            <span v-else>Download</span>
                        </button>

                        <table id="table-platinum-sales" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th>Purchaser ID</th>
                                <th>Purchaser</th>
                                <th>Invoice</th>
                                <th>Sponsor ID</th>
                                <th>Sponsor</th>
                                <th>Order #</th>
                                <th>CV</th>
                                <th>Total \$ paid</th>
                                <th>Sponsor Type</th>
                                <th>Clawback</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                    </div>
                </div>
            </div>
        </div>

    <div class="modal fade" id="modal-view-gold-sales" role="dialog" aria-labelledby="modal-view-gold-sales-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                     <h4 class="modal-title" id="modal-view-gold-sales-title">
                        Pack Sales
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-gold-sales" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>Purchaser ID</th>
                            <th>Purchaser</th>
                            <th>Invoice</th>
                            <th>Sponsor ID</th>
                            <th>Sponsor</th>
                            <th>Order #</th>
                            <th>CV</th>
                            <th>Total \$ paid</th>
                            <th>Sponsor Type</th>
                            <th>Clawback</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-view-ibo-sales" role="dialog" aria-labelledby="modal-view-ibo-sales-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                     <h4 class="modal-title" id="modal-view-ibo-sales-title">
                        Pack Sales
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>

                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-ibo-sales" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>Purchaser ID</th>
                            <th>Purchaser</th>
                            <th>Invoice</th>
                            <th>Sponsor ID</th>
                            <th>Sponsor</th>
                            <th>Order #</th>
                            <th>CV</th>
                            <th>Total \$ paid</th>
                            <th>Sponsor Type</th>
                            <th>Clawback</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-view-endorsers" role="dialog" aria-labelledby=modal-view-endorsers-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                     <h4 class="modal-title" id="modal-view-endorsers-title">
                        $affiliate_plural
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                   
                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-modal-view-endorsers" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>$affiliate ID</th>
                            <th>$affiliate</th>
                            <th>Invoice</th>
                            <th>Description</th>
                            <th>Transaction Date</th>
                            <th>Sponsor ID</th>
                            <th>Sponsor</th>
                            <th>Order #</th>
                            <th>CV</th>
                            <th>Total \$ paid</th>
                            <th>Shipping City</th>
                            <th>Shipping State</th>
                            <th>Sponsor Type</th>
                            <th>Phone Number</th>
                        </tr>
                        </thead>
                    </table>
                    <p class="endorser-purchase-note">The invoice shown per member is the first purchased of the member as $affiliate type</p>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_dashboard.js?v=1.5&app=$app_js_version"></script>

EOS
1;