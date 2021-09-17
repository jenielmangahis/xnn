print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_ledger_adjustment.css?v=1" />

<div id="ledger-adjustment" class="ledger-adjustment tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Ledger Adjustment</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 ">
            <div class="tool-container__actions pull-right">
                <button type="button" class="btn btn-secondary btn-sm" v-on:click.prevent="showAddModal">
                    Add Adjustment
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <table id="table-ledger-adjustment" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Name</th>
                    <th class="table__cell">Notes</th>
                    <th class="table__cell">Amount</th>
                    <th class="table__cell">Set By</th>
                    <th class="table__cell">Set Date</th>
                    <th class="table__cell">Action</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="modal-ledger-adjustment" role="dialog" aria-labelledby="modal-ledger-adjustment-label">
        <div class="modal-dialog" role="document">
            <form class="modal-content" id="form-ledger-adjustment" >
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-ledger-adjustment-label">Ledger Adjustment</h4>
                    <button v-bind:disabled="isProcessing === 1" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="user_id">Member</label>
                        <select2-autocomplete-member ref="autocompleteMember" id="user_id"  v-bind:url="autocompleteUrl" v-model="adjustment.user_id"></select2-autocomplete-member>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input id="amount" type="number" class="form-control" v-model="adjustment.amount">
                    </div>

                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea v-model="adjustment.notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary" v-bind:disabled="isProcessing === 1" v-on:click.prevent="saveAdjustment('remove')">Remove</button>
                    <button type="submit" class="btn btn-primary" v-bind:disabled="isProcessing === 1" v-on:click.prevent="saveAdjustment('add')">Add</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/admin_ledger_adjustment.js?v=1.0&app=$app_js_version"></script>

EOS
1;