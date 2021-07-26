(function ($, api_url, Vue, swal, moment, axios, location, undefined) {

    const client = commissionEngine.createAccessClient();
    commissionEngine.setupAccessTokenJQueryAjax();

    let $dtRunning;
    let $dtClosed;
    let $dtArbitrary;
    $.fn.ddatepicker = $.fn.datepicker;

    const vm = new Vue({
        el: ".admin-incentive-tool",
        data: {
            settings_id: 0,
            is_active: 1,
            is_locked: 0,
            today: moment().format("YYYY-MM-DD"),
            settings: {
                title: '',
                description: '',
                start_date: null,
                end_date: null,
                is_display_insentives:0,
                is_double_points_on: 0,
                double_points_start_date: null,
                double_points_end_date: null,
                is_points_per_prs: 0,
                points_per_prs:0,
                is_promote_to_or_higher:0,
                promote_to_or_higher_points:0,
                rank_id: 0,
                is_has_new_representative:0,
                new_representative_points:0,
                new_representative_start_date: null,
                new_representative_end_date: null,
                new_representative_min_prs:0,
                new_representative_first_n_days: 1,
                is_double_points_new_representative:0,
                double_points_new_representative_start_date: null,
                double_points_new_representative_end_date: null,
                double_points_new_representative_first_n_days: 1,
            },
            ranks: {},
            arbitrary: {
                representative_id: null,
                listRepresentatives: {},
                incentive_id: null,
                openIncentives: {},
                bonus_points: null
            },
            is_processing: false,
            downloadLinkState: '',
            downloadLink: '',
        },
        mounted() {
            this.initializeDataTables();
            this.initializeJQueryEvents();
            this.initializeDatePicker();
            this.getRanks();
            this.getIncentives();
        },
        methods: {
            initializeDataTables() {
                let _this = this;

                $dtRunning = $("#running_incentives").DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        "url": api_url + 'admin/incentives/running',
                        "data": function (d) {
                            
                        },
                    },
                    columns: [
                        {data: 'title'},
                        {data: 'period'},
                        {
                            data: 'action',
                            width: '200px',
                            render: function (data, type, row, meta) {
                                return `<button class="btn btn-info btn-view-representatives">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn  btn-success btn-edit-representatives">
                                            <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn  btn-info">
                                            <i class="fa fa-download btn-download-representatives" aria-hidden="true" v-bind:disabled="_this.downloadLinkState === 'fetching'"></i>
                                        </button>
                                        <button class="btn btn-danger btn-delete-incentive">
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>`;
                            }
                        },
                  
                    ],
                    // columnDefs: [

                    //     {responsivePriority: 1, targets: 0},
                    //     {responsivePriority: 2, targets: -1},
                    // ]
                });

                $dtClosed = $("#close_incentives").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    // pageLength: 25,
                    ajax: {
                        "url": api_url + 'admin/incentives/closed',
                        "data": function (d) {
                            
                        },
                    },
                    columns: [
                        {data: 'title'},
                        {data: 'period'},
                        {
                            data: 'action',
                            width: '200px',
                            render: function (data, type, row, meta) {
                                return `<button class="btn btn-info btn-hide-incentive">
                                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                                        </button>
                                        <button class="btn  btn-info btn-view-representatives">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>`;
                            }
                        },
                  
                    ],
                });

                $dtArbitrary = $("#arbitrary_points").DataTable({
                    processing: true,
                    serverSide: true,
                    // responsive: true,
                    // pageLength: 25,
                    ajax: {
                        "url": api_url + 'admin/incentives/arbitrary-points',
                        "data": function (d) {
                            
                        },
                    },
                    columns: [
                        {data: 'user_id'},
                        {data: 'name'},
                        {data: 'title'},
                        {data: 'points'},
                        {data: 'bonus_points'},
                        {data: 'total'},
                        {
                            data: 'action',
                            width: '200px',
                            render: function (data, type, row, meta) {
                                return `<button class="btn  btn-danger btn-delete-arbitrary-points"><i class="fa fa-trash-o" aria-hidden="true"></i>`;
                            }
                        },
                    ],
                    columnDefs: [
                        {responsivePriority: 1, targets: 0},
                        {responsivePriority: 2, targets: -1},
                    ]
                });

                $dtIncentiveReps = $("#view_incentive_representatives").DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: false,
                    // pageLength: 25,
                    ajax: {
                        "url": api_url + 'admin/incentives/view-incentive-reps',
                        "data": function (d) {
                            d.id = _this.settings_id;
                            d.is_active = _this.is_active;
                            d.is_locked = _this.is_locked;
                        },
                    },
                    columns: [
                        {data: 'user_id'},
                        {data: 'name'},
                        {data: 'points'},
                        {data: 'bonus_points'}
                    ],
                });
            },
            initializeJQueryEvents() {
                let _this = this;
                $('#running_incentives tbody').on('click', '.btn-view-representatives', function () {
                    let data = $dtRunning.row($(this).parents('tr')).data();
                    _this.showRepresentatives(data, 1, 0);
                });
                $('#running_incentives tbody').on('click', '.btn-edit-representatives', function () {
                    let data = $dtRunning.row($(this).parents('tr')).data();
                    _this.editIncentiveSettings(data.settings_id);
                });
                $('#running_incentives tbody').on('click', '.btn-download-representatives', function () {
                    let data = $dtRunning.row($(this).parents('tr')).data();
                    _this.downloadRepresentatives(data.settings_id, data.title);
                });
                $('#running_incentives tbody').on('click', '.btn-delete-incentive', function () {
                    let data = $dtRunning.row($(this).parents('tr')).data();
                    _this.deleteIncentiveSettings(data.settings_id, data.title);
                });

                $('#close_incentives tbody').on('click', '.btn-hide-incentive', function () {
                    let data = $dtClosed.row($(this).parents('tr')).data();
                    _this.hideIncentive(data.settings_id, data.title);
                });
                $('#close_incentives tbody').on('click', '.btn-view-representatives', function () {
                    let data = $dtClosed.row($(this).parents('tr')).data();
                    _this.showRepresentatives(data, 0, 1);
                });
                $('#arbitrary_points tbody').on('click', '.btn-delete-arbitrary-points', function () {
                    let data = $dtArbitrary.row($(this).parents('tr')).data();
                    _this.deleteArbitraryPoints(data);
                });

                var members = new Bloodhound({

                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('display'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    remote: {
                        url:  api_url + 'admin/incentives/get-representatives',
                        prepare: function(query, settings){
                            settings.url += '?q=' + query +'&f='+$('#member-filter-by').val();
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
                                return '<p style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"> #'+data.user_id+': '+ data.value + ' ('+data.site + ')</p>';
                            }
                        },
                        limit:'Infinity',
                        source: members.ttAdapter()
                    }).on('typeahead:select', function(evt, item) {

                    var _this = $(this);
                    _this.parent().parent().find('.hidden-id').val(item.user_id);
                    _this.parent().parent().find('.display').val(item.display);
                    _this.parent().parent().find('.display').removeClass('hide');
                    _this.parent().parent().find('.twitter-typeahead').addClass('hide');
                    _this.parent().parent().find('.loader').addClass('hide');
                    _this.parent().parent().find('.clear-typeahead').removeClass('hide');

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

                $('#btn-reset').click(function(e){
                    e.preventDefault();
                    $('#btn-reset').addClass('hide');
                    var id =$('#member').val();
                    $('.clear-typeahead').trigger('click');
                    _this.setupTree(id);
                });
 
            },
            initializeDatePicker() {
                _this = this;              
                $('input[name="period_range"]').daterangepicker({
                    locale: {
                        format: 'YYYY-MM-DD',
                        cancelLabel: 'Clear',
                      }
                }, function(start, end, label) {
                    _this.settings.start_date = start.format('YYYY-MM-DD');
                    _this.settings.end_date = end.format('YYYY-MM-DD');
                });

                // double points on
                $('#date-from').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {

                    $('#date-to').ddatepicker('setStartDate', e.date);
                    _this.settings.double_points_start_date = e.format("yyyy-mm-dd");
                    _this.settings.double_points_end_date = $("#date-to").val();

                    if ($('#date-to').ddatepicker('getDate') < e.date) {
                        $('#date-to').ddatepicker('setDate', e.date);
                        _this.settings.double_points_end_date = $("#date-to").val();
                    }
                });

                $('#date-to').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    _this.settings.double_points_end_date = e.format("yyyy-mm-dd");
                });

                $('#date-from').ddatepicker('setDate', new Date());
                $('#date-to').ddatepicker('setDate', new Date());

                // has enrolled new representative
                $('#range-date-from').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#range-date-to').ddatepicker('setStartDate', e.date);
                    _this.settings.new_representative_start_date = e.format("yyyy-mm-dd");
                    _this.settings.new_representative_end_date = $("#range-date-to").val();

                    if ($('#range-date-to').ddatepicker('getDate') < e.date) {
                        $('#range-date-to').ddatepicker('setDate', e.date);
                        _this.settings.new_representative_end_date = $("#range-date-to").val();
                    }
                });

                $('#range-date-to').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    _this.settings.new_representative_end_date = e.format("yyyy-mm-dd");
                });

                $('#range-date-from').ddatepicker('setDate', new Date());
                $('#range-date-to').ddatepicker('setDate', new Date());

                // double points for new representative
                $('#enroll-date-from').ddatepicker({
                    "setDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    $('#enroll-date-to').ddatepicker('setStartDate', e.date);

                    _this.settings.double_points_new_representative_start_date = e.format("yyyy-mm-dd");
                    _this.settings.double_points_new_representative_end_date = $("#enroll-date-to").val();

                    if ($('#enroll-date-to').ddatepicker('getDate') < e.date) {
                        $('#enroll-date-to').ddatepicker('setDate', e.date);
                        _this.settings.double_points_new_representative_end_date = $("#enroll-date-to").val();
                    }
                });

                $('#enroll-date-to').ddatepicker({
                    "setDate": new Date(),
                    "startDate": new Date(),
                    "format": "yyyy-mm-dd"
                }).on('changeDate', function (e) {
                    _this.settings.double_points_new_representative_end_date = e.format("yyyy-mm-dd");
                });

                $('#enroll-date-from').ddatepicker('setDate', new Date());
                $('#enroll-date-to').ddatepicker('setDate', new Date());

                $(".prev-tab").on('click', function(event) {

                    event.preventDefault();
                
                    let $decriptionTab = $("#nav-description-tab");
                
                    $decriptionTab.addClass("active");
                    $decriptionTab.attr("aria-delected", "true");
                
                    let $decriptionContainer = $("#nav-description");
                
                    $decriptionContainer.addClass("active");
                    $decriptionContainer.addClass("show");
                
                    let $rulesTab = $("#nav-rules-tab");
                
                    $rulesTab.removeClass("active");
                    $rulesTab.attr("aria-delected", "false");
                
                    let $rulesContainer = $("#nav-rules");
                    $rulesContainer.removeClass("active");
                    $rulesContainer.removeClass("show");
                
                });
                
                $(".next-tab").on('click', function(event) {
                
                    event.preventDefault();
                
                    let $decriptionTab = $("#nav-description-tab");
                
                    $decriptionTab.removeClass("active");
                    $decriptionTab.attr("aria-delected", "false");
                
                    let $decriptionContainer = $("#nav-description");
                
                    $decriptionContainer.removeClass("active");
                    $decriptionContainer.removeClass("show");
                
                    let $rulesTab = $("#nav-rules-tab");
                
                    $rulesTab.addClass("active");
                    $rulesTab.attr("aria-delected", "true");
                
                    let $rulesContainer = $("#nav-rules");
                    
                    $rulesContainer.addClass("active");
                    $rulesContainer.addClass("show");

                });
            },
            showRepresentatives(data, is_active, is_locked) {
                
                this.settings_id = data.settings_id;
                this.is_active = is_active;
                this.is_locked = is_locked;
                
                $dtIncentiveReps.clear().draw();
                $('#view-incentive').modal({backdrop: 'static', keyboard: false});
            },
            addIncentive() {

                $('#add-incentive').modal('show');
            },
            editIncentiveSettings(settings_id) {
                
                client.get(`admin/incentives/get-incentive/${settings_id}`)
                .then(response => {
                    this.settings = response.data;
                    
                    var period_range = this.settings.start_date +' - '+this.settings.end_date;
                    $('#period_range').val(period_range);

                    $('#add-incentive').modal('show');
                }).catch(error => {
                    swal('Unable to fetch!','','error');
                    return;
                });
                
            },
            downloadRepresentatives(settings_id, title) {
                swal({
                    title: "Download Qualified Representatives",
                    text: "Are you sure you want to download all qualified representatives in "+title+"?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((download) => {
                    
                    if(download) {
                        this.is_processing = 1;

                        this.downloadLinkState = "fetching";
                        this.downloadLink = "";

                        client.get(`admin/incentives/download/${settings_id}`)
                        .then(response => {
                            this.downloadLinkState = "done";
                            this.downloadLink = response.data.link;

                            if (this.downloadLink) {
                                window.location = this.downloadLink;
                            }
                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;
                            this.downloadLinkState = "error";

                        });
                    }
                });
            },
            deleteIncentiveSettings(settings_id, title) {
                swal({
                    title: "Delete Incentive Tool",
                    text: "Are you sure you want to delete " + title +" Incentive Tool?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((deleteIncentive) => {
                    this.is_processing = 1;
                    if(deleteIncentive) {
                        
                        client.post(`admin/incentives/${settings_id}/delete`).then(response => {
                            
                            this.is_processing = 0;

                            swal({
                                title: "Success!",
                                text: "Successfully Deleted",
                                icon: "success",
                            });


                            $dtRunning.clear().draw();
                            $dtRunning.responsive.recalc();

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;

                        });
                    }
                });

            },
            hideIncentive(settings_id, title) {
                swal({
                    title: "Hide Incentive Tool",
                    text: "Are you sure you want to hide " + title +"?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((hideIncentive) => {

                    this.is_processing = 1;

                    if(hideIncentive) {
                        
                        client.post(`admin/incentives/${settings_id}/hide`).then(response => {
                            
                            this.is_processing = 0;

                            swal({
                                title: "Success!",
                                text: "Successfully Updated",
                                icon: "success",
                            });


                            $dtClosed.clear().draw();
                            $dtClosed.responsive.recalc();

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;

                        });
                    }
                });
            },
            deleteArbitraryPoints(data) {
                swal({
                    title: "Delete Arbitrary Points",
                    text: "Are you sure you want to delete bonus points("+data.bonus_points+") for "+data.name +"?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((deleteArbitrary) => {

                    this.is_processing = 1;

                    if(deleteArbitrary) {
                        
                        client.post(`admin/incentives/${data.id}/deleteArbitrary`).then(response => {
                            
                            this.is_processing = 0;

                            swal({
                                title: "Success!",
                                text: "Successfully Deleted",
                                icon: "success",
                            });


                            $dtArbitrary.clear().draw();
                            $dtArbitrary.responsive.recalc();

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;

                        });
                    }
                });
            },
            getRanks() {
                client.get(`admin/incentives/get-ranks`).then(response => {
                    this.ranks = response.data;
                    
                }).catch(error => {
                    swal('Unable to fetch!','','error');
                });
            },
            getIncentives() {
                client.get(`admin/incentives/get-incentives`).then(response => {
                    
                    this.arbitrary.openIncentives = response.data;
                    
                }).catch(error => {
                    swal('Unable to fetch!','','error');
                });
            },
            createIncentive() {
                
                if(this.settings.title.length == 0) {
                    swal('Title is Required');
                    return;
                } else if(this.settings.end_date < this.today) {
                    swal('Period End Date must be greater than today');
                    return;
                }

                if(this.settings.description.length == 0) {
                    swal('Description is Required');
                    return;
                }

                if(!this.settings.start_date && !this.settings.end_date) {
                    swal("Period is Required");
                    return;
                }

                if(this.is_processing) return;

                swal({
                    title: "Create New Incentive Tool",
                    text: "Are you sure you want to create this Incentive Tool?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((createIncentive) => {
                    
                    if(createIncentive) {
                        this.is_processing = 1;

                        this.settings.double_points_start_date = $("#date-from").val();
                        this.settings.double_points_end_date = $("#date-to").val();

                        this.settings.new_representative_start_date = $("#range-date-from").val();
                        this.settings.new_representative_end_date = $("#range-date-to").val();

                        this.settings.double_points_new_representative_start_date = $("#enroll-date-from").val();
                        this.settings.double_points_new_representative_end_date = $("#enroll-date-to").val();

                        client.post(`admin/incentives`, this.settings).then(response => {
                            
                            this.is_processing = 0;

                            swal({
                                title: "Success!",
                                text: "Successfully Created",
                                icon: "success",
                            });

                            $dtRunning.clear().draw();
                            // $dtClosed.clear().draw();

                            $dtRunning.responsive.recalc();
                            // $dtClosed.responsive.recalc();

                            this.clearRunningIncentiveTab();
                            this.getIncentives();
                            
                            $('#add-incentive').modal('hide');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;

                        });
                    }
                });
                
            },
            updateIncentive() {
                
                if(this.settings.title.length == 0) {
                    swal('Title is Required');
                    return;
                } else if(this.settings.end_date < this.today) {
                    swal('Period End Date must be greater than today');
                    return;
                }

                if(this.settings.description.length == 0) {
                    swal('Description is Required');
                    return;
                }

                if(!this.settings.start_date && !this.settings.end_date) {
                    swal("Period is Required");
                    return;
                }

                if(this.is_processing) return;

                swal({
                    title: "Update Incentive Tool",
                    text: "Are you sure you want to update this Incentive Tool? Name:"+ this.settings.title,
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((updateIncentive) => {
                    
                    if(updateIncentive) {
                        this.is_processing = 1;

                        this.settings.double_points_start_date = $("#date-from").val();
                        this.settings.double_points_end_date = $("#date-to").val();

                        this.settings.new_representative_start_date = $("#range-date-from").val();
                        this.settings.new_representative_end_date = $("#range-date-to").val();

                        this.settings.double_points_new_representative_start_date = $("#enroll-date-from").val();
                        this.settings.double_points_new_representative_end_date = $("#enroll-date-to").val();

                        client.post(`admin/incentives/update`, this.settings).then(response => {
                            
                            this.is_processing = 0;

                            swal({
                                title: "Success!",
                                text: "Successfully Updated",
                                icon: "success",
                            });


                            $dtRunning.clear().draw();
                            // $dtClosed.clear().draw();

                            $dtRunning.responsive.recalc();
                            // $dtClosed.responsive.recalc();

                            this.clearRunningIncentiveTab();
                            
                            $('#add-incentive').modal('hide');

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;

                        });
                    }
                });
                
            },
            addBonusPoints() {
                
                var user_id = $('#hidden-member-id').val();
                
                if(!user_id) {
                    swal("Please select a Member");
                    return;
                }
                if(!this.arbitrary.incentive_id) {
                    swal("Please select an Incentive");
                    return;
                }

                if(this.arbitrary.bonus_points <= 0) {
                    swal("Please Enter Bonus Points");
                    return;
                }

                if(this.is_processing == 1) return;

                swal({
                    title: "Add Arbitrary Points",
                    text: "Are you sure you want to add bonus points("+this.arbitrary.bonus_points+") for member: "+ user_id +"?",
                    icon: "warning",
                    confirmButtonClass: "btn-success",
                    buttons: ["No, cancel please!", "Yes!"],
                  })
                  .then((addArbitrary) => {

                    this.is_processing = 1;

                    if(addArbitrary) {

                        var data = {
                            user_id: user_id,
                            settings_id: this.arbitrary.incentive_id,
                            bonus_points: this.arbitrary.bonus_points
                        }
                        client.post(`admin/incentives/addArbitrary`, data).then(response => {
                            
                            this.is_processing = 0;

                            swal({
                                title: "Success!",
                                text: "Successfully Added",
                                icon: "success",
                            });


                            $dtArbitrary.clear().draw();
                            $dtArbitrary.responsive.recalc();

                            this.clearArbitraryTab();

                        }).catch(this.axiosErrorHandler).finally(()=> {
                            
                            this.is_processing = 0;

                        });
                    }
                });
            },
            clearRunningIncentiveTab() {
                this.settings.title = '';
                this.settings.description = '';
                this.settings.start_date = null;
                this.settings.end_date = null;
                this.settings.is_display_insentives = 0;
                this.settings.is_double_points_on = 0;
                this.settings.is_points_per_prs = 0;
                this.settings.points_per_prs = 0;
                this.settings.is_promote_to_or_higher = 0;
                this.settings.promote_to_or_higher_points =0;
                this.settings.rank_id = 0;
                this.settings.is_has_new_representative = 0;
                this.settings.new_representative_points = 0;
                this.settings.new_representative_min_prs = 0;
                this.settings.new_representative_first_n_days = 1;
                this.settings.is_double_points_new_representative = 0;
                this.settings.double_points_new_representative_first_n_days = 1;

                $("#period_range").val('');
                this.initializeDatePicker();
                
            },
            clearArbitraryTab() {
                $('.clear-typeahead').trigger( "click");
                this.arbitrary.incentive_id = null;
                this.arbitrary.bonus_points = null;

            },
            axiosErrorHandler(error) {
                var return_error = commissionEngine.parseAxiosErrorData(error);
                
                if(error.message) {
                    return_error = JSON.stringify(return_error);

                    JSON.stringify(return_error, function (key, value) {
                        if (key == "message") {
                            this.error.message = value;        
                        }
                        if (key == "type") {
                            this.error.type = value;        
                        }
                        if (key == "data") {
                            this.error.data = value;        
                        }

                    });
                } else if(error.response.data) {

                    var data = error.response.data;
                    this.error.message = data.message;
                    this.error.type = data.type;
                    this.error.data = data.data;
                }

                swal(this.error.message, "", "error");
            },
        }
    });
    window.onbeforeunload = function () {
        if (vm.downloadLinkState === "fetching") {
            return "Do you really want to leave? Download will be cancelled.";
        } else {
            return;
        }
    };

}(jQuery, window.commissionEngine.API_URL, Vue, swal, moment, axios, window.location));