print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/custom.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.theme.default.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">

<style>

\@media (max-width: 768px){
.tool-container .table-responsive .table-bordered {
    border: 0!important;
    font-size:9px !important;
}
}

</style>

<div id="enroller-tree" class="enroller-tree tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Enroller Tree</h4>
            <hr />
        </div>
    </div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-group">
                    <div class="col-md-3">
                        <label>Search By</label>
                        <select2-autocomplete-member v-on:select-change="selectionChange" :id="downline_id" :url="autocomplete_url" v-model="downline_id"></select2-autocomplete-member>
                    </div>
                     <div class="col-md-3">
                     <!-- STATIC INPUT -->
                      <label>Search Key</label>
                      <input type="text" class="form-control"  placeholder="Enter Keyword...">
                     </div>
                     <div class="col-md-3"></div>
                     <div class="col-md-3"></div>
                     <div class="col-md-9"></div>
                     <div class="col-md-3">
                        <button type="button" class="btn btn-primary" style="margin-top:10px;float:right;">
                            Apply Filter
                         </button>
                     </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="table-enroller" class="table table-striped table-bordered table--small dt-responsive nowrap collapsed">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Consultant ID</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Name</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Enrollment Date</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Current $rank_title</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Paid-as $rank_title</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">PV</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">L1V</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Orders</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Qualified</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Active</th>    
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Level</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Enrollement Sponsor</th>
                      
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-order-history" role="dialog" aria-labelledby="modal-order-history-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h5 class="modal-title" id="modal-order-history-label">
                        Order History<br/>
                        ID: {{ order_history_user_id }}<br/>
                        NAME: {{ order_history_name }}
                    </h5>
                </div>
                <div class="modal-body">
                <div class="table-responsive">
                    <table id="table-order-history" class="table table table-striped dt-responsive nowrap collapsed" style="width:100%" cellspacing="0" width="100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Invoice No.</th>
                            <th class="table__cell">Sponsor</th>
                            <th class="table__cell">Products</th>
                            <th class="table__cell">Net Total</th>
                            <th class="table__cell">CV</th>
                            <th class="table__cell">QV</th>
                            <th class="table__cell">Date</th>
                        </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="$commission_engine_api_url/js/jquery.treetable.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="$commission_engine_api_url/js/affiliate_enroller_tree.js"></script>

EOS
1;