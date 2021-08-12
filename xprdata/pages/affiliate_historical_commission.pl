print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_historical_commission.css?v=1" />

<div class="historical-commission tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Historical Commission</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="frequency">Frequency <span class="text-danger">*</span></label>
                        <select disabled
                                v-bind:disabled="frequencyState !== 'loaded' || commissionPeriodState === 'fetching'"
                                id="frequency"
                                class="form-control"
                                v-model="frequency">
                            <option v-if="frequencyState === 'fetching'" value="" selected disabled>
                                Fetching...
                            </option>
                            <option v-else-if="frequencyState === 'error'" value="" selected disabled>
                                Error
                            </option>
                            <option v-else-if="frequencyState === 'loaded'" value="" selected disabled>
                                Select a frequency
                            </option>
                            <option v-for="(frequency, index) in frequencies"
                                    v-bind:value="frequency.name"
                                    v-bind:key="frequency.name"
                            >
                                {{ frequency.name | capitalize}}
                            </option>
                        </select>
                        <a style="display: none;" v-show="frequencyState === 'error'" v-on:click.prevent="getFrequencies" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
                    </div>
                    <div class="col-md-6">
                        <label for="commission_type">Commission Type</label>
                        <select
                                name="commission_type"
                                id="commission_type"
                                class="form-control"
                                v-model="commissionType"
                        >
                            <option value="" selected disabled>Select a type</option>
                            <option value="all" v-show="commissionTypes.length > 0">All {{ frequency | capitalize }}</option>
                            <option v-for="(type, index) in commissionTypes"
                                    v-bind:value="type.id"
                                    v-bind:key="type.id">
                                {{ type.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-md-12">
                        <label for="commission_period">Commission Period <span class="text-danger">*</span></label>
                        <select
                                disabled
                                name="commission_period"
                                id="commission_period"
                                class="form-control"
                                v-model="commissionPeriodIndex"
                                v-bind:disabled="commissionPeriodState !== 'loaded'"
                        >
                            <option v-if="commissionPeriodState === 'fetching'" value="" selected disabled>
                                Fetching...
                            </option>
                            <option v-else-if="commissionPeriodState === 'error'" value="" selected disabled>
                                Error
                            </option>
                            <option v-else-if="commissionPeriodState === 'loaded'" value="" selected disabled>
                                Select a commission period
                            </option>
                            <option v-for="(period, index) in commissionPeriods"
                                    v-bind:value="index + 1"
                                    v-bind:key="index">
                                {{ period.display_start_date }} to {{ period.display_end_date }}
                            </option>
                        </select>
                        <a
                                style="display: none;"
                                v-show="commissionPeriodState === 'error'"
                                v-on:click.prevent="getCommissionPeriods" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-md-12">
                        <label for="invoice">Invoice No.</label>
                        <input type="text" id="invoice" name="invoice" v-model="invoice" class="form-control" />
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-6">
                        <button
                                type="button"
                                class="btn btn-primary btn-block"
                                v-on:click.prevent="view"
                        >
                            View
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-2">
            <button
                    type="button"
                    v-on:click.prevent="getDownloadLink"
                    class="btn btn-info btn-block btn-sm"
                    v-bind:disabled="downloadLinkState === 'fetching'"
                    v-show="total > 0"
            >
                <span v-if="downloadLinkState !== 'fetching'">Download</span>
                <span v-else>
                    Generating <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>

        </div>
        <div class="col-sm-10">
            <div class="pull-right">
                <h5><strong>Total {{ total | money }}</strong></h5>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            <table id="table-historical-commission" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Commission Type</th>
                    <th class="table__cell">Commission Period</th>
                    <th class="table__cell">Invoice</th>
                    <th class="table__cell">Purchaser</th>
                    <th class="table__cell">CV</th>
                    <th class="table__cell">Percentage</th>
                    <th class="table__cell">Amount Earned</th>
                    <th class="table__cell">Level</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_historical_commission.js?v=1.1"></script>

EOS
1;