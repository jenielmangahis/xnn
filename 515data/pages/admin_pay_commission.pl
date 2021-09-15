print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_pay_commission.css?v=1" />



<div class="pay-commission tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Pay Commission</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#tab-pay" aria-controls="tab-pay" role="tab" data-toggle="tab">Pay</a></li>
                <li role="presentation"><a href="#tab-pending" aria-controls="tab-pending" role="tab" data-toggle="tab">Pending Upload</a></li>
                <li role="presentation"><a href="#tab-history" aria-controls="tab-history" role="tab" data-toggle="tab">History</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;border-radius: 0;margin-top: 0;">
                <div role="tabpanel" class="tab-pane active" id="tab-pay">


                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-inline">
                                <div class="form-group">
                                    <button type="button" class="btn btn-primary" id="btn-view" v-on:click="viewPayouts">Refresh List</button>
                                </div>
                                <div class="form-group" backupClass=" tool-container__actions pull-right">
                                    <button
                                            v-on:click.prevent="pay"
                                            v-bind:disabled="selectedIds.length === 0 || is_processing === 1"
                                            id="btn-view-pay"
                                            class="btn btn-success">
                                        Generate CSV <span class="badge" v-show="selectedIds.length > 0">{{ selectedIds.length }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="table-main" style="width: 100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell">
                                            <div class="checkbox">
                                                <input title="Check All" type="checkbox" id="checkbox-check-all" v-model="is_check_all" v-on:change="toggleCheckAll" style="opacity: 1 !important;">
                                            </div>
                                        </th>
                                        <th class="table__cell">Name</th>
                                        <th class="table__cell">Username</th>
                                        <th class="table__cell">Commission Type</th>
                                        <th class="table__cell">Total Amount</th>
                                        <th class="table__cell">Currency</th>
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
                <div role="tabpanel" class="tab-pane" id="tab-pending">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="table-pending" style="width: 100%">
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
                <div role="tabpanel" class="tab-pane" id="tab-history">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="table-history" style="width: 100%">
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
                    <div class="pay-commission__log"
                         :class="[history.status == 'FAILED' ? 'pay-commission__log--error' : 'pay-commission__log--success']"
                    >
                        <p class="pay-commission__log-message" v-for="line in lines">{{ line }}</p>
                    </div>
                    <div class="table-responsive">
                        <table v-if="history.status != 'RUNNING' || history.status != 'PENDING'" class="table table-bordered" id="table-view-details" style="width: 100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">ID</th>
                                <th class="table__cell">Receipt No</th>
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
                    <p v-show="!!history.csv_file">
                        <a :href="download(history.id)" class="btn btn-success" target="_blank" download>Download CSV file</a>
                    </p>
                </div>
                <div class="modal-footer" v-if="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-csv-upload" role="dialog" aria-labelledby="label-csv-upload">
        <div class="modal-dialog modal-lg" role="document">
            <form class="modal-content">
                <div class="modal-header">
                    <button v-if="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="label-csv-upload">
                        # {{ history.id }}<br>
                        Prepared By: {{ history.prepared_by }}<br>
                        Date: {{ history.created_at }}<br>
                        Status: {{ history.status }}<br>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="csv-file">Upload Payout file</label>
                        <input type="file" id="csv-file" ref="csvFile">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" v-on:click.prevent="upload">Upload</button>
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/admin_pay_commission.js?v=2.0.0"></script>

EOS
1;