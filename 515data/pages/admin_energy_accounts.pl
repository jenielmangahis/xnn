print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_energy_accounts.css?v=1" />

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
            <h4>Energy Accounts</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-4">
                    <form class="form-horizontal">
                        <div class="form-group">
                            <div class="col-sm-6">
                                <label>From</label>
                                <datepicker id="start-date" v-model="filters.start_date" v-bind:end-date="today"></datepicker>
                            </div>
                            <div class="col-sm-6">
                                <label>To</label>
                                <datepicker id="end-date" v-model="filters.end_date" v-bind:end-date="today"></datepicker>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-6">
                                <label for="rank_id">Status</label>
                                <select
                                        id="status"
                                        class="form-control"
                                        v-model="filters.selectedStatus"
                                        >
                                        <option disabled>Select an option</option>
                                        <option v-for="status in statuses" v-bind:value="status.id">{{ status.type }}</option>
                                </select>
                                <a style="display: none;" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
                            </div>
                            <div class="col-sm-6">
                                <button
                                        type="button"
                                        class="btn btn-primary btn-block button-filter"
                                        v-on:click.prevent="filter">
                                    Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4 col-md-offset-4">
                    <table id="table-status" class="table table-striped table-bordered">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">Status</th>
                                <th class="table__cell"></th>
                            </tr>
                            </thead>
                            <tbody class="table__body">
                                <tr>
                                    <td>Pending Confirmation</td>
                                    <td>{{ pendingConfirmationCount }}</td>
                                </tr>

                                <tr>
                                    <td>Pending Approval</td>
                                    <td>{{ pendingApprovalCount }}</td>
                                </tr>

                                <tr>
                                    <td>Pending Rejection</td>
                                    <td>{{ pendingRejectionCount }}</td>
                                </tr>

                                <tr>
                                    <td>Approved, Pending Flowing</td>
                                    <td>{{ approvePendingFlowingCount }}</td>
                                </tr>

                                <tr>
                                    <td>Flowing</td>
                                    <td>{{ flowingCount }}</td>
                                </tr>

                                <tr>
                                    <td>Flowing, Pending Cancellation</td>
                                    <td>{{ flowingPendingCancellation }}</td>
                                </tr>

                                <tr>
                                    <td>Cancelled</td>
                                    <td>{{ cancelledCount }}</td>
                                </tr>
                            </tbody>
                        </table>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="table-energy-accounts" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">Customer ID</th>
                                <th class="table__cell">Customer</th>
                                <th class="table__cell">Associate</th>
                                <th class="table__cell">Associate ID</th>
                                <th class="table__cell">Associate's Sponsor</th>
                                <th class="table__cell">Account</th>
                                <th class="table__cell">POD/PDR #</th>
                                <th class="table__cell">Date Accepted</th>
                                <th class="table__cell">Date Started Flowing</th>
                                <th class="table__cell">Current Status</th>
                                <th class="table__cell">Status History</th>
                                <th class="table__cell">id</th>
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

<div class="modal fade" id="modal-status-history" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel">
                        Status History
                    </h4>
                </div>
                <div class="modal-body">
                    
                    <div class="table-responsive">
                        <table id="table-status-history" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">Status</th>
                                <th class="table__cell">Date</th>
                            </tr>
                            </thead>
                            <tbody class="table__body">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Close</button>
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
<script src="$commission_engine_api_url/js/admin_energy_accounts.js?v=1.2"></script>

EOS
1;