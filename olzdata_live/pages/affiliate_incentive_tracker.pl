print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datatables.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">


<div id="incentive-progress" class="incentive-progress tool-container tool-container--default" >
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">INCENTIVES</h4>
        </div>
    </div>
<div class="olz-money-border">
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal font-weight-bold" style="margin-bottom: 15px;">
                <div class="form-row">
                    <div class="form-group col-md-4 mt-1">
                        <label for="incentive_id">Select Incentive:</label>
                        <select id="incentive_id"
                                v-bind:disabled="incentiveState !== 'loaded'"
                                id="incentive_id"
                                class="form-control"
                                v-model="incentiveId">
                            <option v-if="incentiveState === 'fetching'" value="" selected disabled>
                                Fetching...
                            </option>
                            <option v-else-if="incentiveState === 'error'" value="" selected disabled>
                                Error
                            </option>
                            <option v-else-if="incentiveState === 'loaded'" value="" selected disabled>
                                Select an incentive
                            </option>
                            <option v-for="(incentive, index) in incentives"
                                    v-bind:value="incentive.id"
                                    v-bind:key="incentive.title"
                            >
                                {{ incentive.title}}
                            </option>
                                                        
                        </select>
                        <a style="display: none;" v-show="incentiveState === 'error'" v-on:click.prevent="getRanks" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 table-custom-border">
        <div class="table-responsive">
                            <table id="table-incentive-progress" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">

                        <tr class="table__row table-custom-border">
                            <th class="table__cell">ID </th>
                            <th class="table__cell">Name </th>
                            <th class="table__cell">Sponsor </th>
                            <th class="table__cell">Level </th>
                            <th class="table__cell">Points </th>
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

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="$commission_engine_api_url/js/affiliate_incentive_tracker.js"></script>

EOS
1;