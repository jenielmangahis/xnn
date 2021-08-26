<?php
    namespace Commissions\interfaces;
    
    interface PaycommissionInterface
    {
        public static function getUnpaidPayoutByPeriodIdQuery($period_id_listing);
        public static function getPaymentList($period_id_listing, $user_id = null);
        public static function markAsPaid($period_listing, $user_id);
        public static function markAsForwarded($user_id);
        public static function getCSV($data, $filename);
        public static function getTotal($period_id_listing);
        public static function getTotalRollover($period_id_listing);
        public static function getTotalTransferFee($period_id_listing);
        public static function getDownloadLink($period_id_listing);
        
        public static function routeToggleCheckUncheckPayoutItem(); /* routeToggleCheckUncheckPayoutItem */
        public static function routeGetUnpaidList();
        public static function routeGetCommissionTypeList();
        public static function routeGetLockPeriods();
        public static function routeGetUnpaidPayoutsByPeriodId();
        public static function routePayCommission();
        public static function routeSaveBackgroundWorker();
        public static function routeSaveSyncAccountBackgroundWorker();
        public static function routeGetBackgroundWorker();
        public static function routeGetSyncAccountBackgroundWorker();
        public static function routeSyncAccount();
        public static function routeManualPayment();
        public static function routeDefault();
        public static function routeGetTotalPayment();
        public static function routeCancelBackgroundWorker();
        public static function routeMarkAllAsPaid();
        public static function route($app);
    }