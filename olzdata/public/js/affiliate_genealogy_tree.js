(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient(`member/enroller-tree`);
    commissionEngine.setupAccessTokenJQueryAjax();

    const vm = new Vue({
        el: "#enroller-tree",
        data: {
            enrollment: {
                start_date: moment().format("YYYY-MM-DD"),
                filters: {
                    start_date: moment().format("YYYY-MM-DD"),
                }
            },
            owner_id: null,
            root_id: null,
            cancel_previous_request: false,
            enroller: null,
            enrollerBody: null,
            downline_id: null,
            order_history: null,
            order_history_user_id: null,
            order_history_name: null,
            wishlist_user_id: null,
            wishlist_name: null,
            colspan: 12,
            today: moment().format("YYYY-MM-DD")
        },
        mounted() {
            this.owner_id = $('#member').val();
            this.order_history_user_id = $('#member').val();
            this.wishlist_user_id = $('#member').val();

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
                    ajax: {
                        url: `${api_url}member/enroller-tree/order-history`,
                        data: function (d) {
                            d.user_id = _this.order_history_user_id;
                        },
                    },
                    order: [[1, 'desc']],
                    columns: [
                        {data: 'invoice'},
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

                this.wishlist = $("#table-wishlist").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: `${api_url}member/enroller-tree/wishlist`,
                        data: function (d) {
                            d.user_id = _this.wishlist_user_id;
                        },
                    },
                    order: [[1, 'desc']],
                    columns: [
                        {data: 'product_name'},
                        {data: 'quantity', className: 'text-center'},
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

                    _this.order_history.draw();
                });

                _this.enroller.on('click', '.btn-show-wishlist', function (e) {
                    e.preventDefault();

                    let user_id = $(this).closest('tr').data('tt-id');
                    let name = $(this).closest('tr').data('tt-name');

                    _this.wishlist_user_id = user_id;
                    _this.wishlist_name = name;

                    _this.wishlist.clear().draw();
                    $('#modal-wishlist').modal({backdrop: 'static', keyboard: false});
                });

                $('#modal-wishlist').on('shown.bs.modal', function (e) {

                    _this.wishlist.draw();
                });

                var members = new Bloodhound({

                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('display'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    remote: {
                        url:  api_url + 'member/enroller-tree/user-downlines',
                        prepare: function(query, settings){
                            settings.url += '?q=' + query +'&f='+$('#member-filter-by').val()+'&m='+$('#member').val();
                            return settings;
                        },
                        wildcard: '%QUERY',
                    }
                });

                members.initialize();



                $('#typeahead-member-name').typeahead(
                    {
                        highlight: true,
                        minLength: 1
                    },
                    {
                        name: 'typeahead-member-name',
                        displayKey: 'display',
                        templates: {
                            empty: [
                                '<div class="empty-message">',
                                "Member doesn't exist.",
                                '</div>'
                            ].join('\n'),

                            suggestion:  function(data) {
                                return '<p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"> #'+data.id+': '+ data.value + '  </br><i>Site URL: '+data.site + '</i></p>';
                            }
                        },
                        limit:'Infinity',
                        source: members.ttAdapter()
                    }).on('typeahead:select', function(evt, item) {

                    var _this = $(this);
                    _this.parent().parent().find('.hidden-id').val(item.id);
                    _this.parent().parent().find('.display').val(item.display);
                    _this.parent().parent().find('.display').removeClass('hide');
                    _this.parent().parent().find('.twitter-typeahead').addClass('hide');
                    _this.parent().parent().find('.loader').addClass('hide');
                    _this.parent().parent().find('.clear-typeahead').removeClass('hide');

                    //$(this).parent().parent().find('.cancel-btn').removeClass('hide');

                }).on('typeahead:asyncrequest', function() {
                    $(this).parent().parent().find('.loader').removeClass('hide');
                }).on('typeahead:asynccancel typeahead:asyncreceive', function() {
                    $(this).parent().parent().find('.loader').addClass('hide');
                });


                $('.filter-by').change(function(e){

                    var _this = $(this);

                    _this.parent().parent().find('.twitter-typeahead').removeClass('hide');
                    _this.parent().parent().find('.typeahead').val(null);
                    _this.parent().parent().find('.display').addClass('hide');
                    _this.parent().parent().find('.clear-typeahead').addClass('hide');
                    _this.parent().parent().find('.hidden-id').val(0);
                    _this.parent().parent().find('.display').val('');
                    e.preventDefault();
                });

                $('.clear-typeahead').click(function(e){
                    $('.typeahead-member-name').typeahead('setQuery', '');
                    var _this = $(this);
                    _this.addClass('hide');
                    _this.parent().parent().find('.twitter-typeahead').removeClass('hide');
                    _this.parent().parent().find('.typeahead').val(null);
                    _this.parent().parent().find('.display').addClass('hide');
                    _this.parent().parent().find('.hidden-id').val(0);
                    _this.parent().parent().find('.display').val('');
                    e.preventDefault();
                });

                // $('#btn-make-root').click(function(e){
                //     e.preventDefault();
                //
                // });

                $('#btn-reset').click(function(e){
                    e.preventDefault();
                    $('#btn-reset').addClass('hide');
                    var id =$('#member').val();
                    $('.clear-typeahead').trigger('click');
                    _this.setupTree(id);
                });

            },
            getSelectedDate(start_date){
                this.enrollment.filters.start_date = start_date;
            },
            selectionChange(value) {
                if (!!value) {
                    this.root_id = value;
                } else {
                    this.root_id = this.owner_id;
                }

                this.setupTree(this.root_id);

            },
            viewDownline() {
                console.log("here");
                $('#btn-reset').addClass('hide');
                $('.wError').html('');
                var id =$('#hidden-member-id').val();
                var root_id = $('#member').val();
                var member_id;
                if(id == 0) {
                    member_id = root_id;
                }else {
                    member_id = id;
                }
                this.enrollment.filters.start_date = this.enrollment.start_date;
                $('#btn-reset').removeClass('hide');
                this.setupTree(member_id);

            },
            setupTree(parentID) {
                let _this = this;
                this.cancel_previous_request = true;
                this.enrollment.filters.start_date = this.enrollment.start_date;
                if (_this.enroller.data('treetable') !== undefined) {
                    _this.enroller.treetable('destroy');
                    _this.enrollerBody.html(`<tr class="table__row"><td class="table__row" colspan="${_this.colspan}" align="center"><i class="fa fa-spin fa-spinner"></i> Generating data...</td></tr>`);
                }

                _this.getParent(parentID, this.enrollment.filters.start_date, function (data) {

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

                let has_first_90_days = `<span class="label label-warning">No</span>`;

                if (data.has_first_90_days == 'Yes') {
                    has_first_90_days = `<span class="label label-success">Yes</span>`;
                }

                return `
                    <tr data-tt-id="${data.user_id}" data-tt-name="${data.member}"  data-tt-branch="${!!+data.branch}" ${data.parent_id !== undefined ? `data-tt-parent-id=${data.parent_id}` : ''} class="row-id-${data.user_id} table__row">
                        <td class="table__cell table__cell--align-middle">${data.user_id}</td>
                        <td class="table__cell table__cell--align-middle">${data.member}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.paid_as_rank}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.prs}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.grs}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.sponsored_qualified_representatives}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.sponsored_leader_or_higher}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.sponsored_leader_or_higher_count}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">
                            <button class="btn btn-link btn-sm btn-show-order">View</button>
                        </td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${has_order_last_30_days}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${has_first_90_days}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">
                            <button class="btn btn-link btn-sm btn-show-wishlist">View</button>
                        </td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.enrolled_date}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle" id="enroller-tree-level-${data.user_id}">${data.level}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.sponsor}</td>
                    </tr>
                `;
            },
            nodeExpand(parentNode, pageNo) {

                let _this = this;
               _this.enrollment.filters.start_date =  _this.enrollment.start_date;
                if (pageNo === undefined) {
                    pageNo = 1;
                }

                _this.getChildren(parentNode.id, pageNo, _this.enrollment.filters.start_date, function (data) {

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
            getParent(parentID, start_date, callback) {
                client.get(`parent/${parentID}/${start_date}`).then(response => {
                    callback(response.data);
                }).catch(error => {
                    callback({});
                });
            },
            getChildren(parentID, pageNo, start_date, callback) {
                console.log("pageNo", pageNo);
                client.get(`parent/${parentID}/children/${pageNo}/${start_date}`).then(response => {
                    callback(response.data);
                }).catch(error => {
                    callback({});
                });
            },

        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));