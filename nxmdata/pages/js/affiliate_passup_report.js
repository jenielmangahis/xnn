var api_url = "https://office.stg-naxum.xyz:81/";

$(function() {

    loadPassups();
});

function loadOverlay() {
    var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';

    $('body').append(overlay);
} // loadOverlay.

function removeOverlay() {

    $('#overlay').remove();
} // removeOverlay.

function loadPassups(){
    var id = $("#member").val();
    var url = api_url +'affiliatepassupreport/' + id;

    $.ajax({
        url: url,
        type:"GET",
        dataType: "json",
        success:function(data){
            loadTable(data);
        }
    });
} // loadPassups()

function loadTable(data) {
    var html="";
    
    $.each(data, function(index, object){
        html += "<tr>";
        html += "<td class='text-left'>" + object.Name + "</td>";
        html += "<td class='text-left'>" + object.Email + "</td>";
        html += "<td class='text-left'>" + object.Phone + "</td>";
        html += "<td class='text-right'>" + object.OrderId + "</td>";
        html += "<td class='text-left'>" + object.ProductSku + "</td>";
        html += "<td class='text-left'>" + object.Product + "</td>";
        html += "</tr>";
    });

    $('#history2 tbody').append(html);
    $('#result').show();
    $('#history2').DataTable({pageLength:25,"order": [[ 3, "desc" ]]});
} // loadTable(data)