print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.3/css/responsive.bootstrap.min.css">
<link rel="stylesheet" href="$commission_engine_api_url/css/admin_commission_adjustments.css?v=1.0&app=$app_css_version" />

<div class="tool-container tool-container--default" v-cloak>

	<div class="row">
        <div class="col-md-12">
            <h4 class="admin-money-title">Commission Adjustments</h4>
        </div>
    </div>
    <div class="panel panel-default">

        <div class="panel-body">

			<div class="row margin-top-bottom">
				<div class="col-md-3">
					<button type="button" class="btn btn-block btn-success" v-on:click.prevent="showCreateModal">
						New adjustment
					</button>
				</div>
				<div class="col-md-3"></div>
				<div class="col-md-3"></div>
				<div class="col-md-3"></div>
			</div>

			<div class="row">
				<div class="col-md-12">
					<table class="table table-striped table-bordered dt-responsive nowrap" id="table-adjustments" style="width:100%">
						<thead class="table__header table__header--bg-primary">
							<tr class="table__row">
								<td class="table__cell">Name</td>
								<td class="table__cell">Commission Type</td>
								<td class="table__cell">Commission Period</td>
								<td class="table__cell">Amount</td>
								<td class="table__cell"></td>
							</tr>
						</thead>
		                <tbody class="table__body">
						</tbody>
					</table>
				</div>
			</div>

		</div>
	</div>

	<!-- NEW ADJUSTMENTS MODAL -->
    <div class="modal fade" id="modal-create" role="dialog" aria-labelledby=modal-create-title">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header" style="display: block;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 v-if="isEditMode === 1" class="modal-title" id="modal-create-title">
                        <i class="fa fa-plus" aria-hidden="true"></i> Edit adjustment
                    </h4>
					<h4 v-else-if="isViewMode === 1" class="modal-title" id="modal-create-title">
                        <i class="fa fa-plus" aria-hidden="true"></i> View adjustment
                    </h4>
					<h4 v-else class="modal-title" id="modal-create-title">
                        <i class="fa fa-plus" aria-hidden="true"></i> New adjustment
                    </h4>
                </div>
                <div class="modal-body">
            		<form class="form-horizontal" style="margin-bottom: 15px;">
						<div class="form-group">
							<div class="col-md-8">
								<label class="control-label" for="userId">Step 1. Select member</label>
								<select2-autocomplete-member ref="autocompleteMember" v-bind:url="autocompleteUrl" v-model="filters.member_id"></select2-autocomplete-member>
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-12">
								<label for="commission_type">Commission Type</label>
								<select name="commission_type" id="commission_type" class="form-control" v-model="commissionType" :disabled="!!isViewMode">
									<option value="" selected disabled>Select a type</option>
									<option v-for="(type, index) in commissionTypes"
											v-bind:value="type.id"
											v-bind:key="type.id">
										{{ type.name }}
									</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-12">
								<label for="commission_period">Commission Period <span class="text-danger">*</span></label>
								<select disabled name="commission_period" id="commission_period" class="form-control" v-model="commissionPeriodIndex" v-bind:disabled="commissionPeriodState !== 'loaded'" :disabled="!!isViewMode">
									<option v-if="commissionPeriodState === 'fetching'" value="" selected disabled>
										Fetching...
									</option>
									<option v-else-if="commissionPeriodState === 'error'" value="" selected disabled>
										Error
									</option>
									<option v-else-if="commissionPeriodState === 'loaded'" value="" selected disabled>
										Select a commission period
									</option>
									<option v-for="(period, index) in commissionPeriods"
											v-bind:value="period.id" 
											v-bind:key="index">
										{{ period.start_date }} to {{ period.end_date }}
									</option>
								</select>
								<a style="display: none;" v-show="commissionPeriodState === 'error'" v-on:click.prevent="getCommissionPeriods" class="help-block text-danger">Unable to fetch. Click here to try again.</a>
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-12">
								<label class="control-label" for="commission_period"> Step 4. Input transaction details</label>
							</div>
							<div class="col-md-8">
								<label class="control-label" for="purchaserId">Purchaser</label>
								<select2-autocomplete-member ref="autocompletePurchaser" v-bind:url="autocompleteUrl" v-model="filters.purchaser_id"></select2-autocomplete-member>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-3">
								<label class="control-label label-light" for="">Order ID</label>
								<input data-validation="required" type="number" class="form-control" v-model="filters.order_id" id="form-order_id" placeholder="" :disabled="!!isViewMode">
							</div>
							<div class="col-sm-3">
								<label class="control-label label-light" for="">Item ID</label>
								<input data-validation="required" type="number" class="form-control" v-model="filters.item_id" id="form-item_id" placeholder="" :disabled="!!isViewMode">
							</div>
							<div class="col-sm-3">
								<label class="control-label label-light" for="">Amount</label>
								<input data-validation="required" type="number" class="form-control" v-model="filters.amount" id="form-amount" placeholder="" :disabled="!!isViewMode">
							</div>
							<div class="col-sm-3">
								<label class="control-label label-light" for="">Level</label>
								<input data-validation="required" type="number" class="form-control" v-model="filters.level" id="form-level" placeholder="" :disabled="!!isViewMode">
							</div>
						</div>
						<div class="form-group">
							<div class="col-md-12">
								<label class="control-label" for="">Step 5: Remarks</label>
							</div>
							<div class="col-md-12">
								<textarea data-validation="required" class="form-control" rows="3" v-model="filters.remarks" id="form-remarks" :disabled="!!isViewMode"></textarea>
							</div>
						</div>
            		</form>
                </div>
                <div class="modal-footer">
					<button v-if="isCreateMode === 1" type="submit" class="btn btn-primary" id="btn-set" v-bind:disabled="isProcessing === 1" v-on:click.prevent="saveAdjustment">Set</button>
					<button v-else-if="isEditMode === 1" type="submit" class="btn btn-primary" id="btn-set" v-bind:disabled="isProcessing === 1" v-on:click.prevent="updateAdjustment">Edit</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>


<script src="$commission_engine_api_url/js/app.js?v=$app_js_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.3/js/responsive.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js" integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
<script src="$commission_engine_api_url/js/admin_commission_adjustments.js?v=2.0&app=$app_js_version"></script>


EOS
1;