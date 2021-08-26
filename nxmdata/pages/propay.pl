print <<EOS;

<script type="text/javascript">
    var api_url = "https://office.stg-naxum.xyz:81/";
    //var api_url = "http://gtt.api/";

    \$(document).ready(function(){

        var url = api_url + 'affiliatepropay/' + \$('\#user_id').val();
        \$.ajax({
            url: url,
            type:"GET",
            dataType: "json",
            success:function(data){

                if(!data){+
                        \$('\#is_new').val('1');
                }else{

                    \$('\#is_new').val('0');

                    \$("\#account_number").val(data.account_number);
                    \$("\#first_name").val(data.first_name);
                    \$("\#last_name").val(data.last_name);
                }

            }
        });

        \$('\#btn-submit').on('click',function(){
            var url2 = api_url + 'updatepropayinfo/';
            var data = \$('\#frm-propay').serialize();
            \$.ajax({
                url: url2,
                data:data,
                type:"POST",
                dataType: "json",
                success:function(data){
                    swal("Saved", "Your ProPay info has been updated.", "info");
                }
            });
        });

    });

jQuery('li[data-id="propay-view"]').addClass('active');

</script>

<div class="container">
    <div class="row">
        <h3>Pro Pay Details</h3>
    </div>
    <hr />
<div id="dynamic-txt-container-propay" page-name="propay" class="container dynamic-txt"></div>
	
    <div class="row">
        <form id="frm-propay" name="frm-propay" method="post">
            <input type="hidden" id="user_id" name="user_id" value="$uid"/>
            <input type="hidden" name="is_new"  id="is_new" value="1" />


            <div class="form-group">
                <label for="account_number">Account Number</label>
                <input type="text" class="form-control" id="account_number" name="account_number" placeholder="Account Number">
            </div>
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name">
            </div>
            <hr />
            <button type="button" id="btn-submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>


EOS
1;