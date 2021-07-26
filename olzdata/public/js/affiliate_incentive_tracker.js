(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#incentive-progress",
        data: {
            dt: null,
            incentives: [],
            incentiveState: "loaded", // loaded/fetching/error
            incentiveId: 0 ,
            
            filters: {
                incentiveId: "",
            }
        },
        mounted() {
            this.getOpenIncentives();
            this.initializeDataTables();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.dt = $("#table-incentive-progress").DataTable({
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/incentive-report/progress`,

                        data: function (d) {
                            d.incentiveId = _this.filters.incentiveId;
                        },
                    },
                    order: [],
                    columns: [
                        {
                            data: 'user_id'
                        },
                        {
                            data: 'member_name'
                        },
                        {
                            data: 'sponsor_name'
                        },
                        {
                            data: 'level'
                        },
                        {
                            data: 'points'
                        }
                    ],
                    columnDefs: [

                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                        {responsivePriority: 3, targets: 1},
                    ]
                });
            },
            getOpenIncentives() {

                if (this.incentiveState === "fetching") return;

                this.incentiveState = "fetching";
                this.incentives = [];
                this.incentiveId = "";

                client.get(`${api_url}member/incentive-report/available`)
                    .then(response => {
                        this.incentives = response.data;
                        this.incentiveState = "loaded";

                    })
                    .catch(error => {
                        this.incentiveState = "error";
                    })

            },
            view() {
                this.filters.incentiveId = this.incentiveId;

                this.dt.clear().draw();
            },
        },
        watch:{
            incentiveId: function(val){
                if(val){
                    this.filters.incentiveId = val;
                    this.dt.clear().draw();

                }
            }
        }
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));