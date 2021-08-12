print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">

<div id="rewards" class="tool-container tool-container--default" v-cloak >
    <div class="row">
        <div class="col-md-12">
            <h4>Customer Dashboard</h4>
            <hr />
        </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
    <div class="row">
        <div class="col-md-12">
            <h4>Gift Card</h4>
        </div>
        <div class="col-md-12">
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-gift-cards" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Code</th>
                    <th class="table__cell">Period Earned</th>
                    <th class="table__cell">Amount</th>
                    <th class="table__cell">Balance</th>
                    <th class="table__cell">Expiration Date</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <h4>Coupon</h4>
        </div>
        <div class="col-md-12">
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-coupons" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Name</th>
                    <th class="table__cell">No of Items</th>
                    <th class="table__cell">Expiration Date</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/affiliate_rewards.js?v=1.2"></script>


EOS
1;