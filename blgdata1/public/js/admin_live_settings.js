(function ($, api_url, Vue, swal, axios, location, moment, _, undefined) {

    const client = commissionEngine.createAccessClient('admin');
    commissionEngine.setupAccessTokenJQueryAjax();

    const sampData = [{
        "enrollment_kit": "Small Builder Pack",
        "price": "$199.00",
        "flat_rate_bonus": "$50",
        "id": 1
    }];

    const dataSet = [[ "Small Builder Pack", "$199.00", "Edinburgh", "5421", "2011/04/25", "$320,800" ]]

    const vm = new Vue({
        el: "#live-settings",
        data: {
            pool:{
                id:null,
                name:'',
                percentage:0
            }
        },
        mounted() {
            this.initializeDataTables();
            this.initializeJQueryEvents();
        },
        computed: {

        },
        methods: {

            showAddModal() {
                $('#modal-fast-start').modal({ backdrop: 'static', keyboard: false });
            },
            showEditModal(data) {
                $('#modal-fast-start').modal({ backdrop: 'static', keyboard: false });
            },
            showModalLogs(){
                $('#modal-show-logs').modal({ backdrop: 'static', keyboard: false });
            },



            showAddMatchingBonusModal() {
                $('#modal-matching-bonus').modal({ backdrop: 'static', keyboard: false });
            },
            showEditMatchingBonusModal(data) {
                $('#modal-matching-bonus').modal({ backdrop: 'static', keyboard: false });
            },
            showMatchingBonusModalLogs(){
                $('#modal-show-matching-bonus-logs').modal({ backdrop: 'static', keyboard: false });
            },



            showAdd60daysModal() {
                $('#modal-sixty-day').modal({ backdrop: 'static', keyboard: false });
            },
            showEdit60daysModal(data) {
                $('#modal-sixty-day').modal({ backdrop: 'static', keyboard: false });
            },
            show60daysModalLogs(){
                $('#modal-show-sixty-day-logs').modal({ backdrop: 'static', keyboard: false });
            },



            showAddUnilevelModal() {
                $('#modal-unilevel').modal({ backdrop: 'static', keyboard: false });
            },
            showEditUnilevelModal(data) {
                $('#modal-unilevel').modal({ backdrop: 'static', keyboard: false });
            },
            showUnilevelModalLogs(){
                $('#modal-show-unilevel-logs').modal({ backdrop: 'static', keyboard: false });
            },



            showAddUnilevelBonusModal() {
                $('#modal-unilevel-bonus').modal({ backdrop: 'static', keyboard: false });
            },
            showEditUnilevelBonusModal(data) {
                $('#modal-unilevel-bonus').modal({ backdrop: 'static', keyboard: false });
            },
            showUnilevelBonusModalLogs(){
                $('#modal-show-unilevel-bonus-logs').modal({ backdrop: 'static', keyboard: false });
            },



            showEditCustomerBonusModal(data) {
                $('#modal-customer-bonus').modal({ backdrop: 'static', keyboard: false });
            },
            showCustomerBonusModalLogs(){
                $('#modal-show-customer-bonus-logs').modal({ backdrop: 'static', keyboard: false });
            },



            showEditPoolModal(data) {
                $('#modal-pool').modal({ backdrop: 'static', keyboard: false });
            },
            showPoolModalLogs(){
                $('#modal-show-pool-logs').modal({ backdrop: 'static', keyboard: false });
            },
            initializeDataTables()
            {
                $dt = $("#tbl-pool").DataTable({
                    searching: false,
                    ordering: false,
                    lengthChange: false,
                    ajax:{
                        "url": api_url + 'admin/poolbonus-live-settings/'
                    },
                    columns: [
                        {data: 'name'},
                        {data: 'percentage', className: "text-center",
                            render: $.fn.dataTable.render.number( ',', '', 0, '',  '%')
                        },
                        {
                            data: 'action',
                            width: '200px',
                            render: function (data, type, row, meta) {
                                return '<div class="btn-group-xs" role="group" aria-label="...">' +
                                    '<button type="button" class="btn btn-primary btn-pool-edit">Edit</button>' +
                                    '</div>';
                            }
                        }

                    ]
                });

            },
            initializeJQueryEvents() {
                let _this = this;

                $('#tbl-pool tbody').on('click', '.btn-pool-edit', function () {
                    let data = $dt.row($(this).parents('tr')).data();
                    _this.showEditPoolModal(data);
                });
            }
        },
    });

    window.onbeforeunload = function() {
        if (vm.is_processing) {
            return "Do you really want to leave? Pay commission is currently processing";
        } else {
            return;
        }
    };

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment, _));