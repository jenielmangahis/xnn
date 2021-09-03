print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_rank_progress.css?v=1" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" />


<div id="rank-progress" class="rank-progress tool-container tool-container--default" >
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">$rank_title Progress</h4>
        </div>
    </div>
<div class="mba-money-border">
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-row font-weight-bold">
                    <div class="form-group col-md-4">
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
                                Show all $rank_title_plural below the Selected $rank_title
                            </label>
                        </div>
                    </div>
                </div>


                <div class="form-row ">
                    <div class=" form-group col-md-3 col-sm-4 ">
                        <button
                                type="button"
                                class="new-btn-mba btn btn-primary btn-block"
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
                      <tr class="table__row">
                        <th colspan="5" class="table__cell sorting"></th>
                        <th colspan="2"class="table__cell sorting">Binary Volume</th>
                         <th colspan="10" class="table__cell sorting"></th>
                        </tr>
                        <tr class="table__row table-custom-border">
                        <th class="table__cell">Name </th>
                        <th class="table__cell">Current $rank_title </th>
                        <th class="table__cell">Paid-as $rank_title </th>
                        <th class="table__cell">PV </th>
                        <th class="table__cell">BV </th>
                        <th class="table__cell">Left Leg </th>
                        <th class="table__cell">Right Leg </th>
                        <th class="table__cell">Personal Active Enrollment </th>
                        <th class="table__cell">Active </th>
                        <th class="table__cell">Need for Next Rank </th>
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
<script src="$commission_engine_api_url/js/app.js"></script>
<script src="$commission_engine_api_url/js/admin_rank_progress.js?v=1.2"></script>


EOS
1;