var api_url = "https://office.responseaffiliate.com:81/";
//var api_url = "http://evt.api/";
$(function() {
	
	
	$("#confirm_lock").dialog({
				autoOpen: false,
				resizable: false,
				width: 400,
				height:250,
				modal: true,
				buttons: {
					"Lock Now": function() {
						lockCommission();
					},
					Cancel: function() {
						$( this ).dialog( "close" );
					}
				}
		});

	loadPeriodTypes();
	
	$("#commission_period_type").change(function(){
		$("#get_commission_period").show();
		$("#get_commissions").show();
		$("#get_commission_period_options").show();
		$("#commission_period").html("");
		var type = $("#commission_period_type").val();
		//var url = api_url+'api/CommissionPeriods?filter={"where":{"commissionPeriodTypeId":'+type+'}}';
		var url = api_url + 'periodsall/' + type;
		var selected_type = $("#commission_period_type option:selected").text();
		var start_date;
		var end_date;
		
		$.ajax({		  
		  url: url,
		  type:"GET",
		  dataType: "json",
		}).done(function(data) {
			$("#select_commission_period").show();			
			var html = "<option disabled='disabled' selected='selected'>SELECT COMMISSION PERIOD</option>";			  
			for(i=0;i<data.length;i++){
			  start_date = new Date(data[i].startDate);
			  start_date = $.datepicker.formatDate("yy-mm-dd",start_date);
			  
			  end_date = new Date(data[i].endDate);
			  end_date = $.datepicker.formatDate("yy-mm-dd",end_date);
			  
			  html += "<option value="+data[i].commission_period_id+">"+selected_type+" ("+data[i].start_date+" - "+data[i].end_date+")</option>";
			}	
			$("#commission_period").append(html);
		});
		
	});
	
	$("#generate_report").click(function(){
		var type = $("#commission_period_type").val();
		var period_id = $("#commission_period").val();
		var data = {periodType:type,periodId:period_id};
		//var url = api_url+'api/CommissionPayouts/computeCommission';
		var url = api_url + 'commissionreport';
		$("#commissions").hide();
		loadOverlay();
		
		$.ajax({		  
		  url: url,
		  type:"POST",
		  data: data,
		  dataType:'text',
		  success: function(data){
			
			$("#commissions").show();
			$("#commissions").html(data);
			$("#lock_commissions").show();	
			removeOverlay();
		  }
		});
	});	
	
	$("#lock_commission_period").click(function(){
		$( "#confirm_lock" ).dialog("open");
		
	});
 	    
   
});

function lockCommission(){
	var period_id = $("#commission_period").val();
	var url = api_url+'lockperiod';
	var data = {periodId:period_id};
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
}

function loadPeriodTypes(){
	
	//var url = api_url+'api/CommissionPeriodTypes?filter={"where":{"active":1}}';
	var url = api_url + 'periodtypes/1';
	$.ajax({		  
	  url: url,
	  type:"GET",
	  dataType: "json",
	}).done(function(data) {
		  var html = '<option disabled="disabled" selected="selected">SELECT COMMISSION TYPE</option>';
		  for(i=0;i<data.length;i++){
			  html += "<option value="+data[i].commission_period_type_id+">"+data[i].name+"</option>";
		  }	
		  
		  $("#commission_period_type").append(html);
	});
}

