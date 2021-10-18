(function ($, api_url, Vue, swal, axios, location, moment, _, undefined) {

    const client = commissionEngine.createAccessClient('admin/ledger-withdrawal');
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dt = null;
    let $dtHistory = null;
    let $dtPaymentDetails = null;

    const vm = new Vue({
        el: "#sandbox-settings",

        data: {

        },
        mounted() {

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