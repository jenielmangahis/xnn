(function ($, api_url, Vue, swal, axios, location, moment, undefined) {
    
    let $dt_sales = null;
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

                $dt_sales = $("#table-admin-group-sales").DataTable({
                    "processing": false,
                    "serverSide": false,
                    responsive: true,
                    "pageLength": 25,
                    columns     : [
                        {data    : 'id'},
                        {data    : 'fullname'},
                        {data    : 'enrollment_date'},
                        {data    : 'upgrade_date'},
                        {data    : 'email'},
                        {data    : 'country'},
                        {data    : 'sponsorid'},
                        {data    : 'sponsor_name'},
                        {data    : 'prs'},
                        {data    : 'id'},
                        {data    : 'grs'}

                        /*
                        {data    : 'total_cv', render: $.fn.dataTable.render.number( ',', '.', 2)},
                        {data    :  'volume', render: $.fn.dataTable.render.number( ',', '.', 2 )},
                        {data    : 'transaction_date'},
                        {data    : 'amount_paid'},
                        {data    : 'credited'},
                        {data    : 't_status'},
                        {data    : 'order_type'},
                        {data    : 'payment_type'}*/
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

                $('#report-date').ddatepicker({
                    "setDate" : new Date(),
                    "format": "yyyy-mm",
                    "autoclose": true,
                    "startView": 1,
                    viewMode: "months",
                    minViewMode: "months"
                });
            
                $('.report-date-icon').on('click', function(){
                    $('#report-date').focus();
                });

            },
            view() {

                if ($("report-date").val() === "") {
                    swal({
                        title: "Please fill up report date.",
                        type: "warning",
                        confirmButtonClass: "btn-danger",
                        confirmButtonText: "Ok",
                        closeOnConfirm: false
                    });
                } else {
                    $dt_sales.clear().draw();
                    $btn = $('#button-generate-report');
                    /*
                    $('#download-report-links').hide();
                    $btn.html("<i class='fa fa-spinner fa-spin '></i> Generating Report");
                    $btn.prop("disabled", true);
                    $('#link-download-csv').attr("href", "#");
                    $('#link-download-line-item').attr("href", "#");
                    $('#link-download-transaction-level').attr("href", "#");
                    $('report-date, #date-to, #status').prop('readonly', true).prop('disabled', true);*/
        
                    Promise.all([
                        client.get(`admin/group-sales/${$("#report-date").val()}`),
                    ]).then(responses => {
                        let transactions = responses[0];
        
                        $dt_sales.rows.add(transactions.data).draw();
                        $dt_sales.columns.draw();

        /*
                        let transactionCSV = responses[2];
                        $('#link-download-csv').attr("href", transactionCSV.data.link);
        */
                    }).catch(error => {
                        console.log(error);
                    }).finally(() => {
                        $btn.html("GO");
                        $btn.prop("disabled", false);
                        $('#download-report-links').show();
                        $('#report-date').prop('readonly', false).prop('disabled', false);
                    });
        
                }
            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));