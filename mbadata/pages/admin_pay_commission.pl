print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_pay_commission.css?v=1" />


<div class="pay-commission tool-container tool-container--default" >
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5" >Pay Commission</h4>

        </div>
    </div>

    <div class="row">
        <div class="col-md-12"> 
            <nav>
                    <div class="nav nav-tabs" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" id="nav-pay-tab" data-toggle="tab" href="#nav-pay" role="tab" aria-controls="nav-pay" aria-selected="true">Pay</a>
                        <a class="nav-item nav-link" id="nav-history-tab" data-toggle="tab" href="#nav-history" role="tab" aria-controls="nav-history" aria-selected="false">History</a>
                    
                    </div>
            </nav>
             <!-- Tab panes -->
            <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-pay" role="tabpanel" aria-labelledby="nav-pay-tab">
                                <div class="row">
                                    <div class="col-md-12">
                                        <form class="form-horizontal">
                                            <div class="form-row">
                                                <div class="form-group col-md-4 mba-responsive-laptop mt-1">
                                                <label class="font-weight-bold" for="commission-type">Step 1: Choose Commission Type</label>
                                                <select class="form-control" id="exampleFormControlSelect1">
                                                        <option>1</option>
                                                        <option>2</option>
                                                        <option>3</option>
                                                        <option>4</option>
                                                        <option>5</option>
                                                    </select>
                                                </div>

                                                <div class="form-group col-md-4 mba-responsive-laptop mt-1">
                                                <label class="font-weight-bold" for="commission-period">Step 2: Choose Commission Period</label>
                                                <select name="commission_period" id="commission-period" class="form-control">
                                                         <option>1</option>
                                                        <option>2</option>
                                                        <option>3</option>
                                                        <option>4</option>
                                                        <option>5</option>
                                                    </select>
                                                </div>
                                                <!--Button-->
                                                <div class="form-group col-md-4 ">
                                                <label>&nbsp;</label><br>
                                                <button type="button" class="new-btn-mba generate-width btn btn-primary" id="btn-view" v-on:click="viewPayouts">View</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="row mt-5">
                                    <div class="col-md-12 mba-mobile-view-center">
                                        <div class="pull-left">
                                            <button
                                                    v-on:click.prevent="pay"
                                                    v-bind:disabled="selectedIds.length === 0"
                                                    id="btn-view-pay"
                                                    class="mba-pay-comm-btn btn btn-success pull-left"
                                                    style="margin-bottom: 10px;">
                                                Pay Commission <span class="badge"></span>
                                            </button>
                                            <div id="new-generated-link" style="margin-right: 10px;"></div>
                                        </div>
                                        <div class="pull-right">
                                            <div class="input-group">
                                                <input class="form-control py-2 border-right-0 border" type="search" placeholder="Search" id="example-search-input">
                                                <span class="input-group-append mba-border">
                                                    <div class="input-group-text bg-transparent"><i class="fa fa-search"></i></div>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="table-main" style="width: 100%">
                                                <thead class="table__header table__header--bg-primary">
                                                <tr class="table__row">
                                                    <th class="table__cell">
                                                        <div class="text-center">
                                                            <input type="checkbox" aria-label="Checkbox for following text input">
                                                        </div>
                                                    </th>
                                                    <th class="table__cell">Name <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Username <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Commission Type <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Total Amount <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Actions <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                </tr>
                                                </thead>
                                                <tbody class="table__body">
                                                    <tr class="table__row">
                                                        <td class="table__cell table__cell--align-middle"> 
                                                            <div class="text-center">
                                                             <input type="checkbox" aria-label="Checkbox for following text input">
                                                            </div>
                                                        </td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                        
                        
                        </div>
                        <div class="tab-pane fade" id="nav-history" role="tabpanel" aria-labelledby="nav-history-tab">
                            <div class="row form-group">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="table-history" style="width: 100%">
                                                <thead class="table__header table__header--bg-primary">
                                                <tr class="table__row">
                                                    <th class="table__cell"># <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Prepared By <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Date <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Status <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                    <th class="table__cell">Action <i class="bi bi-arrow-down-up pull-right"></i></th>
                                                </tr>
                                                </thead>
                                                <tbody class="table__body">
                                                    <tr class="table__row">
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                        <td class="table__cell  table__cell--align-middle">Super James</td>
                                                    </tr>
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

<!-- <script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="$commission_engine_api_url/js/admin_pay_commission.js?v=1.0.5"></script> -->

EOS
1;