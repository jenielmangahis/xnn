print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_sponsor_change.css?v=1" />


<div class="tool-container tool-container--default" id="sponsor-change" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Sponsor Change</h4>
            <hr />
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li class="nav-item">
                    <a class="nav-link active" href="#tab-tool" aria-controls="tab-tool" role="tab" data-toggle="tab" style="color: black !important;">Tool</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#tab-log" aria-controls="tab-log" role="tab" data-toggle="tab" style="color: black !important;">Logs</a>
                </li>
            </ul>
        </div>
        <div class="col-md-12">
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="tab-tool">
                    <div class="row mb-5">
                        <div class="col-md-4">
                            <form>
                                <div class="form-group">
                                    <label for="tree_type">Step 1: Select tree to apply change</label>
                                    <select name="tree_type" id="tree_type" class="form-control" v-model="tree_id">
                                        <option value="1" selected>Enroller Tree</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Step 2: Select which member to move</label>
                                    <select2-autocomplete-member v-bind:url="autocompleteUrl" v-model="member_id"></select2-autocomplete-member>
                                </div>
                                <div class="form-group">
                                    <label>Step 3: Select new sponsor</label>
                                    <select2-autocomplete-member v-bind:url="autocompleteUrl" v-model="sponsor_id"></select2-autocomplete-member>
                                </div>
                                <div class="form-group" v-show="!!error.message">
                                    <p class="text-danger">
                                        <i class="fa fa-exclamation-circle" aria-hidden="true"></i> {{ error.message }}
                                    </p>
                                </div>
                                <div class="form-group">
                                    <div class="checkbox">
                                    <input type="checkbox" class="form-check-input" id="updatePastOrders" value="true" v-model="update_past_orders">
                                    <label class="form-check-label">Update Representative's Past Orders</label>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button :disabled="!!is_processing" v-on:click.prevent="viewDetails" class="btn btn-primary btn-block" style="margin-top:10px" type="button">
                                        <i class="fa fa-refresh" aria-hidden="true"></i></i> Apply Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="h4 label-red">Current Relationship</div>
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
                            <div class="h4 label-red">After the Change</div>
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
                            <button :disabled="!!is_processing" class="btn btn-secondary" style="margin-top:10px" v-on:click.prevent="changeSponsor">
                                <i class="fa fa-cog fa-spin" v-if="is_processing"></i> Change Sponsor
                            </button>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab-log">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered dt-responsive nowrap" id="table-history" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                            <tr class="table__row">
                                <th class="table__cell">#</th>
                                <th class="table__cell">Representative</th>
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

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>

<script src="$commission_engine_api_url/js/admin_sponsor_change.js?v=1.2"></script>

EOS
1;


