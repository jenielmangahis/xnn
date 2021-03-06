print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_run_commission.css?v=1" />

<div class="run-commission tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="admin-money-title">Run Commission</h4>
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
        
        <form class="form-horizontal">
            <div class="form-group col-md-12">
                <div class="form-sub-group col-md-4 margin-top-20">
                    <label for="commission-type">Choose Commission Type:</label>
                    <select v-model="commissionType" id="commission-type" class="form-control" v-on:change="onChangeCommissionTypes" :disabled="background != null && background.is_running == 'YES' || is_generating == 1 ? true : false">
                        <option disabled="disabled" selected="selected" value="">SELECT COMMISSION TYPE</option>
                        <option v-for="(type, i) in commissionTypes" :value="type" >{{ type.name }}</option>
                    </select>
                </div>
                <div class="form-sub-group col-md-4 margin-top-20">
                    <label for="commission-period">Choose Commission Period:</label>
                    <select v-on:change="onChangeCommissionPeriods" v-model="commissionPeriod" id="commission-period" class="form-control" :disabled="commissionPeriods.length == 0 || (background != null && background.is_running == 'YES' || is_generating == 1) ? true : false">
                        <option disabled="disabled" selected="selected" value="">SELECT COMMISSION PERIOD</option>
                        <option v-for="(period, i) in commissionPeriods" :value="period" >{{ period.start_date }} to {{ period.end_date }}</option>
                    </select>
                </div>
                <div class="form-sub-group col-md-4 margin-top-20">
                    <div class="col-md-8 mobile-padding-lr">
                        <label>&nbsp;</label><br/>
                        <button v-if="background == null || background.is_running !== 'YES'" v-on:click="run" :disabled="commissionType === '' || commissionPeriod === '' || is_generating == 1 ? true : false" class="btn btn-block btn-primary" type="button">
                            <span v-if="is_generating == 0">Generate Report</span>
                            <span v-else>Generating <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                        </button>
                        <button v-on:click="cancelRun" v-else class="btn btn-block btn-danger" type="button" :disabled="is_cancelling == 1 ? true : false">
                            <span v-if="is_cancelling == 0">Cancel</span>
                            <span v-else>Cancelling <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                        </button>
                    </div>
                    <div class="col-md-4"></div>
                </div>
            </div>
            <div class="form-group" v-if="commissionType != '' && commissionPeriod != '' && background == null">
                <div class="form-sub-group col-lg-4">
                    <button :disabled="is_viewing_previous_run == 1 ? true : false" v-if="commissionType != '' && commissionPeriod != '' && background == null && is_generating == 0" v-on:click="viewPreviousRun" class="btn btn-block btn-info text-uppercase" type="button">
                        <span v-if="is_viewing_previous_run == 0">View Previous Run</span>
                        <span v-else>Retrieving <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                    </button>
                </div>
            </div>
        </form>
        
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="h5">
                Details
            </div>
            <dl>
                <p><strong>Commission Type:</strong> <span class="light-blue">{{ commissionType !== "" ? commissionType.name : ""  }}</span></p>
                <p><strong>Commission Period:</strong> <span class="light-blue">{{ commissionPeriod !== "" ? commissionPeriod.start_date + ' to ' + commissionPeriod.end_date : ""  }}</span></p>
                <p><strong>Status:</strong> <span class="light-blue">{{ background === null ? "" : (background.is_running === 'YES' ? 'RUNNING' : background.is_running) }}</span></p>
            </dl>
            <div class="progress">
                <div class="progress-bar " :class="{'active progress-bar-striped' : background !== null && background.is_running === 'YES', 'progress-bar-success' : background !== null && background.is_running !== 'FAILED', 'progress-bar-danger' : background !== null && background.is_running === 'FAILED'}" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" :style="{width: progressPercentage}">
                    {{ progressPercentage }}
                </div>
            </div>
            <div class="h5">
                Logs
            </div>
            <div class="run-commission__log"
                 :class="[background != null && background.is_running == 'FAILED' ? 'run-commission__log--error' : 'run-commission__log--success']"
            >
                <p class="run-commission__log-message" v-for="line in lines">{{ line }}</p>
            </div>
            <div class="h5">
                Processes
            </div>

            <div>

                <p class="run-commission__step margin-top-bottom"><strong>Step 3. Download Commissions Reports:</strong></p>
                <div>
                    <div class="row">

                        <div class="col-md-4 margin-top-20 mobile-padding-lr">
                            <a class='btn btn-block btn-success run-commission__download-link'  :href="background != null ? background.download_link : ''" download>
                                Download Commission Payouts Summary
                            </a>
                        </div>

                        <div class="col-md-4 margin-top-20 mobile-padding-lr">
                            <a class='btn btn-block btn-success run-commission__download-link'  :href="background != null ? background.download_details_link : ''" download>
                                Download Commission Payout Details
                            </a>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                    
                </div>
                <p v-else class="no-commission-report h4">NO COMMISSION REPORT</p>
            </div>

            <div>
                <p class="run-commission__step margin-top-bottom"><strong>Step 4. Lock Commissions:</strong><p>
                <p><strong>Note: You can skip this step if you want to run commissions again for these commission period.</strong></p>
                
                <div class="row">

                    <div class="col-md-4 margin-top-20 mobile-padding-lr">
                        <button type="button" class="btn btn-primary btn-block commission-lock-period" v-on:click="lockCommissionPeriod" :disabled="is_locking == 1 ? true : false">
                            <span v-if="is_locking == 0"> &nbsp;&nbsp;<i class="fa fa-lock" aria-hidden="true"></i> &nbsp;&nbsp; Lock Commission Period</span>
                            <span v-else> Locking Commission Period <i class="fa fa-spinner fa-pulse fa-fw"></i></span>
                        </button>
                    </div>

                    <div class="col-md-4"></div>
                    <div class="col-md-4"></div>

                </div>

                
            </div>

        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="$commission_engine_api_url/js/admin_run_commission.js?v=1.1"></script>

EOS
1;