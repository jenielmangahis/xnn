print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/affiliate_party_manager.css?v=$app_css_version">

<div id="party-manager" class="party-manager tool-container tool-container--default" v-cloak>
    
    <div class="row">
        <div class="col-md-12 bottom-padding">
            <div class="party-actions" style="display: flow-root;">
                <div class=" pull-right">
                    <button class="new-btn-olz btn btn-primary mt-3" v-on:click.prevent="showCreateModal">
                        CREATE AN EVENT
                    </button>
                </div>
            </div>
        
            <h3>
                Open Events
            </h3> 

            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-open-events" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Hostess</th>
                        <th class="table__cell">Sharing Link</th>
                        <th class="table__cell">Start Date</th>
                        <th class="table__cell">End Date</th>
                        <th class="table__cell">Time Left</th>
                        <th class="table__cell">Total Sales</th>
                        <th class="table__cell">Orders</th>      
                        <th class="table__cell"></th>  
                    </tr>
                </thead>
                <tbody class="table__body"></tbody>
            </table>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 bottom-padding">
            <h3>
                Past Events
            </h3>    

            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-past-events" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Hostess</th>
                        <th class="table__cell">Sharing Link</th>
                        <th class="table__cell">Start Date</th>
                        <th class="table__cell">End Date</th>
                        <th class="table__cell">Total Sales</th>
                        <th class="table__cell">Orders</th>                                          
                    </tr>
                </thead>
                <tbody class="table__body"></tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 bottom-padding"> 
            <h3>
                Top Hostesses
            </h3>

            <form class="form-horizontal">
                <div class="form-row font-weight-bold m-0">
                    <div class="form-group col-md-4 mt-1">
                        <label for="start_date">From</label>
                        <datepicker id="top_hostess_start_date" v-model="top_hostess.start_date"></datepicker>
                    </div>
                    <div class="form-group col-md-4 mt-1">
                        <label for="end_date">To</label>
                        <datepicker id="top_hostess_end_date" v-model="top_hostess.end_date" v-bind:start-date="event.start_date" ></datepicker>
                    </div>
                    <!--Button-->

                    <div class="form-group col-md-3 ">
                            <label>&nbsp;</label><br>
                        <button
                                type="button"
                                class="new-btn-olz btn btn-primary btn-block mt-1"
                                style="line-height: 25px;top: -1px;position: relative;" v-on:click.prevent="viewTopHostesses">
                            Filter
                        </button>
                    </div>
                </div>   
            </form>
            
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-top-hostesses" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Hostess</th>
                        <th class="table__cell">Sharing Link</th>
                        <th class="table__cell">Total Sales</th>
                    </tr>
                </thead>
                <tbody class="table__body"></tbody>
            </table>

        </div>
    </div>

    <!-- MODALS -->
    <div class="modal fade" id="modal-create" role="dialog" aria-labelledby="modal-create-label">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="form-create" v-on:submit.prevent="Save">
                <div class="modal-header">
                    <h4 class="modal-title" id="modal-create-label">CREATE AN EVENT</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="hostess_id">Hostess</label>
                        <select2-autocomplete-member ref="autocompleteMember" id="hostess_id"  v-bind:url="autocompleteUrl" v-model="event.hostess_id"></select2-autocomplete-member>
                    </div>

                    <div class="form-group">
                        <label for="dt-promo-period">Event Period</label>
                        <input type="text" id="dt-promo-period" name="dt-promo-period" class="form-control"/>
                    </div>

                    <div align="right">
                        <p style="font-style: italic;color: red;">* max of 14 days</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">CLOSE</button>
                    <button type="submit" class="btn btn-primary" id="btn-save" v-bind:disabled="isProcessing === 1" v-on:click.prevent="saveEvent">SAVE</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modal-view-orders" role="dialog" aria-labelledby="modal-view-orders-label">
        <div class="modal-dialog modal-lg" style="padding-top: 60px;" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Order List</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="table-view-orders" class="table table table-striped" style="width:100%" cellspacing="0" width="100%">
                        <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">Customer</th>
                            <th class="table__cell">Order ID</th>
                            <th class="table__cell">Description</th>
                            <th class="table__cell">Amount</th>
                        </tr>
                        </thead>
                        <tbody class="table__body"></tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal">Close</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-delete" role="dialog" aria-labelledby="modal-delete-label">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="form-delete" v-on:submit.prevent="Delete">
                <div class="modal-body">
                    <div class="icon-box">
                        <i class="fa fa-times"></i>
                    </div>				
                    <h4 class="modal-title w-100">Delete Event?</h4>	
                    <p class="text-center mt-3">Do you really want to delete this event? This process cannot be undone.</p>
                </div>
                <div class="modal-footer d-block">
                    <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger">Delete</button>
                </div>
		    </form>
        </div>
    </div>    

</div>

    
<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/affiliate_party_manager2.js?v=3.0"></script>
EOS
1;