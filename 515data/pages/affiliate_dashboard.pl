print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datatables.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_dashboard.css?v=1" />
<style>
    /*TODO: arrange the css*/
    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid #e3e6f0;
        border-radius: .35rem;
    }

    .tool-container {
        color: #000 !important;
    }

    .card-body {
        flex: 1 1 auto;
        padding: 2.25rem;
    }

    .card-body .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -.75rem;
        margin-left: -.75rem;
    }

    .no-gutters {
        margin-right: 0;
        margin-left: 0;
    }

    .align-items-center {
        align-items: center!important;
    }

    .justify-content-between {
        justify-content: space-between!important;
    }

    .card-header:first-child {
        border-radius: calc(.35rem - 1px) calc(.35rem - 1px) 0 0;
    }
    .card-header {
        padding: 1.5rem 1.25rem;
        margin-bottom: 0;
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
    }

    .font-weight-bold {
        font-weight: 700!important;
    }

    .mb-4, .my-4 {
        margin-bottom: 1.5rem!important;
    }

    .text-gray-800, .card-details span {
        color: #5a5c69!important;
    }

    .text-gray-300 {
        color: #dddfeb!important;
    }

    .no-gutters>.col, .no-gutters>[class*=col-] {
        padding-right: 0;
        padding-left: 0;
    }

    .col-auto {
        flex: 0 0 auto;
        width: auto;
        max-width: 100%;
    }

    .text-xs {
        font-size: 1rem;
    }

    .d-flex {
        display: flex!important;
    }

    .flex-row {
        flex-direction: row!important;
    }

    .bg-pending-bonus {
        background-color: #f5f5f5 !important;
        color: #000 !important;
    }

    .bg-achieved-bonus {
        background-color: #6c757d !important;
        color: #fff !important;
    }

    .referral-points {
        color: #17a2b8!important;
        font-weight: bolder !important;
    }
</style>

<div class="dashboard tool-container" v-cloak>

    <div class="row">
    	<div class="col-md-3 mb-4">
            <div class="card dashboard-card border-left-success shadow h-100 py-2">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase next-title-req-label">Next Title Requirements</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-5" style="margin-bottom: 20px;">
                        <label class="text-info next-title-label">Next Title:</label>  
                        <div class="col-md-9">
                            <div class="value">{{current_rank.next_rank}}</div>
                        </div>
                    </div>

                    <h4 class="m-0 mb-3 needs-label" style="margin-bottom: 10px;">Needs:</h4>

                    <div id="needs">
                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card border-left-success shadow h-100 py-2">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase title-label">Title</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <label class="text-info career-title">Career Title:</label>  
                        <div class="col-md-5">
                            <div class="value">{{current_rank.current_rank}}</div>
                        </div>
                    </div>

                    <div class="d-flex" style="margin-bottom: 20px;">
                        <label class="text-info paid-as-title">Paid as Title:</label>  
                        <div class="col-md-5">
                            <div class="value">{{current_rank.paid_as_rank}}</div>
                        </div>
                    </div>

					<div id="current-rank-deets" class="deets-current">
					</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card border-left-success shadow h-100 py-2">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase last-three-months-label">Last 3 Months Earnings</h5>
                </div>
                <div id="last-three-months" class="card-body">
                </div>
            </div>
        </div>
		<div class="col-md-3 mb-4">
            <div class="card dashboard-card border-left-success shadow h-100 py-2">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase qualification-req-label">Qualification Requirement</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        <label class="text-info qualified-label">Qualified:</label>  
                        <div class="col-md-12 ">
                            <div class=" value" id="qualified-text">{{qualified.is_qualified}}</div>
                        </div>
                    </div>

                    <div class="d-flex">
                        <label class="text-info" v-if="qualified.is_qualified !== 'No'">&#10004;</label>  
                        <label class="text-info" v-if="qualified.is_qualified === 'No'">&#10006;</label>
                        <div class="col-md-12">
                            <div class="value" id="qualified-text-2">{{qualified.requirements}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-4 d-flex flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase personal-energy-label">Personal Energy Accounts</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

<!--
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="button-row">
                                <button type="button" class="btn btn-primary btn-sm mb-3" v-on:click.prevent="() => dtCurrentPeriodOrders.draw()">Refresh</button>
                            </div>
                        </div>
                    </div> -->

                    <table id="table-members" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header">
                        <tr class="table__row">
                            <th class="table__cell">POD / PDR #</th>
                            <th class="table__cell customer-label">Customer</th>
                            <th class="table__cell account-label">Account</th>
                            <th class="table__cell date-accepted-label">Date Accepted</th>
                            <th class="table__cell date-started-label">Date Started Flowing</th>
                            <th class="table__cell th-status">Status</th>
                            <th class="table__cell th-status-history-label">Status History</th>
                        </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="modal-status-history" role="dialog" aria-labelledby="modal-status-history-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-status-history-title">
                        Status History
                    </h4>
                </div>
                <div class="modal-body">

                    <table id="table-status-history" class="table table-striped table-bordered dt-responsive nowrap table--align-middle" style="width:100%">
                        <thead class="table__header">
                        <tr class="table__row">
                            <th class="table__cell">Status</th>
                            <th class="table__cell">Date</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="//cdn.datatables.net/plug-ins/1.10.25/dataRender/datetime.js"></script>
<script src="$commission_engine_api_url/js/affiliate_dashboard.js?v=1.5"></script>

EOS
1;