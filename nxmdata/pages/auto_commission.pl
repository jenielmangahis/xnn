if($oa or $ad){


print <<EOS;
<script src="https://office.stg-naxum.xyz:81/pages/js/auto_commissions.js"></script>

<style>
	#overlay {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        right: 0;
        background: #000;
        opacity: 0.8;
        filter: alpha(opacity=80);
        width: 100%;
        height: 100%;
    }

    #loading {
        width: 50px;
        height: 57px;
        position: absolute;
        /*background: url('img/loading.gif') no-repeat top right;*/
        top: 50%;
        left: 50%;
        margin: -28px 0 0 -25px;
    }


</style>

<br />
<div class="container">
    <div class="row">


        <div class="col-md-4" id="get_commission_type">
            <p><b>Step 1. Choose Commission Type:</b></p>
            <div id="get_commission_period_type"><select class="form-control" id="commission_period_type"></select></div>
        </div>


        <div class="col-md-4" id="get_commission_period" style="display:none;">
            <div id="get_commission_period_options" style="display:none;">
                <p><b>Step 2. Choose Commission Period:</b><p>
            </div>
            <div id="select_commission_period" style="display: none;">
                <!--<p><b>Select Commission Period&nbsp;</b></p>-->
                    <select class="form-control" id="commission_period"></select>
            </div>
        </div>


        <div class="col-md-4" id="get_commissions" style="display:none;">
            <input class="btn btn-primary" type="button" id="generate_commissions" style="margin-top: 30px"  value="View Report" />
        </div>

    </div>

	<hr />

	<div class="row">
		<div class="col-md-12">
            <div id="get_commission_period_download" style="display:none;">
                <p><b>Step 3. Download Commissions:</b><p>
            </div>
			<div id="commissions" style="display:none;"></div>

		</div>

	</div>
    <hr />
    <div class="row">
        <div class="col-md-12">
            <div id="lock_commissions" style="display:none;">
                <p><b>Step 4. Lock Commissions:</b><p>
                <p>Note: You can skip this step if you want to run commissions again for these commission period.</p>
                <input type="button" class="btn btn-danger" id="lock_commission_period"  value="LOCK COMMISSION PERIOD" />
            </div>
        </div>

    </div>
</div>






<div id="loading" style="display:none;"/></div>




<div id="confirm_lock" style="display:none;" title="Lock Commission Period?">
  <p><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 auto;"></span>The commission period will be permanently locked. Are you sure?</p>
</div>
<div id="result"></div>



EOS
}else{
    print '<h1>You do not have permission to view this page.</h1>';
}
1;
