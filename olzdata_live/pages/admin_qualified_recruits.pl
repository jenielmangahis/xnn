print <<EOS; 
<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_qualified_recruits.css?v=1" />



<div id="qualified-recruits" class="qualified-recruits tool-container tool-container--default">
    <div class="row">
        <div class="col-md-10">
            <h4>Qualified Recruits</h4>
        </div>

        <div class="col-md-2">
            <div class="pull-right">
                <button
                        type="button"
                        v-on:click.prevent="getDownloadQualifiedRecruits"
                        class="btn btn-excel"
                        v-bind:disabled="csvQualifiedRecruits.downloadLinkState === 'fetching'"
                >
                    <span v-if="csvQualifiedRecruits.downloadLinkState !== 'fetching'"><i class="bi bi-file-earmark-ruled-fill"></i> Export to Excel</span>
                    <span v-else>
                    <i class="bi bi-file-earmark-ruled-fill"></i> Generating <i class="fa fa-spinner fa-spin"></i>
                </span>
                </button>
            </div>    
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-md-10">
            <form class="form-horizontal ">
                <!--
                <div class="form-row">
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="start-date" type="text" class="form-control flat" placeholder="Start Date">
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <input id="end-date" type="text" class="form-control flat" placeholder="End Date">
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <select2-autocomplete-member id="member-id" :url="autocompleteUrl" v-model="qualifiedRecruits.filters.memberId"></select2-autocomplete-member>
                    </div>
                    <div class="form-group col-lg-2 col-md-3 col-6">
                        <button type="button" class="btn btn-primary btn-block" v-on:click.prevent="viewQualifiedRecruits">Search</button>
                    </div>
                </div>
                -->
                
                <div class="form-group">
                    <div class="col-lg-3 col-md-4 col-6">
                        <label for="report-date">Calendar Month</label>
                        <input id="report-date" type="text" class="form-control flat" >
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-3 col-md-4 col-6">
                        <label for="rep-id">Representative (Optional)</label>
                        <select2-autocomplete-member id="member-id" :url="autocompleteUrl" v-model="qualifiedRecruits.filters.memberId"></select2-autocomplete-member>
                        </select2-autocomplete-member>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-2 col-md-3 col-6">
                        <button type="button" class="btn btn-primary btn-block" v-on:click.prevent="viewQualifiedRecruits">Search</button>
                    </div>
                </div>

            </form>
        </div>
   
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table id="table-qualified-recruits" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead class="table__header table__header--bg-primary">
                        <tr class="table__row">
                            <th class="table__cell">ID</th>
                            <th class="table__cell">Full Name</th>
                            <th class="table__cell">Enrollment Date</th>
                            <th class="table__cell">Upgrade Date</th>
                            <th class="table__cell">Email</th>
                            <th class="table__cell">Country</th>
                            <th class="table__cell">Sponsor ID</th>
                            <th class="table__cell">Sponsor Name</th>
                            <th class="table__cell">Reps</th>
                            <th class="table__cell">Qualified Reps</th>
                        </tr>
                    </thead>
                    <tbody class="table__body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-user-representative-list" role="dialog" aria-labelledby="modal-order-items-label">
        <div class="modal-dialog modal-xl modal-lg" role="document">
            <form class="modal-content" v-on:submit.prevent>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="modal-order-items-label">USER ID {{ user_id }} - Representative List</h4>
                </div>
                <div class="modal-body">

                    <div class="table-responsive">
                        <table id="table-reps-list" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                            <thead class="table__header table__header--bg-primary">
                                <tr class="table__row">
                                    <th class="table__cell">#</th>
                                    <th class="table__cell">ID</th>
                                    <th class="table__cell">Name</th>
                                </tr>
                            </thead>
                            <tbody class="table__body">
                                <tr v-for="(user, index) in userReps" class="table__row">
                                    <td class="table__cell text-left">{{index + 1}}</td>
                                    <td class="table__cell text-left">{{ user.user_id }}</td>
                                    <td class="table__cell text-left">{{ user.member_name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
<script src="$commission_engine_api_url/js/admin_qualified_recruits.js?v=1.1"></script>


EOS
1;