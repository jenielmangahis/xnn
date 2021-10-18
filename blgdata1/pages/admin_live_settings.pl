print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_live_settings.css?v=1" />
<link rel="stylesheet" href="$commission_engine_api_url/css/money-admin-menu.css" />

<style>
div.dataTables_wrapper div.dataTables_length select 
    {
        margin-left: 14px !important;
    }
.tool-container .btn 
    {
        padding:5px 12px !important;
    }    
</style>

<div id="live-settings" class="live-settings tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4>Live Settings</h4>
            <hr />
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist" id='nav-tab-report'>
                <li role="presentation" class="active"><a href="#fast-start" aria-controls="fast-start" role="tab" data-toggle="tab" style="color: black !important;">Fast Start Bonus</a></li>
                <li role="presentation"><a href="#matching-bonus" aria-controls="matching-bonus" role="tab" data-toggle="tab" style="color: black !important;">Fast Start Matching Bonus</a></li>
                <li role="presentation"><a href="#60-day" aria-controls="60-day" role="tab" data-toggle="tab" style="color: black !important;">60-Day Matching Bonus</a></li>
                <li role="presentation"><a href="#unilevel" aria-controls="unilevel" role="tab" data-toggle="tab" style="color: black !important;">Unilevel Commissions</a></li>
                <li role="presentation"><a href="#unilevel-matching" aria-controls="unilevel-matching" role="tab" data-toggle="tab" style="color: black !important;">Unilevel Matching Bonus</a></li>
                <li role="presentation"><a href="#customer-acquisition" aria-controls="customer-acquisition" role="tab" data-toggle="tab" style="color: black !important;">Customer Acquisition Bonus</a></li>
                <li role="presentation"><a href="#pools" aria-controls="pools" role="tab" data-toggle="tab" style="color: black !important;">Pools</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content" style="padding: 15px;border: 1px solid #ddd;border-top: none;">
                <div role="tabpanel" class="tab-pane active" id="fast-start">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="showModalLogs">View Logs</button>
                            <button class="btn btn-primary" v-on:click.prevent="showAddModal">Add</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Enrollment Kits</th>
                                            <th>Price</th>
                                            <th>Flat Rate Bonus</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>Small Builder Pack</td>
                                            <td>\$199.00</td>
                                            <td>\$50</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>Medium Builder Pack</td>
                                            <td>\$199.00</td>
                                            <td>\$50</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>Large Builder Pack</td>
                                            <td>\$199.00</td>
                                            <td>\$50</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditModal">Edit</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>


                <div role="tabpanel" class="tab-pane" id="matching-bonus">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="showMatchingBonusModalLogs">View Logs</button>
                            <button class="btn btn-primary" v-on:click.prevent="showAddMatchingBonusModal">Add</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Enrollment Tree Level</th>
                                            <th>Percentage</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditMatchingBonusModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>10%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditMatchingBonusModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>5%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditMatchingBonusModal">Edit</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>


                <div role="tabpanel" class="tab-pane" id="60-day">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="show60daysModalLogs">View Logs</button>
                            <button class="btn btn-primary" v-on:click.prevent="showAdd60daysModal">Add</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Bonus</th>
                                            <th>Matching Bonus</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>60-Day Run Bonus</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEdit60daysModal">Edit</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>


                <div role="tabpanel" class="tab-pane" id="unilevel">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="showUnilevelModalLogs">View Logs</button>
                            <button class="btn btn-primary" v-on:click.prevent="showAddUnilevelModal">Add</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">

                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>L1</th>
                                            <th>L2</th>
                                            <th>L3</th>
                                            <th>L4</th>
                                            <th>L5</th>
                                            <th>L6</th>
                                            <th>L7</th>
                                            <th>L8</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>BG2</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>BG3</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>BG4</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>BG5</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelModal">Edit</button></td>
                                        </tr>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>

                </div>


                <div role="tabpanel" class="tab-pane" id="unilevel-matching">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="showUnilevelBonusModalLogs">View Logs</button>
                            <button class="btn btn-primary" v-on:click.prevent="showAddUnilevelBonusModal">Add</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Percentage</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelBonusModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>10%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelBonusModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>5%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditUnilevelBonusModal">Edit</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>


                <div role="tabpanel" class="tab-pane" id="customer-acquisition">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="showCustomerBonusModalLogs">View Logs</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Influencer Level</th>
                                            <th>Percentage</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>Free or Ambassador</td>
                                            <td>15%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditCustomerBonusModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>Verified Influencer</td>
                                            <td>10%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditCustomerBonusModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>A-Lister Influencer</td>
                                            <td>5%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditCustomerBonusModal">Edit</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>


                <div role="tabpanel" class="tab-pane" id="pools">

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary" v-on:click.prevent="showPoolModalLogs">View Logs</button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Pool</th>
                                            <th>Percentage</th>
                                            <th>Action</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <tr>
                                            <td>Performance Bonus</td>
                                            <td>1%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditPoolModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>Big Dog Bonus</td>
                                            <td>1%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditPoolModal">Edit</button></td>
                                        </tr>
                                        <tr>
                                            <td>Leadership Pool</td>
                                            <td>2%</td>
                                            <td><button class="btn btn-primary" v-on:click.prevent="showEditPoolModal">Edit</button></td>
                                        </tr>
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



<div class="modal fade" id="modal-fast-start" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-fast-bonus" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Bonus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Product</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="Enter SKU, Product Name"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Bonus</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="\$0.00"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-show-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Enrollment Kits</th>
                                        <th>Price</th>
                                        <th>Flat Rate Bonus</th>
                                        <th>Modefied By</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>Small Builder Pack</td>
                                        <td>\$199.00</td>
                                        <td>\$50</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>Medium Builder Pack</td>
                                        <td>\$199.00</td>
                                        <td>\$100</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>Large Builder Pack</td>
                                        <td>\$199.00</td>
                                        <td>\$200</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>



<div class="modal fade" id="modal-matching-bonus" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-matching-bonus" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Bonus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="4"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Percentage</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="2%"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-show-matching-bonus-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Enrollment Tree Level</th>
                                        <th>Percentage</th>
                                        <th>Last Modefied</th>
                                        <th>Modefied By</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>10%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>5%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="modal-sixty-day" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-sixty-day" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Bonus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Percentage</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="2%"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-show-sixty-day-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Matching Bonus Percentage</th>
                                        <th>Last Modefied</th>
                                        <th>Modefied By</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>10%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>5%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="modal-unilevel" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-unilevel" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Bonus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Rank</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="Enter rank name"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 1 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 2 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 3 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 4 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 5 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 6 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 7 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Level 8 %</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="10"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-show-unilevel-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>L1</th>
                                        <th>L2</th>
                                        <th>L3</th>
                                        <th>L4</th>
                                        <th>L5</th>
                                        <th>L6</th>
                                        <th>L7</th>
                                        <th>L8</th>
                                        <th>Last Modified</th>
                                        <th>Action</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>BG1</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>BG2</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>BG3</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>BG4</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="modal-unilevel-bonus" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-unilevel-bonus" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Bonus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Rank</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="Enter rank name"/>
                        </div>
                    </div>

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Percentage</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="2%"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-show-unilevel-bonus-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Percentage</th>
                                        <th>Last Modified</th>
                                        <th>Action</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>BG1</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>BG2</td>
                                        <td>10%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>BG3</td>
                                        <td>5%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>




<div class="modal fade" id="modal-customer-bonus" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-customer-bonus" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Bonus</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Percentage</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="2%"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="modal-show-customer-bonus-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Influencer Level</th>
                                        <th>Percentage</th>
                                        <th>Last Modified</th>
                                        <th>Action</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>Free or Ambassador</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>Verified Influencer</td>
                                        <td>10%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>A-lister Influencer</td>
                                        <td>5%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>



<div class="modal fade" id="modal-pool" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="form-pool" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-fast-start-label">Set Performance Bonus Pool</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               <div class="row">

                    <div class="col-md-12 use-flex-space-between">
                        <div class="">
                            <label>Percentage</label>
                        </div>
                        <div class="field">
                            <input type="text" class="form-control" placeholder="2%"/>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
                <a type="button" class="btn btn-primary" data-dismiss="modal">Close</a>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="modal-show-pool-logs" role="dialog" aria-labelledby="modal-minimum-rank-label">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="form-logs" >
            <div class="modal-header">
                <h4 class="modal-title" id="modal-logs-label">Logs</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
               
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">

                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Pool</th>
                                        <th>Percentage</th>
                                        <th>Last Modified</th>
                                        <th>Action</th>
                                    </tr>
                                <thead>
                                <tbody>
                                    <tr>
                                        <td>Big Dog Bonus</td>
                                        <td>15%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>Performance Bonus</td>
                                        <td>10%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                    <tr>
                                        <td>Leadership Pool</td>
                                        <td>5%</td>
                                        <td>2001-01-01 05:00:01</td>
                                        <td>Admin Account</td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>


<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_live_settings.js?v=1.1"></script>
EOS
1;