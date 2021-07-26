(function (rewardsDashboard) {
    rewardsDashboard(window.jQuery, window, document);
}(function ($, window, document) {
    $(function() {
        'use strict';

        /* =================== SET NAMESPACE ================= */
        var sparklePartyRewardsNamespace = {
            memberID : $("#member_id").val(),
            voURL : API_URL,
            countDownTimerEndpointURL : 'affiliate-rewards/countdown-end-date',
            countDownTimerObj : '#rewards-dashboard__countdown-timer',
            dateRangePickerEndpointURL : 'affiliate-rewards/daterange',
            dateRangePickerObj : '.affiliate-rewards-dashboard__sales-daterange',
            dataTablesPendingOrdersEndpointURL : 'affiliate-rewards/affiliate-pending-orders/',
            dataTablesPendingOrdersObj : '.affiliate-rewards-dashboard__pending-orders-table',
            tooltipsterObj: '.tooltip-affiliate-pending-orders',
            dataTablesPendingOrdersBreakdownEndpointURL : 'affiliate-rewards/affiliate-pending-orders-breakdown/',
            dataTablesPendingOrdersBreakdownObj : '.affiliate-rewards-dashboard__pending-orders-table--breakdown',
            dataTablesTopHostessesAllTimeEndpointURL : 'affiliate-rewards/affiliate-customer-sales-alltime/',
            dataTablesTopHostessesAllTimeObj : '.affiliate-rewards-dashboard__top-hostess-table',

            dataTablesCouponEndpointURL : 'affiliate-rewards/affiliate-coupon',
            dataTablesCouponObj : '.affiliate-rewards-dashboard__coupon-table',

            dataTablesGiftCardHistoryEndpointURL : 'affiliate-rewards/affiliate-gift-cards-history',
            dataTablesGiftCardHistoryObj : '.affiliate-rewards-dashboard__gift-cards-history-table',
        }
        /* ==================================================== */

        /* =================== COUNTDOWN TIMER =================== */
        setCountdownTimer(sparklePartyRewardsNamespace.voURL,
            sparklePartyRewardsNamespace.countDownTimerEndpointURL,
            sparklePartyRewardsNamespace.countDownTimerObj);
        /* ======================================================= */

        /* =================== DATERANGE PICKER =================== */
        setDateRangePicker(sparklePartyRewardsNamespace.voURL,
            sparklePartyRewardsNamespace.dateRangePickerEndpointURL,
            sparklePartyRewardsNamespace.dateRangePickerObj,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersEndpointURL,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersObj,
            sparklePartyRewardsNamespace.memberID,
            sparklePartyRewardsNamespace.tooltipsterObj,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersBreakdownEndpointURL,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersBreakdownObj);
        /* ======================================================= */

        /* =================== INITIALIZE PENDING ORDERS TABLE =================== */
        setPendingOrdersDataTable(sparklePartyRewardsNamespace.voURL,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersEndpointURL,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersObj,
            sparklePartyRewardsNamespace.memberID,
            sparklePartyRewardsNamespace.tooltipsterObj,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersBreakdownEndpointURL,
            sparklePartyRewardsNamespace.dataTablesPendingOrdersBreakdownObj);
        /* ======================================================================= */

        /* =================== INITIALIZE TOP HOSTESSESS ALL TIME TABLE =================== */
        setTopHostessesAllTimeDataTable(sparklePartyRewardsNamespace.voURL,
            sparklePartyRewardsNamespace.dataTablesTopHostessesAllTimeEndpointURL,
            sparklePartyRewardsNamespace.dataTablesTopHostessesAllTimeObj,
            sparklePartyRewardsNamespace.memberID);
        /* ================================================================================ */
        setCouponDataTable(sparklePartyRewardsNamespace.voURL,
            sparklePartyRewardsNamespace.dataTablesCouponEndpointURL,
            sparklePartyRewardsNamespace.dataTablesCouponObj,
            sparklePartyRewardsNamespace.memberID);

        setGiftCardsHistoryDataTable(sparklePartyRewardsNamespace.voURL,
            sparklePartyRewardsNamespace.dataTablesGiftCardHistoryEndpointURL,
            sparklePartyRewardsNamespace.dataTablesGiftCardHistoryObj,
            sparklePartyRewardsNamespace.memberID);
    });
}));
