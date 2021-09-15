(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: ".tool-container",
        data: {
            dt: null,
            isProcessing: 0,
            
            autocompleteUrl: `${api_url}common/autocomplete/affiliates`,

            filters: {
                member_id: null,
                tree_type: 1,
            },
            
            error: {
                message: null,
                type: null,
            },
        },
        mounted() {
            // this.initializeDataTables();

            this.dt = $("#table-uplines").DataTable({
                responsive: true,
                order: [],
                columns: [
                    {data: 'member_id', className: "text-center"},
                    {data: 'member', className: "text-center"},
                    {data: 'level', className: "text-center"},
                    {data: 'rank', className: "text-center"},
                    {data: 'sponsor_id', className: "text-center"},
                    {data: 'sponsor', className: "text-center" },
                ],
                columnDefs: [
                    {responsivePriority: 1, targets: 0},
                    {responsivePriority: 2, targets: 1},
                    {responsivePriority: 3, targets: 2},
                ]
            });
            
        },
        methods: {
            viewUplines() {
                
                this.filters.member_id = this.filters.member_id;

                this.dt.clear().draw();
                this.dt.responsive.recalc();

                client.get(`admin/upline-report/uplines/${this.filters.member_id}?tree_type=${this.filters.tree_type}`).then(response => {
                    this.dt.rows.add(response.data);
                    this.dt.columns.adjust().draw();
                });

            },
        },
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));