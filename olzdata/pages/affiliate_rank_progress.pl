print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datatables.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_rank_progress.css?v=1" />

<div id="rank-progress" class="rank-progress tool-container tool-container--default" >
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">$rank_title Progress</h4>
        </div>
    </div>
<div class="olz-money-border">
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal font-weight-bold" style="margin-bottom: 15px;">
                <div class="form-row">
                    <div class="form-group col-md-4 mt-1">
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
                    <div class="form-group col-md-4 pt-5 mt-2">
                        <div class="checkbox">
                            <input
                                    type="checkbox"
                                    id="is_all_below"
                                    v-model="isAllBelow"
                                    true-value="1"
                                    false-value="0">
                            <label for="is_all_below">
                                Show all $rank_title_plural below the selected $rank_title
                            </label>
                        </div>
                    </div>
                    <div class=" form-group col-md-3 ">
                        <label>&nbsp;</label><br>
                        <button
                                type="button"
                                class="new-btn-olz btn btn btn-primary btn-block"
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
        <div class="col-md-12 table-custom-border">
        <div class="table-responsive">
                            <table id="table-rank-progress" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">

                        <tr class="table__row table-custom-border">
                        <th class="table__cell">Name </th>
                        <th class="table__cell">Current $rank_title </th>
                        <th class="table__cell">Paid-as $rank_title </th>
                        <th class="table__cell">PRS </th>
                        <th class="table__cell">GRS </th>
                        <th class="table__cell">Sponsored Qualified Representatives </th>
                        <th class="table__cell">Sponsored Leader or higher </th>
                        <th class="table__cell">Level 1 Leader </th>
                        <th class="table__cell">Need for Next Rank <i class="bi bi-arrow-down-up pull-right"></i></th>
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
<script src="$commission_engine_api_url/js/affiliate_rank_progress.js"></script>

EOS
1;