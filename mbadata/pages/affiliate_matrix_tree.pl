print <<EOS;

<link rel="stylesheet" href="$commission_engine_api_url/css/app.css?v=$app_css_version" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" />
<link rel="stylesheet" href="$commission_engine_api_url/css/select2-bootstrap.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/orgchart/2.1.8/css/jquery.orgchart.min.css"  />

<style>
    #chart-container {
        position: relative;
        display: inline-block;
        padding: 2px;
        top: 10px;
        left: 10px;
        height: auto;
        width: calc(100% - 24px);
        /*border-radius: 5px;*/
        /*border: 1px solid #ddd;*/
        /*border-collapse: collapse;*/
        overflow: auto;
        text-align: center;
    }

    .orgchart {
        background: white;
        background-image:none !important;
    }

    .orgchart .node .title {
        width: 100px !important;
        font-size: 0.9em !important;
        height: 23px !important;
        font-weight: 900 !important;
        padding: 3px !important;
        /*background-color: #00AEEF !important;*/
    }



    .orgchart .node .content {
        height: 70px !important;
        max-width:  100px;
        font-size: 0.8em !important;
        text-transform: capitalize;
        padding: 3px !important;
        white-space:normal;
        /*border: 1px solid #00AEEF !important;*/
    }

    .orgchart .node.node-green .title {
        background-color: #5cb85c !important;
    }

    .orgchart .node.node-green .content {
        border: 1px solid #5cb85c !important;
    }

    .orgchart .node.node-red .title {
        background-color: #d9534f !important;
    }

    .orgchart .node.node-red .content {
        border: 1px solid #d9534f !important;
    }
    .orgchart .node.node-yellow .title {
        background-color: #57caf0 !important;
    }

    .orgchart .node.node-yellow .content {
        border: 1px solid #57caf0 !important;
    }

    .orgchart .node .content .content-name {
        font-weight: bolder !important;
        margin-bottom: 3px;
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
        white-space:normal;
    }

    .orgchart .node .content .content-current-rank {
        font-size: 0.85em !important;
    }

    .orgchart .lines .topLine {
        border-top: 2px solid #2E3192 !important;;
    }

    .orgchart .lines .rightLine {
        border-right: 1px solid #2E3192 !important;
    }

    .orgchart .lines .leftLine {
        border-left: 1px solid #2E3192 !important;
    }

    .orgchart .lines .downLine {
        background-color: #2E3192 !important;
    }

    .orgchart .verticalNodes ul>li::before,
    .orgchart .verticalNodes ul>li::after {
        border-color: #2E3192 !important;
    }

    .orgchart .verticalNodes>td::before {
        border: 1px solid  #2E3192 !important;
    }


    /*.orgchart .node-info-icon {*/
    /*    transition: opacity .5s;*/
    /*    opacity: 0;*/
    /*    right: -5px;*/
    /*    top: -5px;*/
    /*    z-index: 2;*/
    /*    position: absolute;*/

    /*    width: 25px;*/
    /*    height: 25px;*/
    /*    padding: 5px 0px;*/
    /*    border-radius: 15px;*/
    /*    text-align: center;*/
    /*    font-size: 12px;*/
    /*    line-height: 1.42857;*/

    /*    color: #fff;*/
    /*    !*background-color: #5cb85c;*!*/
    /*    !*border-color: #4cae4c;*!*/
    /*    background-color: #82B941;*/
    /*    border-color: #67b010;*/
    /*}*/
    /*.orgchart .node-info-icon::before { background-color: rgba(130, 185, 65, 0.5); }*/
    /*.orgchart .node-info-icon:hover::before { background-color: #82B941; }*/
    /*.orgchart .node:hover .node-info-icon { opacity: 1; }*/

    /* tippy */

    .tippy-content p {
        /*font-size:large;*/
        margin-bottom: 3px !important;
    }

    .tippy-content p span {
        font-weight: bolder;
        margin-left: 3px;
        /*text-transform: uppercase;*/
    }

    .tippy-content p small {
        margin: 5px 0 !important;
    }

    .tippy-content h5 {
        margin-bottom: 5px;
        /*font-size:larger;*/
        font-weight: 900;
    }

    .tippy-content h5:not(:first-child) {
        margin-top: 20px;
    }

    .tippy-tooltip.light-theme {
        background-color: black;
    }

    div#container{
        background: #333333;
        border-radius: 5px !important;
    }


    .column {
        float: left;
        width: 50%;
        padding: 10px;
        height: auto; /* Should be removed. Only for demonstration */
    }
</style>

<div id="matrix-tree" class="matrix-tree tool-container tool-container--default" >
    <div class="row">
        <div class="col-md-12">
            <h4>Matrix Tree</h4>
            <hr />
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <form class="form-horizontal" style="margin-bottom: 15px;">
                <div class="form-group">
                    <div class="col-md-4">
                        <label>Filter Downline</label>
                        <select2-autocomplete-member v-on:select-change="selectionChange" :id="downline_id" :url="autocomplete_url" v-model="downline_id"></select2-autocomplete-member>
                    </div>
                </div>
            </form>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" v-if="breadcrumb.length != 0">
                    <li class="breadcrumb-item">
                        <a href="#"  v-on:click="loadMatrixTree(root_id)">Root</a>
                    </li>
                    <li v-for="b in breadcrumb" class="breadcrumb-item" :class="{'active': b.user_id == parent_id}">
                        <a href="#" v-if="b.user_id != parent_id" v-on:click="loadMatrixTree(b.user_id)">{{ b.name }}</a>
                        <span v-else>{{ b.name }}</span>
                    </li>
                </ol>
            </nav>
            <div id="chart-container"></div>
        </div>
    </div>

</div>

<!-- Development version -->
<script src="https://unpkg.com/\@popperjs/core\@2/dist/umd/popper.js"></script>
<script src="https://unpkg.com/tippy.js\@6/dist/tippy-bundle.umd.js"></script>

<!-- Production version -->
<!--<script src="https://unpkg.com/\@popperjs/core\@2"></script>-->
<!--<script src="https://unpkg.com/tippy.js\@6"></script>-->

<script src="$commission_engine_api_url/js/app.js?v=$app_css_version"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/orgchart/2.1.8/js/jquery.orgchart.js" ></script>
<script src="$commission_engine_api_url/js/affiliate_matrix_tree.js"></script>

EOS
1;