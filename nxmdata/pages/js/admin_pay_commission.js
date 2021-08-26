var apiUrl = "https://office.stg-naxum.xyz:81/";
var $paymentTable;

$(document).ready(function () {

    /* Inititalization */
    loadPeriodTypes();
    $paymentTable = $('#payment_table').DataTable();
    
    $("#commission_period_type").change(cboPeriodType_Change);
    $('#generate_report').on('click', btnGenerateReport_Click);
    $('#btn_mark_paid').on('click', btnMarkPaid_Click);
}); /* $(document).ready(function () */

function btnMarkPaid_Click() {

    swal({
         title: "Pay Commissions"
        ,text: "The commissions will be payed in ProPay. Are you sure?"
        ,type: "warning"
        ,showCancelButton: true
        ,confirmButtonClass: "btn-success"
        ,confirmButtonText: "Yes!"
        ,cancelButtonText: "No, cancel please!"
        ,closeOnConfirm: true
        ,closeOnCancel: true
    }
    ,function(isConfirm) {
        if (isConfirm) {
            payCommissions();
        } else {
            swal("Cancelled", "Commission not paid :)", "error");
        }
    });
} /* btnMarkPaid_Click */

function btnGenerateReport_Click() {
    var period_id = $("#commission_period").val();
    var url = apiUrl + 'generatepayouts/' + period_id;
    
    loadOverlay();
    $.ajax({
         url: url
        ,type:"GET"
        ,dataType:'json'
        ,success: function(data) {
            wrapDataToTable(data);
            removeOverlay();
        }
        ,error: function() {
            removeOverlay();
        }
    });        
} /* btnGenerateReport_Click */

function cboPeriodType_Change() {
    var type = $("#commission_period_type").val();
    var url = apiUrl + 'periodslocked/' + type;
    var selected_type = $("#commission_period_type option:selected").text();
    var start_date;
    var end_date;
    var $select = $("#commission_period");
    var text = "";

    $("#get_commission_period").show();
    $("#get_commissions").show();
    $("#get_commission_period_options").show();
    $("#commission_period").html("");
    
    $.ajax({          
         url: url
        ,type:"GET"
        ,dataType: "json"
    }).done(function(data) {
        $("#select_commission_period").show();
        $select.html("");
        $select.append(
            $("<option>")
                .attr("disabled", "disabled")
                .attr("selected", "selected")
                .text("SELECT COMMISSION PERIOD")
        );
        for (i = 0; i < data.length; i++) {
            text = selected_type + " (" + data[i].start_date + " - " + data[i].end_date + ")";
            $select.append(
                $("<option>")
                    .attr("value", data[i].commission_period_id)
                    .text(text)
            );
        } // for (i = 0; i < data.length; i++)
    });        
} /* cboPeriodType_Change */

function payCommissions() {
    var url = apiUrl + 'pay_commissions';
    var payoutIds = [];

    /* Getting the payout id's. */
    $('#payment_table tbody tr').each(function() {
        var i = 0;
        $('td', this).each(function() {
            if (i == 0) {
                var payoutId = $(this).data('id');
                payoutIds.push(payoutId);
            }
            i += 1;
        });
    });

    $.ajax({
         url: url
        ,type:"POST"
        ,dataType:"json"
        ,data : { 'data' : JSON.stringify(payoutIds) }
    }).done(function(data) {
        if (data) {
            swal("Paid!", "Commission has been paid.", "success");
            $('#result-header').hide();
            $('#payment_log_json').val(JSON.stringify(data));
            $('#payment-log-header').show();
        } /* (data) */
        $('.confirm').prop('disabled','false');
    });
} /* payCommissions */

function wrapDataToTable(data) {
    /* Declaration section. ---> */
    var $tableBody = $('#payment_table tbody');
    var $row = {};
    var html = "";
    var total = 0;
    var $colId = {}
       ,$colAccNo = {}
       ,$colRoutingNo = {}
       ,$colPaymentType = {}
       ,$colName = {}
       ,$colActive = {}
       ,$colTotalPayout = {};

    /* Body ---> */
    $paymentTable.destroy();
    $tableBody.html('');
    $.each(data, function(index, value) {
        $colId          = $("<td>").addClass("text-left").attr("data-id", value.payout_id).text(value.member_id);
        $colAccNo       = $("<td>").addClass("text-left").text(value.account_number);
        $colRoutingNo   = $("<td>").addClass("text-left").text(value.routing_number);
        $colPaymentType = $("<td>").addClass("text-left").text(value.payment_type);
        $colName        = $("<td>").addClass("text-left").text(value.first_name + " " + value.last_name);
        $colActive      = $("<td>").addClass("text-center").text(value.active);
        $colTotalPayout = $("<td>").addClass("text-right").text(value.total_payout);
        $row = $("<tr>").append($colId).append($colAccNo).append($colRoutingNo)
                    .append($colPaymentType).append($colName).append($colActive)
                    .append($colTotalPayout).appendTo($tableBody);
        total = parseFloat(total) + parseFloat(value.total_payout);
    });
    $paymentTable = $('#payment_table').DataTable({
          dom: 'Bfrtip'
        , buttons: [
            {
                  extend: 'csv'
                , text: 'Export CSV'
                , title:'Commission Payouts'
                , exportOptions: { modifier: { search: 'none' }}
            }
        ]
    });
    $('#spn_total').html(total);
    $('#result').show();
} // wrapDataToTable(data)

function loadPeriodTypes() {
    var  $select = $("#commission_period_type")
        ,$option = {};

    $select.html("");
    $option = $("<option>")
                .attr("disabled", "disabled")
                .attr("selected", "selected")
                .attr("value", -1)
                .text("SELECT COMMISSION TYPE")
                .appendTo($select);
    $.ajax({
         url: apiUrl + 'periodtypes/1'
        ,type: "GET"
        ,dataType: "json"
    }).done(function(data) {
        if (data.length > 0) {
            for (i = 0; i < data.length; i++) {
                $option = $("<option>")
                            .attr("value", data[i].commission_period_type_id)
                            .text(data[i].description)
                            .appendTo($select);
            }
        }
    });
} // loadPeriodTypes()

function loadOverlay() {
     var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';

     $('body').append(overlay);
} // loadOverlay()

function removeOverlay() {

     $('#overlay').remove();
} // removeOverlay()
