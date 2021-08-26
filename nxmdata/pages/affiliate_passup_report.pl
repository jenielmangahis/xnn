print <<EOS;
<script src="https://office.stg-naxum.xyz:81/pages/js/affiliate_passup_report.js"></script>

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

    /* Panel for Quick Info and Dashboard Status */

    .quick-info {
        color: white;
        text-shadow: 1px 2px 1px rgba(0, 0, 0, 0.3);
        padding-bottom: 1rem;
    }

    .quick-info .col-sm-3 {
        padding-left: 5px;
        padding-right: 5px;
    }

    .quick-info h3 {
        font-size: 2.3rem;
        font-weight: 100;
        text-align: left;
        margin-left: 15px;
        margin-right: 15px;
        margin-bottom: 15px;
    }

    .quick-info h3 small {
        color: inherit;
        display: block;
        font-size: 1.6rem;
    }

    .quick-info .dashboard-status {
        position: relative;
        padding-left: 0;
        padding-right: 0;
        padding-bottom: 1rem;
        background-color: \#00c0ef;
        border-left: 5px solid \#0097bc;
    }


</style>

<br />

<div class="container">
    <input type="hidden" id="member" value="3" />

    <h3>Pass-up Report</h3>
    <hr />

    <div class="row col-md-12">
        <div id="result" style="display:none;">
            <table id="history2" class="table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th style="align:left;">User</th>
                    <th style="align:left;">Email</th>
                    <th style="align:right;">Phone</th>
                    <th style="align:left;">Order ID</th>
                    <th style="align:left;">Product SKU</th>
                    <th style="align:left;">Product</th>

                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>


<script>

jQuery('li[data-id="passup-view"]').addClass('active');

</script>

<br />

EOS
1;
