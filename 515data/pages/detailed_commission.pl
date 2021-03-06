print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.theme.default.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_enroller_tree.css?v=1" />

<style>

.tt-hint {
    width: 100%;
    height: 30px;
    padding: 15px 12px;
    font-size: 14px;
    line-height: 30px;
    border: 2px solid #ccc;
    border-radius: 8px;
    outline: none;
}

.tt-query {
    /* UPDATE: newer versions use tt-input instead of tt-query */
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
}

.tt-hint {
    color: #999;
}

.tt-menu {
    /* UPDATE: newer versions use tt-menu instead of tt-dropdown-menu */
    width: 100%;
    margin-top: 12px;
    padding: 8px 0;
    background-color: #fff;
    border: 1px solid #ccc;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
    max-height: 500px;
    overflow-y: auto;
}

.tt-suggestion {
    padding: 8px 20px;
    font-size: 14px;
    line-height: 24px;
    cursor: pointer;
}

.tt-suggestion:hover {
    color: #f0f0f0;
    background-color: #818956;
}

.tt-suggestion p {
    margin: 0;
}

.twitter-typeahead {
    width: 100%;
}

.temp-parent-txt {
    width: 100%;
}

.empty-message {
    padding: 5px 10px;
}

.red {
    color: #A94442;
}

.cancel-btn {
    background-color: #EEEEEE;
    position: relative;
    display: inline-block;
    top: -29px;
    right: 6px;
    float: right;
}

.loader {
    position: relative;
    display: inline-block;
    top: -26px;
    right: 6px;
    float: right;
}

.typeahead-container {}

.height-52 {
    max-height: 30px;
}

.white {
    color: #fff;
}

.form-container {
    border: 1px solid #DDDDDD;
    padding: 20px;
    height: 160px;
    width: 50%;
    float: right;
}

.clear-typeahead {
    background-color: #fff;
    position: relative;
    display: inline-block;
    float: right;
    margin-top: -50px;
    margin-right: -17px;
    border-radius: 50%;
}

.box-border {
    width: 45%;
    padding: 15px;
    border-style: solid;
    border-width: 1px;
    border-color: #DDDDDD;
    margin: 0;
}
.btn-default {
z-index: 0 !important;
}
</style>

<div id="enroller-tree" class="enroller-tree tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Detailed Commission</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
        <p>test</p>
        </div>   
    </div>

    <div class="modal fade" id="modal-pea" role="dialog" aria-labelledby="modal-pea-label">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-pea-label">
                        Personal Energy Accounts
                    </h4>
                </div>
                <div class="modal-body">
                    <table id="table-pea" class="table table table-striped" style="width:100%" cellspacing="0" width="100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Customer Name</th>
                            <th class="table__cell">Date Accepted</th>
                            <th class="table__cell">Date Started Flowing</th>
                            <th class="table__cell">Status</th>
                        </tr>

                        </thead>
                        <tbody class="table__body">
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" data-dismiss="modal">Close</button>
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
<script src="$commission_engine_api_url/js/plugins/typeahead.bundle.js"></script>
<script src="$commission_engine_api_url/js/affiliate_enroller_tree.js"></script>

EOS
1;