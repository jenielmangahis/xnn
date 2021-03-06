if($oa or $ad){

print <<EOS;


<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
<!-- Include Date Range Picker -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.10.10/css/dataTables.bootstrap.min.css"/>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.1.0/css/buttons.dataTables.min.css">


<script src="https://cdn.datatables.net/1.10.10/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.10/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.1.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.1.0/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.1.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.1.0/js/buttons.print.min.js"></script>



<style>
    \#overlay {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        right: 0;
        background: \#000;
        opacity: 0.8;
        filter: alpha(opacity=80);
        width: 100%;
        height: 100%;
    }

    \#loading {
        width: 50px;
        height: 57px;
        position: absolute;
        /*background: url('img/loading.gif') no-repeat top right;*/
        top: 50%;
        left: 50%;
        margin: -28px 0 0 -25px;
    }

    .daterangepicker {
        width: 310px !important;
    }
    \#history{
        width:100% !important;
    }
</style>

<script type="text/javascript">

var api_url = "https://office.globaltraffictakeover.com:81/";
//var api_url = "http://gtt.api/";
var tbl_history;

\$(document).ready(function () {

    \$('\#date_from').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        locale: {
            format: 'YYYY/MM/DD'
        }
    });


	\$('\#date_to').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        locale: {
            format: 'YYYY/MM/DD'
        }
    });
	

	
	\$('\#btn_generate').on('click',function(){
		url = api_url + 'orderreports';
		
		var date_from = \$('\#date_from').val();
		var date_to = \$('\#date_to').val();
		

		var data = {datefrom:date_from,dateto:date_to};
		
		
		\$.ajax({		  
		  url: url,
		  type:"POST",
		  data: data,
		  dataType:'json',
		  success: function(data){
			loadTable(data,"\#history");

		  }
		});
	});

    tbl_history = \$("\#history").DataTable();
});

function loadTable(data) {

    tbl_history.destroy();
    \$('\#history tbody').html('');

    var html = "";
    var total = 0;
    \$.each(data, function (index, object) {

        html += "<tr>";
        html += "<td>"+object.user_name+"</td>";
        html += "<td>"+object.sponsor_name+"</td>";
        html += "<td>"+object.order_id+"</td>";
        html += "<td>"+object.sku+"</td>";
        html += "<td>"+object.product_name+"</td>";
        html += "<td>"+object.commission_value+"</td>";
        html += "<td>"+object.purchasedate+"</td>";
        html += "<td>"+object.user_url+"</td>";
        html += "</tr>";

    });

    \$('\#history tbody').append(html);

    tbl_history = \$('\#history').DataTable( {
        dom: 'Bfrtip',
        pageLength:25,
         buttons: [
            {
                 extend: 'csv',
                text: 'Export CSV',
                title:'Transaction Reports',
                exportOptions: {
                    modifier: {
                        search: 'none'
                    }
                }
            }
        ]
    } );
}


function loadOverlay(){
    var overlay = '<div id="overlay"><img id="loading" src="img/loading.gif"></div>';
    \$('body').append(overlay);
}

function removeOverlay(){
    \$('\#overlay').remove();
}

</script>


<br />
<div class="container">
    <h3>Select Dates</h3>
    <div class="row">
        <div class="col-md-3" id="get_commission_type">
            <p><b>From:</b></p>
            <input type="text" class="form-control" id="date_from" />
        </div>


        <div class="col-md-3" id="get_commission_period">
            <div id="get_commission_period_options">
                <p><b>To:</b><p>
            </div>
            <div id="select_commission_period" >
                <input type="text" class="form-control" id="date_to" />
            </div>
        </div>

        <div class="col-md-3" id="get_commissions" >
            <a id="btn_generate" style="margin-top: 30px"  href="javascript:void(0);" class="btn btn-primary"><span class='glyphicon glyphicon-search' aria-hidden='true'></span>&nbsp; View Report</a>

        </div>
    </div>

    <hr />
    <h3>Transaction/Order Reports</h3>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-bordered"   id="history">
                <thead>
                <tr>
                    <th style="width:15%;">User</th>
                    <th style="width:15%;">Sponsor</th>
                     <th style="width:10%;">Order Id</th>
                    <th style="width:10%;">Product SKU</th>
                    <th style="width:15%;">Product Name</th>
                    <th style="width:10%;">Volume</th>
                    <th style="width:10%;">Date Purchased</th>
                    <th style="width:15%;">URL</th>
                </tr>
                </thead>
                <tbody>
                </tbody>

            </table>
        </div>
    </div>
</div>

<br />


EOS
}else{
print '<h1>You do not have permission to view this page.</h1>';
}
1;