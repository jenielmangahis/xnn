print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<!--<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_rank_history.css?v=1" />-->

<style>
    .btn-download {
        margin-bottom: 10px !important;
    }

    .datepicker-dropdown.dropdown-menu {
        min-width: 0;
        color: #333 !important;
        background-color: #fff !important;
    }
</style>

<div id="rank-history" class="rank-history tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>$rank_title History</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#tree" class="tab-header" aria-controls="tree" role="tab" data-toggle="tab" style="color: black !important;">$rank_title History</a></li>
                <li role="presentation"><a href="#new-highest-rank" class="tab-header" aria-controls="new-highest-rank" role="tab" data-toggle="tab" style="color: black !important;">New Highest Achieved $rank_title_plural</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="tree">
                    <div class="row">
                        <div class="col-md-4">
                            <form class="form-horizontal">
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <label>Date</label>
                                        <datepicker id="start-date" v-model="enrollment.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="col-sm-6">
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
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <button
                                                type="button"
                                                class="btn btn-primary btn-block"
                                                v-on:click.prevent="viewEnrollment">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="pull-right">
                                <button
                                        type="button"
                                        v-on:click.prevent="getDownloadEnrollmentLink"
                                        class="btn btn-info btn-sm btn-download"
                                        v-bind:disabled="enrollment.downloadLinkState === 'fetching'"
                                >
                                    <span v-if="enrollment.downloadLinkState !== 'fetching'">Download</span>
                                    <span v-else>
                                    Generating <i class="fa fa-spinner fa-spin"></i>
                                </span>
                                </button>
                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table id="table-rank-history-enrollment" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                    <thead class="table__header table__header--bg-primary">
                                    <tr class="table__row">
                                        <th class="table__cell">Name</th>
                                        <th class="table__cell">Paid-as $rank_title</th>
                                        <th class="table__cell">CS</th>
                                        <th class="table__cell">DS</th>
                                        <th class="table__cell">MSR</th>
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

                <div role="tabpanel" class="tab-pane" id="new-highest-rank">
                    <div class="row">
                        <div class="col-md-4">
                            <form class="form-horizontal">
                                <div class="form-group" v-show="+highest.is_all === 0">
                                    <div class="col-sm-6">
                                        <label>From</label>
                                        <datepicker v-model="highest.start_date" v-bind:end-date="today"></datepicker>
                                    </div>
                                    <div class="col-sm-6">
                                        <label>To</label>
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
                                            <label for="highest_is_all">
                                                Show all highest achieved $rank_title
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-6">
                                        <button
                                                type="button"
                                                class="btn btn-primary btn-block"
                                                v-on:click.prevent="viewHighest">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="pull-right">
                                <button
                                        type="button"
                                        v-on:click.prevent="getDownloadHighestLink"
                                        class="btn btn-info btn-sm btn-download"
                                        v-bind:disabled="highest.downloadLinkState === 'fetching'"
                                >
                                    <span v-if="highest.downloadLinkState !== 'fetching'">Download</span>
                                    <span v-else>
                                    Generating <i class="fa fa-spinner fa-spin"></i>
                                </span>
                                </button>
                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table id="table-rank-history-highest" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell">Name</th>
                                    <th class="table__cell">Paid as $rank_title</th>
                                    <th class="table__cell">Highest $rank_title Achieved</th>
                                    <th class="table__cell">Date Achieved</th>
                                    <th class="table__cell">Sponsor</th>
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
<script src="$commission_engine_api_url/js/admin_rank_history.js?v=1.1"></script>

EOS
1;