print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.theme.default.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_detailed_commission.css?v=1" />

<style>

.tt-hint {
    width: 100%;
    height: 30px;
    padding: 15px 12px;
    font-size: 14px;
    line-height: 30px;
    border: 2px solid #ccc;
    border-radius: 8px;
    outline: none;
}

.tt-query {
    /* UPDATE: newer versions use tt-input instead of tt-query */
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
}

.tt-hint {
    color: #999;
}

.tt-menu {
    /* UPDATE: newer versions use tt-menu instead of tt-dropdown-menu */
    width: 100%;
    margin-top: 12px;
    padding: 8px 0;
    background-color: #fff;
    border: 1px solid #ccc;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
    max-height: 500px;
    overflow-y: auto;
}

.tt-suggestion {
    padding: 8px 20px;
    font-size: 14px;
    line-height: 24px;
    cursor: pointer;
}

.tt-suggestion:hover {
    color: #f0f0f0;
    background-color: #818956;
}

.tt-suggestion p {
    margin: 0;
}

.twitter-typeahead {
    width: 100%;
}

.temp-parent-txt {
    width: 100%;
}

.empty-message {
    padding: 5px 10px;
}

.red {
    color: #A94442;
}

.cancel-btn {
    background-color: #EEEEEE;
    position: relative;
    display: inline-block;
    top: -29px;
    right: 6px;
    float: right;
}

.loader {
    position: relative;
    display: inline-block;
    top: -26px;
    right: 6px;
    float: right;
}

.typeahead-container {}

.height-52 {
    max-height: 30px;
}

.white {
    color: #fff;
}

.form-container {
    border: 1px solid #DDDDDD;
    padding: 20px;
    height: 160px;
    width: 50%;
    float: right;
}

.clear-typeahead {
    background-color: #fff;
    position: relative;
    display: inline-block;
    float: right;
    margin-top: -50px;
    margin-right: -17px;
    border-radius: 50%;
}

.box-border {
    width: 45%;
    padding: 15px;
    border-style: solid;
    border-width: 1px;
    border-color: #DDDDDD;
    margin: 0;
}
.btn-default {
z-index: 0 !important;
}
</style>

<div id="commission-report" class="enroller-tree tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="detailed-commission">Detailed Commission</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-6">
                        <div class="form-group row">
                            <label class="col-md-4 col-form-label col-form-label-sm commission-category">Commission Category</label>
                            <div class="col-md-8">
                               <select disabled
                               v-bind:disabled="frequencyState !== 'loaded'"
                               id="frequency"
                               class="form-control commission-dropdowns"
                               v-model="frequency"
                               v-on:change="onChange(this.frequency)">
                           <option v-if="frequencyState === 'fetching'" value="" selected disabled>
                               Fetching...
                           </option>
                           <option v-else-if="frequencyState === 'error'" value="" selected disabled>
                               Error
                           </option>
                           <option v-else-if="frequencyState === 'loaded'" value="" selected disabled>
                               Select a Commission Category
                           </option>
                           <option v-for="(frequency, index) in frequencies"
                                   v-bind:value="frequency.name"
                                   v-bind:key="frequency.name"
                                    v-bind:class="frequency.name"
                                   
                           >
                               {{ frequency.name | capitalize}}
                           </option>
                           <option value="all" class="all-label" v-show="frequencies.length > 0">All</option>
                       </select>
                       <a style="display: none;" v-show="frequencyState === 'error'" v-on:click.prevent="getFrequencies" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-4 col-form-label detailed-commission-type-label">Commission Type</label>
                            <div class="col-md-8">
                                <select
                                        disabled
                                        name="commission_period"
                                        id="commission_period"
                                        class="form-control"
                                        v-model="commissionPeriodIndex"
                                        v-bind:disabled="commissionPeriodState !== 'loaded'"
                                >
                                    <option v-if="commissionPeriodState === 'fetching'" value="" selected disabled>
                                        Select a commission type
                                    </option>
                                    <option v-else-if="commissionPeriodState === 'error'" value="" selected disabled>
                                        Error
                                    </option>
                                    <option v-else-if="commissionPeriodState === 'all'" value="all" selected>
                                        Select a commission type
                                    </option>
                                    <option v-else-if="commissionPeriodState === 'loaded'" value="" selected disabled>
                                        Select a commission type
                                    </option>
                                    <option v-for="(period, index) in commissionPeriods"
                                            v-bind:value="period.id"
                                            v-bind:key="index">
                                        {{ period.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-4 col-form-label commission-period-label">Commission Period</label>
                            <div class="col-sm-8">
                              
                                <div class="row">

                                    <div class="col-md-2">
                                        <label class="col-form-label from-label">From</label>
                                    </div>
                                    
                                    <div class="col-md-4 padding-lr-0">
                                        <datepicker v-model="filters.period.start_date" v-bind:end-date="today"></datepicker>
                                    </div>

                                    <div class="col-sm-1">
                                        <label class="col-form-label to-label">To</label>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <datepicker v-model="filters.period.end_date" v-bind:start-date="filters.period.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    
                                </div>

                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-4 col-form-label"></label>
                            <div class="col-md-8">
                                <button class="btn btn-default pull-right view-btn-label" v-on:click.prevent="view">&nbsp;&nbsp;&nbsp;View&nbsp;&nbsp;&nbsp;</button>
                            </div>
                        </div>
                </div>
                <div class="col-md-6"></div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <button type="button"
                    v-on:click.prevent="getDownloadLink"
                    class="btn btn-default"
                    v-bind:disabled="downloadLinkState === 'fetching'"
                    v-show="dtCount > 0"
                    >
                    &nbsp;&nbsp;&nbsp;<span class="download-label">Download</span>&nbsp;&nbsp;&nbsp;
                    <span v-if="downloadLinkState !== 'fetching'" class="glyphicon glyphicon-download-alt"></span>&nbsp;&nbsp;&nbsp;
                    <span v-else>
                        Generating <i class="fa fa-spinner fa-spin"></i>
                    </span>
                    </button>
                    <br>
                    <br>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <table id="detailed_commission" class="table table-striped table-bordered dt-responsive nowrap com-report-header" style="width:100%">
                        <thead class="com-report-header table__header table__header--bg-primary">
                            <tr class="com-report-header table__row">
                                <th class="com-report-header table__cell category-label">Category</th>
                                <th class="com-report-header table__cell th-type-label">Type</th>
                                <th class="com-report-header table__cell th-level-label">Level</th>
                                <th class="com-report-header table__cell th-assoc-name-label">Associate Enroller</th>
                                <th class="com-report-header table__cell th-assoc-id-label-detailed">Associate ID</th>
                                <th class="com-report-header table__cell th-customer-label">Customer Name</th>
                                <th class="com-report-header table__cell th-pod-label">POD / PDR #</th>
                                <th class="com-report-header table__cell th-account-label">Account</th>
                                <th class="com-report-header table__cell th-gross-amount-earned-label">Gross Amount Earned</th>
                                <th class="com-report-header table__cell th-week-enrolled-label">Week Enrolled</th>
                                <th class="com-report-header table__cell th-week-accpeted-label">Week Accepted</th>
                                <th class="com-report-header table__cell th-week-receipt-label">Week Receipt No.</th>
                            </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>   
            </div>

        </div>   
    </div>

    <div class="modal fade" id="modal-pea" role="dialog" aria-labelledby="modal-pea-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-pea-label">
                        Personal Energy Accounts
                    </h4>
                </div>
                <div class="modal-body">
                    <table id="table-pea" class="table table table-striped" style="width:100%" cellspacing="0" width="100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Customer Name</th>
                            <th class="table__cell">Date Accepted</th>
                            <th class="table__cell">Date Started Flowing</th>
                            <th class="table__cell">Status</th>
                        </tr>

                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Close</button>
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
<script src="$commission_engine_api_url/js/affiliate_detailed_commission.js?v=1.2"></script>


EOS
1;