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
        <div class="col-md-12">
            <div class="card shadow mb-4 border-bottom-primary">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-4 d-flex flex-row align-items-center justify-content-between">
                    <h5 class="m-0 font-weight-bold text-primary text-uppercase personal-energy-label">Technology Fee</h5>
                </div>
                <!-- Card Body -->
                <div class="card-body" style="padding: 1.25rem;">

                    <table id="table-members" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                        <thead class="table__header">
                        <tr class="table__row">
                            <th class="table__cell">Date</th>
                            <th class="table__cell">Invoice Number</th>
                            <th class="table__cell">Description</th>
                            <th class="table__cell">Amount</th>
                            <th class="table__cell">Status</th>
                            <th class="table__cell">Download PDF</th>
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

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="//cdn.datatables.net/plug-ins/1.10.25/dataRender/datetime.js"></script>
<script src="$commission_engine_api_url/js/affiliate_techfee.js"></script>

EOS
1;