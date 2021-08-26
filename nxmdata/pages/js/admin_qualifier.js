var apiUrl = "https://office.stg-naxum.xyz:81/";
/* Store jquery collection of the row that contain the edit button after the user click it. */
var $selectedRow = {};
var isNoResult = true; /* By default there no results. */

$(document).ready(function() {

	/* Initialization. */
	$("#echo_name").text('');
	$("#echo_id_number").text('');

	/* Add action listeners */
	$("#update_list").on("click", btnIdNumber_Click)
	$("#item-btn-cancel").on("click", btnCancel_Click)
	$("#item-btn-submit").on("click", btnSubmit_Click)
	$("#save").on("click", btnSave_Click);
}) /* $(document).ready(function() */

/*
* Listen to click events of the table edit button.
* Show the popup modal and display the sales count on the modal
* the selected sales count of the selected row. */
function btnEdit_Click(e) {
	var $value = {};

	$selectedRow = $(this).closest("tr");
	$value = $(".sales-count", $selectedRow);

	$("#item-sales-count").val($value.text());
	$("#item-editor").modal("toggle");
} /* btnEdit_Click */

/*
* Load the list of sales count from the server related to entered
* user id on the id number input text field, the result is wrap 
* into the table. */
function btnIdNumber_Click() {
	var userId = $("#id_number").val();

	$("#echo_name").text('');
	$("#echo_id_number").text('');
	loadListing(userId);
} /* btnIdNumber_Click */

function btnCancel_Click() {
	$("#item-editor").modal("toggle");
} /* btnCancel_Click */

function btnSubmit_Click() {
	var $salesCount = $("#item-sales-count");

	if (isNaN($salesCount.val())) {
		swal("Invalid input!", "Please enter a number!", "error");
		return 0;
	} /* (isNaN($salesCount.val())) */

	$(".sales-count", $selectedRow).text($salesCount.val());
	$("#item-editor").modal("toggle");		
} /* btnSubmit_Click */

function btnSave_Click() {
	var $rows = $("#history tbody");
	var $items = $("tr", $rows);

	if (isNoResult) {
		swal("Affiliate ID is required!", "Please choose an affilliate!", "error");
		return 0;
	} /* (isNoResult) */

	swal({
	         title: "Confirmation"
	        ,text: "Do you want to save changes?"
	        ,type: "warning"
	        ,showCancelButton: true
	        ,confirmButtonClass: "btn-success"
	        ,confirmButtonText: "Yes, Save changes!"
	        ,cancelButtonText: "No, Cancel pls!"
	        ,closeOnConfirm: false
	        ,closeOnCancel: false
	    }
	    ,function(isConfirm) {
	        if (isConfirm) {
				$.each($items, function(index, value) {
					var   userId
						, productSku
						, salesCount;

					userId = $("#id_number").val();
					productSku = $(".product-sku", value).text();
					salesCount = $(".sales-count", value).text();
					saveSalesCount(
						 userId 	/* userId */
						,productSku	/* productSku */
						,salesCount	/* salesCount */
					);

				});
	            swal("Succesfully save!", "", "success");
	        } else {
	            swal("Cancelled", "Changes is not saved!", "error");
	        }
    	}
    ); /* swal */
} /* btnSave_Click */

function loadListing(userId) {
	
	loadOverlay();
	$.ajax({
		 url: apiUrl + 'fetchusersalescount/' + userId
	  	,type: "GET"
	  	,dataType: "json"
	  	,success: function(data) {
	  		if (data.length > 0) {
	  			isNoResult = false;
	  			removeOverlay();	
		  		loadTable(data);
	  		} else {
	  			isNoResult = true;
	  			removeOverlay();
	  			swal("No result!", "", "error");
	  		}
		}
		,error: function(data) {
			removeOverlay();
		}
	});
} /* loadHistoricalReport */

function loadTable(data){
	var html = "";
	var $table = $('#history tbody');
	var  $productName = {}
		,$productSku = {}
		,$sales_count = {}
		,$btnEdit = {}
		,$colEdit = {};

	$table.html("");
	$.each(data, function(index, value) {
		$productName = $("<td>").addClass("text-left").text(value.name);
		$productSku = $("<td>").addClass("product-sku text-center").text(value.id);
		$sales_count = $("<td>").text(value.sales_count).addClass("sales-count text-right");
		$btnEdit = $("<button>").addClass("btn btn-default btn-fluid text-align-left").attr("type", "button")
								.css({"width" : "100%"}).text("Edit").bind("click", btnEdit_Click)
		$colEdit = $("<td>").append($btnEdit);
		$table.append($("<tr>").append($productName).append($productSku).append($sales_count).append($colEdit));
		$("#echo_name").text(value.users_name);
		$("#echo_id_number").text(value.users_id);
	});
} /* loadTable */

function loadOverlay(){
	 var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';

	 $('body').append(overlay);
} /* loadOverlay */

function removeOverlay(){
	 $('#overlay').remove();
} /* removeOverlay */

function saveSalesCount(userId, productSku, salesCount) {
	var returnValue = -1;

	$.ajax({
		 url: apiUrl + 'saveusersalescount/' + userId + '/' + productSku + '/' + salesCount
	  	,type: "GET"
	  	,dataType: "json"
	  	,success: function(data) {
	  		if (data == true) {
	  			returnValue = 0;
	  		}
		}
		,error: function(data) {
			returnValue = -1;
		}
	});

	return returnValue;
} /* saveSalesCount */