print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_upline_report.css?v=1" />

<div class="tool-container tool-container--default" v-cloak >
    <div class="row">
        <div class="col-md-12">
            <h4>Upline Report</h4>
            <hr />
        </div><!-- /.col-md-12 -->
    </div><!-- /.row -->
    
    <div class="row">
        <div class="col-md-4">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-group">
                    <div class="col-sm-12">
                        <label for="member-id" class="control-label">
                            Associate
                        </label>
                        <select2-autocomplete-member id="member-id" ref="autocompleteMember" v-bind:url="autocompleteUrl" v-model="filters.member_id"></select2-autocomplete-member>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-md-12">
                        <label for="tree_type">Tree Type</label>
                        <select id="tree_type" class="form-control" v-model="filters.tree_type">
                            <option value="1">Enroller Tree</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-6">
                        <button v-bind:disabled="!filters.member_id" type="button" class="btn btn-default c-upline-report__btn" id="c-upline-report__btn-generate-report" v-bind:disabled="isProcessing === 1" v-on:click.prevent="viewUplines">
                            <i class="fa fa-list-alt mr-1h" aria-hidden="true"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-bordered dt-responsive nowrap" id="table-uplines" style="width:100%">
                <thead class="table__header table__header--bg-primary">
                    <tr class="table__row">
                        <th class="table__cell">Associate ID</th>
                        <th class="table__cell">Associate</th>
                        <th class="table__cell">Level</th>
                        <th class="table__cell">Paid As Title</th>
                        <th class="table__cell">Sponsor ID</th>
                        <th class="table__cell">Sponsor</th>
                    </tr>
                </thead>
                <tbody class="table__body">
                </tbody>
            </table>
        </div>
    </div
</div>

<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_upline_report.js?v=1.2"></script>


EOS
1;