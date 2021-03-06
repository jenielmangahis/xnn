(function ($, api_url, Vue, swal, axios, location, undefined) {

    const client = commissionEngine.createAccessClient(`member/enroller-tree`);
    commissionEngine.setupAccessTokenJQueryAjax();
    
    const vm = new Vue({
        el: "#enroller-tree",
        data: {
            owner_id: null,
            root_id: null,
            cancel_previous_request: false,
            enroller: null,
            enrollerBody: null,
            autocomplete_url: `${api_url}common/autocomplete/enroller-downline`,
            downline_id: null,
            order_history: null,
            order_history_user_id: null,

            active_personal_enrollment: null,
            active_personal_enrollment_user_id: null,
            order_history_name: null,
            colspan: 12,
        },
        mounted() {
            this.owner_id = $('#member').val();
            this.order_history_user_id = $('#member').val();
            this.active_personal_enrollment_user_id = $('#member').val();

            this.enroller = $('.enroller-tree #table-enroller');
            this.enrollerBody = $('.enroller-tree #table-enroller tbody');

            this.setupTree(this.owner_id);
            this.initializeDataTables();
            this.initializeJQueryEvents();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.order_history = $("#table-order-history").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: {
                        url: `${api_url}member/enroller-tree/order-history`,
                        data: function (d) {
                            d.user_id = _this.order_history_user_id;
                        },
                    },
                    order: [[1, 'desc']],
                    columns: [
                        {data: 'transaction_id'},
                        {
                            data: 'products',
                            orderable: false,
                            render: function (data, type, row) {

                                if(data === null) return null;

                                let products = JSON.parse(data);

                                let list = ``;

                                for(let i = 0; i < products.length; i++) {
                                    let p = products[i];
                                    list += `<li><strong>${p.quantity}x</strong> - ${p.product}</li>`
                                }

                                return `<ul class="list-unstyled">${list}</ul>`;
                            }
                        },
                        {data: 'transaction_date'},
                        {data: 'amount'},
                    ]
                });
                this.active_personal_enrollment = $("#table-active-personal-enrollment").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    searching: false,
                    ajax: {
                        url: `${api_url}member/enroller-tree/active-personal-enrollment`,
                        data: function (d) {
                            d.user_id = _this.active_personal_enrollment_user_id;
                        },
                    },
                    order: [[1, 'asc']],
                    columns: [
                        {data: 'name'},
                        {data: 'enrolled_date'},
                    ]
                });

                
            },
            initializeJQueryEvents() {
                let _this = this;

                _this.enroller.on('click', '.show-more-downlines', function (e) {
                    e.preventDefault();

                    let parentID = $(this).data('parent-id');
                    let pageNo = +$(this).data('pageno');

                    $('.enroller-tree tr[data-tt-id="paganation-' + parentID + '"] > td').html('Loading additional downlines <i class="fa fa-spinner fa-spin"></i>');

                    let parentNode = _this.enroller.treetable("node", parentID);
                    _this.nodeExpand(parentNode, pageNo);
                });

                _this.enroller.on('click', '.btn-show-order', function (e) {
                    e.preventDefault();

                    let user_id = $(this).closest('tr').data('tt-id');
                    let name = $(this).closest('tr').data('tt-name');

                    _this.order_history_user_id = user_id;
                    _this.order_history_name = name;

                    _this.order_history.clear().draw();
                    $('#modal-order-history').modal({backdrop: 'static', keyboard: false});
                });

                $('#modal-order-history').on('shown.bs.modal', function (e) {
                    _this.order_history.responsive.recalc();
                });

                _this.enroller.on('click', '.btn-show-active-personal-enrollment', function (e) {
                    e.preventDefault();

                    let user_id = $(this).closest('tr').data('tt-id');

                    _this.active_personal_enrollment_user_id = user_id;

                    _this.active_personal_enrollment.clear().draw();
                    $('#modal-active-personal-enrollment').modal({backdrop: 'static', keyboard: false});
                });

                $('#modal-active-personal-enrollment').on('shown.bs.modal', function (e) {
                    _this.active_personal_enrollment.responsive.recalc();
                })



            },
            selectionChange(value) {
                if (!!value) {
                    this.root_id = value;
                } else {
                    this.root_id = this.owner_id;
                }

                this.setupTree(this.root_id);

            },
            setupTree(parentID) {
                let _this = this;
                _this.cancel_previous_request = true;

                if (_this.enroller.data('treetable') !== undefined) {
                    _this.enroller.treetable('destroy');
                    _this.enrollerBody.html(`<tr class="table__row"><td class="table__row" colspan="${_this.colspan}" align="center"><i class="fa fa-spin fa-spinner"></i> Generating data...</td></tr>`);
                }

                _this.getParent(parentID, function (data) {

                    data.level = 0;
                    let html = _this.rowTemplate(data);

                    _this.enrollerBody.empty();
                    _this.enrollerBody.append(html);

                    _this.enroller.treetable({
                        expandable: true,
                        onNodeExpand: function () {
                            if (this.children.length > 0) return; // DO NOT FETCH CHILDREN
                            _this.nodeExpand(this);
                        },
                    });

                    // Copy additional info from xen

                    _this.enroller.treetable("reveal", parentID);
                    _this.enrollerBody.append(`<tr class="table__row" data-tt-id="paganation-${parentID}"><td class="table__cell" colspan="${_this.colspan}" align="center"><i class="fa fa-spin fa-spinner"></i> Generating data...</td></tr>`);

                })

            },
            rowTemplate(data) {

                let has_order_last_30_days = `<span class="label label-warning">No</span>`

                if (+data.has_order_last_30_days) {
                    has_order_last_30_days = `<span class="label label-success">Yes</span>`;
                }

                return `
                    <tr data-tt-id="${data.user_id}" data-tt-name="${data.member}"  data-tt-branch="${!!+data.branch}" ${data.parent_id !== undefined ? `data-tt-parent-id=${data.parent_id}` : ''} class="row-id-${data.user_id} table__row">
                        <td class="table__cell table__cell--align-middle">${data.user_id}</td>
                        <td class="table__cell table__cell--align-middle">${data.member}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.paid_as_rank}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.pv}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.bv}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.left_leg}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.right_leg}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">
                            <button class="btn btn-link btn-sm btn-show-active-personal-enrollment">View</button>
                        </td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">
                            <button class="btn btn-link btn-sm btn-show-order">View</button>
                        </td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${has_order_last_30_days}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.enrolled_date}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.sponsor}</td>
                    </tr>
                `;
            },
            nodeExpand(parentNode, pageNo) {

                let _this = this;

                if (pageNo === undefined) {
                    pageNo = 1;
                }

                _this.getChildren(parentNode.id, pageNo, function (data) {

                    let level = +$('.enroller-tree #enroller-tree-level-' + parentNode.id).html() + 1;
                    let html = "";

                    for (let i = 0; i < data.downlines.length; i++) {
                        let child = data.downlines[i];

                        child.level = level;
                        child.parent_id = parentNode.id;

                        html += _this.rowTemplate(child);
                    }

                    _this.enroller.treetable("loadBranch", parentNode, html);

                    $('.enroller-tree tr[data-tt-id="paganation-' + parentNode.id + '"] > td').remove();

                    if (data['total_pages'] > 1) {
                        _this.enroller.treetable(
                            "loadBranch",
                            parentNode,
                            `<tr class="table__row" data-tt-id="paganation-${parentNode.id}" data-tt-branch="false"  data-tt-parent-id="${parentNode.id}">
                                <td class="table__cell" colspan="${_this.colspan}" align="center">
                                    <a href="#" data-pageno="${data['pageno']}" data-parent-id="${parentNode.id}" class="show-more-downlines">
                                        Show more downlines: ${data['total_downlines']} undisplayed downlines.
                                    </a>
                                </td>
                            </tr>`
                        );
                    }
                    // copy additional info from xen

                });
            },
            getParent(parentID, callback) {
                client.get(`parent/${parentID}`).then(response => {
                    callback(response.data);
                }).catch(error => {
                    callback({});
                });
            },
            getChildren(parentID, pageNo, callback) {
                client.get(`parent/${parentID}/children/${pageNo}`).then(response => {
                    callback(response.data);
                }).catch(error => {
                    callback({});
                });
            },

        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location));