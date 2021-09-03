print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_autoship_report.css?v=1" />
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=1.0&app=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">

<style>
    .metric-link {
        text-decoration: underline !important;
    }
</style>

<div class="tool-container tool-container--default" id="autoship" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>$autoship Report</h4>
            <hr />
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="form-horizontal">
                <div class="form-group">
                    <div class="col-md-12">
                        <label for="year_month">Select a month</label>
                        <datepicker-month-year id="year_month" v-model="yearMonth"></datepicker-month-year>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-primary" v-on:click.prevent="view">Generate Report</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <table class="table table-striped table-bordered" id="metric">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Metric</th>
                    <th class="table__cell">Amount</th>
                </tr>
                </thead>
                <tbody class="table__body">
                <tr class="table__row">
                    <td class="table__cell">Pending $autoship</td>
                    <td class="table__cell">
                        <i v-if="metrics.pendingAutoshipAmount === null" class="fa fa-spinner fa-spin"></i>
                        <a v-else class="metric-link btn-link" v-on:click.prevent="showTable('pending_autoship')">{{ metrics.pendingAutoshipAmount | money }}</a>
                    </td>
                </tr>
                <tr class="table__row">
                    <td class="table__cell">Successful $autoship</td>
                    <td class="table__cell">
                        <i v-if="metrics.successfulAutoshipAmount === null" class="fa fa-spinner fa-spin"></i>
                        <a v-else class="metric-link btn-link" v-on:click.prevent="showTable('successful_autoship')">{{ metrics.successfulAutoshipAmount | money }}</a>
                    </td>
                </tr>
                <tr class="table__row">
                    <td class="table__cell">Failed $autoship</td>
                    <td class="table__cell">
                        <i v-if="metrics.failedAutoshipAmount === null" class="fa fa-spinner fa-spin"></i>
                        <a v-else class="metric-link btn-link" v-on:click.prevent="showTable('failed_autoship')">{{ metrics.failedAutoshipAmount | money }}</a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <table class="table table-striped table-bordered" id="metric-total" >
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Metric</th>
                    <th class="table__cell">Amount</th>
                </tr>
                </thead>
                <tbody class="table__body">
                <tr class="table__row">
                    <td class="table__cell">Total $affiliate_plural and Customers</td>
                    <td class="table__cell">
                        <i v-if="metrics.memberCount === null" class="fa fa-spinner fa-spin"></i>
                        <span v-else>{{ metrics.memberCount }}</span>
                    </td>
                </tr>
                <tr class="table__row">
                    <td class="table__cell">Active $affiliate_plural and Customers on $autoship_plural</td>
                    <td class="table__cell">
                        <i v-if="metrics.activeMemberOnAutoshipCount === null" class="fa fa-spinner fa-spin"></i>
                        <a v-else class="metric-link btn-link" v-on:click.prevent="showTable('active_members_on_autoship')">{{ metrics.activeMemberOnAutoshipCount }}</a>
                    </td>
                </tr>
                <tr class="table__row">
                    <td class="table__cell">Canceled $autoship</td>
                    <td class="table__cell">
                        <i v-if="metrics.cancelledAutoshipCount === null" class="fa fa-spinner fa-spin"></i>
                        <a v-else class="metric-link btn-link" v-on:click.prevent="showTable('cancelled_autoship')">{{ metrics.cancelledAutoshipCount }}</a>
                    </td>
                </tr>
                <tr class="table__row">
                    <td class="table__cell">Average Order Size</td>
                    <td class="table__cell">
                        <i v-if="metrics.averageOrderValue === null" class="fa fa-spinner fa-spin"></i>
                        <span v-else>{{ metrics.averageOrderValue | money }}</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row" v-show="activeTable === 'pending_autoship'">
        <div class="col-md-12">
            <h4 class="pull-left">Pending $autoship</h4>
            <button
                v-on:click.prevent="generateCSV('pending_autoship')"
                class="btn btn-primary pull-right"
                style="margin-bottom: 10px;">
                Generate CSV
            </button>
            <div class="table-responsive">
                <table id="table-pending-autoship" class="table table-striped table-bordered table--align-middle table--small" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Name</th>
                        <th class="table__cell">Sponsor</th>
                        <th class="table__cell">Account Type</th>
                        <th class="table__cell">Price</th>
                        <th class="table__cell">Processing Date</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row" v-show="activeTable === 'successful_autoship'">
        <div class="col-md-12">
            <h4>Successful $autoship</h4>
            <div class="table-responsive">
                <table id="table-successful-autoship" class="table table-striped table-bordered table--align-middle table--small" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Name</th>
                        <th class="table__cell">Sponsor</th>
                        <th class="table__cell">Account Type</th>
                        <th class="table__cell">Price</th>
                        <th class="table__cell">CV</th>
                        <th class="table__cell">Processing Date</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row" v-show="activeTable === 'failed_autoship'">
        <div class="col-md-12">
            <h4>Failed $autoship</h4>
            <div class="table-responsive">
                <table id="table-failed-autoship" class="table table-striped table-bordered table--align-middle table--small" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Name</th>
                        <th class="table__cell">Sponsor</th>
                        <th class="table__cell">Account Type</th>
                        <th class="table__cell">Price</th>
                        <th class="table__cell">CV</th>
                        <th class="table__cell">Processing Date</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="row" v-show="activeTable === 'cancelled_autoship'">
        <div class="col-md-12">
            <h4>Canceled $autoship</h4>
            <div class="table-responsive">
                <table id="table-cancelled-autoship" class="table table-striped table-bordered table--align-middle table--small" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Name</th>
                        <th class="table__cell">Sponsor</th>
                        <th class="table__cell">Account Type</th>
                        <th class="table__cell">Price</th>
                        <th class="table__cell">CV</th>
                        <th class="table__cell">Processing Date</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row" v-show="activeTable === 'active_members_on_autoship'">
        <div class="col-md-12">
            <h4 class="pull-left">Active $affiliate_plural and Customers on $autoship_plural</h4>
            <button
                v-on:click.prevent="generateCSV('active_members_on_autoship')"
                class="btn btn-primary pull-right"
                style="margin-bottom: 10px;">
                Generate CSV
            </button>
            <div class="table-responsive">
                <table id="table-active-members-on-autoship" class="table table-striped table-bordered table--align-middle table--small" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Name</th>
                        <th class="table__cell">Sponsor</th>
                        <th class="table__cell">Account Type</th>
                        <th class="table__cell">Price</th>
                        <th class="table__cell">Processing Date</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/admin_autoship_report.js"></script>

EOS
1;