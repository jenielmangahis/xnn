print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=1.0&app=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_titlehistory_ledger.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_rank_history.css?v=1" />

<style>

.rank-history th {
    font-size: 12px;
}
</style>
<div id="rank-history" class="rank-history tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="title-history-label">$rank_title History</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#tree" class="tab-header" aria-controls="tree" role="tab" data-toggle="tab" style="color: black !important;">Enrollment Tree $rank_title_plural</a></li>
                <li role="presentation"><a href="#personal" class="tab-header" aria-controls="personal" role="tab" data-toggle="tab" style="color: black !important;">Personal $rank_title_plural</a></li>
                <li role="presentation"><a href="#new-highest-rank" class="tab-header" aria-controls="new-highest-rank" role="tab" data-toggle="tab" style="color: black !important;">New Highest Achieved $rank_title_plural</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content tab-edit" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="tree">
                    <div class="row">
                        <div class="col-md-4">
                            <form class="form-horizontal">
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <label class="receipt-date-label">Date</label>
                                        <datepicker id="start-date" v-model="enrollment.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="rank_id" class="drop-career-label">Career $rank_title</label>
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
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <button
                                                type="button"
                                                class="btn btn-primary btn-block view-btn-label"
                                                v-on:click.prevent="viewEnrollment">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="table-rank-history-enrollment" class="table table-striped table-bordered dt-responsive nowrap collapsed" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row rank-history">
									<th class="table__cell th-assoc-id-label">Associate ID</th>
                                    <th class="table__cell name-label">Name</th>
                                    <th class="table__cell th-career-label">Career $rank_title</th>
                                    <th class="table__cell th-pea-label">PEA</th>
                                    <th class="table__cell th-pa-label">TA</th>
                                    <th class="table__cell th-mar-label">MAR</th>
                                    <th class="table__cell th-qta-label">QTA</th>
                                    <th class="table__cell th-qualified-label">Qualified</th>
                                    <th class="table__cell active-label">Active</th>
                                    <th class="table__cell th-level-label">Level</th>
                                    <th class="table__cell th-date-label">Date</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="personal">
                    <div class="row">
                        <div class="col-md-4">
                            <form class="form-horizontal">
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <label class="from-label">From</label>
                                        <datepicker v-model="personal.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="to-label">To</label>
                                        <datepicker v-model="personal.end_date" v-bind:start-date="personal.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <label for="personal_rank_id" class="paid-as-label">Paid-as $rank_title</label>
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
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <button
                                                type="button"
                                                class="btn btn-primary btn-block view-btn-label"
                                                v-on:click.prevent="viewPersonal">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="table-rank-history-personal" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row rank-history">
                                    <th class="table__cell paid-as-label">Paid-as $rank_title</th>
                                    <th class="table__cell th-pea-label">PEA</th>
                                    <th class="table__cell th-pa-label">TA</th>
                                    <th class="table__cell th-mar-label">MAR</th>
                                    <th class="table__cell th-qta-label">QTA</th>
                                    <th class="table__cell th-qualified-label">Qualified</th>
                                    <th class="table__cell active-label">Active</th>
                                    <th class="table__cell th-date-labe">Date</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="new-highest-rank">
                    <div class="row">
                        <div class="col-md-4">
                            <form class="form-horizontal">
                                <div class="form-group" v-show="+highest.is_all === 0">
                                    <div class="col-sm-6">
                                        <label class="from-label">From</label>
                                        <datepicker v-model="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="to-label">To</label>
                                        <datepicker v-model="highest.end_date" v-bind:start-date="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                </div>
                                <div class="form-group" v-show="+highest.is_all === 1">
                                    <div class="col-sm-6">
                                        <label>As of</label>
                                        <datepicker v-model="highest.end_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
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
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <div class="checkbox">
                                            <input
                                                    type="checkbox"
                                                    id="highest_is_all"
                                                    v-model="highest.is_all"
                                                    true-value="1"
                                                    false-value="0">
                                            <label for="highest_is_all" class="highest_is_all_label">
                                                Show all highest achieved $rank_title
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <button
                                                type="button"
                                                class="btn btn-primary btn-block view-btn-label"
                                                v-on:click.prevent="viewHighest">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="table-rank-history-highest" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row rank-history">
									<th class="table__cell th-assoc-id-label ">Associate ID</th>
                                    <th class="table__cell name-label">Name</th>
                                    <th class="table__cell">Highest $rank_title Achieved</th>
                                    <th class="table__cell th-date-achieved-label">Date Achieved</th>
                                    <th class="table__cell th-level-label">Level</th>
                                    <th class="table__cell th-sponsor-label">Sponsor</th>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/affiliate_rank_history.js?v=1.1.1"></script>
EOS
1;