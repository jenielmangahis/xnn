print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">


<div id="rank-progress" class="rank-progress tool-container tool-container--default" v-cloak>
    
    <div class="row">
        <h4 class="admin-money-title">$rank_title Progress</h4>
    </div>

    <div class="put-mobile-lr-padding">

        <div class="card with-border-no-radius">

            <div class="card-body">

                <div class="row margin-top">
                    <div class="col-md-4">
                        <label for="rank_id">$rank_title</label>
                        <select disabled
                            v-bind:disabled="rankState !== 'loaded'"
                            id="rank_id"
                            class="form-control"
                            v-model="rankId">

                            <option v-if="rankState === 'fetching'" value="" selected disabled>
                                Fetching...
                            </option>
                            <option v-else-if="rankState === 'error'" value="" selected disabled>
                                Error
                            </option>
                            <option v-else-if="rankState === 'loaded'" value="" selected disabled>
                                Select a $rank_title
                            </option>
                            <option v-for="(rank, index) in ranks"
                                v-bind:value="rank.id"
                                v-bind:key="rank.name"
                            >
                                {{ rank.name | capitalize}}
                            </option>
                        </select>
                        <a style="display: none;" v-show="rankState === 'error'" v-on:click.prevent="getRanks" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
                    </div>

                    <div class="col-md-4">
                        <label>&nbsp;</label><br>
                        <div class="checkbox">
                            <input
                                type="checkbox"
                                id="is_all_below"
                                v-model="isAllBelow"
                                true-value="1"
                                false-value="0">
                            <label for="is_all_below">
                                <strong>Show all $rank_title_plural below the selected $rank_title</strong>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        
                        <label>&nbsp;</label><br>
                        <button
                            type="button"
                            class="btn btn-primary btn-block"
                            v-on:click.prevent="view"
                        >
                            View Report
                        </button>
                        
                    </div>

                </div>

                <div class="row margin-top">
                    <div class="col-md-12">
                        <div class="table-responsive">
                        
                            <table id="table-rank-progress" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <td class="table__cell">Name</td>
                                    <td class="table__cell">Level</td>
                                    <td class="table__cell">Current Title</td>
                                    <td class="table__cell">Paid As Title</td>
                                    <td class="table__cell">PV</td>
                                    <td class="table__cell">LlV</td>
                                    <td class="table__cell">NEEDS</td>
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

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/admin_rank_progress.js?v=1.1"></script>

EOS
1;