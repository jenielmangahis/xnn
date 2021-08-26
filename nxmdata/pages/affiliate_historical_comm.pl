print <<EOS;
<!-- <link rel="stylesheet" type="text/css" href="css/jquery-ui.css"> -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>
<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/> -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.10/css/dataTables.bootstrap.min.css"/>
<link rel="stylesheet" href="https://office.stg-naxum.xyz:81/pages/css/affiliate_historical_comm.css"/>
<!-- <script type="text/javascript" src="https://code.jquery.com/jquery-1.11.3.js"></script> -->

<!-- <script type="text/javascript" src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script> -->
<script type="text/javascript" src="https://cdn.datatables.net/1.10.10/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.10/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="https://office.stg-naxum.xyz:81/pages/js/affiliate_historical_comm.js"></script>
<br />
<div class="container">

    <div class="row">

        <div class="col-md-12">
            <div style="clear:both;">
                <div class="col-md-6">
                    <p><b>Choose Commission Period:</b><p>
                    <select class="form-control" id="frequency_type">
                        <option value="weekly">All Weekly Types</option>
                    </select></p>
                </div>
                <div class="col-md-4"></div>
                <div class="col-md-2 pull-right" id="total_commission" style="display:none">
                    <span style="font-weight:bold">TOTAL: </span>
                    <span style="font-weight:bold" id="total" ></span>
                </div>
            </div>
            <div style="clear:both;">
                <div class="col-md-4">
                    <select class="form-control" id="periods"></select>
                </div>
                <div class="col-md-4">
                    <button class="btn-default btn-md" id="get_commission">GET COMMISSION</button>
                </div>

            </div>

        </div>
        <input type="hidden" id="member" value="\$uid" />
    </div>
    <hr />
    <div class="row">

        <div class="col-md-12" id="get_commission_type">
            <h3>Historical Commissions</h3>
            <table class="table table-striped table-bordered"  id="history">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Commission Type</th>
                    <th>Commission</th>
                    <th>Percentage</th>
                    <th>Level</th>
                    <th>Product</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<br />


<script>

jQuery('li[data-id="historicalcommissions"]').addClass('active');

</script>

EOS
1;

