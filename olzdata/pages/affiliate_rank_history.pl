print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=1.0&app=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_rank_history.css?v=1" />

<div id="rank-history" class="rank-history tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">$rank_title History</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
              <!-- Nav tabs -->
             <nav>
                <div class="nav nav-tabs font-weight-bold" id="nav-tab" role="tablist">
                    <a class="nav-item nav-link active" id="nav-tree-tab" data-toggle="tab" href="#nav-tree" role="tab" aria-controls="nav-tree" aria-selected="true">Genealogy Tree $rank_title_plural</a>
                    <a class="nav-item nav-link" id="nav-personal-tab" data-toggle="tab" href="#nav-personal-rank" role="tab" aria-controls="nav-personal-rank" aria-selected="false">Personal $rank_title_plural</a>
                    <a class="nav-item nav-link" id="nav-new-highest-rank-tab" data-toggle="tab" href="#nav-new-highest-rank" role="tab" aria-controls="nav-highest-rank" aria-selected="false">New Highest Achieved $rank_title_plural</a>
                </div>
            </nav>

            <!-- Tab panes -->
            <div class="tab-content" id="nav-tabContent">
                <div class="tab-pane fade show active" role="tabpanel" id="nav-tree" aria-labelledby="nav-tree-tab">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-horizontal">
                                <div class="form-row font-weight-bold">
                                    <div class="form-group col-md-4 mt-1">
                                        <label>Date</label>
                                        <datepicker id="start-date" v-model="enrollment.start_date" v-bind:end-date="today"></datepicker>
                                        <div class="input-group-addon">
                                            <span class="glyphicon glyphicon-th"></span>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4 mt-1">
                                        <label for="rank_id">Paid-as $rank_title</label>
                                        <select disabled
                                                v-bind:disabled="rankState !== 'loaded'"
                                                id="rank_id"
                                                class="form-control"
                                                v-model="enrollment.rank_id">
                                            <option v-if="rankState === 'fetching'" value="" selected disabled>
                                                Fetching...
                                            </option>
                                            <option v-else-if="rankState === 'error'" value="" selected disabled>
                                                Error
                                            </option>
                                            <option v-else-if="rankState === 'loaded'" value="" selected>
                                                All
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
                                    <!--Button-->
                                     <div class="form-group col-md-3 ">
                                             <label>&nbsp;</label><br>
                                           <button
                                                    type="button"
                                                    class="new-btn-olz btn btn-primary btn-block mt-1"
                                                    v-on:click.prevent="viewEnrollment">
                                                View
                                            </button>
                                    </div>
                                </div>   
                            </form>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="table-responsive">
                            <table id="table-rank-history-enrollment" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell">Name</th>
                                        <th class="table__cell">Paid-as $rank_title</th>
                                        <th class="table__cell">PRS</th>
                                        <th class="table__cell">GRS</th>
                                        <th class="table__cell">Sponsored Qualified Representatives</th>
                                        <th class="table__cell">Active</th>
                                        <th class="table__cell">Level</th>
                                        <th class="table__cell">Date</th>
                                    </tr>
                                    </thead>
                                    <tbody class="table__body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="nav-personal-rank" role="tabpanel" aria-labelledby="nav-personal-rank">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-horizontal">
                                <div class="form-row font-weight-bold">
                                    <div class="form-group col-md-4">
                                         <label>From</label>
                                         <datepicker v-model="personal.start_date" v-bind:end-date="today"></datepicker>
                                     </div>
                                     <div class="form-group col-md-4">
                                         <label>To</label>
                                         <datepicker v-model="personal.end_date" v-bind:start-date="personal.start_date" v-bind:end-date="today"></datepicker>
                                     </div>
                                    <div class="form-group col-md-4">
                                        <label for="personal_rank_id">Paid-as $rank_title</label>
                                        <select disabled
                                                v-bind:disabled="rankState !== 'loaded'"
                                                id="personal_rank_id"
                                                class="form-control"
                                                v-model="personal.rank_id">
                                            <option v-if="rankState === 'fetching'" value="" selected disabled>
                                                Fetching...
                                            </option>
                                            <option v-else-if="rankState === 'error'" value="" selected disabled>
                                                Error
                                            </option>
                                            <option v-else-if="rankState === 'loaded'" value="" selected>
                                                All
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
                                </div>
                                <div class="form-row font-weight-bold">

                                </div>
                                <div class="form-row">
                                     <div class="form-group col-md-3">
                                        <button
                                                type="button"
                                                class="new-btn-olz btn btn btn-primary btn-block"
                                                v-on:click.prevent="viewPersonal">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="table-responsive">
                            <table id="table-rank-history-personal" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell">Name</th>
                                    <th class="table__cell">Paid-as $rank_title</th>
                                    <th class="table__cell">PRS</th>
                                    <th class="table__cell">GRS</th>
                                    <th class="table__cell">Sponsored Qualified Representatives</th>
                                    <th class="table__cell">Level</th>
                                    <th class="table__cell">Date</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" role="tabpanel" id="nav-new-highest-rank" aria-labelledby="nav-new-highest-rank-tab">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-horizontal">
                                <div class="form-row font-weight-bold">
                                    <div class="form-group col-md-4" v-show="+highest.is_all === 0">
                                        <label>From</label>
                                        <datepicker v-model="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="form-group col-md-4" v-show="+highest.is_all === 0">
                                         <label>To</label>
                                         <datepicker v-model="highest.end_date" v-bind:start-date="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="highest_rank_id">$rank_title</label>
                                        <select disabled
                                                v-bind:disabled="rankState !== 'loaded'"
                                                id="highest_rank_id"
                                                class="form-control"
                                                v-model="highest.rank_id">
                                            <option v-if="rankState === 'fetching'" value="" selected disabled>
                                                Fetching...
                                            </option>
                                            <option v-else-if="rankState === 'error'" value="" selected disabled>
                                                Error
                                            </option>
                                            <option v-else-if="rankState === 'loaded'" value="" selected>
                                                All
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
                                </div>
                                <div class="form-group" v-show="+highest.is_all === 1">
                                    <div class="col-sm-6">
                                        <label>As of</label>
                                        <datepicker v-model="highest.end_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                </div>
                                <div class="form-row font-weight-bold">
                                    <div class="form-group col-md-4">
                                        <div class="checkbox">
                                            <input
                                                    type="checkbox"
                                                    id="highest_is_all"
                                                    v-model="highest.is_all"
                                                    true-value="1"
                                                    false-value="0">
                                            <label for="highest_is_all">
                                                Show all highest achieved $rank_title
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <button
                                                type="button"
                                                class="new-btn-olz btn btn-primary btn-block"
                                                v-on:click.prevent="viewHighest">
                                            View
                                        </button>
                                    </div>
                                </div>       
                            </form>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="table-responsive">
                            <table id="table-rank-history-highest" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell">Date  </th>
                                    <th class="table__cell">Notes  </th>
                                    <th class="table__cell">Amount</th>
                                    <th class="table__cell">Level  </th>
                                    <th class="table__cell">Sponsor  </th>
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
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/affiliate_rank_history.js?v=1.1"></script>

EOS
1;