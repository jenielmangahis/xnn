if($oa or $ad){

print <<EOS;

<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css" />
<!-- Include Date Range Picker -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js"></script>

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
</style>


<script type="text/javascript">
    var api_url = "https://office.globaltraffictakeover.com:81/";
    //var api_url = "http://gtt.api/";
    var tbl_history;

    \$(document).ready(function () {

        loadWireTransfers();
        tbl_history = \$("\#history").DataTable();

        \$( "\#history tbody" ).on( "click", ".btn_pay", function() {
            \$('\#order_id').val(\$(this).attr('data-id'));
            \$('\#sponsor_id').val(\$(this).attr('data-sponsor-id'));
            \$('\#user_id').val(\$(this).attr('data-user-id'));
            \$('\#product_id').val(\$(this).attr('data-product-id'));
            \$('\#reference_number').val('');
            \$('\#date_paid').val('');

            \$('\#myModal').modal('show');
        });

        \$('\#date_paid').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            locale: {
                format: 'YYYY/MM/DD'
            }
        });

        \$('\#btn_submit').on('click',function(){
           var url_sub = api_url + 'updatewiretransfer';
           var data = \$('\#frm-wire-transfer').serialize();
           \$.ajax({
               url: url_sub,
               data:data,
               type:"POST",
               dataType: "json",
               success:function(obj){
                   window.location="https://office.globaltraffictakeover.com/money.cgi?p=admin_wire_transfer";
               }
           });
       });


    });

    function loadWireTransfers(){
        var url = api_url + 'getwiretransfers';

        \$.ajax({
            url: url,
            type:"GET",
            dataType: "json",
            success:function(data){
                loadTable(data);
                \$('\#history').show();
            }
        });
    }

    function loadTable(data){
        tbl_history.destroy();
        \$('\#history tbody').html('');
        var html="";

        \$.each(data, function(index,object){


            html += "<tr>";
            html += "<td>"+object.order_id+"</td>";
            html += "<td>"+object.product_name+"</td>";
            html += "<td>"+object.user_name+"</td>";
            html += "<td>"+object.purchasedate+"</td>";
            if(object.is_wire_transfered =='1'){
                html += "<td>"+object.wire_reference_number+"</td>";
                html += "<td>"+object.wire_date_paid+"</td>";
                html += "<td>Paid</td>";

            }else{
                html += "<td></td>";
                html += "<td></td>";
                html += "<td><input type='button' data-id='"+object.order_id+"' data-product-id='"+object.product_id+"'  data-user-id='"+object.user_id+"' data-sponsor-id='"+object.sponsorid+"' class='btn btn-success btn_pay' value='Mark as Paid' /></td>";

            }



            html += "</tr>";
        });

        \$('\#history tbody').append(html);
        \$('\#result').show();
        tbl_history = \$('\#history').DataTable({pageLength:25,"order": [[ 2, "desc" ]]});
    }

</script>

<br />
<div class="container">


    <div class="row">

        <div class="col-md-12" id="get_commission_type">
            <h3>Wire Transfers</h3>
            <hr />
            <table class="table table-striped table-bordered"  id="history">
                <thead>
                <tr>
                    <th>Order Id</th>
                    <th>Product</th>
                    <th>User</th>
                    <th>Date Purchased</th>
                    <th>Reference Number</th>
                    <th>Date Paid</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<br />


<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	  <div class="modal-dialog" role="document">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel">Pay Wire Transfer</h4>
		  </div>
		  <div id="mod-body" class="modal-body">
		    <div class="row ">
		        <div class="col-md-12">
                    <form id="frm-wire-transfer" name="frm-wire-transfer" method="post">
                        <input type="hidden" id="order_id" name="order_id" value=""/>
                        <input type="hidden" id="sponsor_id" name="sponsor_id" value=""/>
                        <input type="hidden" id="user_id" name="user_id" value=""/>
                        <input type="hidden" id="product_id" name="product_id" value=""/>

                        <div class="form-group">
                            <label for="account_number">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Reference Number">
                        </div>
                        <div class="form-group">
                            <label for="first_name">Date Paid</label>
                            <input type="text" class="form-control" id="date_paid" name="date_paid" placeholder="Date Paid">
                        </div>
                        <hr />
                        <button type="button" id="btn_submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
		    </div>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		  </div>
		</div>
	  </div>
	</div>

EOS

}else{
print '<h1>You do not have permission to view this page.</h1>';
}

1;




