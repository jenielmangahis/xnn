print <<EOS;

<link rel="stylesheet" type="text/css" href="css/jquery-ui.css">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>

<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css"/> -->

<link rel="stylesheet" href="https://office.stg-naxum.xyz:81/pages/css/jquery.treetable.css"/>
<link rel="stylesheet" href="https://office.stg-naxum.xyz:81/pages/css/jquery.treetable.theme.default.css"/>
<link rel="stylesheet" href="https://office.stg-naxum.xyz:81/pages/css/sweet-alert.css" type="text/css" />

<!--
<script src="https://code.jquery.com/jquery-1.11.3.js"></script>
<script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script src="https://office.stg-naxum.xyz:81/pages/js/jquery-ui.js"></script>
 -->

<script src="https://office.stg-naxum.xyz:81/pages/js/jquery.treetable.js"></script>
<script src="https://office.stg-naxum.xyz:81/pages/js/sweet-alert.min.js" type="text/javascript"></script>
<script type="text/javascript" src="https://office.stg-naxum.xyz:81/pages/js/genealogy.js"></script>
<script type="text/javascript" src="https://office.stg-naxum.xyz:81/pages/js/handlebars-v4.0.5.js"></script>

<div class="container">

    <h3>Distributor Downline Uni Level Tree View:</h3>
    <hr />
    <input type="hidden" id="member" value="10" />

    <div class="row">
        <div class="col-md-3">
            <select id="sel_toggle" class="form-control">
                <option value="1">Collapse</option>
                <option value="0">Expand</option>
            </select>
        </div>

        <div class="col-md-12">
            <table id="enroller" class="table table-striped table-bordered" >
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Country</th>
                    <th>Rank</th>
                    <th>Last Retail Sale</th>
                    <th>Enrollment Sponsor</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>

jQuery('li[data-id="genealogy-view"]').addClass('active');

</script>

EOS
1;