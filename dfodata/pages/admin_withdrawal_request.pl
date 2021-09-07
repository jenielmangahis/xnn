print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_withdrawal_request.css?v=1" />

<div id="withdrawal-request" class="withdrawal-request tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="admin-money-title">Withdrawal Request</h4>
        </div>
    </div>

    <div class="row margin-top-bottom">
        <div class="col-md-12">

            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#tab-pay" aria-controls="tab-pay" role="tab" data-toggle="tab" style="color: black !important;">Request</a></li>
                <li role="presentation"><a href="#tab-history" aria-controls="tab-history" role="tab" data-toggle="tab" style="color: black !important;">Approval History</a></li>
            </ul>


            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="tab-pay">

                    <div class="row margin-top-bottom">
                        <div class="col-md-4">
                            <div class="col-md-12">
                                <label for="start-date">From</label>
                                <div class="input-group start-date">
                                    <input type="text" id="start_date" class="form-control"> 
                                    <span class="input-group-addon date-from-icon">
                                        <i class="glyphicon glyphicon-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="col-md-12">
                                <label for="end-date">To</label>
                                <div class="input-group to-date">
                                    <input type="text" id="end_date" class="form-control"> 
                                    <span class="input-group-addon date-from-icon">
                                        <i class="glyphicon glyphicon-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="col-md-8">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-block btn-primary" id="btn-view" v-on:click.prevent="viewRequest">View</button>
                            </div>
                            <div class="col-md-4"></div>
                        </div>
                    </div>

                    <div class="row margin-bottom">
                        <div class="col-md-3 padding-left-only">
                            <button
                                v-on:click.stop="approveRequest"
                                v-bind:disabled="selected_ids.length === 0"
                                class="btn btn-success btn-block">
                                Approve <span class="badge" v-show="selected_ids.length > 0">{{ selected_ids.length }}</span>
                            </button>
                        </div>
                        <div class="col-md-3"></div>
                        <div class="col-md-3"></div>
                        <div class="col-md-3"></div>
                    </div>

                    <div class="row">
                        <div class="table-responsive">
                            <div class="col-md-12 padding-left-right">
                            
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
                                        <th class="table__cell">Date</th>
                                        <th class="table__cell">Member ID</th>
                                        <th class="table__cell">Name</th>
                                        <th class="table__cell">Amount</th>
                                        <th class="table__cell">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table__body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div role="tabpanel" class="tab-pane" id="tab-history">
                    <div class="row margin-top-bottom">
                        <div class="table-responsive">
                            <div class="col-md-12">
                            
                                <table class="table table-bordered table--align-middle" id="table-history" style="width: 100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell">#</th>
                                        <th class="table__cell">Prepared By</th>
                                        <th class="table__cell">Date</th>
                                        <th class="table__cell">Status</th>
                                        <th class="table__cell">Action</th>
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
                    <button v-if="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">
                        # {{ history.id }}<br>
                        Prepared By: {{ history.prepared_by }}<br>
                        Date: {{ history.created_at }}<br>
                        Status: {{ history.status }}<br>
                    </h4>
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
                                    <th class="table__cell">Username</th>
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
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
<script src="$commission_engine_api_url/js/admin_withdrawal_request.js?v=1.1"></script>

EOS
1;