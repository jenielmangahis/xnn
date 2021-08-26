//var api_url = "https://office.globaltraffictakeover.com:81/";
var api_url = "https://office.stg-naxum.xyz:81/";
var mTable = $("#enroller");

$(document).ready(function () {

    setUpTree();

    $("#sel_toggle").change(function(){
        var select = $(this).val();
        if(select == "1")
            $("#enroller").treetable('collapseAll');
        else
            $("#enroller").treetable('expandAll');
    });
});

function setUpTree(){
    var user_id = $('\#member').val();
    var url = api_url + 'getuser/' + user_id;
    $.ajax({
        url: url,
        type:"GET",
        dataType: "json",
        success:function(object){
            var html="";
            var level = 0;

            html += '<tr  data-tt-id="'+object.id+'" data-tt-branch="true">';
            html += "<td>"+object.id+"</td>";
            html += "<td>"+object.fname+" "+object.lname+"</td>";
            html += "<td>"+object.country+"</td>";
            html += "<td>"+object.rank+"</td>";
            html += "<td>"+object.last_retail_sale+"</td>";
            html += "<td>"+object.sponsor_name+"</td>";
            html += "</tr>";

            $('#enroller tbody').append(html);
            $("#enroller").treetable({
                expandable: true,
                onNodeCollapse: function() {
                    var node = this;
                    $("#enroller").treetable("collapseNode",node.id);
                },
                onNodeExpand: function() {
                    var node = this;

                    if (node.children.length > 0) {

                        $("#enroller").treetable("expandNode",node.id);
                    } else {

                        var mUser = node.id;
                        var url2 = api_url + 'getChildren/' + mUser;
                        var level = $('#level' + mUser).html();
                        level = parseInt(level) + 1;
                        $.ajax({
                            url: url2,
                            type:"GET",
                            dataType: "json",
                            success:function(data) {
                                var m_html = '';

                                $.each(data, function(index,obj) {

                                    m_html += '<tr  data-tt-id="'+obj.id+'" data-tt-parent-id="'+node.id+'" data-tt-branch="'+obj.branch+'">';
                                    m_html += "<td>"+obj.id+"</td>";
                                    m_html += "<td>"+obj.fname+" "+obj.lname+"</td>";
                                    m_html += "<td>"+obj.country+"</td>";
                                    m_html += "<td>"+obj.rank+"</td>";
                                    m_html += "<td>"+obj.last_retail_sale+"</td>";
                                    m_html += "<td>"+obj.sponsor_name+"</td>";
                                    m_html += "</tr>";
                                });

                                $("#enroller").treetable("loadBranch", node, m_html);
                            }
                        });
                    }
                }
            });
        }
    });


}
