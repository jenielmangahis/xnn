/**
 * Function to initialize Countdown Timer.
 * @param  {[string]} voURL  [VO URL]
 * @param  {[string]} apiURL [API URL]
 * @param  {[object]} obj    [DOM element]
 */
function setCountdownTimer (voURL, apiURL, obj) {
    var url = voURL + apiURL;

    $.get(url, function (response) {
        $(obj).countdown({
            date: response,
            day: 'Day',
            days: 'Days',
            offset: -7
        }, function () {
            console.log('Done!');
        });
    });
}

/**
 * Function to initialize Date Range Picker.
 * @param  {[string]}  voURL            [VO URL]
 * @param  {[string]}  apiURL           [API URL]
 * @param  {[object]}  obj              [DOM element]
 * @param  {[string]}  apiURL2          [API URL 2]
 * @param  {[object]}  obj2             [DOM element 2]
 * @param  {[integer]} memberID         [Member ID]
 * @param  {[object]}  tooltipsterObj   [Tooltipster object]
 * @param  {[string]}  apiBreakdownURL  [API breakdown URL]
 * @param  {[object]}  objBreakdown     [DOM element 2]
 */
function setDateRangePicker (voURL, apiURL, obj, apiURL2, obj2, memberID, tooltipsterObj, apiBreakdownURL, objBreakdown) {
    var url = voURL + apiURL;

    $.get(url, function (response) {
        var getDates = response.split('-'),
            getMinDate = getDates[0].trim(),
            getMaxDate = getDates[2].trim(),
            startDate = getDates[1].trim();

        $(obj).daterangepicker({
            opens: 'left',
            startDate : startDate,
            endDate : getMaxDate,
            minDate : getMinDate,
            maxDate : getMaxDate
        }, function(start, end, label) {
            console.log("A new date selection was made: " + start.format('MM/DD/YYYY') + ' to ' + end.format('MM/DD/YYYY'));

            var dateRange = "/" + start.format('YYYY-MM-DD') + "/" + end.format('YYYY-MM-DD');
            var pendingOrdersObj = {
                url : voURL + apiURL2 + memberID + dateRange,
                targetElement : obj2,
                tooltipsterObj : tooltipsterObj,
                urlBreakdown : voURL + apiBreakdownURL + memberID,
                targetBreakdownElement : objBreakdown,
                dateRange : dateRange
            }

            renderPendingOrdersDataTable(pendingOrdersObj.targetElement,
                                         pendingOrdersObj.url,
                                         pendingOrdersObj.tooltipsterObj,
                                         pendingOrdersObj.urlBreakdown,
                                         pendingOrdersObj.targetBreakdownElement,
                                         pendingOrdersObj.dateRange);
        });

        // $(obj).val(response);
    });
}

/**
 * Function to initialize Pending Orders DataTable.
 * @param  {[string]}  voURL            [VO URL]
 * @param  {[string]}  apiURL           [API URL]
 * @param  {[object]}  obj              [DOM element]
 * @param  {[integer]} memberID         [Member ID]
 * @param  {[object]}  tooltipsterObj   [Tooltipster object]
 * @param  {[string]}  apiBreakdownURL  [API breakdown URL]
 * @param  {[object]}  objBreakdown     [DOM element 2]
 */
function setPendingOrdersDataTable (voURL, apiURL, obj, memberID, tooltipsterObj, apiBreakdownURL, objBreakdown) {
    var pendingOrdersObj = {
        url : voURL + apiURL + memberID,
        targetElement : obj,
        tooltipsterObj : tooltipsterObj,
        urlBreakdown: voURL + apiBreakdownURL + memberID,
        targetBreakdownElement: objBreakdown
    }

    renderPendingOrdersDataTable(pendingOrdersObj.targetElement,
                                 pendingOrdersObj.url,
                                 pendingOrdersObj.tooltipsterObj,
                                 pendingOrdersObj.urlBreakdown,
                                 pendingOrdersObj.targetBreakdownElement,
                                 '');
}

/**
 * Function to format number - [$, comma separated].
 * @param  {[string]}  n [String to format]
 */
/* function formatNumber (n) {
    return n.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
} */
function formatNumber(n, c, d, t) {
    var c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))),
        j = (j = i.length) > 3 ? j % 3 : 0;

    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

/**
 * Function render DataTable.
 * @param  {[string]}  targetElement           [DOM element]
 * @param  {[string]}  url                     [URL]
 * @param  {[object]}  tooltipsterObj          [Tooltipster object]
 * @param  {[string]}  urlBreakdown            [API breakdown URL]
 * @param  {[object]}  targetBreakdownElement  [DOM element]
 * @param  {[string]}  dateRange               [Date range]
 */
function renderPendingOrdersDataTable (targetElement, url, tooltipsterObj, urlBreakdown, targetBreakdownElement, dateRange) {
    var tblPendingOrders = $(targetElement).DataTable({
        pageLength  : 20,
        lengthMenu  : [10, 20, 50, 100],
        data        : [],
/*        responsive  : true, */
        destroy     : true,
        columns     : [            
            {
                data    : 'hostess_name',
                render  : function (data, type, row) {
                    return '<span class="float-left"><i class="fa fa-plus-circle tooltip-affiliate-pending-orders" data-user-order-id="' + row['id'] + '" aria-hidden="true"></i></span>' + data;
                }
            },
            {
                data    : 'sponsor_link',
                render  : function (data, type) {
                    if (type === 'display') {
                        data = (data == 'N/A') ? 'N/A' : '<a href="' + data + '" target="_blank"><u>' + data + '</u></a>';
                    }

                    return data;
                }
            },
            {
                data    : 'total_sales',
                render  : function (data, type) {
                    return '$' + formatNumber(data);
                }
            },
            {
                data    : 'site_name',
                render  : function (data, type) {
                    if (type === 'display') {
                        data = '<a href="https://cart.opulenzadesigns.shop/' + data + '" target="_blank"><u>https://cart.opulenzadesigns.shop/' + data + '/</u></a>';
                    }

                    return data;
                }
            },
            {
                data    : 'amount',
                render  : 'amount'
            }            
        ],
        columnDefs: [
            {
                width: "20%",
                targets: 3
            }
        ],
        order: [[ 2, "desc" ]]
    });

    $.get(url, function (response){
        tblPendingOrders.clear();
        tblPendingOrders.rows.add(response);
        tblPendingOrders.columns.adjust().draw();
    }, "json").done(function () {
        setTooltipster(tooltipsterObj);
        setTooltipsterDataTable(tooltipsterObj, urlBreakdown, targetBreakdownElement, dateRange);
    });
}

/**
 * Function to initialize tooltipster.
 * @param  {[object]}  obj [DOM element]
 */
function setTooltipster (obj) {
    $(obj).tooltipster({
        theme: 'tooltipster-shadow',
        trigger: 'click',
        position: 'bottom-left',
        content: $('#tooltip-affiliate-pending-orders-breakdown'),
        contentCloning: true,
        interactive: true,
        autoClose: false,
        'functionReady': function(){
            $('.tooltipster-close').click(function(){
                $(obj).tooltipster('hide');
            });
        }
    });
}

/**
 * Function to initialize tooltipster DataTable.
 * @param  {[object]}  obj              [DOM element]
 * @param  {[string]}  url              [URL]
 * @param  {[string]}  targetElement    [DOM element]
 * @param  {[string]}  dateRange        [Date range]
 */
function setTooltipsterDataTable (obj, url, targetElement, dateRange) {
    $(obj).on('click', function(e) {
        var userOrderID = $(this).attr('data-user-order-id');

        setPendingOrdersBreakdownDataTable(url, targetElement, userOrderID, dateRange);
    });
}

/**
 * Function to initialize Pending Orders DataTable.
 * @param  {[string]}  url          [API URL]
 * @param  {[object]}  obj          [DOM element]
 * @param  {[integer]} userOrderID  [User order ID]
 * @param  {[string]}  dateRange    [Date range]
 */
function setPendingOrdersBreakdownDataTable (url, obj, userOrderID, dateRange) {
    var pendingOrdersBreakdownObj = {
        url : url + '/' + userOrderID + dateRange,
        targetElement : obj,
        tblPendingOrdersBreakdownElement : '.tooltip-affiliate-pending-orders-breakdown__template #tooltip-affiliate-pending-orders-breakdown .affiliate-rewards-dashboard__pending-orders-table--breakdown'
    }

    var tblPendingOrdersBreakdown;

    if (!$.fn.DataTable.isDataTable(pendingOrdersBreakdownObj.tblPendingOrdersBreakdownElement)) {
        tblPendingOrdersBreakdown = $(pendingOrdersBreakdownObj.targetElement).DataTable({
            pageLength  : 5,
            data        : [],
            responsive  : false,
            destroy     : true,
            order       : [[4, 'desc']],
            columns     : [
                {data    : 'purchaser'},
                {data    : 'invoice'},
                {
                    data    : 'product',
                    render  : function (data, type) {
                        if (type === 'display') {
                            data = '<a href="javascript:void(0);" class="btn-view-order-modal" data-product="' + data + '"><u>View Order</u></a>';
                        }

                        return data;
                    }
                },
                {
                    data    : 'amount_paid',
                    render  : function (data, type) {
                        return '$' + formatNumber(data);
                    }
                },
                {data    : 'transaction_date'},
            ],
            columnDefs: [
                {
                    targets: [4],
                    visible: false
                },
                {
                    width: "15%",
                    targets: [2]
                }
            ],
            "fnDrawCallback": function() {
                $('.tooltip-affiliate-pending-orders').tooltipster('content', $('#tooltip-affiliate-pending-orders-breakdown'));
                $('.tooltipster-content #tooltip-affiliate-pending-orders-breakdown .affiliate-rewards-dashboard__pending-orders-table--breakdown').css('width', '100%');
            },
            searching: false,
            info: false,
            lengthChange : false
        });
    } else {
        tblPendingOrdersBreakdown = $(pendingOrdersBreakdownObj.tblPendingOrdersBreakdownElement).DataTable();
    }

    $.get(pendingOrdersBreakdownObj.url, function (response){
        tblPendingOrdersBreakdown.clear();
        tblPendingOrdersBreakdown.rows.add(response);
        tblPendingOrdersBreakdown.columns.adjust().draw();
    }, "json").done(function () {
        $('.tooltip-affiliate-pending-orders').tooltipster('content', $('#tooltip-affiliate-pending-orders-breakdown'));
        $('.tooltipster-content #tooltip-affiliate-pending-orders-breakdown .affiliate-rewards-dashboard__pending-orders-table--breakdown').css('width', '100%');

        $(document).off('click', '.btn-view-order-modal').on('click', '.btn-view-order-modal', function(e) {
            e.preventDefault();
            var orderList = $(this).data('product').split(",").join("</li><li>");

            $.dialog({
                icon: 'fa fa-list',
                title: 'Order List',
                theme: 'supervan',
                content: '<hr><ul><li>' + orderList + '</li></ul>',
            });
        });
    });
}

/**
 * Function to initialize Top Hostesses Of All Time DataTable.
 * @param  {[string]}  voURL     [VO URL]
 * @param  {[string]}  apiURL    [API URL]
 * @param  {[object]}  obj       [DOM element]
 * @param  {[integer]} memberID  [Member ID]
 */
function setTopHostessesAllTimeDataTable (voURL, apiURL, obj, memberID) {
    var topHostessessAllTimeObj = {
        url : voURL + apiURL + memberID,
        targetElement : obj
    }

    var tblTopHostessessAllTime = $(topHostessessAllTimeObj.targetElement).DataTable({
        pageLength  : 20,
        lengthMenu  : [10, 20, 50, 100],
        data        : [],
 /*       responsive  : true, */
        destroy     : true,
        columns     : [
            {data    : 'purchaser'},
            {
                data    : 'amount_paid',
                render  : function (data, type) {
                    return '$' + formatNumber(data);
                }
            },
            {
                data    : 'direct_commissions',
                render  : function (data, type) {
                    return '$' + formatNumber(data);
                }
            }
        ],
        columnDefs: [
            {
                width: "15%",
                targets: [3]
            }
        ],
        order: [[ 1, "desc" ]]
    });

    $.get(topHostessessAllTimeObj.url, function (response){
        tblTopHostessessAllTime.clear();
        tblTopHostessessAllTime.rows.add(response);
        tblTopHostessessAllTime.columns.adjust().draw();
    }, "json");


}

/**
 * Function to initialize Coupon DataTable.
 * @param  {[string]}  voURL     [VO URL]
 * @param  {[string]}  apiURL    [API URL]
 * @param  {[object]}  obj       [DOM element]
 * @param  {[integer]} memberID  [Member ID]
 */
function setCouponDataTable (voURL, apiURL, obj, memberID) {

    const $dt = $(obj).DataTable({
        pageLength  : 20,
        lengthMenu  : [10, 20, 50, 100],
        responsive  : true,
        processing  : true,
        serverSide  : true,
        ajax        : {
            url  : voURL + apiURL,
            data : function(d) {
                d.member_id = memberID
            },
        },
        columns     : [
            {data    : 'code'},
            {data    : 'gv2'},
            {
                data    : 'balance',
                render  : function (data, type) {
                    return '$' + data;
                }
            }
        ],
    });
}


/**
 * Function to initialize Coupon DataTable.
 * @param  {[string]}  voURL     [VO URL]
 * @param  {[string]}  apiURL    [API URL]
 * @param  {[object]}  obj       [DOM element]
 * @param  {[integer]} memberID  [Member ID]
 */
function setGiftCardsHistoryDataTable (voURL, apiURL, obj, memberID) {

    const $dt = $(obj).DataTable({
        pageLength  : 20,
        lengthMenu  : [10, 20, 50, 100],
        responsive  : true,
        processing  : true,
        serverSide  : true,
        ajax        : {
            url  : voURL + apiURL,
            data : function(d) {
                d.member_id = memberID
            },
        },
        columns     : [
            {data    : 'transaction_id'},
            {data    : 'description'},
            {
                data    : 'amount',
                render  : function (data, type) {
                    return '$' + data;
                }
            }
        ],
    });
}



$(document).ready(function(){
    $('a').tooltip({
        trigger: 'click',
        placement: 'bottom'
      });
      
      function setTooltip(btn, message) {
        $(btn).tooltip('hide')
          .attr('data-original-title', message)
          .tooltip('show');
      }
      
      function hideTooltip(btn) {
        setTimeout(function() {
          $(btn).tooltip('hide');
        }, 1000);
      }
      
      // Clipboard
      
      var clipboard = new Clipboard('a');
      
      clipboard.on('success', function(e) {
        setTooltip(e.trigger, 'Link has been copied!');
        hideTooltip(e.trigger);
      });
      
      clipboard.on('error', function(e) {
        setTooltip(e.trigger, 'Failed!');
        hideTooltip(e.trigger);
      });
});


  
  
