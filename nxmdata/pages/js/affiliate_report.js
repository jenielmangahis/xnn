var api_url = "https://office.globaltraffictakeover.com:81/";
//var api_url = "http://gtt.api/";
$(function() {
	$("table thead th").data("sorter", false);
	initLoad();	
	loadReferals();
    loadSales()
});


function loadOverlay(){
	 var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
	 $('body').append(overlay);
}

function removeOverlay(){
	 $('#overlay').remove();
} 


function initLoad(){
	var id = $("#member").val();

		
	//var url = api_url+'api/memberpoints/getMemberPoints?userId='+id;
	var url = api_url + 'affiliatereport/' + id;
	$.ajax({		  
	  url: url,
	  type:"GET",
	  dataType: "json",
	  success:function(data){	

		$("#pending-commission").html('$ ' + data.pending_earnings);
		$("#lifetime-commission").html('$ ' + data.life_earnings);
		
		$("#summary-tbl").removeClass("ui-widget-content");	
		$("table").trigger("update");		  
	  }
	});
}

function loadReferals(){

	var id = $("#member").val();
	
	var url = api_url +'pendingreferrals/' + id
	$.ajax({		  
	  url: url,
	  type:"GET",
	  dataType: "json",
	success:function(data){	
		
		loadTable(data);

	  }
	});
}

function loadSales(){

    var id = $("#member").val();

    var url = api_url +'getsalescount/' + id
    $.ajax({
        url: url,
        type:"GET",
        dataType: "json",
        success:function(data){

            loadTableSales(data);

        }
    });
}

function loadTable(data){
	var html="";
	
	$.each(data, function(index,object){		
	
			var status = '';
			if(object.status == 'Commissionable'){
				status = 'Yes';
			}else{
				status = 'No';
			}
	        var total_points = parseFloat(object.total_points);
            total_points = total_points.toFixed(2);
			html += "<tr>";			
			html += "<td style='text-align:center;'>"+object.seller+"</td>";
			html += "<td style='text-align:center;'>"+object.sold_to+"</td>";
            html += "<td style='text-align:center;'>"+object.sku+" - "+object.product_name+"</td>";
			html += "<td style='text-align:center;'>"+object.purchasedate+"</td>";
            html += "<td style='text-align:center;'>"+object.commission_type+"</td>";
            html += "<td style='text-align:center;'>"+object.sales_count+"</td>";
            html += "<td style='text-align:right;'>"+total_points+"</td>";

			html += "</tr>";		
	});
	
	$('#history2 tbody').append(html);
	$('#result').show();
	$('#history2').DataTable({pageLength:25,"order": [[ 3, "desc" ]]});
}


function loadTableSales(data){
    var html="";

    $.each(data, function(index,object){

        html += "<tr>";
        html += "<td style='text-align:center;'>"+object.sku+" - "+object.name+"</td>";
        html += "<td style='text-align:center;'>"+object.sales_count+"</td>";
        html += "</tr>";
    });

    $('#sales tbody').append(html);
    $('#result_sales').show();
    $('#sales').DataTable({pageLength:25,"order": [[ 0, "asc" ]]});
}