print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_sponsor_change.css?v=1" />

<div class="tool-container tool-container--default" id="sponsor-change" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">Sponsor Change</h4>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <nav>
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <a class="nav-item nav-link active" id="nav-tool-tab" data-toggle="tab" href="#nav-tool" role="tab" aria-controls="nav-tool" aria-selected="true">Tool</a>
                <a class="nav-item nav-link" id="nav-logs-tab" data-toggle="tab" href="#nav-logs" role="tab" aria-controls="nav-logs" aria-selected="false">Logs</a>
            </div>
            </nav>
        </div>
        <!-- Tab panes -->
        <div class="col-md-12">
            <div class="tab-content" id="nav-tabContent">
                <div  class="tab-pane fade show active"  id="nav-tool" role="tabpanel" aria-labelledby="nav-tool-tab">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="form-horizontal font-weight-bold">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="tree_type">Step 1: Select tree to apply change</label>
                                        <select name="tree_type" id="tree_type" class="form-control" v-model="tree_id">
                                            <option value="1" selected>Enroller Tree</option>
                                            <option value="2">Binary Tree</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-8"></div>
                                    <div class="form-group col-md-4">
                                        <label>Step 2: Select which member to move</label>
                                        <select2-autocomplete-member v-bind:url="autocompleteUrl" v-model="member_id"></select2-autocomplete-member>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Step 3: Select new sponsor</label>
                                        <select2-autocomplete-member v-bind:url="autocompleteUrl" v-model="sponsor_id"></select2-autocomplete-member>
                                    </div>
                                    <div v-if="tree_id == 2" class="form-group col-md-4">
                                        <label for="leg_position">Step 4: Select Leg position</label>
                                        <select name="leg_position" id="leg_position" class="form-control" v-model="leg_position">
                                            <option value="0" selected>Left Leg</option>
                                            <option value="1">Right Leg</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">    
                                    <div class="form-group col-md-12" v-show="!!error.message">
                                        <p class="text-danger">
                                            <i class="fa fa-exclamation-circle" aria-hidden="true"></i> {{ error.message }}
                                        </p>
                                    </div>
                                        <!-- START UPDATE ORDERS FILTER -->
                                    <div class="form-group col-md-4 pt-4 mt-3">
                                        <div class="checkbox">
                                        <input type="checkbox" class="form-check-input" id="updatePastOrders" value="true" v-model="update_past_orders">
                                        <label class="form-check-label">Update Member's Past Orders</label>
                                        </div>
                                    </div>
                                    <!-- END UPDATE ORDERS FILTER -->
                                    <div class="form-group col-md-4">
                                        <button :disabled="!!is_processing" v-on:click.prevent="viewDetails" class="new-btn-mba generate-width btn btn-primary" style="margin-top:10px" type="button">
                                            <i class="fa fa-refresh" aria-hidden="true"></i> Apply Changes
                                        </button>
                                    </div>
                                 </div>    
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="h4 label-mba">Current Relationship</div>
                             <div class="table-responsive">
                            <table class="table table-striped table-bordered table-relationship">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Level</th>
                                    <th>Sponsor</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-show="relationship.before.length == 0">
                                    <td colspan="4" class="text-center">
                                        <i class="fa fa-cog fa-spin" v-if="is_processing"></i>
                                        <span v-else>No preview available</span>
                                    </td>
                                </tr>
                               <tr v-for="(item, index) in relationship.before">
                                    <td v-if="item.message != undefined" colspan="4" class="text-center">
                                        {{ item.message }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        #{{ item.member_id }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        {{ item.member_name }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        {{ item.level }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        #{{ item.sponsor_id }} {{ item.sponsor_name }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="h4 label-mba">After the Change</div>
                            <div class="table-responsive">
                            <table class="table table-striped table-bordered table-relationship">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Level</th>
                                    <th>Sponsor</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-show="relationship.after.length == 0">
                                    <td colspan="4" class="text-center">
                                        <i class="fa fa-cog fa-spin" v-if="is_processing"></i>
                                        <span v-else>No preview available</span>
                                    </td>
                                </tr>
                                <tr v-for="(item, index) in relationship.after">
                                    <td v-if="item.message != undefined" colspan="4" class="text-center">
                                        {{ item.message }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        #{{ item.member_id }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        {{ item.member_name }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        {{ item.level }}
                                    </td>
                                    <td v-if="item.message == undefined">
                                        #{{ item.sponsor_id }} {{ item.sponsor_name }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>

                    <div class="row" v-if="relationship.before.length > 0 && relationship.after.length > 0">
                        <div class="col-md-12">
                            <button :disabled="!!is_processing" class="btn btn-success" style="margin-top:10px" v-on:click.prevent="changeSponsor">
                                <i class="fa fa-cog fa-spin" v-if="is_processing"></i> Change Sponsor
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="nav-logs" role="tabpanel" aria-labelledby="nav-logs-tab">
                <div class="row form-group">
                    <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="table-history" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">#</th>
                                <th class="table__cell">Member</th>
                                <th class="table__cell">New Sponsor</th>
                                <th class="table__cell">Old Sponsor</th>
                                <th class="table__cell">Tree</th>
                                <th class="table__cell">Move By</th>
                                <th class="table__cell">Past Orders Updated</th>
                                <th class="table__cell">Date</th>
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
    </div>
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="$commission_engine_api_url/js/admin_sponsor_change.js?v=2.2"></script>

EOS
1;

