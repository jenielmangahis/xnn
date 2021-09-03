print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="$commission_engine_api_url/css/datepicker.css?v=$app_css_version" />
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_minimum_rank.css?v=1" />


<div class="minimum-rank tool-container tool-container--default" v-cloak>
    <div class="row">
        <div class="col-md-12">
            <h4 class="tool-container__header mb-5">Minimum $rank_title</h4>
        </div>
    </div>

<div class="mba-money-border">
    <div class="row">
        <div class="col-md-12 ">
            <div class="tool-container__actions pull-left">
                <button type="button" class="new-btn-mba btn btn-success" v-on:click.prevent="showAddModal">
                    Add Minimum $rank_title
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
            <table id="table-minimum-rank" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                <tr class="table__row">
                    <th class="table__cell">Name</th>
                    <th class="table__cell">$rank_title</th>
                    <th class="table__cell">Start Date</th>
                    <th class="table__cell">End Date</th>
                    <th class="table__cell">Set By</th>
                    <th class="table__cell">Set Date</th>
                    <th class="table__cell">Action</th>
                </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-minimum-rank" role="dialog" aria-labelledby="modal-minimum-rank-label">
        <div class="modal-dialog" role="document">
            <form class="modal-content" id="form-minimum-rank" >
                <div class="modal-header">
<button v-bind:disabled="isProcessing === 1" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
<h4 class="modal-title" id="modal-minimum-rank-label">{{ isEditMode ? 'Edit' : 'Set' }} Minimum Rank</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="user_id">Member</label>
                       <select2-autocomplete-member ref="autocompleteMember" id="user_id"  v-bind:url="autocompleteUrl" v-model="minimumRank.user_id"></select2-autocomplete-member>
                    </div>
                    <div class="form-group">
                        <label for="rank_id">Minimum $rank_title</label>
                        <select
                                name="rank_id"
                                id="rank_id"
                                class="form-control"
                                v-model="minimumRank.rank_id"
                        >
                            <option value="" selected disabled>Select a $rank_title</option>
                            <option v-for="(rank, index) in ranks"
                                    v-bind:value="rank.id"
                                    v-bind:key="rank.id">
                                {{ rank.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <datepicker id="start_date" v-model="minimumRank.start_date" v-bind:start-date="today"></datepicker>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                       <datepicker id="end_date" v-model="minimumRank.end_date" v-bind:start-date="minimumRank.start_date" ></datepicker>
                    </div>

                </div>
               <div class="modal-footer">
                    <a type="button" class="btn btn-default" data-dismiss="modal" v-show="isProcessing === 0">Close</a>
                    <button type="submit" class="btn btn-primary" id="btn-set" v-bind:disabled="isProcessing === 1" v-on:click.prevent="saveMinimumRank">{{ isEditMode ? 'Edit' : 'Set' }}</button>
                </div>
            </form>
        </div>
    </div>
  </div>  

</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_minimum_rank.js?v=1.1"></script>

EOS
1;