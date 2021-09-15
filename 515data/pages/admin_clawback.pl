print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_clawback.css?v=1" />

<div class="tool-container tool-container--default" id="app-clawback">

    <div class="row">
        <div class="col-md-12">
            <h4>CLAWBACK</h4>
            <hr />
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12" >
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#tab-orders" aria-controls="tab-orders" role="tab" data-toggle="tab">Orders</a></li>
                <!--<li role="presentation"><a href="#tab-logs" aria-controls="tab-logs" role="tab" data-toggle="tab">Move Logs</a></li>-->
            </ul>
            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="tab-orders">

                    <form class="form-horizontal">
                        <div class="form-group">
                            <div class="col-sm-3 col-md-3 col-lg-3">
                                <label for="start-date">From</label>
                                <input id="start-date" type="text" class="form-control flat">
                            </div>
                            <div class="col-sm-3 col-md-3 col-lg-3">
                                <label for="end-date">To</label>
                                <input id="end-date" type="text" class="form-control flat">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-3 col-md-3 col-lg-3">
                                <label for="purchaser-id">Associate (Optional)</label>
                                <select2-autocomplete-member id="purchaser-id" :url="autocompleteUrl" v-model="filters.memberId"></select2-autocomplete-member>
                            </div>
                            <div class="col-sm-3 col-md-3 col-lg-3">
                                <label for="purchaser-id">POD/PDR (Optional)</label>
                                <select2-autocomplete-pod id="pod-id" :url="autocompletePodUrl" v-model="filters.pod"></select2-autocomplete-pod>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-12">
                                <button id="button-view-orders" type="button" class="btn btn-primary" >View</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table id="table-orders" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%;">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">POD/PDR#</th>
                                <th class="table__cell">Associate ID</th>
                                <th class="table__cell">Associate Name</th>
                                <th class="table__cell">Customer</th>
                                <th class="table__cell">Account Type</th>
                                <th class="table__cell">Date Accepted</th>
                                <th class="table__cell">Date Started Flowing</th>
                                <th class="table__cell">Status</th>
                                <th class="table__cell">Action</th>
                            </tr>
                            </thead>
                            <tbody class="table__body">
                            </tbody>

                        </table>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab-logs">
                    <div class="table-responsive">
                        <table id="table-logs" class="table table-bordered" style="width: 100%;">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">Order ID</th>
                                <th class="table__cell">New Purchaser</th>
                                <th class="table__cell">Old Purchaser</th>
                                <th class="table__cell">New Transaction Date</th>
                                <th class="table__cell">Old Transaction Date</th>
                                <th class="table__cell">Date</th>
                                <th class="table__cell">Changed by</th>
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

    <div class="modal fade" id="modal-order-items" role="dialog" aria-labelledby="modal-order-items-label">
        <div class="modal-dialog modal-xl modal-lg" role="document">
            <form class="modal-content" v-on:submit.prevent>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-order-items-label">ORDER ID {{ transaction_id }} - ORDER ITEMS</h4>
                </div>
                <div class="modal-body">

                    <div class="table-responsive">
                        <table id="table-order-items" class="table table table-striped" >
                            <!--Table head-->
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell col-md-2">ID</th>
                                <th class="table__cell col-md-3 text-left">Name</th>
                                <th class="table__cell col-md-1">Qty</th>
                                <th class="table__cell col-md-1">CV</th>
                                <th class="table__cell col-md-1">Total CV</th>
                                <th class="table__cell col-md-1">Refunded Qty</th>
                                <th class="table__cell col-md-2">Total CV Refunded</th>
                                <th class="table__cell col-md-1">Refund Qty</th>
                            </tr>
                            </thead>
                            <!--Table head-->
                            <tbody class="table__body">
                            <tr v-if="products.length == 0" class="table__row text-center">
                                <td colspan="6">Fetching <i class="fa fa-spinner fa-pulse"></i></td>
                            </tr>
                            <tr v-for="(product, index) in products" class="table__row">
                                <td class="table__cell text-center">{{ product.transaction_product_id }}</td>
                                <td class="table__cell text-left">
                                    {{ product.name }}
                                    <ul  style="padding-left: 20px;font-size: 12px;">
                                        <li class="text-danger" v-for="(option, i) in product.options">{{ option.name }}: {{ option.value }}</li>
                                    </ul>
                                </td>
                                <td class="table__cell text-center">{{ product.quantity }}</td>
                                <td class="table__cell text-right">{{ product.price }}</td>
                                <td class="table__cell text-right">{{ product.total }}</td>
                                <td class="table__cell text-center">{{ product.refunded_quantity }}</td>
                                <td class="table__cell text-right">{{ product.refunded_amount }}</td>
                                <td class="table__cell text-center">
                                    <input :name="p(product.transaction_product_id, 'order_id')" v-model="product.order_id" type="hidden">
                                    <input :name="p(product.transaction_product_id, 'transaction_product_id')" v-model="product.transaction_product_id" type="hidden">
                                    <input :readonly="readonlyProduct(order) == 1 || product.quantity < 1" :name="p(product.transaction_product_id, 'refund_quantity')" v-model="product.refund_quantity" type="text" class="form-control input-sm">
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="form-refund-order-products-error" class="form-group" v-show="error.message != null">
                        <div class="col-sm-12 has-error text-center">
                            <span class="help-block">{{ error.message }}</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button v-on:click.prevent="onSubmit('merchant')" type="submit" class="btn btn-primary hidden" id="btn-save-refund-products">Refund</button>
                    <!--<button v-on:click.prevent="onSubmit('commission')" type="submit" class="btn btn-primary" id="btn-save-refund-products-commission">Refund (Commission Only)</button>-->
                    <button v-on:click.prevent="onSubmit('commission')" type="submit" class="btn btn-primary" id="btn-save-refund-products-commission">Refund</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modal-refund-order" role="dialog" aria-labelledby="modal-refund-order-label">
        <div class="modal-dialog modal-xl" role="document">
            <form class="modal-content form-horizontal" id="form-refund-order">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-refund-order-label">CLAWBACK</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group" v-show="error.message != null">
                        <div class="col-sm-12 has-error text-center">
                            <span class="help-block">{{ error.message }}</span>
                        </div>
                    </div>
                    <div class="table-responsive">
						<table id="user-payouts" class="table table table-striped">
							<thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
								<th class="table__cell">Associate ID</th>
								<th class="table__cell">Associate Name</th>
								<th class="table__cell">Commission Type</th>
								<th class="table__cell">Commission Period</th>
								<th class="table__cell">Commission Earned</th>
								<th class="table__cell">Commission Clawedback</th>
								<th class="table__cell">Date Clawedback</th>
								<th class="table__cell">Amount to Clawback</th>
								<th class="table__cell">Action</th>
                            </tr>
                            </thead>
                            <tbody class="table__body">
                            </tbody>
						</table>
					</div>
                </div>
                <div class="modal-footer">
                    <!--<button v-if="!order.is_clawback" v-on:click.prevent="onSubmitRefund('commission')" type="submit" class="btn btn-primary" id="btn-save-refund-commission">Refund (Commission Only)</button>-->
                    <!--<button v-if="!order.is_clawback" v-on:click.prevent="onSubmitRefund('commission')" type="submit" class="btn btn-primary disabled" id="btn-save-refund-commission">Clawback</button>-->
                    <button class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move Order Modal -->
    <div class="modal fade" id="modal-move" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <form class="modal-content form-horizontal" id="form-move" v-on:submit.prevent="save">
                <div class="modal-header">

                    <h4 class="modal-title" id="myModalLabel">ORDER ID {{ order.order_id }}</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label for="invoice">Invoice</label>
                            <input class="form-control" id="invoice" v-model="order.invoice" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label for="transaction-date">Transaction Date</label>
                            <input class="form-control" id="transaction-date" v-model="order.transaction_date" onkeydown="return false"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label for="purchaser">Purchaser</label>
                            <input class="form-control" id="purchaser" v-model="displayPurchaser" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label for="sponsor">Sponsor</label>
                            <input class="form-control" id="sponsor" v-model="displaySponsor" readonly />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <label for="new-purchaser-id">New Purchaser</label><br/>
                            <select2-autocomplete-member id="new-purchaser-id" :url="autocompleteUrl" v-model="order.new_purchaser_id"></select2-autocomplete-member>
                        </div>
                    </div>
                    <div class="form-group" v-show="error.message != null">
                        <div class="col-sm-12 has-error text-center">
                            <span class="help-block">{{ error.message }}</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="btn-save" >Update</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
<script src="$commission_engine_api_url/js/admin_clawback.js?v=1"></script>

EOS
1;