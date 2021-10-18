print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/datatables.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/member-menu.css" />

<style>
.ledger {
    margin-bottom: 20px;
    background-color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
    padding: 15px;}

.table__header { background-color:#9aa1a5; color:#fff; }

</style>


<div class="ledger tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#ledger" class="tab-header" aria-controls="ledger" role="tab" data-toggle="tab" style="color: black !important;">Ledger</a></li>
                <li role="presentation"><a href="#withdrawals" class="tab-header" aria-controls="withdrawals" role="tab" data-toggle="tab" style="color: black !important;">Withdrawals</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">

                <div role="tabpanel" class="tab-pane active" id="ledger">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="ledger-actions ">
                                <button class="btn btn-primary pull-left" v-on:click.stop="refresh">REFRESH</button>
                                <div class=" pull-right">
                                    <button class="btn btn-success" v-on:click.stop="showTransfer">TRANSFER</button>
                                    <button class="btn btn-danger" v-on:click.stop="showWithdraw">WITHDRAW</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="ledger-balance pull-right">
                                <h5><strong>Total Balance: {{ total_balance | money }}</strong></h5>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table id="table-ledger" class="table table-striped table-bordered" style="width:100%">
                                    <!--Table head-->
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell all">Date</th>
                                        <th class="table__cell desktop">Notes</th>
                                        <th class="table__cell desktop tablet">Amount</th>
                                    </tr>
                                    </thead>
                                    <!--Table head-->
                                    <tbody class="table__body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="withdrawals">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="ledger-actions ">
                                <button class="btn btn-primary pull-left" v-on:click.stop="refresh">REFRESH</button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="ledger-balance pull-right">
                                <h5>&nbsp;</h5>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <table id="table-withdraw" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <!--Table head-->
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell">Date</th>
                                    <th class="table__cell">Amount</th>
                                    <th class="table__cell">Status</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                </tbody>
                                <!--Table head-->
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- MODALS -->
    <div class="modal fade" id="modal-transfer" role="dialog" aria-labelledby="modal-transfer-label">
        <div class="modal-dialog" role="document">
            <form class="modal-content" id="form-transfer" v-on:submit.prevent="transferFund">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-transfer-label">TRANSFER FUND</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="transfer-balance">Total Balance</label>
                        <input type="text" id="transfer-balance" class="form-control" v-bind:value="total_balance | money" readonly>
                    </div>
                    <div class="form-group">
                        <label for="transfer-member">Member</label>
                        <select2-autocomplete-member id="transfer-member" :url="autocomplete_url" v-model="transfer.member_id"></select2-autocomplete-member>
                    </div>
                    <div class="form-group">
                        <label for="transfer-amount">Amount</label>
                        <input type="text" id="transfer-amount" class="form-control" v-model="transfer.amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                    <button type="submit" class="btn btn-primary" id="btn-save-transfer">Transfer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="modal-withdraw" role="dialog" aria-labelledby="modal-withdraw-label">
        <div class="modal-dialog" role="document">
            <form class="modal-content" id="form-withdraw" v-on:submit.prevent="withdrawFund">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-withdraw-label">WITHDRAW FUND</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="transfer-balance">Total Balance</label>
                        <input type="text" id="withdraw-balance" class="form-control" v-bind:value="total_balance | money" readonly>
                    </div>
                    <div class="form-group">
                        <label for="transfer-amount">Amount</label>
                        <input type="text" id="withdraw-amount" class="form-control" v-model="withdraw.amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                    <button type="submit" class="btn btn-primary" id="btn-save-withdraw">Withdraw</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_ledger.js?v=1"></script>

EOS
1;
