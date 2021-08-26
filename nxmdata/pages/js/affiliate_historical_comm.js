var api_url = "https://office.stg-naxum.xyz:81/";
var tbl_history;

$(document).ready(function () {
    tbl_history = $("#history").DataTable();
    loadPeriods();
    $('#frequency_type').hide();
  /*  $('#frequency_type').on('change',function(){
        $('#periods').empty();
        loadPeriods();
        tbl_history.clear();
    });*/

    $("#get_commission").click(function(){
        var date = $("#periods option:selected").text();
        var dates = date.split("-");
        var user_id = $("#member").val();
        var from = dates[0];
        var to = dates[1];

        var type = $("#frequency_type").val();

        // var url = api_url + 'historicalcommission/' + user_id+ '/' + urlencodefrom + '/' + to + '/' + type;
        var url = api_url + 'historicalcommission';
        var data = {user_id:user_id,from:from,to:to,type:type};
        console.log(url);
        //loadOverlay();
        $.ajax({
            url: url,
            type:"POST",
            data: data,
            dataType: "json",
            success:function(data){
                loadTable(data);
                $('#history').show();
                //removeOverlay();
            }
        });
    });

});

function loadPeriods(){
    var type = $("#frequency_type").val();
    var url = api_url + 'dateperiodsbytype/' + type;
    $.ajax({
        url: url,
        type:"GET",
        dataType: "json",
        success:function(data){
            $.each(data, function(index,object){
                var value =  object.start_date + " - " + object.end_date;
                $('#periods').append($("<option></option>").attr("value",index).text(value));
            });
        }
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
    tbl_history.destroy();
    $('#history tbody').html('');
    var html="";
    var total = 0;
    $.each(data, function(index,object){

        total += parseFloat(object.commission);

        html += "<tr>";
        html += "<td>"+object.buyer+"</td>";
        html += "<td>"+object.commission_type+"</td>";
        html += "<td>"+object.commission+"</td>";
        html += "<td>"+object.percent+"</td>";
        html += "<td>"+object.level+"</td>";
        html += "<td>"+object.product+"</td>";
        html += "</tr>";
    });
    $("#total").html("$"+total.toFixed(2));
    $('#total_commission').show();
    $('#history tbody').append(html);
    $('#result').show();
    tbl_history = $('#history').DataTable({pageLength:25,"order": [[ 2, "desc" ]]});
}
