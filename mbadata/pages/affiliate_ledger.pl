print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_ledger.css?v=1" />

<div class="ledger tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">Ledger</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
             <nav>
                <div class="nav nav-tabs font-weight-bold" id="nav-tab" role="tablist">
                    <a class="nav-item nav-link active" id="nav-ledger-tab" data-toggle="tab" href="#nav-ledger" role="tab" aria-controls="nav-ledger" aria-selected="true">Ledger</a>
                    <a class="nav-item nav-link" id="nav-withdrawals-tab" data-toggle="tab" href="#nav-withdrawals" role="tab" aria-controls="nav-withdrawals" aria-selected="false">Withdrawals</a>
                </div>
            </nav>

            <!-- Tab panes -->
            <div class="tab-content" id="nav-tabContent">
                <div  class="tab-pane fade show active" id="nav-ledger" role="tabpanel" aria-labelledby="nav-ledger-tab">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="ledger-actions ">
                                <div class=" pull-left">
                                <button class="new-btn-mba btn btn-primary mt-1" v-on:click.stop="refresh">REFRESH</button>
                                </div>
                                <div class=" pull-right">
                                    <button class=" new-btn-mba btn btn-success mt-1" v-on:click.stop="showTransfer">TRANSFER</button>
                                    <button class=" new-btn-mba btn btn-danger mt-1" v-on:click.stop="showWithdraw">WITHDRAW</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row dash-lines mt-5 mb-5">
                        <div class="col-md-12">
                            <div class="ledger-balance pull-right">
                                <h4><strong>Total Balance: {{ total_balance | money }}</strong></h4>
                            </div>
                        </div>
                    </div>

                    <!--<div class="row">
                        <div class="col-md-12">
                            <div class="ledger-balance pull-right">
                                <h5><strong>Total Balance: {{ total_balance | money }}</strong></h5>
                            </div>
                        </div>
                    </div> -->

                    <div class="row mt-3">
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

                <div class="tab-pane fade" id="nav-withdrawals" role="tabpanel" aria-labelledby="nav-withdrawals-rank">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="ledger-actions ">
                                <button class="new-btn-mba btn btn-primary pull-left" v-on:click.stop="refresh">REFRESH</button>
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
                            <div class="table-responsive">
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
                                <tr class="table__row">
                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                    </tr>
                                </tbody>
                                <!--Table head-->
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- MODALS -->
    <div class="modal fade" id="modal-transfer" role="dialog" aria-labelledby="modal-transfer-label">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="form-transfer" v-on:submit.prevent="transferFund">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-transfer-label">TRANSFER FUND</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                    <button type="submit" class="btn btn-primary" id="btn-save-transfer">Transfer</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="modal-withdraw" role="dialog" aria-labelledby="modal-withdraw-label">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="form-withdraw" v-on:submit.prevent="withdrawFund">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-withdraw-label">WITHDRAW FUND</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
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
                    <button type="submit" class="btn btn-primary" id="btn-save-withdraw">Withdraw</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_ledger.js?v=1.2"></script>

EOS
1;
