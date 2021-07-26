/**
 * Function to format number - [$, comma separated].
 * @param  {[string]}  n [String to format]
 */
function formatNumber (n) {
    return (n == null) ? '' : n.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
}

/**
 * Function to format number amount - [$, comma separated].
 * @param  {[number]}  n [String to format]
 */
function formatNumberAmount (n, c, d, t) {
    var c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
        j = (j = i.length) > 3 ? j % 3 : 0;

    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

/**
 * Function to check if JavaScript Object is empty.
 * @param  {[string]}  obj [Object to check]
 */
function isEmpty(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return false;
    }
    return true;
}

/**
 * Function to format data result.
 * @param  {[string]}  rangeFrom [Date from]
 * @param  {[string]}  rangeTo   [Date to]
 */
function formatDataResult(rangeFrom, rangeTo) {
    var rangeFrom = '$' + formatNumber(rangeFrom),
        rangeTo = (rangeTo == '9999') ? '+' : ('-' + '$' + formatNumber(rangeTo));

    return rangeFrom + rangeTo;
}

/**
 * Function to show gift card details.
 */
function showGiftCardDetails(data, hostessRewardObj, rewardAmountObj, periodObj, btnSaveObj, voURL, apiUpdateGiftCardRewardURL, apiURL, obj, btnObj, apiUpdateGiftCardRewardLogsURL, apiUpdateGiftCardRewardLogsObj) {
    var giftCardDetails = {
        hostessReward : formatDataResult(data.range_from, data.range_to),
        id: data.id,
        amount : data.amount,
        startDate : data.start_date,
        endDate : data.end_date
    }

    $(hostessRewardObj).text(giftCardDetails.hostessReward);
    $(rewardAmountObj).val(giftCardDetails.amount);

    // INITIALIZE PERIOD DATE RANGE PICKER
    $(periodObj).daterangepicker({
        opens: 'left',
        startDate : giftCardDetails.startDate,
        endDate : giftCardDetails.endDate,
    }, function(start, end, label) {
        console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
    });

    // INITIALIZE SAVE BUTTON
    $(btnSaveObj).click(function (e) {
        e.preventDefault();

        var mAmount = $('#gift-card-rewards-modal__reward-amount').val(),
            mStartDate = $(periodObj).data('daterangepicker').startDate.format('YYYY-MM-DD'),
            mEndDate = $(periodObj).data('daterangepicker').endDate.format('YYYY-MM-DD'),
            mID = giftCardDetails.id,
            mModified = $('#member').val(),
            oAmount = giftCardDetails.amount,
            oStartDate = moment(giftCardDetails.startDate).format('YYYY-MM-DD'),
            oEndDate = moment(giftCardDetails.endDate).format('YYYY-MM-DD'),
            apiRewardURL = voURL + apiUpdateGiftCardRewardURL;

        swal({
            title: "Are you sure you want to change the Hostess Reward " + giftCardDetails.hostessReward + "?",
            type: "warning",
            confirmButtonClass: "btn-success",
            confirmButtonText: "Confirm",
            cancelButtonText: "Cancel",
            showCancelButton: true,
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, function () {
            var mObject = {
                mID: mID,
                mAmount: mAmount,
                mStartDate: mStartDate,
                mEndDate: mEndDate,
                mModified: mModified,
                oAmount: oAmount,
                oStartDate: oStartDate,
                oEndDate: oEndDate
            }

            $.ajax({
                type: "POST",
                url: apiRewardURL,
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify(mObject)
            }).done(function (data) {
                swal("Updated!", "Hostess Reward " + giftCardDetails.hostessReward + " updated.", "success");
                $('#gift-card-rewards-modal').modal('hide');

                setGiftCardRewardsDashboardTable(voURL, apiURL, obj, btnObj, hostessRewardObj, rewardAmountObj, periodObj, btnSaveObj, apiUpdateGiftCardRewardURL, apiUpdateGiftCardRewardLogsURL, apiUpdateGiftCardRewardLogsObj);
                setGiftCardRewardsLogsDashboardTable(voURL, apiUpdateGiftCardRewardLogsURL, apiUpdateGiftCardRewardLogsObj)
            }).fail(function (jqXHR) {
                var result = jqXHR.responseJSON;

                if (result) {
                    swal("Error!", result.error, "error");
                } else {
                    swal("Error!", "Invalid hostess reward.", "error");
                }
            });
        });
    });
}

/**
 * Function to initialize Rewards Dashboard DataTable.
 * @param  {[string]}  voURL             [VO URL]
 * @param  {[string]}  apiDateRangeURL   [Date Range API URL]
 * @param  {[string]}  apiURL            [API URL]
 * @param  {[string]}  objDropdown       [Date Range Picker drop down]
 * @param  {[object]}  obj               [Date Range Picker object]
 * @param  {[string]}  targetElement     [DOM element]
 * @param  {[string]}  btnDownloadCSV    [DOM element]
 */
function setRewardsDashboardTable (voURL, apiDateRangeURL, apiURL, objDropdown, obj, targetElement, btnDownloadCSV) {
    $(targetElement).DataTable({
        pageLength  : 20,
        lengthMenu  : [10, 20, 50, 100],
        data        : [],
        responsive  : true,
        destroy     : true,
        columnDefs: [
            {
                width: "20%",
                targets: [0, 1, 2, 3, 4]
            }
        ]
    });

    var urlDateRange = voURL + apiDateRangeURL;

    /** DON'T REMOVE IN CASE THE CLIENT WANTS DATE RANGE PICKER
     * $.get(urlDateRange, function (response) {
     *     var getDates = response.split('-'),
     *         getMinDate = getDates[0].trim(),
     *         getMaxDate = getDates[1].trim();
     *
     *     $(obj).daterangepicker({
     *         opens: 'left',
     *         minDate : getMinDate,
     *         maxDate : getMaxDate
     *     }, function(start, end, label) {
     *         console.log("A new date selection was made: " + start.format('MM/DD/YYYY') + ' to ' + end.format('MM/DD/YYYY'));
     *
     *         var dateRange = "/" + start.format('YYYY-MM-DD') + "/" + end.format('YYYY-MM-DD');
     *         var url = voURL + apiURL + dateRange;
     *
     *         var tblRewards = $(targetElement).DataTable({
     *             pageLength  : 20,
     *             lengthMenu  : [10, 20, 50, 100],
     *             data        : [],
     *             responsive  : true,
     *             destroy     : true,
     *             columns     : [
     *                 {data    : 'id_number'},
     *                 {data    : 'name'},
     *                 {
     *                     data    : 'party_sales',
     *                     render  : function (data, type) {
     *                         return '$' + formatNumber(data);
     *                     }
     *                 },
     *                 {
     *                     data    : 'rewards_earned',
     *                     render  : function (data, type) {
     *                         return '$' + formatNumber(data);
     *                     }
     *                 },
     *                 {data    : 'sponsor'}
     *             ],
     *             buttons     : [
     *                 {
     *                     extend  : 'csv',
     *                     title  : 'hostess_rewards_report_' + start.format('YYYY-MM-DD') + '_' + end.format('YYYY-MM-DD')
     *                 }
     *             ]
     *         });
     *
     *         $.get(url, function (response){
     *             tblRewards.clear();
     *             tblRewards.rows.add(response);
     *             tblRewards.columns.adjust().draw();
     *         }, "json").done(function () {
     *             $(btnDownloadCSV).prop("disabled", false);
     *
     *             $(btnDownloadCSV).click(function () {
     *                 tblRewards.button('.buttons-csv').trigger();
     *             });
     *         });
     *     });
     *});
     */

    $.getJSON(urlDateRange, function (response) {
        if (response) {
            if (!isEmpty(response)) {
                $(objDropdown).empty();
                var appendDate = '',
                    objDropdownLink = objDropdown + ' a';

                // APPEND DATES
                $.each(response, function(k, v) {
                    appendDate = v.start_date + ' to ' + v.end_date;

                    $(objDropdown).append('<li><a href="javascript:void(0);">' + appendDate + '</a></li>')
                });

                // SET DATATABLE BASED ON SELECT DATE RANGE
                $(objDropdownLink).click(function () {
                    $(obj).html($(this).text() + ' <span class="caret"></span>');

                    var currentDateRange = $(this).text(),
                        getCurrentDateRange = '/' + currentDateRange.split(' to ').join('/'),
                        url = voURL + apiURL + getCurrentDateRange;

                    var tblRewards = $(targetElement).DataTable({
                        pageLength  : 20,
                        lengthMenu  : [10, 20, 50, 100],
                        data        : [],
                        responsive  : true,
                        destroy     : true,
                        columns     : [
                            {data    : 'id_number'},
                            {data    : 'name'},
                            {
                                data    : 'party_sales',
                                render  : function (data, type) {
                                    return '$' + formatNumber(data);
                                }
                            },
                            {
                                data    : 'reward_earned',
                                render  : function (data, type) {
                                    return '$' + formatNumber(data);
                                }
                            },
                            {data    : 'sponsor'}

                        ],
                        columnDefs: [
                            {
                                width: "20%",
                                targets: [0, 1, 2, 3, 4]
                            }
                        ],
                        buttons     : [
                            {
                                extend  : 'csv',
                                title  : 'hostess_rewards_report_' + currentDateRange
                            }
                        ]
                    });

                    $.get(url, function (response) {
                        if (response) {
                            tblRewards.clear();
                            tblRewards.rows.add(response);
                            tblRewards.columns.adjust().draw();
                        }
                    }, "json").done(function () {
                        $(btnDownloadCSV).prop("disabled", false);

                        $(btnDownloadCSV).click(function () {
                            tblRewards.button('.buttons-csv').trigger();
                        });
                    });
                });
            }
        }
    });
}

/**
 * Function to initialize Gift Card Rewards Dashboard DataTable.
 * @param  {[string]}  voURL                        [VO URL]
 * @param  {[string]}  apiURL                       [API URL]
 * @param  {[string]}  obj                          [Datatable element]
 * @param  {[string]}  btnObj                       [Datatable button element]
 * @param  {[string]}  hostessRewardObj             [Datatable modal header hostess reward element]
 * @param  {[string]}  rewardAmountObj              [Datatable modal reward amount input element]
 * @param  {[string]}  periodObj                    [Datatable modal period input element]
 * @param  {[string]}  btnSaveObj                   [Datatable modal save button input element]
 * @param  {[string]}  apiUpdateGiftCardRewardURL   [API URL]
 */
function setGiftCardRewardsDashboardTable (voURL, apiURL, obj, btnObj, hostessRewardObj, rewardAmountObj, periodObj, btnSaveObj, apiUpdateGiftCardRewardURL, apiUpdateGiftCardRewardLogsURL, apiUpdateGiftCardRewardLogsObj) {
    var giftCardRewardsObj = {
        url : voURL + apiURL,
        targetElement : obj,
        btnChangeReward : obj + ' tbody'
    }

    var tblGiftCardRewards;

    if (!$.fn.DataTable.isDataTable(giftCardRewardsObj.targetElement)) {
        tblGiftCardRewards = $(giftCardRewardsObj.targetElement).DataTable({
            pageLength  : 20,
            lengthMenu  : [10, 20, 50, 100],
            data        : [],
            responsive  : true,
            destroy     : true,
            columns     : [
                {
                    data    : null,
                    render  : function (data, type, full) {
                        return formatDataResult(full['range_from'], full['range_to']);
                    }
                },
                {
                    data    : 'amount',
                    render  : function (data, type) {
                        return '$' + formatNumberAmount(data);
                    }
                },
                {
                    data    : null,
                    render  : function (data, type, full) {
                        return full['start_date'] + ' - ' + full['end_date'];
                    }
                },
                {
                    data    : null,
                    render  : function () {
                        return '<button class="btn btn-primary btn-change-reward" data-toggle="modal" data-target="#gift-card-rewards-modal" data-keyboard="false" data-backdrop="static">Change</button>'
                    }
                }
            ],
            order: []
        });
    } else {
        tblGiftCardRewards = $(giftCardRewardsObj.targetElement).DataTable();
    }

    $.get(giftCardRewardsObj.url, function (response){
        tblGiftCardRewards.clear();
        tblGiftCardRewards.rows.add(response);
        tblGiftCardRewards.columns.adjust().draw();
    }, "json");

    // CHANGE REWARD BUTTON LISTENER
    $(giftCardRewardsObj.btnChangeReward).on('click', btnObj, function () {
        var data = tblGiftCardRewards.row($(this).parents('tr')).data();
        showGiftCardDetails(data, hostessRewardObj, rewardAmountObj, periodObj, btnSaveObj, voURL, apiUpdateGiftCardRewardURL, apiURL, obj, btnObj, apiUpdateGiftCardRewardLogsURL, apiUpdateGiftCardRewardLogsObj);
    });
}

/**
 * Function to initialize Gift Card Rewards Dashboard DataTable.
 * @param  {[string]}  voURL     [VO URL]
 * @param  {[string]}  apiURL    [API URL]
 * @param  {[string]}  obj       [Datatable element]
 */
function setGiftCardRewardsLogsDashboardTable (voURL, apiURL, obj) {
    var giftCardRewardsLogsObj = {
        url : voURL + apiURL,
        targetElement : obj
    }

    var tblGiftCardRewardsLogs;

    if (!$.fn.DataTable.isDataTable(giftCardRewardsLogsObj.targetElement)) {
        tblGiftCardRewardsLogs = $(giftCardRewardsLogsObj.targetElement).DataTable({
            pageLength  : 20,
            lengthMenu  : [10, 20, 50, 100],
            data        : [],
            responsive  : true,
            destroy     : true,
            columns     : [
                {
                    data    : null,
                    render  : function (data, type, full) {
                        return formatDataResult(full['range_from'], full['range_to']);
                    }
                },
                {
                    data    : 'new_rewards_amount',
                    render  : function (data, type) {
                        return '$' + formatNumberAmount(data);
                    }
                },
                {
                    data    : 'old_rewards_amount',
                    render  : function (data, type) {
                        return '$' + formatNumberAmount(data);
                    }
                },
                {
                    data    : null,
                    render  : function (data, type, full) {
                        return full['new_start_date'] + ' - ' + full['new_end_date'];
                    }
                },
                {
                    data    : null,
                    render  : function (data, type, full) {
                        return full['old_start_date'] + ' - ' + full['old_end_date'];
                    }
                },
                {data    : 'changed_by'}
            ],
            order: []
        });
    } else {
        tblGiftCardRewardsLogs = $(giftCardRewardsLogsObj.targetElement).DataTable();
    }

    $.get(giftCardRewardsLogsObj.url, function (response){
        tblGiftCardRewardsLogs.clear();
        tblGiftCardRewardsLogs.rows.add(response);
        tblGiftCardRewardsLogs.columns.adjust().draw();
    }, "json");
}
