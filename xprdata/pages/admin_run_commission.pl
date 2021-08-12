print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_run_commission.css?v=1" />

<div class="run-commission tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header" id="tool-container__header">Generate Commission</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div id="error-message" class="alert" :class="'alert-' + error.type" v-show="error.message != null">
                {{ error.message }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal">
                <div class="form-group">
                    <div class="form-sub-group col-md-4 col-sm-12 col-xs-12">
                        <label for="commission-type">Step 1. Choose Commission Type:</label>
                        <select v-model="commissionType" id="commission-type" class="form-control" v-on:change="onChangeCommissionTypes" :disabled="background != null && background.is_running == 'YES' || is_generating == 1 ? true : false">
                            <option disabled="disabled" selected="selected" value="">SELECT COMMISSION TYPE</option>
                            <option v-for="(type, i) in commissionTypes" :value="type" >{{ type.name }}</option>
                        </select>
                    </div>
                    <div class="form-sub-group col-md-4 col-sm-12 col-xs-12">
                        <label for="commission-period">Step 2. Choose Commission Period:</label>
                        <select v-on:change="onChangeCommissionPeriods" v-model="commissionPeriod" id="commission-period" class="form-control" :disabled="commissionPeriods.length == 0 || (background != null && background.is_running == 'YES' || is_generating == 1) ? true : false">
                            <option disabled="disabled" selected="selected" value="">SELECT COMMISSION PERIOD</option>
                            <option v-for="(period, i) in commissionPeriods" :value="period" >{{ period.start_date }} to {{ period.end_date }}</option>
                        </select>
                    </div>
                    <div class="form-sub-group col-md-4 col-sm-12 col-xs-12">
                        <label>&nbsp;</label><br/>
                        <button v-if="background == null || background.is_running !== 'YES'" v-on:click="run" :disabled="commissionType === '' || commissionPeriod === '' || is_generating == 1 ? true : false" class="btn btn-primary" type="button">
                            <span v-if="is_generating == 0">Generate</span>
                            <span v-else>Generating <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                        </button>
                        <button v-on:click="cancelRun" v-else class="btn btn-danger" type="button" :disabled="is_cancelling == 1 ? true : false">
                            <span v-if="is_cancelling == 0">Cancel</span>
                            <span v-else>Cancelling <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                        </button>
                    </div>
                </div>
                <div class="form-group" v-if="commissionType != '' && commissionPeriod != '' && background == null">
                    <div class="form-sub-group col-lg-4">
                        <button :disabled="is_viewing_previous_run == 1 ? true : false" v-if="commissionType != '' && commissionPeriod != '' && background == null && is_generating == 0" v-on:click="viewPreviousRun" class="btn btn-sm btn-info text-uppercase" type="button">
                            <span v-if="is_viewing_previous_run == 0">View Previous Run</span>
                            <span v-else>Retrieving <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row" v-show="background != null">
        <div class="col-md-12">
            <hr />
            <div class="h5">
                Details
            </div>
            <dl>
                <dt>Process ID</dt><dd>{{ background === null ? "" : background.id }}</dd>
                <dt>Commission Type</dt><dd>{{ commissionType !== "" ? commissionType.name : ""  }}</dd>
                <dt>Commission Period</dt><dd>{{ commissionPeriod !== "" ? commissionPeriod.start_date + ' to ' + commissionPeriod.end_date : ""  }}</dd>
                <dt>Status</dt><dd>{{ background === null ? "" : (background.is_running === 'YES' ? 'RUNNING' : background.is_running) }}</dd>
            </dl>
            <div class="progress">
                <div class="progress-bar " :class="{'active progress-bar-striped' : background !== null && background.is_running === 'YES', 'progress-bar-success' : background !== null && background.is_running !== 'FAILED', 'progress-bar-danger' : background !== null && background.is_running === 'FAILED'}" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" :style="{width: progressPercentage}">
                    {{ progressPercentage }}
                </div>
            </div>
            <div class="h5">
                Logs <small>refreshes every {{ logSecondsInterval }} seconds</small>
            </div>
            <div class="run-commission__log"
                 :class="[background != null && background.is_running == 'FAILED' ? 'run-commission__log--error' : 'run-commission__log--success']"
            >
                <p class="run-commission__log-message" v-for="line in lines">{{ line }}</p>
            </div>
            <div class="h5">
                Processes <small>refreshes every {{ detailsSecondsInterval }} seconds</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                    <tr>
                        <th>PID</th>
                        <th>Chunk/Type</th>
                        <th>CPU</th>
                        <th>Mem</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(process, index) in processes">
                        <th>{{ process.pid }}</th>
                        <th>
                            <span v-if="process.type=='payout'">{{ +process.offset + 1 }} - {{ (+process.count) + (+process.offset)}}</span>
                            <span v-else class="text-uppercase">{{ process.type }}</span>
                        </th>
                        <th>{{ process.cpu }}</th>
                        <th>{{ process.mem }}</th>
                        <th>{{ process.status }}</th>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="background != null && +background.is_report_generated === 1">
                <hr />
                <p class="run-commission__step">Step 3. Download Commissions:</p>
                <div v-if="background != null && (background.download_link != '' && background.download_link != null)">
                    <a class='btn btn-success run-commission__download-link'  :href="background != null ? background.download_link : ''" download>
                        <i class="fa fa-th-list" aria-hidden="true"></i> Download Commission Payouts
                    </a><br />
                    <a class='btn btn-success run-commission__download-link'  :href="background != null ? background.download_details_link : ''" download>
                        <i class="fa fa-th-list" aria-hidden="true"></i> Download Commission Payout Details
                    </a>
                </div>
                <p v-else class="no-commission-report h4">NO COMMISSION REPORT</p>
            </div>
            <div v-if="background != null && background.is_running == 'COMPLETED'">
                <hr />
                <p class="run-commission__step"> Step 4. Lock Commissions:<p>
                <p>Note: You can skip this step if you want to run commissions again for these commission period.</p>
                <button type="button" class="btn btn-danger" v-on:click="lockCommissionPeriod" :disabled="is_locking == 1 ? true : false">
                    <span v-if="is_locking == 0">LOCK COMMISSION PERIOD</span>
                    <span v-else>LOCKING COMMISSION PERIOD <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="$commission_engine_api_url/js/admin_run_commission.js?v=1.1"></script>

EOS
1;