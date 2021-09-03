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
                    scrollX: true,
                    processing: false,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}admin/rank-progress`,
                        data: function (d) {
                            d.rank_id = _this.filters.rankId;
                            d.is_all_below = _this.filters.isAllBelow;
                        },
                    },
                    order: [[2, 'desc']],
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
                            data: 'rank_id',
                            render: function (data, type, row, meta) {
                                return row.current_rank;
                            }
                        },
                        {
                            data: 'paid_as_rank_id',
                            render: function (data, type, row, meta) {
                                return row.paid_as_rank;
                            }
                        },
                        {data: 'pv', className: "text-center"}, // render: $.fn.dataTable.render.number(',', '.', 2, '$')
                        {data: 'gv', className: "text-center"},
                        {data: 'group_volume_left_leg', className: "text-center"},
                        {data: 'group_volume_right_leg', className: "text-center"},
                        {data: 'active_personal_enrollment_count', className: "text-center"},
                        {
                            data: 'is_active',
                            className: "text-center",
                            render: function (data, type, row, meta) {

                                if (+row.is_active) {
                                    return `<span class="label label-success">Yes</span>`;
                                }

                                return `<span class="label label-warning">No</span>`;
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
            },
        },
    });


}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));