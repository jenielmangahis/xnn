print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_pay_commission.css?v=1" />

<div class="pay-commission tool-container tool-container--default" v-cloak>

    <div class="row">
        <div class="col-md-12">
            <h4 class="admin-money-title">Pay Commission</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#tab-pay" aria-controls="tab-pay" role="tab" data-toggle="tab">Pay</a></li>
                <li role="presentation"><a href="#tab-history" aria-controls="tab-history" role="tab" data-toggle="tab">History</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="tab-pay">

                    <div class="row margin-top-bottom">
                        
                        <form class="form-horizontal">
                            <div class="form-group">
                                <div class="form-sub-group col-sm-4 col-md-4 col-lg-4">
                                    <div class="col-md-12">
                                        <label for="commission-type">Choose Commission Type</label>
                                        <select class="form-control">
                                            <option>Select Commission Type</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-sub-group col-sm-4 col-md-4 col-lg-4">
                                    <div class="col-md-12">
                                        <label for="commission-period">Choose Commission Period</label>
                                        <select class="form-control">
                                            <option>Select Commission Period</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-sub-group col-sm-4 col-md-4 col-lg-4">
                                    <div class="col-md-12">
                                        <label>&nbsp;</label><br>
                                        <div class="col-md-8 no-padding-left">
                                            <button type="button" class="btn btn-primary btn-block" id="btn-view" v-on:click="viewPayouts">View Report</button>
                                        </div>
                                        <div class="col-md-4">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                     
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="table-main" style="width: 100%">
                                    <thead class="table__header table__header--bg-primary">
                                        <tr class="table__row">
                                            <td class="table__cell">Name</td>
                                            <td class="table__cell">Username</td>
                                            <td class="table__cell">Commission Type</td>
                                            <td class="table__cell">Amount</td>
                                            <td class="table__cell">Actions</td>
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
                    <div class="row margin-top">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="table-history" style="width: 100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <td class="table__cell">#</td>
                                        <td class="table__cell">Prepared By</td>
                                        <td class="table__cell">Date</td>
                                        <td class="table__cell">Status</td>
                                        <td class="table__cell">Action</td>
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
                <div class="modal-footer" v-if="!is_processing && history.status != 'RUNNING' && history.status != 'PENDING'">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="$commission_engine_api_url/js/admin_pay_commission.js?v=1.0.5"></script>

EOS
1;