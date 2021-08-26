var api_url = "https://office.responseaffiliate.com:81/";
//var api_url = "http://evt.api/";
$(function() {		
	loadHistory();
});

function loadHistory(){
	loadOverlay();
	var id = $("#member").val();	
	//var url = api_url+'api/memberpoints/getMemberNotifications?userId='+id;
	var url = api_url + 'membernotifications/' + id;
	$.ajax({		  
	  url: url,
	  type:"GET",
	  dataType: "json",
	}).done(function(data) {
		  loadTable(data);
		  removeOverlay();
	});
}

function loadOverlay(){
	 var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
	 $('body').append(overlay);
}

function removeOverlay(){
	 $('#overlay').remove();
}


function loadTable(data){
	var html="";
	
	var date ="";
	$.each(data, function(index,object){
			date = "";	
			status = object.status;
			if(object.status_id == 3){		
				date = new Date(object.date_attended);
				date = $.datepicker.formatDate("yy-mm-dd",date);
				date = object.date_attended;
				status = "Attended";
			}
			html += "<tr>";			
			html += "<td style='text-align:center;'>"+object.fname+" "+object.lname+"</td>";
			html += "<td style='text-align:center;'>"+object.ticket+"</td>";
			html += "<td style='text-align:center;'>"+status+"</td>";
			html += "<td style='text-align:center;'>"+date+"</td>";			
			html += "</tr>";		
	});
	
	$('#history tbody').append(html);
	$('#result').show();
	$('#history').DataTable({pageLength:25,"order": [[ 3, "desc" ]]});
}


