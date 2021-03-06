print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=1.0&app=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_receipts_details.css?v=1" />

<div id="receipts-details" class="receipts-details tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="receipts-details-label">Receipts Details</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <form class="form-horizontal">
                <div class="form-group">
                    <div class="col-sm-4 translatable">
                        <label class="from-label">From</label>
                        <datepicker  v-model="start_date" :option="options.language" v-bind:end-date="today"></datepicker>
                    </div>
                    <div class="col-sm-4 translatable">
                        <label class="to-label">To</label>
                        <datepicker  v-model="end_date" :option="options.language" v-bind:start-date="start_date" v-bind:end-date="today"></datepicker>
                    </div>
                    <div class="col-sm-4 ">
                        <label>&nbsp;</label>
                        <button
                                type="button"
                                class="btn btn-primary btn-block receipts-view-button view-btn-label"
                                v-on:click.prevent="viewReceipts">
                            View
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table id="table-receipts-details" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell th-date-label">Date</th>
                    <th class="table__cell th-receipt-number-label">Receipt No</th>
                    <th class="table__cell th-payment-bank-label">Bank Payment Ref.</th>
                    <th class="table__cell th-month-reference-label">Month of Reference</th>
                    <th class="table__cell th-download-label">Download</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://nxmcdn.com/js/515/vo-assets/bootstrap-datepicker.js" ></script>
<script src="https://nxmcdn.com/js/515/vo-assets/locales/bootstrap-datepicker.it.js" charset="UTF-8"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/affiliate_receipts_details.js?v=1.1"></script>


EOS
1;