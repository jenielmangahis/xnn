var api_url = "https://office.stg-naxum.xyz:81/";

$(document).ready(function() {

	initLoad();
});

function initLoad() {
	var $pendingOrdersTable = $("#pendingOrders tbody");
	var $weeklySnapshotTable = $("#weekly-snapshot tbody");
	var $qualifiedDirectTable = $("#qualified-directs tbody");
	var $row = $("<tr>");
	var $colSeller = $("<td>");
	var $colSoldTo = $("<td>");
	var $colProduct = $("<td>");
	var $colPurchaseDate = $("<td>");
	var $colSalesCount = $("<td>");
	var $colCommissionAmount = $("<td>");
	
	$.ajax({
	   	 url: api_url + 'fetch_affiliate_dashboard_report/' + $("#member").val()
	  	,type:"GET"
	  	,dataType: "json"
	  	,success: function(result){

	  		if (result.is_successful == false) { 
	  			return 0;
	  		}

	  		/* Wrap history to the history table. */
	  		$("#current-rank").html(result.current_rank);
	  		$("#last-weekly-commission-amount").html(result.last_weekly_commission.amount);
	  		$("#last-weekly-commission-date").html(result.last_weekly_commission.date);
	  		$("#lifetime-earnings").html(result.lifetime_earnings);
	  		$pendingOrdersTable.html("");
	  		$.each(result.commission_history, function(index, value) {
  				$colSeller = $("<td>").html(value.seller).addClass("text-left");
				$colSoldTo = $("<td>").html(value.sold_to).addClass("text-left");
				$colProduct = $("<td>").html(value.product).addClass("text-left");
				$colPurchaseDate = $("<td>").html(value.date_purchase).addClass("text-left");
				$colSalesCount = $("<td>").html(value.sales_count).addClass("text-right");
				$colCommissionAmount = $("<td>").html(value.amount).addClass("text-right");
				$row = $("<tr>")
					.append($colSeller)
					.append($colSoldTo)
					.append($colProduct)
					.append($colPurchaseDate)
					.append($("<td>").html(value.commission_type))
					.append($colSalesCount)
					.append($colCommissionAmount);
				$pendingOrdersTable.append($row);
	  		})
			$("#total-pending-order").html(result.total_pending_order);


	  		$qualifiedDirectTable.html("");
	  		$.each(result.qualified_directs, function(index, value) {
	  			$row = $("<tr>")
					.append($("<td>").html(value.id))
					.append($("<td>").html(value.first_name))
					.append($("<td>").html(value.last_name))
					.append($("<td>").html(value.last_retail_sale))
				$qualifiedDirectTable.append($row);
	  		})
	  	}
	});
} /* initLoad */

function loadOverlay() {
	 var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
	 $('body').append(overlay);
}

function removeOverlay() {
	 $('#overlay').remove();
}

function loadReferals() {

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

function loadSales() {

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

function loadTable(data) {
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