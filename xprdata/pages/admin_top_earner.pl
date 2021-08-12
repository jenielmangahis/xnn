print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">

<div id="top-earners" class="top-earners tool-container tool-container--default" v-cloak >
    <div class="row">
        <div class="col-md-12">
            <h4>Top Earners</h4>
            <hr />
        </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
    
    <div class="row">
        <div class="col-md-4">
            <form class="form-horizontal">
                <div class="form-group" v-show="+top_earners.is_all === 0">
                    <div class="col-sm-6">
                        <label>From</label>
                        <datepicker v-model="top_earners.start_date" v-bind:end-date="today"></datepicker>
                    </div>
                    <div class="col-sm-6">
                        <label>To</label>
                        <datepicker v-model="top_earners.end_date" v-bind:start-date="top_earners.start_date" v-bind:end-date="today"></datepicker>
                    </div>
                </div>
                <div class="form-group" v-show="+top_earners.is_all === 1">
                    <div class="col-sm-6">
                        <label>As of</label>
                        <datepicker v-model="top_earners.end_date" v-bind:end-date="today"></datepicker>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6">
                        <div class="checkbox">
                            <input
                                    type="checkbox"
                                    id="top_earners_is_all"
                                    v-model="top_earners.is_all"
                                    true-value="1"
                                    false-value="0">
                            <label for="top_earners_is_all">
                                Show All Time
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6">
                        <button
                                type="button"
                                class="btn btn-primary btn-block"
                                v-on:click.prevent="viewTopEarners">
                            View
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-top_earners" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Member</th>
                        <th class="table__cell">Sitename</th>
                        <th class="table__cell">Earnings</th>
                    </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_top_earner.js?v=1.2"></script>


EOS
1;