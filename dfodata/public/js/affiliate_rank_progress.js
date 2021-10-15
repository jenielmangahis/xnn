(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#rank-progress",
        data: {
            dt: null,
            ranks: [],
            rankState: "loaded", // loaded/fetching/error
            rankId: "",
            isAllBelow: 0,
            
            filters: {
                rankId: "",
                isAllBelow: 0,
            }
        },
        mounted() {
            this.getRanks();
            //this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-rank-progress").DataTable({
                    // searching: false,
                    // lengthChange: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/rank-progress`,
                        data: function (d) {
                            d.rank_id = _this.filters.rankId;
                            d.is_all_below = _this.filters.isAllBelow;
                        },
                    },
                    order: [[2, 'asc']],
                    columns: [
                        { data: 'member' },
                        { data: 'level' },
                        { data: 'current_rank' },
                        { data: 'paid_as_rank' },
                        {data: 'pv', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'l1v', className: "text-center"},
                        {data: 'l1vneeds', className: "text-center"}                        
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: 1},
                    ]
                });
            },
            getRanks() {

                if (this.rankState === "fetching") return;

                this.rankState = "fetching";
                this.ranks = [];
                this.rankId = "";

                client.get("common/ranks?excludes_ids=1")
                    .then(response => {
                        this.ranks = response.data;
                        this.rankState = "loaded";

                    })
                    .catch(error => {
                        this.rankState = "error";
                    })

            },
            view() {

                this.filters.rankId = this.rankId;
                this.filters.isAllBelow = this.isAllBelow;

                this.dt.clear().draw();
                this.dt.responsive.recalc();
            },
        },
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));