print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_transactions_report.css?v=1" />
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<style>
    .running-incentive-button .btn-info, .running-incentive-button .btn-success, .running-incentive-button .btn-danger {
        margin-right: 5px;
    }

    .td-200-px {
        width: 200px;
    }

    ul.incentive-ul2 {
        list-style-type: none;
    }

    .label-container2 input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }

    .label-container2 {
        position: relative;
        padding-top: 5px;
        cursor: pointer;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        margin-left: 25px;
    }

    .checkmark2 {
        position: absolute;
        top: 0px;
        left: -42px;
        height: 22px;
        width: 22px;
        background-color: transparent!important;
        margin-top: 4px;
        border: .13rem solid #00000073!important;
        border-radius: 5px;
    }

    .label-container2:hover input ~ .checkmark2 {
        background-color: #ccc!important;
        border: none!important;
    }

    .label-container2 input:checked ~ .checkmark2 {
        background-image: url('https://nxmcdn.com/images/OLZ/capture/incentive_checkbox.png'), 
        linear-gradient(135deg, #1ecab2fa 0%, #1a946c 100%);
        border: .12rem solid #40404000!important;
        background-position: center;
    }

    .checkmark2:after {
        content: "";
        position: absolute;
        display: none;
    }

    .btn-add-warning {
        color: #fff;
    }

    .btn-add-warning:hover {
        color: #fff!important;
    }

    .add-new-style {
        margin-right: 15px;
    }

    \@media only screen and (device-width: 428px) {
        .add-new-style {
            margin-right: 0px;
        }
    }

    \@media only screen and (device-width: 414px) {
        .add-new-style {
            margin-right: 0px;
        }
    }

    \@media only screen and (device-width: 375px) {
        .add-new-style {
            margin-right: 0px;
        }
    }

    \@media only screen and (device-width: 360px) {
        .add-new-style {
            margin-right: 0px;
        }
    }

    \@media only screen and (device-width: 320px) {
        .add-new-style {
            margin-right: 0px;
        }
    }
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
.hide {
    display:none!important;
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

                <div class="col-md-12 text-right mt-4">
                    <button class="btn btn-success add-new-style" v-on:click.prevent="addIncentive()"><i class="fa fa-plus-circle" aria-hidden="true"></i> Add Incentive</button>
                </div>

            </div>
            
            <div class="row">
                <div class="col-md-12 mt-5">
                    <div class="table-responsive">
                        <table id="running_incentives" class="table table-striped table-bordered" style="width:100%">
                            <thead class="com-report-header table__header table__header--bg-primary">
                                <tr class="com-report-header table__row">
                                    <th class="com-report-header table__cell">Name</th>
                                    <th class="com-report-header table__cell">Period</th>
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

        <div class="tab-pane fade" id="nav-close-incentives" role="tabpanel" aria-labelledby="nav-close-incentives-tab">
            
            
            <div class="row">
                <div class="col-md-12 mt-5">
                    <div class="table-responsive">
                        <table id="close_incentives" class="table table-striped table-bordered" style="width:100%">
                            <thead class="com-report-header table__header table__header--bg-primary">
                                <tr class="com-report-header table__row">
                                    <th class="com-report-header table__cell">Name</th>
                                    <th class="com-report-header table__cell">Period</th>
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

        <div class="tab-pane fade" id="nav-arbitrary-points" role="tabpanel" aria-labelledby="nav-arbitrary-points-tab">
           
           <div class="row">
               <div class="col-md-6">
               </div>
                <div class="col-md-6">

                    <div class="row">

                        <div class="col-md-6">  
                            <div class="form-group">
                                <label>Select Member</label>
                                <select class="form-control" id="member-filter-by">
                                    <option value="id" class="assoc-id-label">Member ID</option>
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
                                <select 
                                    class="form-control"
                                    name="representatives"
                                    id="incentive_id"
                                    v-model="arbitrary.incentive_id">
                                    <option selected>Select an Incentive</option>
                                    <option v-for="(incentive, index) in arbitrary.openIncentives"
                                            v-bind:value="incentive.id"
                                            v-bind:key="incentive.id">
                                        {{ incentive.title }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="number" min="0" step="1"
                                    class="form-control" 
                                    v-model="arbitrary.bonus_points"
                                    placeholder="Bonus Points">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <button class="btn btn-warning btn-add-warning"
                            v-on:click.prevent="addBonusPoints()">ADD</button>
                        </div>

                    </div>
                    
                    
                </div>
            </div>
           
            <div class="row">
                <div class="col-md-12 mt-5">
                    <div class="table-responsive">
                        <table id="arbitrary_points" class="table table-striped table-bordered" style="width:100%">
                            <thead class="com-report-header table__header table__header--bg-primary">
                                <tr class="com-report-header table__row">
                                    <th class="com-report-header table__cell">ID</th>
                                    <th class="com-report-header table__cell">Member Name</th>
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
                    <h4 class="modal-title">Add Incentive</h4>
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
                        
                            <form class="mt-3">
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
                                    <button type="button" class="prev-tab btn btn-info disabled">Prev</button>
                                    <button type="button" class="next-tab btn btn-info" data-toggle="tab" href="#nav-rules" role="tab">Next</button>
                                </div>

                            </form>

                        </div>

                        <div class="tab-pane fade show" id="nav-rules" role="tabpanel" aria-labelledby="nav-rules-tab">
                        
                            <form>

                                <div class="row mt-5">
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
                                    <button type="button" class="prev-tab btn btn-info" data-toggle="tab" href="#nav-description" role="tab">Prev</button>
                                    <button type="button" class="next-tab btn btn-info disabled">Next</button>
                                    <button type="button" v-if="settings.id > 0"
                                        class="btn btn-success" 
                                        v-on:click.prevent="updateIncentive()"
                                    >Update</button>
                                    <button type="button" v-else
                                        class="btn btn-success" 
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
                                <div class="col-md-12 mt-5">
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