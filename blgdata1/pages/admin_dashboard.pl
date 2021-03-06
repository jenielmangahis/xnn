print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<style>

    .text-number {
        text-align: right;
        padding-right: 5px;
    }

    .tool-container .col-md-12 {
        padding-right: 15px;
        padding-left: 15px;
    }

    .tool-container .modal-body {
        overflow-x: hidden;
    }

    #table-top-endorsers {
        margin-top: 0px !important;
    }

    .endorser-purchase-note {
        font-style: italic;
        font-size: small;
        color: #8a6d3b;
        text-align: right;
    }

    .modal th {
        text-align: center !important;
    }

    #table-new-members > thead > tr > th {
        font-size: 12px !important;
    }

</style>


<div id="dashboard" class="dashboard tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4> Dashboard</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal">
                <div class="form-group">
                    <div class="form-sub-group col-sm-3 col-md-3 col-lg-3">
                        <label for="start-date">From</label>
                        <input type="text" id="start-date" class="form-control" />
                    </div>
                    <div class="form-sub-group col-sm-3 col-md-3 col-lg-3">
                        <label for="end-date">To</label>
                        <input type="text" id="end-date" class="form-control" />
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-sub-group col-sm-12">
                        <button id="btn-view" type="button" class="btn btn-primary flat" v-on:click="view">View</button>
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
                        <th>Description</th>
                        <th>Amount</th>
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
                    <tr class="table__row">
                        <td>New Customers with $autoship</td>
                        <td class="text-number">
                            <i v-if="new_customer_with_product_subscription_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewCustomersWithProductSubscription">{{new_customer_with_product_subscription_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>New Influencer</td>
                        <td class="text-number">
                            <i v-if="new_endorser_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewEndorsers">{{new_endorser_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>New Influencers with $autoship</td>
                        <td class="text-number">
                            <i v-if="new_endorser_with_product_subscription_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewEndorsersWithProductSubscription">{{new_endorser_with_product_subscription_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>New Distributors</td>
                        <td class="text-number">
                            <i v-if="new_ambassador_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewAmbassador">{{new_ambassador_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>New Distributors with $autoship</td>
                        <td class="text-number">
                            <i v-if="new_ambassador_with_product_subscription_count === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewNewAmbassadorWithProductSubscription">{{new_ambassador_with_product_subscription_count}}</a>
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>Enrollment Pack 1</td>
                        <td class="text-number">
                        <!--    <i v-if="customer_transformation_pack_total_sales === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewCustomerTransformationPackSales">{{customer_transformation_pack_total_sales | money}}</a>
                            -->
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>Enrollment Pack 2</td>
                        <td class="text-number">
                        <!--
                            <i v-if="transformation_pack_total_sales === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewTransformationPackSales">{{transformation_pack_total_sales | money}}</a>
                            -->
                        </td>
                    </tr>
                    <tr class="table__row">
                        <td>Enrollment Pack 3</td>
                        <td class="text-number">
                            <!--<i v-if="elite_pack_total_sales === null" class="fa fa-spinner fa-spin"></i>
                            <a class="btn-link" v-else v-on:click="viewElitePackSales">{{elite_pack_total_sales | money}}</a>
                            -->
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
                    <th>Top</th>
                    <th>ID</th>
                    <th>Distributor/Influencer</th>
                    <th>Volume</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-view-new-members" role="dialog" aria-labelledby="modal-view-new-members-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-view-new-members-title">
                        New Members
                    </h4>
                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-new-members" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th>ID</th>
                            <th class="table-new-member-type">Member</th>
                            <th>Sponsor ID</th>
                            <th>Sponsor</th>
                            <th>Sponsor Type</th>
                            <th>Order #</th>
                            <th>CV</th>
                            <th>Total \$ paid</th>
                            <th>Coupon used</th>
                            <th>Shipping City</th>
                            <th>Shipping State</th>
                            <th>Product Subscription</th>
                            <th>Phone Number</th>
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

    <div class="modal fade" id="modal-view-pack-sales" role="dialog" aria-labelledby="modal-view-pack-sales-title">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-view-pack-sales-title">
                        Pack Sales
                    </h4>
                </div>
                <div class="modal-body">

                    <button type="button" v-on:click="download" :disabled="is_downloading" class="btn btn-primary" style="margin-bottom: 10px;">
                        <span v-if="is_downloading">Generating&hellip;</span>
                        <span v-else>Download</span>
                    </button>

                    <table id="table-pack-sales" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
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
                            <th>Gift Card used</th>
                            <th>Coupon used</th>
                            <th>Shipping City</th>
                            <th>Shipping State</th>
                            <th>Sponsor Type</th>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-view-endorsers-title">
                        $affiliate_plural
                    </h4>
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
                            <th>Gift Card used</th>
                            <th>Coupon used</th>
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

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_dashboard.js?v=1.0&app=$app_js_version"></script>

EOS
1;