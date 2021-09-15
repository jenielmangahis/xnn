(function ($, api_url, Vue, swal, axios, location, moment, undefined) {

    const client = commissionEngine.createAccessClient(`member/enroller-tree`);
    commissionEngine.setupAccessTokenJQueryAjax();
    const lang = cookieValue = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
    $.fn._datepicker = jQuery.fn.datepicker;

    
    if(lang == 'english'){
        $.fn._datepicker.dates['en'] = {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
            months: ["Januarys", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            today: "Today",
            clear: "Clear",
            format: "YYYY-MM-DD",
            titleFormat: "MM yyyy", /* Leverages same syntax as 'format' */
            weekStart: 0
    };
     }
    else{
        $.fn._datepicker.dates['en'] = {
            days: ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"],
            daysShort: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"],
            daysMin: ["Do", "Lu", "Ma", "Me", "Gi", "Ve", "Sa"],
            months: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"],
            monthsShort: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
            today: "Oggi",
            monthsTitle: "Mesi",
            clear: "Cancella",
            format: "YYYY-MM-DD",
            titleFormat: "MM yyyy", /* Leverages same syntax as 'format' */
            weekStart: 0
    };
    
    }

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
            colspan: 12,
            today: moment().format("YYYY-MM-DD")
        },
        mounted() {
            this.owner_id = $('#member').val();
            this.order_history_user_id = $('#member').val();

            this.enroller = $('.enroller-tree #table-enroller');
            this.enrollerBody = $('.enroller-tree #table-enroller tbody');

            this.setupTree(this.owner_id);
            this.initializeDataTables();
            this.initializeJQueryEvents();
        },
        methods: {
            initializeDataTables() {
                let _this = this;
                this.order_history = $("#table-pea").DataTable({
                    destroy: true,
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    "searching": false,
                    ajax: {
                        url: `${api_url}member/enroller-tree/pea-users`,
                        data: function (d) {
                            d.user_id = _this.order_history_user_id;
                        },
                    },
                    order: [[0, 'asc']],
                    columns: [
                        {data: 'customer_name_redacted'},
                        {
                            data: 'date_accepted',
                            render: function (data, type, row, meta) {
                                let date = row.date_accepted;

                                if(date === null || date === 'N/A') {
                                        return 'N/A';
                                }
                                

                                return moment(date).format('DD-MM-YYYY');
                            },
                        },
                        {
                            data: 'date_flowing',
                            render: function (data, type, row, meta) {
                                let date = row.date_flowing;

                                if(date === null || date === 'N/A') {
                                    return 'N/A';
                                }

                                return moment(date).format('DD-MM-YYYY');
                            },
                        },
                        {
                            data: 'status',
                            render: function (data, type, row, meta) {
                                let status = row.status;
                                let currentLang = document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
                                if (currentLang == 'english'){
                                    return status;
                                }else{
                                    switch (status) {
                                        case 'Pending Confirmation':
                                            return 'Trasmesso';
                                        case 'Pending Approval':
                                            return 'Da attivare';
                                        case 'Pending Rejection':
                                            return 'Da verificare';
                                        case 'Approved, Pending flowing':
                                            return 'In attesa di inizio fornitura';
                                        case 'Flowing':
                                            return 'Attivo';
                                        case 'Flowing, Pending Cancellation':
                                            return 'Attivo, in fase di cessazione';
                                        case 'Change of ownership':
                                            return 'Voltura in corso';
                                        case 'Cancelled':
                                            return 'Cessato';
                                        default:
                                            return 'Mai attivato';
                                    }
                                }

                            },
                        },
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
                    $('#modal-pea').modal({backdrop: 'static', keyboard: false});
                });

                $('#modal-pea').on('shown.bs.modal', function (e) {

                    _this.order_history.responsive.draw();
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

                return `
                    <tr data-tt-id="${data.user_id}" data-tt-name="${data.member}"  data-tt-branch="${!!+data.branch}" ${data.parent_id !== undefined ? `data-tt-parent-id=${data.parent_id}` : ''} class="row-id-${data.user_id} table__row">
                        <td class="table__cell table__cell--align-middle">${data.user_id}</td>
                        <td class="table__cell table__cell--align-middle ${data.member}">${data.member}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${moment(data.enrolled_date).format('DD-MM-YYYY')}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.current_rank}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle"><button class="btn btn-link btn-show-order btn-xs" data-user-id="${data.user_id}">${data.pea}</button></td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.ta}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.mar}</td>
                        <td class="table__cell table__cell--text-center table__cell--align-middle">${data.qta}</td>
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
                       let 	cookieValue =	document.cookie.replace(/(?:(?:^|.*;\s*)selectedLang\s*\=\s*([^;]*).*$)|^.*$/, "$1");
                       if(cookieValue == 'italian'){
                        if (data['total_pages'] > 1) {
                            _this.enroller.treetable(
                                "loadBranch",
                                parentNode,
                                `<tr class="table__row" data-tt-id="paganation-${parentNode.id}" data-tt-branch="false"  data-tt-parent-id="${parentNode.id}">
                                    <td class="table__cell" colspan="${_this.colspan}" align="center">
                                        <a href="#" data-pageno="${data['pageno']}" data-parent-id="${parentNode.id}" class="show-more-downlines">
                                            <span class="show-more">MOSTRA PIÙ DOWNLINE: </span> ${data['total_downlines']} <span class="undisplayed-downlines">DOWNLINES ANCORA DA SCOPRIRE.</span>
                                        </a>
                                    </td>
                                </tr>`
                            );
                        }
                       }else{
                        if (data['total_pages'] > 1) {
                            _this.enroller.treetable(
                                "loadBranch",
                                parentNode,
                                `<tr class="table__row" data-tt-id="paganation-${parentNode.id}" data-tt-branch="false"  data-tt-parent-id="${parentNode.id}">
                                    <td class="table__cell" colspan="${_this.colspan}" align="center">
                                        <a href="#" data-pageno="${data['pageno']}" data-parent-id="${parentNode.id}" class="show-more-downlines">
                                            <span class="show-more">Show more downlines:</span> ${data['total_downlines']} <span class="undisplayed-downlines">undisplayed downlines.</span>
                                        </a>
                                    </td>
                                </tr>`
                            );
                        }
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
                client.get(`parent/${parentID}/children/${pageNo}/${start_date}`).then(response => {
                    callback(response.data);
                }).catch(error => {
                    callback({});
                });
            },

        }
    });

}(jQuery, window.commissionEngine.API_URL, Vue, swal, axios, window.location, moment));