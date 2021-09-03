print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/jquery.treetable.theme.default.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">


<div id="placement-tree" class="placement-tree tool-container tool-container--default">
    <div class="row">
        <div class="col-md-12">
            <h4>Placement Tree</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-sm-12">
            <h5>Holding Tank</h5>

            <div class="table-responsive">
                <table class="table table-striped table-bordered table--small">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell table__cell--text-center table__cell--align-middle">ID</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Name</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Current $rank_title</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Days Left</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle" colspan="2">New Sponsor</th>
                    </tr>
                    </thead>
                    <tbody class="table__body">
                    <tr class="table__row" v-for="(member, index) in unplacedMembers"
                        v-bind:key="member.user_id">
                        <td class="table__cell table__cell--align-middle">{{ member.user_id }}</td>
                        <td class="table__cell table__cell--align-middle">{{ member.member }}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">{{ member.paid_as_rank }}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">{{ member.days_left }}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle" >
                            <select2-autocomplete-member container-css-class=":all:" size="sm" v-bind:url="autocomplete_url" v-model="member.new_sponsor_id"></select2-autocomplete-member>
                        </td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">
                            <button class="btn btn-success btn-sm" v-on:click.prevent="placeMember(member)">Place</button>
                        </td>
                    </tr>
                    <tr class="table__row" v-if="unplacedMembersState === 'fetching'">
                        <td colspan="6" class="table__cell table__cell--text-center table__cell--align-middle">
                            <i class="fa fa-spinner fa-spin"></i>
                        </td>
                    </tr>
                    <tr class="table__row" v-else-if="unplacedMembersState === 'error'">
                        <td colspan="6" class="table__cell table__cell--text-center table__cell--align-middle">
                            <a v-on:click.prevent="getUnplacedMembers" class="btn btn-link">Unable to fetch data. Click here to try again</a>
                        </td>
                    </tr>
                    <tr class="table__row" v-else-if="unplacedMembersState === 'loaded' && unplacedMembers.length === 0">
                        <td colspan="6" class="table__cell table__cell--text-center table__cell--align-middle">
                            All members are placed in the placement tree.
                        </td>
                    </tr>
                    </tbody>
                    <tfoot class="table__footer">
                    <tr>
                        <th colspan="6" class="text-danger text-right small">
                            <em>* Unplaced members after 30 days will be automatically placed in the first tier.</em>
                        </th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-group">
                    <div class="col-md-4">
                        <label>Filter Downline</label>
                        <select2-autocomplete-member v-on:select-change="selectionChange" :id="downline_id" :url="autocomplete_url" v-model="downline_id"></select2-autocomplete-member>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="table-placement" class="table table-striped table-bordered table--small">
                    <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell table__cell--text-center table__cell--align-middle">ID</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Name</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Paid-as $rank_title</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Coach Points</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Referral Points</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Org. Points</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Team Group Points</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Order History</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle" style="max-width: 100px">Orders in Last 30 Days</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Enrollment</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Gen.</th>
                        <th class="table__cell table__cell--text-center table__cell--align-middle">Enroller</th>
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
<script src="$commission_engine_api_url/js/affiliate_placement_tree.js"></script>

EOS
1;