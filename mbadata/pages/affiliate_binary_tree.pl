print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/2.1.8/css/jquery.orgchart.min.css"  />
<link rel="stylesheet" href="https://unpkg.com/tippy.js\@6/themes/light.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_binary_tree.css?v=1" />

<div class="binary-tree tool-container tool-container--default"  id="binary-tree" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5"> Binary Tree</h4>
        </div>
    </div>
<div class="mba-money-border"> 
    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" style="margin-bottom: 15px;">

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <div class="table-responsive">
                            <table id="table-left-leg" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header ">
                                <tr class="table__row">
                                    <th colspan="3" class="table__cell text-center">LEFT LEG</th>
                                </tr>
                                <tr class="table__row">
                                    <th class="table__cell">Carry Over</th>
                                    <th class="table__cell">Today</th>
                                    <th class="table__cell">Total</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                <tr class="table__row">
                                   <td class="table__cell table__cell--align-middle">
                                        <span v-if="member.carry_over_volume_left === null">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                        <span v-else>{{ member.carry_over_volume_left }}</span>
                                   </td>
                                   <td class="table__cell table__cell--align-middle">
                                        <span v-if="member.current_group_volume_left === null">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                        <span v-else>{{ member.current_group_volume_left }}</span>
                                   </td>
                                   <td class="table__cell table__cell--align-middle">
                                        <span v-if="member.total_group_volume_left === null">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                        <span v-else>{{ member.total_group_volume_left }}</span>
                                   </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group col-md-4">
                        <div class="table-responsive">
                            <table id="table-left-leg" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                                <thead class="table__header ">
                                <tr class="table__row">
                                    <th colspan="3" class="table__cell text-center">RIGHT LEG</th>
                                </tr>
                                <tr class="table__row">
                                    <th class="table__cell">Carry Over</th>
                                    <th class="table__cell">Today</th>
                                    <th class="table__cell">Total</th>
                                </tr>
                                </thead>
                                <tbody class="table__body">
                                <tr class="table__row">
                                    <td class="table__cell table__cell--align-middle">
                                        <span v-if="member.carry_over_volume_right === null">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                        <span v-else>{{ member.carry_over_volume_right }}</span>
                                   </td>
                                   <td class="table__cell table__cell--align-middle">
                                        <span v-if="member.current_group_volume_right === null">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                        <span v-else>{{ member.current_group_volume_right }}</span>
                                   </td>
                                   <td class="table__cell table__cell--align-middle">
                                        <span v-if="member.total_group_volume_right === null">
                                            <i class="fa fa-spinner fa-spin"></i>
                                        </span>
                                        <span v-else>{{ member.total_group_volume_right }}</span>
                                   </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group col-md-offset-3 col-md-4">
                        <div id="preference-header" class="panel-heading text-capitalize text-center preference-header">
                                Placement Preference
                            </div>
                        <div class="panel panel-primary mx-3 px-2 border">
                                <div class="panel-body">
                                    <form>
                                        <div class="custom-group">
                                            <div class="radio">
                                                <input type="radio" name="placement_preference" id="PREFERENCE_LEFT_LEG" value="LEFT_LEG"
                                                    v-model="placement_preference"
                                                    v-on:change.prevent="updatePlacementPreference"
                                                    :disabled="placement_preference === null || is_updating_preference === 1">
                                                <label for="PREFERENCE_LEFT_LEG">
                                                    Left Leg <span v-if="is_updating_preference === 1 && placement_preference === 'LEFT_LEG'"><i class="fa fa-spinner fa-spin"></i></span>
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <input type="radio" name="placement_preference" id="PREFERENCE_RIGHT_LEG" value="RIGHT_LEG"
                                                    v-model="placement_preference"
                                                    v-on:change.prevent="updatePlacementPreference"
                                                    :disabled="placement_preference === null || is_updating_preference === 1">
                                                <label for="PREFERENCE_RIGHT_LEG">
                                                    Right Leg <span v-if="is_updating_preference === 1 && placement_preference === 'RIGHT_LEG'"><i class="fa fa-spinner fa-spin"></i></span>
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <input type="radio" name="placement_preference" id="PREFERENCE_LESSER_VOLUME_LEG" value="LESSER_VOLUME_LEG"
                                                    v-model="placement_preference"
                                                    v-on:change.prevent="updatePlacementPreference"
                                                    :disabled="placement_preference === null || is_updating_preference === 1">
                                                <label for="PREFERENCE_LESSER_VOLUME_LEG">
                                                    Lesser Volume Leg <span v-if="is_updating_preference === 1 && placement_preference === 'LESSER_VOLUME_LEG'"><i class="fa fa-spinner fa-spin"></i></span>
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8 ">
                        
                    </div>
                </div>
            </form>
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

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" v-if="breadcrumb.length != 0">
                    <li class="breadcrumb-item">
                        <a href="#"  v-on:click="loadMatrixTree(root_id)">Root</a>
                    </li>
                    <li v-for="b in breadcrumb" class="breadcrumb-item" :class="{'active': b.user_id == parent_id}">
                        <a href="#" v-if="b.user_id != parent_id" v-on:click="loadMatrixTree(b.user_id)">{{ b.name }}</a>
                        <span v-else>{{ b.name }}</span>
                    </li>
                </ol>
            </nav>
            <div id="chart-container"></div>
        </div>
    </div>
</div>
</div>

<!-- Development version -->
<script src="https://unpkg.com/\@popperjs/core\@2/dist/umd/popper.js"></script>
<script src="https://unpkg.com/tippy.js\@6/dist/tippy-bundle.umd.js"></script>

<!-- Production version -->
<!--<script src="https://unpkg.com/\@popperjs/core\@2"></script>-->
<!--<script src="https://unpkg.com/tippy.js\@6"></script>-->


<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/2.1.8/js/jquery.orgchart.js" ></script>
<script src="$commission_engine_api_url/js/affiliate_binary_tree.js?v=1.0&app=$app_js_version"></script>
EOS
1;