var apiUrl = "https://office.stg-naxum.xyz:81/";

$(document).ready(function() {
	
	loadCommissionPeriodTypes();
	
	$("#commission_period_type").change(function() {
		var commissionPeriodTypeId = $("#commission_period_type").val();
		loadCommissionPeriod(commissionPeriodTypeId);
	});
	
	$("#generate_commissions").click(function() {
		var type = $("#commission_period_type").val();
		var period_id = $("#commission_period").val();
		var url = apiUrl + 'computecommission';
		
		$("#commissions").hide();
		$.ajax({
			url: url,
			type:"POST",
			data: { "periodType" : type, "periodId" : period_id },
			dataType:'text',
			success: function(data) {
				$("#get_commission_period_download").show();
				$("#commissions").show();
				$("#commissions").html(data);
				$("#lock_commissions").show();
			}
		});
	});
	
	$("#lock_commission_period").click(function() {
		
        swal({
                title: "Lock Commission Period",
                text: "The commission period will be permanently locked. Are you sure?",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-success",
                confirmButtonText: "Yes, Lock it!",
                cancelButtonText: "No, cancel pls!",
                closeOnConfirm: false,
                closeOnCancel: false
            },
            function(isConfirm) {
                if (isConfirm) {
                	lockCommission();
                    swal("Locked!", "Locked.", "success");
                } else {
                    swal("Cancelled", "Commission Period not locked :)", "error");
                }
            });
	});
}); // function()

function loadOverlay(){
	 var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
	 $('body').append(overlay);
}

function removeOverlay() {
	 $('#overlay').remove();
}

function lockCommission() {
	var period_id = $("#commission_period").val();
	var url = apiUrl + 'lockperiod';
	var data = { periodId : period_id };

	$.ajax({
		url: url,
		type:"POST",
		data: data,
		dataType:'text',
		success: function(data){				
			$("#confirm_lock").dialog( "close" );
			location.reload();
		}
	});
} // lockCommission

function loadCommissionPeriodTypes(){
	var url = apiUrl + 'periodtypes/1';
	
	$.ajax({		  
	  url: url,
	  type:"GET",
	  dataType: "json",
	}).done(function(data) {

		var $select = $("#commission_period_type");
		$select.append(
			$("<option>")
				.attr("disabled", "disabled")
				.attr("selected", "selected")
				.text("SELECT COMMISSION TYPE")
		);
		for(i = 0; i < data.length; i++){	
			$select.append(
				$("<option>")
					.attr("value", data[i].commission_period_type_id)
					.text(data[i].description)
			);
		}
	});
} // loadCommissionPeriodTypes()

function loadCommissionPeriod(commissionPeriodTypeId) {
	var url = apiUrl + 'periods/' + commissionPeriodTypeId;
	var periodType = $("#commission_period_type option:selected").text();
	
	$("#get_commission_period").show();
	$("#get_commissions").show();
	$("#get_commission_period_options").show();
	$("#commission_period").html("");
	
	$.ajax({
		 url: url
		,type:"GET"
		,dataType: "json"
	}).done(function(data) {
		var $select = $("#commission_period");

		$("#select_commission_period").show();
		$select.append(
			$("<option>")
				.attr("disabled", "disabled")
				.attr("selected", "selected")
				.text("SELECT COMMISSION PERIOD")
		);
		for (i = 0; i < data.length; i++) {
			console.log(data);
			$select.append(
				$("<option>")
					.attr("value", data[i].commission_period_id)
					.text(periodType + " (" + data[i].start_date + " - " + data[i].end_date + ")")
			);
		}
	});	
} // loadCommissionPeriod