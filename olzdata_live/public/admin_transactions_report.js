(function ($, api_url, Vue, swal, axios, location, moment, undefined) {
    
    let $dt_transactions = null;
    let $dt_totals = null;

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    $.fn.ddatepicker = $.fn.datepicker;

    const vm = new Vue({
        el: "#transactions-report",
        data: {
            
        },
        mounted() {
            this.initializeDataTables();
            this.initializeDatePicker();
            this.initializeJQueryEvents();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                $dt_transactions = $("#table-admin-transactions").DataTable({
                    "processing": false,
                    "serverSide": false,
                    responsive: true,
                    "pageLength": 25,
                    columns     : [
                        {data    : 'invoice'},
                        {data    : 'purchaser'},
                        {data    : 'sponsor'},
                        {data    : 'product'},
                        {data    : 'total_cv', render: $.fn.dataTable.render.number( ',', '.', 2)},
                        {data    :  'volume', render: $.fn.dataTable.render.number( ',', '.', 2 )},
                        {data    : 'transaction_date'},
                        {data    : 'amount_paid'},
                        {data    : 'credited'},
                        {data    : 't_status'},
                        {data    : 'order_type'},
                        {data    : 'payment_type'}
                    ]
                });
            
                $dt_totals = $("#table-admin-totals").DataTable({
                    processing: false,
                    responsive: true,
                    serverSide: false,
                    searching: false,
                    ordering: false,
                    paging: false,
                    info: false,
                    lengthChange: false,
                    destroy     : true,
                    retrieve: true,
                    "pageLength": 25,
                    columns     : [
                        {data    : 'tags'},
                        {data    : 'over_all'},
                        {data    : 'approved'},
                        {data    : 'declined'},
                        {data    : 'error'},
                        {data    : 'failed'}
                    ]
                });

            },
            initializeJQueryEvents() {
                let _this = this;
                
                $('#button-generate-report').on('click', function () {
                    _this.view();
                });
            },
            initializeDatePicker() {

                $('#date-from').ddatepicker({
                    "setDate" : new Date(),
                    "format": "yyyy-mm-dd",
                    "autoclose": true
                }).on('changeDate', function(e){
                    $('#date-to').ddatepicker('setStartDate' , e.date);
            
                    if($('#date-to').ddatepicker('getDate') < e.date) {
                        $('#date-to').ddatepicker('setDate', e.date);
                    }
                });
            
                $('#date-to').ddatepicker({
                    "setDate" : new Date(),
                    "startDate" : new Date(),
                    "format": "yyyy-mm-dd",
                    "autoclose": true
                });
            
                $('.date-to-icon').on('click', function(){
                    $('#date-to').focus();
                });
            
                $('.date-from-icon').on('click', function(){
                    $('#date-from').focus();
                });
            
                $('#date-from').ddatepicker('setDate', new Date());
                $('#date-to').ddatepicker('setDate', new Date());

            },
            view() {

                if ($("#date-from").val() === "" && $("#date-to").val() === "") {
                    console.log("test1");
                    swal({
                        title: "Please fill up both start date and end date.",
                        type: "warning",
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                    });
        
                } else if ($("#date-from").val() === "") {
                    swal({
                        title: "Please fill up the start date.",
                        type: "warning",
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                    });
        
                } else if ($("#date-to").val() === ""){
                    swal({
                        title: "Please fill up the end date.",
                        type: "warning",
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                    });
                } else {
                    $btn = $('#button-generate-report');
                    $dt_transactions.clear().draw();
                    $dt_totals.clear().draw();
                    $('#download-report-links').hide();
                    $btn.html("<i class='fa fa-spinner fa-spin '></i> Generating Report");
                    $btn.prop("disabled", true);
                    $('#link-download-csv').attr("href", "#");
                    $('#link-download-line-item').attr("href", "#");
                    $('#link-download-transaction-level').attr("href", "#");
                    $('#date-from, #date-to, #status').prop('readonly', true).prop('disabled', true);
        
                    Promise.all([
                        client.get(`admin/transactions/getTransactions/${$("#date-from").val()}/${$("#date-to").val()}?status=${$("#status").val()}`),
                        client.get(`admin/transactions/getTotal/${$("#date-from").val()}/${$("#date-to").val()}`),
                        client.get(`admin/transactions/generate-report/${$("#date-from").val()}/${$("#date-to").val()}?status=${$("#status").val()}`),
                    ]).then(responses => {
                        let transactions = responses[0];
        
                        $dt_transactions.rows.add(transactions.data).draw();
                        $dt_transactions.columns.adjust().draw();
                       /* $dt_transactions.responsive.recalc();*/
        
                        let totals = responses[1];
        
                        $dt_totals.rows.add(totals.data).draw();
                        $dt_totals.columns.adjust().draw();
                        /*$dt_totals.responsive.recalc();*/
        
                        let transactionCSV = responses[2];
                        $('#link-download-csv').attr("href", transactionCSV.data.link);
        
                    }).catch(error => {
                        console.log(error);
                    }).finally(() => {
                        $btn.html("GO");
                        $btn.prop("disabled", false);
                        $('#download-report-links').show();
                        $('#date-from, #date-to, #status').prop('readonly', false).prop('disabled', false);
                    });
        
                }
            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));