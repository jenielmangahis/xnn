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
            this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-rank-progress").DataTable({
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search",
                        paginate: {
                            next: 'Next',
                            previous: 'Previous &nbsp;&nbsp;&nbsp;|'
                        }
                    },
                    responsive: true,
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