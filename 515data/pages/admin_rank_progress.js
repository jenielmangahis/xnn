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
                    searching: false,
                    lengthChange: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/rank-progress`,
                        data: function (d) {
                            d.rank_id = _this.filters.rankId;
                            d.is_all_below = _this.filters.isAllBelow;
                        },
                    },
                    data: [],
                    order: [[2, 'asc']],
                    columns: [
                        {
                            data: 'user_id',
                            render: function (data, type, row, meta) {
                                let user_id = row.user_id;
                                let member = row.member;
                                return `${user_id}: ${member}`;
                            }
                        },
                        {
                            data: 'paid_as_rank_id',
                            render: function (data, type, row, meta) {
                                return row.paid_as_rank;
                            }
                        },
                        {   
                            data: 'pea',
                            className: "text-center"
                        },
                        {   
                            data: 'ta',
                            className: "text-center"
                        },
                        {   
                            data: 'current_rank_id',
                            render: function (data, type, row, meta) {
                                let current_rank_id = row.current_rank_id;
                                let mar = 0

                                switch (current_rank_id) {
                                    case 6:
                                        mar = 48;
                                        break;
                                    case 7:
                                        mar = 90;
                                        break;
                                    case 8:
                                        mar = 210;
                                        break;
                                    case 9:
                                        mar = 420;
                                        break;
                                    case 10:
                                        mar = 900
                                        break;
                                    case 11:
                                        mar = 2100
                                        break;
                                    case 12:
                                        mar = 4200
                                        break;
                                }
                                return mar;
                            },
                            className: "text-center"
                        },
                        {   
                            data: 'qta',
                            className: "text-center"
                        },
                        { data: 'is_active' },
                        { data: 'level', className: "text-center" },
                        {
                            data: 'sponsor_id',
                            render: function (data, type, row, meta) {
                                let sponsor_id = row.sponsor_id;
                                let sponsor = row.sponsor;
                                return `${sponsor_id}: ${sponsor}`;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            render: function (data, type, row) {
                                let needs = row.needs;
                                let list = '';

                                for (let i = 0; i < needs.length; i++) {
                                    let n = needs[i];

                                    if(typeof n.html !== "undefined") {
                                        list += `<li>${n.html}</li>`
                                    } else {
                                        list += `<li><strong>${n.value}</strong> ${n.description}</li>`
                                    }
                                }

                                return `<ul class="list-unstyled">${list}</ul>`;
                            }
                        },
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