var api_url = "https://office.stg-naxum.xyz:81";
//var api_url = "http://gtt.api/";
var tbl_payments;
var payoutIds2 = [];

$(document).ready(function () {
    loadPeriodTypes();
    loadPeriodDates();

    $('#download-payment-reload').click(function() {
        var json = $.parseJSON($("#payment_log_json").val());
        var csv = JSON2CSV(json);
        window.open("data:text/csv;charset=utf-8," + escape(csv));
    });

    $("#commission_period_type").change(function(){
        $("#get_commission_period").show();
        //$("#get_commissions").show();
        //$("#get_commission_period_options").show();
        //$("#commission_period").html("");
        var type = $("#commission_period_type").val();
        //var url = api_url+'api/CommissionPeriods?filter={"where":{"commissionPeriodTypeId":'+type+'}}';
        // var url = api_url + 'periodslocked/' + type;
        // var selected_type = $("#commission_period_type option:selected").text();
        // var start_date;
        // var end_date;

        // $.ajax({
        //   url: url,
        //   type:"GET",
        //   dataType: "json",
        // }).done(function(data) {
        //  $("#select_commission_period").show();
        //  var html = "<option disabled='disabled' selected='selected'>SELECT COMMISSION PERIOD</option>";
        //  for(i=0;i<data.length;i++){
        //    start_date = new Date(data[i].startDate);
        //    start_date = $.datepicker.formatDate("yy-mm-dd",start_date);

        //    end_date = new Date(data[i].endDate);
        //    end_date = $.datepicker.formatDate("yy-mm-dd",end_date);

        //    html += "<option value="+data[i].commission_period_id+">"+selected_type+" ("+data[i].start_date+" - "+data[i].end_date+")</option>";
        //  }
        //  $("#commission_period").append(html);
        // });

    });

    $('#generate_report').on('click',function(){

        // var period_id = $("#commission_period").val();
        // var url = api_url + 'generatepayouts/' + period_id;

        // loadOverlay();
        // $.ajax({
        //   url: url,
        //   type:"GET",
        //   dataType:'json',
        //   success: function(data){
        //  loadTable(data);
        //  removeOverlay();
        //   }
        // });

        var periodDates = $('#commission_period').val();
        var selectedPeriodTypes = '';
        periodDates = periodDates.split(';');
        startDate = periodDates[0];
        startEnd = periodDates[1];
        $('input[name="selectedPeriodTypes"]:checked').each(function() {
            selectedPeriodTypes += $(this).val() + '&';
        });

        if (selectedPeriodTypes.length == 0) {
            alert('Please select a commission type');
            return;
        }
        else {
            var url = api_url + 'generatepayouts_date_range/'+selectedPeriodTypes+'/'+startDate+'/'+startEnd;
            loadOverlay();
            $.ajax({
                url :url,
                type : 'GET',
                dataType : 'json',
                success : function(data) {
                    loadTable(data);
                    removeOverlay();
                }
            });
        }
    });

    tbl_payments = $('#payment_table').DataTable();

    $('#btn_mark_paid').on('click',function(e){
        swal({
                title: "Pay Commissions",
                text: "The commissions will be payed in ProPay. Are you sure?",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-success",
                confirmButtonText: "Yes!",
                cancelButtonText: "No, cancel please!",
                closeOnConfirm: false,
                closeOnCancel: false
            },
            function(isConfirm) {
                if (isConfirm) {
                    payCommissions();
                } else {
                    swal("Cancelled", "Commission not paid :)", "error");
                }
            });
    });
});

function loadTable(data){
    tbl_payments.destroy();
    $('#payment_table tbody').html('');
    var html="";
    var total = 0;
    if (data.direct_commissions) {
        $.each(data.direct_commissions, function(index, object) {
            if (object.account_number == null) {
                object.account_number = '';
            }
            html += "<tr id='payout-id"+object.payout_id+"'>";
            html += "<td>"+object.payout_id+"</td>";
            html += "<td>"+object.order_id+"</td>";
            html += "<td>"+object.fname+' '+object.lname+"</td>";
            html += "<td>"+object.account_number+"</td>";
            html += "<td>"+object.status+"</td>";
            html += "<td>"+object.payout_type+"</td>";
            html += "<td style='text-align: right;'>"+numeral(object.value).format('$0,0.00')+"</td>";
            html += "</tr>";
            total = parseFloat(total) + parseFloat(object.value);
            payoutIds2.push(object.payout_id);
        });
    }
    if (data.override_commissions) {
        console.log('has override');
        $.each(data.override_commissions, function(index, object) {
            if (object.account_number == null) {
                object.account_number = '';
            }
            html += "<tr id='payout-id"+object.payout_id+"'>";
            html += "<td>"+object.payout_id+"</td>";
            html += "<td>"+object.order_id+"</td>";
            html += "<td>"+object.fname+' '+object.lname+"</td>";
            html += "<td>"+object.account_number+"</td>";
            html += "<td>"+object.status+"</td>";
            html += "<td>"+object.payout_type+"</td>";
            html += "<td style='text-align: right;'>"+numeral(object.value).format('$0,0.00')+"</td>";
            html += "</tr>";
            total = parseFloat(total) + parseFloat(object.value);
            payoutIds2.push(object.payout_id)
        });
    }

    $('#payment_table tbody').append(html);

    tbl_payments = $('#payment_table').DataTable( {
        dom: 'Bfrtip',
        pageLength : 25,
        buttons: [
            {
                extend: 'csv',
                text: 'Export CSV',
                title:'Commission Payouts',
                exportOptions: {
                    modifier: {
                        search: 'none'
                    }
                },
            }
        ]
    } );
    total = numeral(total).format('$0,0.00');
    $('#spn_total').html(total);
    $('#result').show();
}

function loadPeriodTypes(){

    //var url = api_url+'api/CommissionPeriodTypes?filter={"where":{"active":1}}';
    var url = api_url + 'periodtypes/1';
    $.ajax({
        url: url,
        type:"GET",
        dataType: "json",
    }).done(function(data) {
            var html = '';
            for(i=0;i<data.length;i++){
                html += '<div class="checkbox"><label><input name="selectedPeriodTypes" type="checkbox" value="'+data[i].commission_period_type_id+'">'+data[i].description+'</label></div>';
            }

            $("#commission_period_type").append(html);
        });
}

function loadPeriodDates() {
    var url = api_url + 'periods';
    $.ajax({
        url : url,
        type: "GET",
        dataType: "json"
    }).done(function(data) {
            var html = '';
            for(i=0;i<data.length;i++) {
                html += '<option id="" value="'+data[i].start_date+';'+data[i].end_date+'">' + data[i].start_date + ' - ' + data[i].end_date + '</option>';
            }
            $('#commission_period').append(html);
        });
}

function payCommissions() {

    $('.confirm').prop('disabled','true');

    var url = api_url + 'pay_commissions';
    var payoutIds = [];
    $('#payment_table tbody tr').each(function() {
        var payoutId = $(this).find('td').eq(0).text();
        payoutIds.push(payoutId);
    });
    //loadOverlay();

    $.ajax({
        url:url,
        type:"POST",
        dataType:"json",
        data : {'data' :JSON.stringify(payoutIds2)}
    }).done(function(data) {
            //removeOverlay();
            if (data) {
                swal("Paid!", "Commission has been paid.", "success");
                $('#result-header').hide();
                $('#payment_log_json').val(JSON.stringify(data));
                $('#payment-log-header').show();

            }
                $('.confirm').prop('disabled','false');
        });
} /* payCommissions */

function loadOverlay(){
    var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
    $('body').append(overlay);
}

function removeOverlay(){
    $('#overlay').remove();
}

function JSON2CSV(objArray) {
    var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;

    var str = '';
    var line = '';

    var head = array[0];
    if ($("#quote").is(':checked')) {
        for (var index in array[0]) {
            var value = index + "";
            line += '"' + value.replace(/"/g, '""') + '",';
        }
    } else {
        for (var index in array[0]) {
            line += index + ',';
        }
    }

    line = line.slice(0, -1);
    str += line + '\r\n';

    for (var i = 0; i < array.length; i++) {
        var line = '';

        for (var index in array[i]) {
            var value = array[i][index] + "";
            line += '"' + value.replace(/"/g, '""') + '",';
        }

        line = line.slice(0, -1);
        str += line + '\r\n';
    }
    return str;
}

