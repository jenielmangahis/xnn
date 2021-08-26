print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.theme.default.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/member-menu.css" />
<style>
.main-container {
    margin-bottom: 20px;
    background-color: #fff;
    border: 1px solid transparent;
    border-radius: 4px;
    padding: 15px;}

.table__header { background-color:#9aa1a5; color:#fff; }

.form-select {
    display: block;
    width: 100%;
    padding: 7px 15px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
 }   

</style>


<div id="enroller-tree" class="enroller-tree tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Enroller Tree</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-group">
                    <div class="col-md-3">
                        <label>Filter By</label>
                        <select2-autocomplete-member v-on:select-change="selectionChange" :id="downline_id" :url="autocomplete_url" v-model="downline_id"></select2-autocomplete-member>
                         <div class="clearfix"></div>
                            <select class="form-select" aria-label="Default select example">
                            <option value="1">Associate ID</option>
                            <option value="2">Two</option>
                            <option value="3">Three</option>
                            </select>                        
                        <div class="clearfix"></div>
                      <label>Date</label>
                      <input type="text" class="form-control">     
                        <button type="button" class="btn btn-primary" style="margin-top:10px;float:right;">
                            Apply Filter
                         </button>                                         
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="table-enroller" class="table table-striped table-bordered table--small">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Associate ID</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Name</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Member Type</th>                        
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Enrollment Date</th>                                             
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Paid-as $rank_title</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">PV</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">GV</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Level</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Sponsor</th>
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
                    <h4 class="modal-title" id="modal-order-history-label">
                        Order History<br/>
                        ID: {{ order_history_user_id }}<br/>
                        NAME: {{ order_history_name }}
                    </h4>
                </div>
                <div class="modal-body">
                    <table id="table-order-history" class="table table table-striped" style="width:100%" cellspacing="0" width="100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Order ID</th>
                            <th class="table__cell">Products</th>
                            <th class="table__cell">Date</th>
                            <th class="table__cell">Paid<br>Amount</th>
                        </tr>
                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
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