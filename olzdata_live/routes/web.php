<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('sample')->group(function() {
    Route::get('datatables', 'SampleController@datatables')->middleware('jwt.auth');
    Route::get('pdo', 'SampleController@pdo');
    Route::post('validation', 'SampleController@validation');
    Route::get('exception', 'SampleController@exception');
    Route::get('config', 'SampleController@config');
    Route::get('abort/{status}', function($status) {
        return response()->json(['error' => $status], $status);
    });
});

// COMMON ROUTES

Route::prefix('common/autocomplete')->namespace('Common')->group(function(){
    Route::get('enroller-downline', 'AutocompleteController@enrollerDownline')->middleware('jwt.auth');
    Route::get('placement-downline', 'AutocompleteController@placementDownline')->middleware('jwt.auth');
    Route::get('matrix-downline', 'AutocompleteController@matrixDownline')->middleware('jwt.auth');
    Route::get('members', 'AutocompleteController@members')->middleware('jwt.auth.level:1,6');
    Route::get('affiliates', 'AutocompleteController@affiliates')->middleware('jwt.auth.level:1,6');
    Route::get('enroller-customer-downline', 'AutocompleteController@enrollerCustomerDownline')->middleware('jwt.auth');
});

Route::prefix('common/commission-types')->namespace('Common')->middleware('jwt.auth')->group(function(){
    Route::get('active-cash-manual', 'CommissionTypesController@activeCashManual');
    Route::get('frequencies', 'CommissionTypesController@frequencies');
    Route::get('{id}/open-periods', 'CommissionTypesController@openPeriods');
    Route::get('{id}/locked-periods', 'CommissionTypesController@lockedPeriods');
});

Route::prefix('common/commission-periods')->namespace('Common')->middleware('jwt.auth')->group(function(){
    Route::get('locked-dates', 'CommissionPeriodsController@lockedDates');
});

Route::prefix('common/ranks')->namespace('Common')->middleware('jwt.auth')->group(function(){
    Route::get('/', 'RankController@index');
});

Route::prefix('common/countries')->namespace('Common')->group(function(){
    Route::get('/', 'CountriesController@index');
});

// MEMBER ROUTES

Route::prefix('member/enroller-tree')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::get('parent/{id}/{start_date}', 'EnrollerTreeController@parent');
    Route::get('parent/{id}/children/{page_no}/{start_date}', 'EnrollerTreeController@children');
    Route::get('order-history', 'EnrollerTreeController@orderHistory');
    Route::get('user-downlines', 'EnrollerTreeController@getDownlines');
    Route::get('wishlist', 'EnrollerTreeController@wishlist');
});

Route::prefix('member/placement-tree')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::get('parent/{id}', 'PlacementTreeController@parent');
    Route::get('parent/{id}/children/{page_no}', 'PlacementTreeController@children');
    Route::get('order-history', 'PlacementTreeController@orderHistory');
    Route::get('unplaced-members', 'PlacementTreeController@unplacedMembers');
    Route::post('place-member', 'PlacementTreeController@placeMember');
});

Route::prefix('member/historical-commission')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::get('/', 'HistoricalCommissionController@index');
    Route::get('total', 'HistoricalCommissionController@total');
    Route::get('download', 'HistoricalCommissionController@download');
});

Route::prefix('member/dashboard')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::get('current-period-orders', 'DashboardController@currentPeriodOrders');
    Route::get('current-rank-details', 'DashboardController@currentRankDetails');
    Route::get('current-qualification-details', 'DashboardController@currentQualificationDetails');
    Route::get('silver-startup-details', 'DashboardController@silverStartUpDetails');
    Route::get('sparkle-startup-details', 'DashboardController@sparkleStartUpDetails');
    Route::get('bash-925-startup-details', 'DashboardController@bash925StartUpDetails');
    Route::get('gift-cards', 'DashboardController@giftCards');
    Route::get('title-achievement-bonus-details', 'DashboardController@titleAchievementBonusDetails');
    Route::get('current-earnings-details', 'DashboardController@currentEarningsDetails');
});

Route::prefix('member/pay-quicker')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::post('sign-up', 'PayQuickerController@signUp');
    Route::get('users', 'PayQuickerController@getUser');
});

Route::prefix('member/hyperwallet')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::post('sign-up', 'HyperwalletController@signUp');
    Route::get('users', 'HyperwalletController@getUser');
});

Route::prefix('member/ipayout')->namespace('Member')->middleware('jwt.auth')->group(function(){
    Route::post('sign-up', 'IPayoutController@signUp');
    Route::get('users', 'IPayoutController@getUser');
});

Route::prefix('member/ledger')->namespace('Member')->middleware('jwt.auth')->group(function () {
    Route::get('/', 'LedgerController@ledger');
    Route::get('withdrawal', 'LedgerController@withdrawal');
    Route::post('transfer', 'LedgerController@transfer');
    Route::post('withdraw', 'LedgerController@withdraw');
    Route::get('total-balance', 'LedgerController@totalBalance');
    Route::get('had-signup', 'LedgerController@hadSignup');
});

Route::prefix('member/rank-history')->namespace('Member')->middleware('jwt.auth')->group(function () {
    Route::get('enrollment', 'RankHistoryController@enrollment');
    Route::get('personal', 'RankHistoryController@personal');
    Route::get('highest', 'RankHistoryController@highest');
});

Route::prefix('member/rank-progress')->namespace('Member')->middleware('jwt.auth')->group(function () {
    Route::get('/', 'RankProgressController@index');
});

Route::prefix('member/rewards')->namespace('Member')->middleware('jwt.auth')->group(function () {
    Route::get('gift-cards', 'RewardsController@giftCards');
    Route::get('coupons', 'RewardsController@coupons');
});

Route::prefix('member/matrix-tree')->namespace('Member')->middleware('jwt.auth')->group(function() {
    Route::get('{root}/downline/{user_id?}', 'MatrixTreeController@getDownline');
    Route::get('{user_id}/current-rank-details', 'MatrixTreeController@currentRankDetails');
});

Route::prefix('member/autoship')->namespace('Member')->middleware('jwt.auth')->group(function () {

    Route::get('pending-autoship-amount', 'AutoshipReportController@pendingAutoshipAmount');
    Route::get('successful-autoship-amount', 'AutoshipReportController@successfulAutoshipAmount');
    Route::get('failed-autoship-amount', 'AutoshipReportController@failedAutoshipAmount');
    Route::get('members-count', 'AutoshipReportController@membersCount');
    Route::get('active-members-on-autoship-count', 'AutoshipReportController@activeMembersOnAutoshipCount');
    Route::get('cancelled-autoship-count', 'AutoshipReportController@cancelledAutoshipCount');
    Route::get('average-order-value', 'AutoshipReportController@averageOrderValue');
    Route::get('personally-enrolled-retention-rate', 'AutoshipReportController@personallyEnrolledRetentionRate');
    Route::get('organizational-retention-rate', 'AutoshipReportController@organizationalRetentionRate');

    Route::get('pending-autoship', 'AutoshipReportController@pendingAutoship');
    Route::get('successful-autoship', 'AutoshipReportController@successfulAutoship');
    Route::get('failed-autoship', 'AutoshipReportController@failedAutoship');
    Route::get('cancelled-autoship', 'AutoshipReportController@cancelledAutoship');
    Route::get('active-members-on-autoship', 'AutoshipReportController@activeMembersOnAutoship');
});

//Hostess Dashboard
Route::prefix('member/hostess-dashboard')->namespace('Member')->middleware('jwt.auth')->group(function () {
    Route::get('countdown', 'HostessDashboardController@getRewardCountdown');
    Route::get('rewards', 'HostessDashboardController@getRewardProgress');
    Route::get('orders/{id}', 'HostessDashboardController@getOrders');
    Route::get('product-credits', 'HostessDashboardController@getProductCredits');
    Route::get('coupons', 'HostessDashboardController@getCoupons');
    Route::get('sharing-link', 'HostessDashboardController@getSharingLink');
    Route::get('daily-rewards', 'HostessDashboardController@getDailyReward');
    Route::get('open-events', 'HostessDashboardController@getOpenEvent');
});

Route::prefix('member/party-manager')->namespace('Member')->middleware('jwt.auth')->group(function () {
    Route::get('open-events', 'PartyManagerController@getOpenEvents');
    Route::get('past-events', 'PartyManagerController@getPastEvents');
    Route::get('top-hostesses', 'PartyManagerController@getTopHostesses');
    Route::post('create', 'PartyManagerController@createEvent');
    Route::post('{id}/delete', 'PartyManagerController@deleteEvent');
    Route::get('orders/{id}', 'PartyManagerController@getOrders');
});

// ADMIN ROUTES

Route::prefix('admin/historical-commission')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::get('/', 'HistoricalCommissionController@index');
    Route::get('total', 'HistoricalCommissionController@total');
    Route::get('download', 'HistoricalCommissionController@download');
});

Route::prefix('admin/run-commission')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::post('commission-periods/{id}/run', 'RunCommissionController@run');
    Route::post('commission-periods/{id}/lock', 'RunCommissionController@lock');
    Route::post('commission-periods/{id}/view-previous-run', 'RunCommissionController@getPreviousRun');
    Route::post('background-worker/{id}/completed', 'RunCommissionController@complete');
    Route::post('background-worker/{id}/cancel', 'RunCommissionController@cancel');
    Route::get('background-worker/{id}/log', 'RunCommissionController@log');
    Route::get('background-worker/{id}/details', 'RunCommissionController@details');
});

Route::prefix('admin/pay-commission')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('locked-periods', 'PayCommissionController@lockedPeriods');
    Route::get('payouts', 'PayCommissionController@payouts');
    Route::get('payment-history', 'PayCommissionController@history');
    Route::get('payment-details', 'PayCommissionController@paymentDetails');
    Route::post('total', 'PayCommissionController@total');
    Route::post('start', 'PayCommissionController@start');
    Route::post('pay', 'PayCommissionController@pay');
    Route::get('log/{id}', 'PayCommissionController@log');
    Route::post('mark-as-paid', 'PayCommissionController@markAsPaid');
});

Route::prefix('admin/minimum-rank')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::post('/', 'MinimumRankController@save');
    Route::get('/', 'MinimumRankController@index');
    Route::post('{user_id}/delete', 'MinimumRankController@delete');
    Route::get('{user_id}', 'MinimumRankController@show');
});

Route::prefix('admin/rank-progress')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('/', 'RankProgressController@index');
});

Route::prefix('admin/rank-history')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('enrollment', 'RankHistoryController@enrollment');
    Route::get('highest', 'RankHistoryController@highest');
    Route::get('download-enrollment', 'RankHistoryController@downloadEnrollment');
    Route::get('download-highest', 'RankHistoryController@downloadHighest');
});

Route::prefix('admin/commission-adjustments')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::get('/', 'CommissionAdjustmentController@index');
    Route::post('/', 'CommissionAdjustmentController@save');
    Route::post('update', 'CommissionAdjustmentController@update');
    Route::post('{id}/delete', 'CommissionAdjustmentController@delete');
});

Route::prefix('admin/ledger-adjustment')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::post('/', 'LedgerAdjustmentController@save');
    Route::get('/', 'LedgerAdjustmentController@index');
    Route::post('{id}/delete', 'LedgerAdjustmentController@delete');
});

Route::prefix('admin/ledger-withdrawal')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::get('pending', 'LedgerWithdrawalController@pending');
    Route::post('reject', 'LedgerWithdrawalController@reject');
    Route::post('start', 'LedgerWithdrawalController@start');
    Route::post('pay', 'LedgerWithdrawalController@pay');
    Route::get('payment-history', 'LedgerWithdrawalController@history');
    Route::get('payment-details', 'LedgerWithdrawalController@paymentDetails');
    Route::get('log/{history_id}', 'LedgerWithdrawalController@log');
});

Route::prefix('admin/upline-report')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function(){
    Route::get('uplines/{member_id}', 'UplineReportController@view');
});

Route::prefix('admin/top-earner')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('/', 'TopEarnerController@topEarner');
});

Route::prefix('admin/transactions')->namespace('Admin')->group(function () {
    Route::get('getTransactions/{start_date}/{end_date}', 'TransactionsReportController@getAllTransactions');
    Route::get('getTotal/{start_date}/{end_date}', 'TransactionsReportController@getAllBreakDown');
    Route::get('generate-report/{start_date}/{end_date}', 'TransactionsReportController@getReport');
    Route::get('download/{file_name}', 'TransactionsReportController@getDownload');
});

Route::prefix('admin/clawback')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('/', 'ClawbackController@clawbacks');
    Route::get('order-products/{transaction_id}', 'ClawbackController@products');
    Route::post('refund-order', 'ClawbackController@refundOrder');
    Route::post('refund-order-products', 'ClawbackController@refundProduct');
});

Route::prefix('admin/move-order')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('logs', 'MoveOrderController@logs');
    Route::post('orders/{id}/change', 'MoveOrderController@change');
});

Route::prefix('admin/dashboard')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('new-customer-count', 'DashboardController@getNewCustomerCount');
    Route::get('new-customer-with-product-subscription-count', 'DashboardController@getNewCustomerWithProductSubscriptionCount');
    Route::get('new-endorser-count', 'DashboardController@getNewEndorserCount');
    Route::get('new-endorser-with-product-subscription-count', 'DashboardController@getNewEndorserWithProductSubscriptionCount');

    Route::get('total-sales/customer-transformation-pack', 'DashboardController@getCustomerTransformationPackTotalSales');
    Route::get('total-sales/transformation-pack', 'DashboardController@getTransformationPackTotalSales');
    Route::get('total-sales/elite-pack', 'DashboardController@getElitePackTotalSales');
    Route::get('total-sales/family-elite-pack', 'DashboardController@getFamilyElitePackTotalSales');

    Route::get('average-reorder', 'DashboardController@getAverageReorder');
    Route::get('top-endorsers', 'DashboardController@getTopEndorsers');
    Route::get('viral-index', 'DashboardController@getViralIndex');

    Route::get('new-customers', 'DashboardController@getNewCustomers');
    Route::get('new-customers-with-product-subscription', 'DashboardController@getNewCustomersWithProductSubscription');
    Route::get('new-endorsers', 'DashboardController@getNewEndorsers');
    Route::get('new-endorsers-with-product-subscription', 'DashboardController@getNewEndorsersWithProductSubscription');

    Route::get('sales/customer-transformation-pack', 'DashboardController@getCustomerTransformationPackSales');
    Route::get('sales/transformation-pack', 'DashboardController@getTransformationPackSales');
    Route::get('sales/elite-pack', 'DashboardController@getElitePackSales');
    Route::get('sales/family-elite-pack', 'DashboardController@getFamilyElitePackSales');

    Route::get('endorsers/{user_id}/endorsers', 'DashboardController@getEndorsersIncludingFirstPurchase');

    Route::get('download', 'DashboardController@getDownloadLink');
});

Route::prefix('admin/sponsor-change')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('members', 'SponsorChangeController@members');
    Route::get('sponsors', 'SponsorChangeController@sponsors');
    Route::get('relationship', 'SponsorChangeController@relationship');
    Route::post('change', 'SponsorChangeController@change');
    Route::get('logs', 'SponsorChangeController@logs');
});

Route::prefix('admin/autoship')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('pending-autoship-amount', 'AutoshipReportController@pendingAutoshipAmount');
    Route::get('successful-autoship-amount', 'AutoshipReportController@successfulAutoshipAmount');
    Route::get('failed-autoship-amount', 'AutoshipReportController@failedAutoshipAmount');
    Route::get('members-count', 'AutoshipReportController@membersCount');
    Route::get('active-members-on-autoship-count', 'AutoshipReportController@activeMembersOnAutoshipCount');
    Route::get('cancelled-autoship-count', 'AutoshipReportController@cancelledAutoshipCount');
    Route::get('average-order-value', 'AutoshipReportController@averageOrderValue');

    Route::get('pending-autoship', 'AutoshipReportController@pendingAutoship');
    Route::get('successful-autoship', 'AutoshipReportController@successfulAutoship');
    Route::get('failed-autoship', 'AutoshipReportController@failedAutoship');
    Route::get('cancelled-autoship', 'AutoshipReportController@cancelledAutoship');
    Route::get('active-members-on-autoship', 'AutoshipReportController@activeMembersOnAutoship');
});


Route::prefix('admin/incentives')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('running', 'IncentiveToolController@running');
    Route::get('closed', 'IncentiveToolController@closed');
    Route::get('arbitrary-points', 'IncentiveToolController@arbitrary');
    Route::get('view-incentive-reps', 'IncentiveToolController@view');
    Route::get('get-ranks', 'IncentiveToolController@getRanks');
    Route::get('get-representatives', 'IncentiveToolController@getRepresentatives');
    Route::get('get-incentives', 'IncentiveToolController@getOpenIncentives');
    Route::get('get-incentive/{settings_id}', 'IncentiveToolController@getIncentiveSettings');
    Route::get('download/{settings_id}', 'IncentiveToolController@download');
    Route::post('/', 'IncentiveToolController@store');
    Route::post('update', 'IncentiveToolController@update');
    Route::post('{settings_id}/delete', 'IncentiveToolController@delete');
    Route::post('{settings_id}/hide', 'IncentiveToolController@hide');
    Route::post('{id}/deleteArbitrary', 'IncentiveToolController@deleteArbitrary');
    Route::post('addArbitrary', 'IncentiveToolController@addArbitrary');
});

Route::prefix('member/incentive-report')->namespace('Member')->middleware('jwt.auth')->group(function () {

    Route::get('available', 'IncentiveReportController@getAvailableIncentives');
    Route::get('progress', 'IncentiveReportController@getProgress');
});

/*
 * Group Sales
 * */
Route::prefix('admin/group-sales')->namespace('Admin')->group(function () {
    Route::get('/{year_month}', 'GroupSalesReportController@index');
    Route::get('getMembers/{parent_id}/{year_month}', 'GroupSalesReportController@getMembers');
    Route::get('getMemberOrders/{member_id}/{year_month}', 'GroupSalesReportController@getMemberOrders');
    Route::get('generate-report/{year_month}', 'GroupSalesReportController@getReport');
    Route::get('download/{file_name}', 'GroupSalesReportController@getDownload');
});

Route::prefix('admin/personal-retail-sales')->namespace('Admin')->middleware('jwt.auth.level:1,6')->group(function () {
    Route::get('enrollment', 'PersonalRetailSaleController@enrollment');
    Route::get('download-personal-retail', 'PersonalRetailSaleController@downloadPersonalRetail');
});