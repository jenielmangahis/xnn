print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_withdrawal_request.css?v=1" />

<div id="withdrawal-request" class="withdrawal-request tool-container tool-container--default" >
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header" >Withdrawal Request</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <nav>
                <div class="nav nav-tabs font-weight-bold" id="nav-tab" role="tablist">
                    <a class="nav-item nav-link active" id="nav-request-tab" data-toggle="tab" href="#nav-request" role="tab" aria-controls="nav-request-tab" aria-selected="true">Request</a>
                    <a class="nav-item nav-link" id="nav-approval-history-tab" data-toggle="tab" href="#nav-approval-history" role="tab" aria-controls="nav-approval-history-tab" aria-selected="true">Approval History</a>
                </div>

            </nav>
            <!-- Tab panes -->
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active"  role="tabpanel"  id="nav-request" aria-labelledby="nav-request-tab">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-horizontal">
                                <div class="form-row font-weight-bold">
                                    <div class="form-group col-md-4 mt-1">
                                        <label for="start-date">From</label>
                                        <input type="text" class="form-control" id="start-date"/>
                                    </div>
                                    <div class="form-group col-md-4 mt-1">
                                        <label for="end-date">To</label>
                                        <input type="text" class="form-control" id="end-date"/>
                                    </div>
                                    <div class="form-group col-md-4 mt-1">
                                        <label>&nbsp;</label>
                                        <br>
                                        <button type="button" class="new-btn-mba generate-width btn btn-primary" id="btn-view"  v-on:click.prevent="viewRequest">View</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-12 mba-mobile-view-center ">
                            <div class="tool-container__actions pull-right">
                                <button
                                        v-on:click.stop="approveRequest"
                                        v-bind:disabled="selected_ids.length === 0"
                                        class="mba-pay-comm-btn btn btn-success ">
                                    Approved 
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table--align-middle" id="table-main" style="width: 100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell">
                                            <div class="checkbox">
                                                <input title="Check All" type="checkbox" id="checkbox-check-all" v-model="is_check_all" v-on:change="toggleCheckAll">
                                                <label for="checkbox-check-all" >
                                                    &nbsp;
                                                </label>
                                            </div>
                                        </th>
                                        <th class="table__cell">Date </th>
                                        <th class="table__cell">Member ID </th>
                                        <th class="table__cell">Name </th>
                                        <th class="table__cell">Amount </th>
                                        <th class="table__cell">Actions </th>
                                    </tr>
                                    </thead>
                                    <tbody class="table__body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="tab-pane fade" role="tabpanel"  id="nav-approval-history" aria-labelledby="nav-approval-history-tab">
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table--align-middle" id="table-history" style="width: 100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell"># </th>
                                        <th class="table__cell">Prepared By </th>
                                        <th class="table__cell">Date </th>
                                        <th class="table__cell">Status </th>
                                        <th class="table__cell">Action </th>
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
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="modal-view-details" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">
                        # {{ history.id }}<br>
                        Prepared By: {{ history.prepared_by }}<br>
                        Date: {{ history.created_at }}<br>
                        Status: {{ history.status }}<br>
                    </h4>
                    <button v-if="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="progress">
                        <div class="progress-bar " :class="{'active progress-bar-striped' : history.status === 'RUNNING', 'progress-bar-success' : history.status !== 'FAILED', 'progress-bar-danger' : history.status === 'FAILED'}" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" :style="{width: progressPercentage}">
                            {{ progressPercentage }}
                        </div>
                    </div>
                    <div class="withdrawal-request__log"
                         v-show="!!lines.length"
                         :class="[history.status == 'FAILED' ? 'withdrawal-request__log--error' : 'withdrawal-request__log--success']"
                    >
                        <p class="withdrawal-request__log-message" v-for="line in lines">{{ line }}</p>
                    </div>
                    <div v-show="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'">
                        <div class="table-responsive">
                            <table class="table table-bordered table--align-middle" id="table-view-details" style="width: 100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell">ID</th>
                                    <th class="table__cell">Reference No</th>
                                    <th class="table__cell">Member</th>
                                    <th class="table__cell">Account Number</th>
                                    <th class="table__cell">Amount</th>
                                    <th class="table__cell">Status</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" v-if="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

</div>


<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_withdrawal_request.js?v=1.3"></script>

EOS
1;