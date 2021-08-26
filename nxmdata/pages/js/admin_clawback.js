var api_url = "https://office.stg-naxum.xyz:81/";
var tbl_orders;
$(document).ready(function () {	
	var url = api_url + 'getusers';
	
		
	$("#distributor").autocomplete({		
		source: function( request, response ) {
        $.ajax({
          url: url,
          dataType: "json",
          data: {
            term: request.term,
            field: $("#distributor_filter").val()
          },
          success: function( data ) {			  
            response( data );
          }
        });
      },
		minLength: 2,
		select: function (event, ui) {			
			var id = ui.item.id;
			$("#hd_user").val(id);
			getOrders(id);
		},
		open: function(event, ui) {
            $(".ui-autocomplete").css("z-index", 10000);
        }
	});

	$("#payment_table").on('click','.refund',function(){
		var id = $(this).attr('id');
		$("#order_id").val(id);
		//$("#user_id").val($("#uid_"+id).val());
		//$("#amount").val($("#paid_"+id).html());
		//$("#locked").val($("#locked_"+id).html());
		//$("#payout_id").val(id);
		$("#refund-confirmation").modal();
	});
	
	$("#refund_order").click(function(){
		setRefund();
	});
	
	$("#reload").click(function(){
		var id = $("#hd_user").val();
		$("#refund-confirmation").modal("hide")
		getOrders(id);
	});
		
	tbl_orders = $('#payment_table').DataTable();
});

function loadTable(data) {
	//console.log(data);
	tbl_orders.destroy();
	$('#payment_table tbody').html('');
	var html = "";
	
	//var status = ['No','Yes'];
	//var locked;
	//var commission_paid;
	var refunded;
	$.each(data, function (index, object) {
		//commission_paid = 0;
		//locked = "No";
		refunded = "No";
		console.log(object.period_locked)
		//if(object.period_locked)
			//locked= "Yes";
		
		//if(object.commission_paid)
			//commission_paid = object.commission_paid;
		
		if(object.commission_payout_refund_id)
			refunded= "Yes";		
		html += "<tr>";
		html += "<td>" + object.order_id + "</td>";
		html += "<td>" + object.product + "</td>";
		html += "<td>" + object.sku + "</td>";		
		html += "<td>" + object.order_date + "</td>";		
		html += "<td>" + object.amount + "</td>";
		//html += "<td>"+status[object.subscription]+"</td>";		
		//html += "<td><span id='locked_"+object.commission_payout_id+"'>"+locked+"</span></td>";		
		//html += "<td><span id='paid_"+object.commission_payout_id+"'>"+commission_paid+"</span></td>";		
		html += "<td>"+refunded+"</td>";
		if(refunded == "Yes")
			html += "<td></td>";
		else
			//html += "<td><button id='"+object.order_id+"' class='refund btn-link btn-md'>Refund</button><input type='hidden' id='uid_"+object.order_id+"' value='"+object.user_id+"'></td>";
			html += "<td><button id='"+object.order_id+"' class='refund btn-link btn-md'>Refund</button></td>";
		html += "</tr>";

	});

	$('#payment_table tbody').append(html);

	tbl_orders = $('#payment_table').DataTable({pageLength:25});


	$('#result').show();

}

function loadOverlay() {
	var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
	$('body').append(overlay);
}

function removeOverlay() {
	$('#overlay').remove();
}

function getOrders(id){
	$("#reload").hide();
	$("#refund_order").show();
	$("#no-btn").show();
	$("#modal-body-txt_1").show();
	$("#modal-body-txt_2").hide();
	loadOverlay();
	var url = api_url + 'refundablepayouts/'+id;
	$.ajax({
		url: url,
		type: "GET",
		dataType: 'json',
		success: function (data) {
			loadTable(data);
			removeOverlay();
		}
	});
}

function setRefund() {
	var order_id = $("#order_id").val();
	var user_id = $("#hd_user").val();
	/*var amount = $("#amount").val();
	var locked = $("#locked").val();
	var payout_id = $("#payout_id").val();*/
	
		
	//var data = {payout_id:payout_id,user_id:user_id,order_id:order_id,amount:amount,locked:locked};
	var data = {order_id:order_id,user_id:user_id};
	
	var url = api_url + 'setrefund';
	loadOverlay();
	$.ajax({
		url: url,
		data: data,
		type: "POST",
		dataType: 'text',
		success: function (data) {
			$("#modal-body-txt_1").hide();
			$("#modal-body-txt_2").show();
			$("#refund_order").hide();
			$("#no-btn").hide();
			$("#reload").show();
			$(".close").hide();
			removeOverlay();
		}
	});
}

