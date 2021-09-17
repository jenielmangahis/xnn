print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_incentives.css?v=1" />
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<style>
    .btn-action {
        width: 30px;
        height: 30px;
        padding: 4px 9px !important;
        font-size: 12px !important;
    }

    .table i {
        font-size: 12px;
    }
</style>

<div id="transactions-report" class="tool-container tool-container--default admin-incentive-tool">

    <div class="row">
        <div class="col-md-12 mb-4">
            <h4>Incentives</h4>
            <hr />
        </div>
    </div>

    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <a class="nav-item nav-link active" id="nav-running-incentives-tab" data-toggle="tab" href="#nav-running-incentives" role="tab" aria-controls="nav-running-incentives" aria-selected="true">Running Incentives</a>
            <a class="nav-item nav-link" id="nav-close-incentives-tab" data-toggle="tab" href="#nav-close-incentives" role="tab" aria-controls="nav-close-incentives" aria-selected="false">Closed Incentives</a>
            <a class="nav-item nav-link" id="nav-arbitrary-points-tab" data-toggle="tab" href="#nav-arbitrary-points" role="tab" aria-controls="nav-arbitrary-points" aria-selected="false">Arbitrary Points</a>
        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">

        <div class="tab-pane fade show active" id="nav-running-incentives" role="tabpanel" aria-labelledby="nav-running-incentives-tab">

            <div class="row">

                <div class="col-md-12 text-right mb-3">
                    <button class="btn btn-secondary btn-sm add-new-style" v-on:click.prevent="addIncentive()"><i class="fa fa-plus-circle" aria-hidden="true"></i> Add Incentive</button>
                </div>

            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="running_incentives" class="table table-striped table-bordered" style="width:100%">
                            <thead class="com-report-header table__header table__header--bg-primary">
                                <tr class="com-report-header table__row">
                                    <th class="com-report-header table__cell">Name</th>
                                    <th class="com-report-header table__cell">Period</th>
                                    <th class="com-report-header table__cell text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">

                            </tbody>
                        </table>
                    </div>
                </div>   
            </div>


        </div>

        <div class="tab-pane fade" id="nav-close-incentives" role="tabpanel" aria-labelledby="nav-close-incentives-tab">
            
            
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="close_incentives" class="table table-striped table-bordered" style="width:100%">
                            <thead class="com-report-header table__header table__header--bg-primary">
                                <tr class="com-report-header table__row">
                                    <th class="com-report-header table__cell">Name</th>
                                    <th class="com-report-header table__cell">Period</th>
                                    <th class="com-report-header table__cell text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                            </tbody>
                        </table>
                    </div> 
                </div>   
            </div>


        </div>

        <div class="tab-pane fade" id="nav-arbitrary-points" role="tabpanel" aria-labelledby="nav-arbitrary-points-tab">
           
           <div class="row">
                <div class="col-md-4">

                    <div class="row">
                        <div class="col-md-12"> 
                            <div class="form-group">
                                <select 
                                    class="form-control"
                                    name="representatives"
                                    id="incentive_id"
                                    v-model="arbitrary.incentive_id">
                                    <option class="assoc-id-label" value="0">Select an Incentive</option>
                                    <option v-for="(incentive, index) in arbitrary.openIncentives"
                                            v-bind:value="incentive.id"
                                            v-bind:key="incentive.id">
                                        {{ incentive.title }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">  
                            <div class="form-group">
                                <label>Select Representative</label>
                                <select class="form-control" id="member-filter-by">
                                    <option value="id" class="assoc-id-label">Representative ID</option>
                                    <option value="fname" class="drop-firstname-label">First Name</option>
                                    <option value="lname" class="drop-lastname-label">Last Name</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">  
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <input type="text" class="form-control search-key-first-name" style="margin-bottom: 5px;display: none;" placeholder="First Name" />
                                    <input type="text" class="form-control search-key-last-name" style="margin-bottom: 5px;display: none;" placeholder="Last Name" />
                                    <input type="hidden" class="hidden-id required" value="0" id="hidden-member-id" />
                                    <input type="text" class="form-control display hide" value="" id="member-display" disabled="">
                                    <input type="text" class="typeahead form-control txt-input" name="typeahead-member-name" id="typeahead-member-name" placeholder="Search Here">
                                    <button class="btn btn-default clear-typeahead hide">
                                        <i class="fa fa-close red"></i>
                                    </button>
                                    <span><i class="fa fa fa-spinner fa-spin loader hide"></i></span>
                                    <span class="error-message wError"></span>
                                    <span class="success-message"></span> <br>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="number" min="0" step="1"
                                    class="form-control" 
                                    v-model="arbitrary.bonus_points"
                                    placeholder="Bonus Points">
                            </div>

                            <div class="form-group">
                                <button class="btn btn-primary btn-block"
                            v-on:click.prevent="addBonusPoints()">ADD</button>
                            </div>
                        </div>

                    </div>
                    
                    
                </div>
            </div>
           
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table id="arbitrary_points" class="table table-striped table-bordered" style="width:100%">
                            <thead class="com-report-header table__header table__header--bg-primary">
                                <tr class="com-report-header table__row">
                                    <th class="com-report-header table__cell">ID</th>
                                    <th class="com-report-header table__cell">Representative Name</th>
                                    <th class="com-report-header table__cell">Incentives</th>
                                    <th class="com-report-header table__cell">Points</th>
                                    <th class="com-report-header table__cell">Bonus Points</th>
                                    <th class="com-report-header table__cell">Total Points</th>
                                    <th class="com-report-header table__cell">Actions</th>
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
    <div class="modal fade" id="add-incentive" tabindex="-1" role="dialog" aria-labelledby="add-incentive" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                
                    <h4 class="modal-title" v-if="settings.id > 0">Update Incentive</h4>
                    <h4 class="modal-title" v-else>Add Incentive</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="nav-description-tab" data-toggle="tab" href="#nav-description" role="tab" aria-controls="nav-description" aria-selected="true">Description</a>
                            <a class="nav-item nav-link" id="nav-rules-tab" data-toggle="tab" href="#nav-rules" role="tab" aria-controls="nav-rules" aria-selected="false">Rules</a>
                        </div>
                    </nav>

                    <div class="tab-content" id="nav-tabContent">

                        <div class="tab-pane fade show active" id="nav-description" role="tabpanel" aria-labelledby="nav-description-tab">
                        
                            <form>
                                <div class="form-group">
                                    <label for="title">Title*</label>
                                    <input type="text" class="form-control input-md" v-model="settings.title">
                                </div>

                                <div class="form-group">
                                    <label for="description">Descriptions*</label>
                                    <textarea class="form-control input-md" rows="3" v-model="settings.description"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="period">Period*</label>
                                    <input type="text" name="period_range" id="period_range" class="form-control">
                                </div>

                                <ul class="incentive-ul2 mt-4">
                                    <li class="custom-checkbox-container">
                                        <label class="label-container2 checkbox-text-holder montserrat-regular grey-text-color">
                                            <strong>Display Incentive to the Representative</strong>
                                            <input 
                                            type="checkbox"
                                            id="is_display_insentives"
                                            v-model="settings.is_display_insentives"
                                            true-value="1"
                                            false-value="0">
                                            <span class="checkmark2"></span>
                                        </label>
                                    </li>
                                </ul>

                                <hr>
                                <div class="text-right">
                                    <button type="button" class="prev-tab btn btn-dark disabled">Prev</button>
                                    <button type="button" class="next-tab btn btn-dark" data-toggle="tab" href="#nav-rules" role="tab">Next</button>
                                </div>

                            </form>

                        </div>

                        <div class="tab-pane fade show" id="nav-rules" role="tabpanel" aria-labelledby="nav-rules-tab">
                        
                            <form>

                                <div class="row">
                                    <div class="col-md-12">

                                        <ul class="incentive-ul2">
                                            <li class="custom-checkbox-container">
                                                <label class="label-container2 checkbox-text-holder montserrat-regular grey-text-color">
                                                    <strong>Double Points On:</strong>
                                                    <input 
                                                        type="checkbox"
                                                        id="is_double_points_on"
                                                        v-model="settings.is_double_points_on"
                                                        true-value="1"
                                                        false-value="0"
                                                    >
                                                    <span class="checkmark2"></span>
                                                </label>
                                            </li>
                                        </ul>

                                    </div>
                                </div>
                                
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        
                                        <div class="input-group mt-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">From:</span>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="date-from" 
                                                class="form-control input-md" 
                                                v-model="settings.double_points_start_date"
                                                :disabled="settings.is_double_points_on == 0"
                                                v-bind:date-from="settings.double_points_start_date" 
                                                onkeydown="return false"/>
                                        </div>

                                    </div>
                                    <div class="col-md-6">

                                        <div class="input-group mt-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">To:</span>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="date-to" 
                                                class="form-control input-md"
                                                v-model="settings.double_points_end_date"
                                                :disabled="settings.is_double_points_on == 0"
                                                v-bind:date-to="settings.double_points_end_date" 
                                                onkeydown="return false"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <ul class="incentive-ul2 mt-4">
                                            <li class="custom-checkbox-container">
                                                <label class="label-container2 checkbox-text-holder montserrat-regular grey-text-color">
                                                    <strong>Point for every PRS:</strong>
                                                    <input 
                                                        type="checkbox"
                                                        id="is_points_per_prs"
                                                        v-model="settings.is_points_per_prs"
                                                        true-value="1"
                                                        false-value="0"
                                                    >
                                                    <span class="checkmark2"></span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mt-2">
                                            <input type="number" min="0" step="1"
                                            class="form-control input-md" 
                                            v-model="settings.points_per_prs"
                                            :disabled="settings.is_points_per_prs == 0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <ul class="incentive-ul2 mt-4">
                                            <li class="custom-checkbox-container">
                                                <label class="label-container2 checkbox-text-holder montserrat-regular grey-text-color">
                                                    <strong>Promote to or higher than: = </strong>
                                                    <input 
                                                        type="checkbox"
                                                        id="is_promote_to_or_higher"
                                                        v-model="settings.is_promote_to_or_higher"
                                                        true-value="1"
                                                        false-value="0"
                                                    >
                                                    <span class="checkmark2"></span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="col-md-6">

                                        <div class="input-group mt-2">
                                            <input type="number" min="0" step="1" 
                                                class="form-control input-md" 
                                                v-model="settings.promote_to_or_higher_points"
                                                :disabled="settings.is_promote_to_or_higher == 0"
                                            >
                                            <div class="input-group-append">
                                                <span class="input-group-text">Point/s</span>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-6">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mt-2">
                                            <select 
                                                class="form-control"
                                                name="rank_id"
                                                id="rank_id"
                                                v-model="settings.rank_id"
                                                :disabled="settings.is_promote_to_or_higher == 0"
                                            >
                                                <option>Select Rank</option>
                                                <option v-for="(rank, index) in ranks"
                                                        v-bind:value="rank.id"
                                                        v-bind:key="rank.id">
                                                    {{ rank.name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <ul class="incentive-ul2 mt-4">
                                            <li class="custom-checkbox-container">
                                                <label class="label-container2 checkbox-text-holder montserrat-regular grey-text-color">
                                                    <strong>Has enrolled new Representative: = </strong>
                                                    <input 
                                                        type="checkbox"
                                                        id="is_has_new_representative"
                                                        v-model="settings.is_has_new_representative"
                                                        true-value="1"
                                                        false-value="0"
                                                    >
                                                    <span class="checkmark2"></span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group mt-2">
                                            <input type="number" min="0" step="1" 
                                                class="form-control input-md" 
                                                v-model="settings.new_representative_points"
                                                :disabled="settings.is_has_new_representative == 0">
                                            <div class="input-group-append">
                                                <span class="input-group-text">Point/s</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        
                                        <div class="input-group mt-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Date Range:</span>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="range-date-from" 
                                                class="form-control input-md" 
                                                v-model="settings.new_representative_start_date"
                                                :disabled="settings.is_has_new_representative == 0"
                                            >
                                        </div>

                                    </div>
                                    <div class="col-md-6">

                                        <div class="input-group mt-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">To:</span>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="range-date-to" 
                                                class="form-control input-md" 
                                                v-model="settings.new_representative_end_date"
                                                :disabled="settings.is_has_new_representative == 0"
                                                >
                                        </div>

                                        <div class="input-group mt-4">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Minimum PRS:</span>
                                            </div>
                                            <input type="number" min="0" step="1"
                                                class="form-control input-md" 
                                                v-model="settings.new_representative_min_prs"
                                                :disabled="settings.is_has_new_representative == 0">
                                        </div>

                                        <div class="input-group mt-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">First:</span>
                                            </div>
                                            <input type="number" min="1" step="1"
                                                class="form-control input-md" 
                                                v-model="settings.new_representative_first_n_days"
                                                :disabled="settings.is_has_new_representative == 0"
                                            >
                                            <div class="input-group-append">
                                                <span class="input-group-text">days:</span>
                                            </div>
                                        </div>
                                
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <ul class="incentive-ul2 mt-4">
                                            <li class="custom-checkbox-container">
                                                <label class="label-container2 checkbox-text-holder montserrat-regular grey-text-color">
                                                    <strong>Double Points For New Representative:</strong>
                                                    <input 
                                                        type="checkbox"
                                                        id="is_double_points_new_representative"
                                                        v-model="settings.is_double_points_new_representative"
                                                        true-value="1"
                                                        false-value="0">
                                                    <span class="checkmark2"></span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="row mt-2 mb-5">
                                    <div class="col-md-6">
                                        
                                        <div class="input-group mt-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Enrolled on:</span>
                                            </div>
                                            <input type="text" 
                                                id="enroll-date-from" 
                                                class="form-control input-md" 
                                                v-model="settings.double_points_new_representative_start_date"
                                                :disabled="settings.is_double_points_new_representative == 0"
                                            >
                                        </div>

                                    </div>
                                    <div class="col-md-6">

                                        <div class="input-group mt-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">To:</span>
                                            </div>
                                            <input type="text" 
                                                id="enroll-date-to" 
                                                class="form-control input-md" 
                                                v-model="settings.double_points_new_representative_end_date"
                                                :disabled="settings.is_double_points_new_representative == 0"
                                            >
                                        </div>

                                        <div class="input-group mt-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">First:</span>
                                            </div>
                                            <input type="number" min="1" step="1" 
                                                class="form-control input-md" 
                                                v-model="settings.double_points_new_representative_first_n_days"
                                                :disabled="settings.is_double_points_new_representative == 0"
                                            >
                                            <div class="input-group-append">
                                                <span class="input-group-text">days:</span>
                                            </div>
                                        </div>
                                
                                    </div>
                                </div>

                                <hr>
                                <div class="text-right">
                                    <button type="button" class="prev-tab btn btn-dark" data-toggle="tab" href="#nav-description" role="tab">Prev</button>
                                    <button type="button" class="next-tab btn btn-dark disabled">Next</button>
                                    <button type="button" v-if="settings.id > 0"
                                        class="btn btn-primary" 
                                        v-on:click.prevent="updateIncentive()"
                                    >Update</button>
                                    <button type="button" v-else
                                        class="btn btn-primary" 
                                        v-on:click.prevent="createIncentive()"
                                    >Submit</button>
                                    
                                </div>

                            </form>

                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="view-incentive" tabindex="-1" role="dialog" aria-labelledby="add-incentive" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h4 class="modal-title">View Incentive</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="nav-top-representative-tab" data-toggle="tab" href="#nav-top-representative" role="tab" aria-controls="nav-top-representative" aria-selected="true">Top Representatives</a>
                        </div>
                    </nav>

                    <div class="tab-content" id="nav-tabContent">

                        <div class="tab-pane fade show active" id="nav-top-representative" role="tabpanel" aria-labelledby="nav-top-representative-tab">
                        
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table id="view_incentive_representatives" class="table table-striped table-bordered" style="width:100%">
                                            <thead class="com-report-header table__header table__header--bg-primary">
                                                <tr class="com-report-header table__row">
                                                    <th class="com-report-header table__cell">ID</th>
                                                    <th class="com-report-header table__cell">Name</th>
                                                    <th class="com-report-header table__cell">Points</th>
                                                    <th class="com-report-header table__cell">Arbitrary Points</th>
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

    </div>
</div>







<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    jQuery.fn.ddatepicker = jQuery.fn.datepicker; // jquery-ui is overriding the bootstrap-datepicker
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>


<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="$commission_engine_api_url/js/plugins/typeahead.bundle.js"></script>
<script src="$commission_engine_api_url/js/admin_running_incentives.js?v=1.4"></script>

EOS
1;