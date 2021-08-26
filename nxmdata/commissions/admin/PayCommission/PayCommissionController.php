<?php
    /*
    |--------------------------------------------------------------------------
    | Depends on the following tables and views.
    |--------------------------------------------------------------------------
    | cm_number_generator
    | cm_commission_payouts
    | cm_commission_periods
    | cm_commission_types
    | cm_payment_balance_forwarded
    | cm_commission_payments
    | cm_background_worker
    | cm_background_worker_messages
    | cm_payments
    | users
    | vw_hpw_payment_history
    | vw_hpw_users
    */
    namespace Commissions\admin\PayCommission;
    
    use App\flw\models\ConvertCountry;
    use App\flw\models\HyperwalletWrapper;
    use Commissions\interfaces\PaycommissionInterface;
    use Commissions\interfaces\RouteInterface;
    use \Illuminate\Database\Capsule\Manager as DB;
    
    class PayCommissionController implements RouteInterface, PaycommissionInterface {
        
        const IS_LOCKED = 1; // Set to 1 to get locked period else 0 for testing.
        const BASE_URI = 'https://office.myfluentworlds.com:81/commission_payments';
        const IS_DEBUG_HPW = false;
        
        private static function getNextNumber() {
        
            $payment_number = DB::table('cm_number_generator')
                ->select('last_value')
                ->where('name', '=','PAYMENT-NUMBER')
                ->first();
            if ($payment_number) {

                return ($payment_number['last_value'] + 0) + 1;
            } else {

                return 0;
            }
        }

        private static function setNextNumber() {
        
            $result = DB::table('cm_number_generator')
                ->select('last_value')
                ->where('name', '=','PAYMENT-NUMBER')
                ->first();
            $rowsAffected = 0;
            if ($result) {
            
                $rowsAffected = DB::table('cm_number_generator')
                    ->where('name', '=', 'PAYMENT-NUMBER')
                    ->update(array('last_value' => (0 + $result['last_value']) + 1));
            }

            return $rowsAffected;
        }

        private static function autoCreateHyperwalletAccount($user_id) {
        
            try {
                $user = DB::table('users')
                    ->where('id', '=', $user_id)
                    ->first();
            
                // ------------------------------------------------------------------------
                // The system just explicitly add a date that makes your age 18 from today.
                // ------------------------------------------------------------------------
                $dob = DB::table(DB::raw('(SELECT DATE_FORMAT(DATE_ADD(NOW(), INTERVAL -18 YEAR), \'%Y-%m-%d\') as dob) as tbl'))
                    ->first();
            
                $value = array(
                    'user_id' => $user['id'],
                    'clientUserId' => $user['id'],
                    'firstName' => $user['fname'],
                    'lastName' => $user['lname'],
                    'email' => $user['email'],
                    'addressLine1' => $user['address'],
                    'city' => $user['city'],
                    'stateProvince' => $user['state'],
                    'country' => ConvertCountry::valueOf($user['country']),
                    'postalCode' => $user['zip'],
                    'dateOfBirth' => $dob['dob'] // $user['dob']
                );

                $response = (new HyperwalletWrapper())
                    ->setIsDebug(self::IS_DEBUG_HPW)
                    ->createAccount($value);

                $jarr = json_decode($response, true);
                if (isset($jarr['token'])) {
                    return 0;
                } else {
                    return -1;
                }
            } catch (\Exception $exception) {
                return -1;
            }
        }
        
        public static function routeGetUser() {
        
            // https://office.trackmyripple.com:81/commission_payments?method=getUser&user_id=20
            $app = \Slim\Slim::getInstance();
            $user_id = $app->request->get('user_id');
        
            $user = DB::table('users')->where('id', '=', $user_id)
                ->select(DB::raw('*'))
                ->first();
            if ($user) {
            
                unset($user['password']);
                $app->response->headers->set('Content-Type', 'application/json');
                $app->response->write(json_encode($user));
                return $app->response;
            } else {
            
                $app->response->headers->set('Content-Type', 'application/json');
                $app->response->write('');
                return $app->response;
            }
        }

        public static function routeCreateHyperwalletUser() {

            $app = \Slim\Slim::getInstance();
            $value = array(
                'user_id' => $app->request->post('clientUserId'),
                'clientUserId' => $app->request->post('clientUserId'),
                'firstName' => $app->request->post('firstName'),
                'lastName' => $app->request->post('lastName'),
                'email' => $app->request->post('email'),
                'addressLine1' => $app->request->post('addressLine1'),
                'city' => $app->request->post('city'),
                'stateProvince' => $app->request->post('stateProvince'),
                'country' => $app->request->post('country'),
                'postalCode' => $app->request->post('postalCode'),
                'dateOfBirth' => $app->request->post('dateOfBirth')
            );
        
            $response = (new HyperwalletWrapper())
                ->setIsDebug(false)
                ->createAccount($value);
        
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->write($response);
            return $app->response;
        }

        public static function routeGetHistory() {
        
            $app = \Slim\Slim::getInstance();
            $draw = $app->request->get('draw');
            $start = $app->request->get('start');
            $length = $app->request->get('length');
            $search = $app->request->get('search');
        
            $filter = 'COMPLETED';
            $result = DB::table('vw_hpw_payment_history')
               // ->where('Status', '=', $filter)
                ->where(function($qry) use($search) {
                    $qry->whereRaw("(FirstName LIKE '%" . $search['value'] . "%')")
                        ->orwhereRaw("(Lastname LIKE '%" . $search['value'] . "%')");
                })
                ->skip($start)
                ->take($length)
                ->get();
        
            $total = DB::table('vw_hpw_payment_history')
                // ->where('Status', '=', $filter)
                ->count();
        
            $response = array(
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $result
            );
            $app->response->header('Content-Type', 'application/json');
            $app->response->write(json_encode($response, JSON_PRETTY_PRINT));
            return $app->response;
        }

        public static function routeTest() {
        
            // https://office.trackmyripple.com:81/commission_payments?method=test&user_id=1095&period_listing=52,59,63,70,74,78,82,86,93
            $app = \Slim\Slim::getInstance();
            $user_id = $app->request->get('user_id');
            $period_listing = $app->request->get('period_listing');
        
            if ($app->request->getIp() === '49.145.218.185') {
            
                $response = array('before' => array(), 'after' => array());
            
                $result = DB::table('cm_commission_payouts')
                    ->select('id', 'amount', 'is_paid')
                    ->whereIn('commission_period_id', explode(',', $period_listing))
                    ->where('user_id', '=', $user_id)
                    ->get();
            
                $response['before'] = $result;
            
                foreach ($result as $row) {
                
                    DB::table('cm_commission_payouts')
                        ->where('id', '=', $row['id'])
                        ->update(array('is_paid' => 1));
                }

                $result1 = DB::table('cm_commission_payouts')
                    ->select('id', 'amount', 'is_paid')
                    ->whereIn('commission_period_id', explode(',', $period_listing))
                    ->where('user_id', '=', $user_id)
                    ->get();
            
                $response['after'] = $result1;
            
                // Get payout that is for payment.
                // This listing should be similar to the list shown on the commission payment table.
                $app->response->headers->set('Content-Type', 'text/html');
                $app->response->write(json_encode($result));
                return $app->response;
            }
        
            return null;
        }
        
        public static function getUnpaidPayoutByPeriodIdQuery($period_id_listing) {
            
            $qry = DB::table('cm_commission_payouts as cp')
                ->leftJoin('cm_commission_periods as p', 'cp.commission_period_id', '=', 'p.id')
                ->leftJoin('cm_commission_types as t', 't.id', '=', 'p.commission_type_id')
                ->leftJoin('users', 'users.id', '=', 'cp.user_id')
                ->leftJoin('vw_hpw_users as gu', 'gu.user_id', '=', 'users.id')
                ->leftJoin(
                    DB::raw('(
                            select sum(coalesce(b.total, 0)) as total, b.user_id
                            from cm_payment_balance_forwarded as b
                            where (b.is_forwarded = \'No\')
                            group by b.user_id
                        ) as ba
                    '), 'ba.user_id','=','users.id'
                )
                ->whereIn('p.id', explode(",", $period_id_listing))
                ->where('p.is_locked', '=', self::IS_LOCKED)
                ->where('cp.is_paid', '=', 0);
            
            return $qry;
        } /* getUnpaidPayoutByPeriodIdQuery */

        public static function getPaymentList($period_id_listing, $user_id = null) {
            
            if ($user_id === null) {
                
                return  self::getUnpaidPayoutByPeriodIdQuery($period_id_listing)
                    ->select(
                        DB::raw('group_concat(distinct t.name separator \'<br/>\') as commission_type'),
                        DB::raw('group_concat(cp.id) as id'),
                        DB::raw('cp.user_id as member_id'),
                        DB::raw('concat(users.fname, \' \', users.lname) as member_name'),
                        DB::raw('users.email as email'),
                        DB::raw('group_concat(cp.commission_period_id) as commission_period_id'),
                        DB::raw('sum(coalesce(cp.amount, 0)) as total'), // total payouts.
                        DB::raw('case when (sum(cp.is_paid) > 0) then 1 else 0 end as is_paid'),
                        DB::raw('case when (sum(cp.is_excluded) > 0) then 1 else 0 end as is_excluded'),
                        DB::raw('coalesce(gu.token, \'\') as gateway'),
                        DB::raw('coalesce(ba.total, 0) as balance') // total rollover.
                    )->groupBy('cp.user_id')
                    ->get();
            } else {
                
                return self::getUnpaidPayoutByPeriodIdQuery($period_id_listing)
                    ->select(
                        DB::raw('group_concat(distinct t.name separator \'' . '< br/>' . '\') as commission_type'),
                        DB::raw('group_concat(cp.id) as id'),
                        DB::raw('cp.user_id as member_id'),
                        DB::raw('concat(users.fname, \' \', users.lname) as member_name'),
                        DB::raw('users.email as email'),
                        DB::raw('group_concat(cp.commission_period_id) as commission_period_id'),
                        DB::raw('sum(cp.amount) + max(coalesce(ba.total, 0)) as total'),
                        DB::raw('case when (sum(cp.is_paid) > 0) then 1 else 0 end as is_paid'),
                        DB::raw('case when (sum(cp.is_excluded) > 0) then 1 else 0 end as is_excluded'),
                        DB::raw('gu.token as gateway'),
                        DB::raw('max(coalesce(gu.ssnssi, \'\')) as gpg_member_ssnssi')
                    )
                    ->where('users.id', '=', $user_id)
                    ->groupBy('cp.user_id')
                    ->get();
            }
        }

        public static function markAsPaid($period_listing, $user_id) {
            
            $result = DB::table('cm_commission_payouts')
                ->select('id', 'amount', 'is_paid')
                ->whereIn('commission_period_id', explode(',', $period_listing))
                ->where('user_id', '=', $user_id)
                ->get();
            $rows_affected = 0;
            foreach ($result as $row) {
                $rows_affected += DB::table('cm_commission_payouts')
                    ->where('id', '=', $row['id'])
                    ->update(array('is_paid' => 1));
            }
            
            return $rows_affected;
        }

        public static function markAsForwarded($user_id) {
            
            return DB::update('
                UPDATE cm_payment_balance_forwarded
                SET is_forwarded = \'Yes\'
                WHERE ((user_id = ?)
                AND   (is_forwarded = \'No\'))', array($user_id)
            );
        }

        public static function getCSV($data, $filename) {
            
            if (count($data) === 0) {
                return '#';
            }
            
            $fp = fopen(__DIR__ . '/../../../app/public/reports/' . $filename, 'wb+');
            fputcsv($fp, array_keys($data[0]));
            foreach ((array)$data as $row) {
                fputcsv($fp, $row, ',', '"');
            }
            fclose($fp);
            
            if (!isset($_SERVER['HTTP_HOST'])) {
                
                $host = 'localhost';
                return strtolower('http://' . $host . '/app/public/reports/' . $filename);
            } else {
                
                $host = $_SERVER['HTTP_HOST'];
                return strtolower('https://' . $host . '/app/public/reports/' . $filename);
            }
        }

        public static function getTotal($period_id_listing) {
            
            $qryObject = self::getUnpaidPayoutByPeriodIdQuery($period_id_listing)
                ->select(DB::raw('sum(coalesce(cp.amount, 0)) as total'))
                ->where('cp.is_excluded', '=', 0)
                ->groupBy('cp.user_id');
            
            return DB::table(DB::raw(sprintf('(%s) as tbl', $qryObject->toSql())))
                ->mergeBindings($qryObject)
                ->sum('total');
        }

        public static function getTotalRollover($period_id_listing) {
            
            $qryObject = self::getUnpaidPayoutByPeriodIdQuery($period_id_listing)
                ->select(DB::raw('coalesce(ba.total, 0) as amount'))
                ->where('cp.is_excluded', '=', 0)
                ->groupBy('cp.user_id');
            
            return DB::table(DB::raw(sprintf('(%s) as tbl', $qryObject->toSql())))
                ->mergeBindings($qryObject)
                ->sum('tbl.amount');
        }

        public static function getTotalTransferFee($period_id_listing) {
            
            $qryObject = self::getUnpaidPayoutByPeriodIdQuery($period_id_listing)
                ->select(DB::raw('1.00 as fee'))
                ->where('cp.is_excluded', '=', 0)
                ->groupBy('cp.user_id');
            
            return DB::table(DB::raw(sprintf('(%s) as tbl', $qryObject->toSql())))
                ->mergeBindings($qryObject)
                ->sum('fee');
        }

        public static function getDownloadLink($period_id_listing) {
            
            try {
                $result = self::getPaymentList($period_id_listing);
                foreach ($result as $row) {
                    if ($row['gateway'] === '') self::autoCreateHyperwalletAccount($row['member_id']);
                }
                
                $resultCsv = array();
                $result = self::getPaymentList($period_id_listing);
                foreach($result as $row) {
                    
                    if ($row['is_excluded'] === '1') continue;
                    if (($row['gateway'] === null) || ($row['gateway'] === '')) continue;
                    $resultCsv[] = array(
                        'MemberId' => $row['member_id'],
                        'Amount' => number_format(+$row['total'], 2, '.', ''),
                        'Description' => 'Commission payout.'
                    );
                } /* foreach($result as $value) */
                
                return self::getCSV($resultCsv, 'unpaid_payout_' . date('Y-m-d') . '.csv');
            } catch(\Exception $exception) {
                
                $resultCsv = array();
                $resultCsv[] = array(
                    'MemberId' => 0,
                    'Amount' => 0,
                    'Description' => $exception->getMessage()
                );
                return self::getCSV($resultCsv, 'unpaid_payout_' . date('Y-m-d') . '.csv');
            }
        }
        
        public static function routeToggleCheckUncheckPayoutItem() {
            
            $app = \Slim\Slim::getInstance();
            $period_id_listing = $app->request->post('period_id_listing');
            $user_id = $app->request->post('user_id');
            $is_excluded = $app->request->post('is_excluded');
            
            if ($is_excluded === '0') {
                
                $is_excluded = 1;
            } else {
                
                $is_excluded = 0;
            }
            
            $rowsAffected = DB::table('cm_commission_payouts')
                ->select('id', 'is_paid')
                ->whereIn('commission_period_id', explode(',', $period_id_listing))
                ->where('user_id', '=', $user_id)
                ->update(array('is_excluded' => $is_excluded));
            
            if ($rowsAffected > 0) {
                
                $response = array('status' => '00', 'message' => 'Successful!');
            } else {
                
                $response = array('status' => '01', 'message' => 'Some payout is not mark as paid');
            }
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->write(json_encode($response, JSON_PRETTY_PRINT));
            return $app->response;
        } /* routeToggleCheckUncheckPayoutItem */
        public static function routeGetUnpaidList()
        {
            
            $app = \Slim\Slim::getInstance();
            $result = DB::table('cm_commission_payments as p')
                ->join('cm_commission_periods as cp', 'cp.id', '=', 'p.period_id')
                ->join('cm_commission_types as pt', 'pt.id', '=', 'cp.commission_type_id')
                ->whereRaw('p.token is not null')
                ->selectRaw('
                    p.period_id as `period_id`,
                    pt.name as `description`,
                    cp.start_date as `from`,
                    cp.end_date as `to`,
                    min(p.created_at) as `created_at`,
                    count(p.id) as `total_paid`,
                    sum(case when coalesce(p.token, \'\') = "" then 0 else 1 end) as `successful`,
                    sum(case when coalesce(p.token, \'\') = "" then 1 else 0 end) as `unsuccessful`
                ')
                ->groupBy('period_id')
                ->get();
            
            $app->response->write(json_encode($result, JSON_PRETTY_PRINT));
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->setStatus(200);
            
            return $app->response;
        }
        public static function routeGetCommissionTypeList()
        {
            
            $app = \Slim\Slim::getInstance();
            $result = DB::table('cm_commission_types as t')
                ->whereRaw('t.is_active = 1')// We only need to get active.
                ->where('frequency', '=', $app->request->get('frequency'))
                ->selectRaw('*')
                ->get();
            
            $app->response->write(json_encode($result, JSON_PRETTY_PRINT));
            $app->response->headers->set('Content-Type', 'application/json');
            return $app->response;
        }
        public static function routeGetLockPeriods() {
            
            $app = \Slim\Slim::getInstance();
            $type_id_listing = $app->request->get('type_id_listing');
            
            $result = DB::table('cm_commission_periods as p')
                ->leftJoin('cm_commission_types as t', 't.id', '=', 'p.commission_type_id')
                ->join(DB::raw('
                    (select
                        count(*) as num_of_unpaid,
                        commission_period_id
                    from cm_commission_payouts cp
                    where (cp.is_paid = 0)
                    group by commission_period_id
                    having num_of_unpaid > 0) as period_with_unpaid
                '), 'period_with_unpaid.commission_period_id', '=', 'p.id') // Do not include period with no payouts unpaid.
                ->where('p.is_locked', '=', self::IS_LOCKED)
                ->whereIn('p.commission_type_id', explode(',', $type_id_listing))
                ->select(
                    DB::raw('p.id'),
                    DB::raw('p.commission_type_id'),
                    DB::raw('p.start_date'),
                    DB::raw('p.end_date'),
                    DB::raw('p.is_locked'),
                    DB::raw('p.is_running'),
                    DB::raw('concat(\'[\', p.id, \']-\', t.name, \'(\', p.start_date, \' \', p.end_date) as description'),
                    DB::raw('period_with_unpaid.num_of_unpaid')
                )->get();;
            
            $app->response->write(json_encode($result, JSON_PRETTY_PRINT));
            $app->response->headers->set('Content-Type', 'application/json');
            return $app->response;
        } /* routeGetLockPeriods */
        public static function routeGetUnpaidPayoutsByPeriodId() {
            
            $app = \Slim\Slim::getInstance();
            $period_id_listing = $app->request->get('period_id_listing');
            
            $result = self::getPaymentList($period_id_listing);

            $total = 0.00;
            foreach ($result as $row) {
                $total += +$row['total'];
            }

            $data = array(
                'total' => $total,
                'totalRollover' => self::getTotalRollover($period_id_listing),
                'downloadLink' => self::getDownloadLink($period_id_listing),
                'data' => $result,
                'count' => count($result),
                'totalFee' => self::getTotalTransferFee($period_id_listing)
            );
            
            $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->setStatus(200);
            return $app->response;
        } /* routeGetUnpaidPayoutsByPeriodId */

        public static function routePayCommission() {
            // -1 - Another instance is running.
            // 00 - Successful.
            // -2 - Internal error.
            // -3 - Invalid resource.
            
            $app = \Slim\Slim::getInstance();
            try {
                $worker_id = $app->request->post('worker_id');
                $period_id_listing = $app->request->post('period_id_listing');
                $line = 1;
                
                // ---------------------------------------------------------------------------------
                // Get payout that is for payment.
                // This listing should be similar to the list shown on the commission payment table.
                // Scan all members with no hyperwallet account via gateway column.
                // If no account auto create it
                // ---------------------------------------------------------------------------------
                $result = self::getPaymentList($period_id_listing);
                foreach ($result as $row) {
                    
                    if ($row['gateway'] === '') {
                        
                        DB::table('cm_background_worker_messages')->insertGetId(array(
                            'worker_id' => $worker_id,
                            'message' => "[" . $line++ . "]-Auto create account for member " . $row['member_id'] . '.'
                        ));
                        
                        $retval = self::autoCreateHyperwalletAccount($row['member_id']);
                        if (+$retval < 0) {

                            DB::table('cm_background_worker_messages')->insertGetId(array(
                                'worker_id' => $worker_id,
                                'message' => "[" . $line++ . "]-Auto create account for member " . $row['member_id'] . ' failed!'
                            ));
                        } else {

                            DB::table('cm_background_worker_messages')->insertGetId(array(
                                'worker_id' => $worker_id,
                                'message' => "[" . $line++ . "]-Auto create account for member " . $row['member_id'] . ' successful!'
                            ));
                        }
                    }
                }
                
                // ---------------------------------------------------------------------------------
                // After we auto-create missing account we will again get payout that is for payment.
                // This listing should be similar to the list shown on the commission payment table.
                // ---------------------------------------------------------------------------------
                DB::table('cm_background_worker_messages')->insertGetId(array(
                    'worker_id' => $worker_id,
                    'message' => "[" . $line++ . "]-Refreshing list..."
                ));
                $result = self::getPaymentList($period_id_listing);
                
                DB::table('cm_background_worker')->where('id', '=', $worker_id)->update(array(
                    'total_task' => count($result),
                    'progress' => 0,
                    'total_task_done' => 0
                ));
                
                $counter = 0;
                $failedCtr = 0;
                $i = 1;
                foreach($result as $row) {
                    
                    $total =  ($row['total'] + 0);
                    
                    // ---------------------------------------
                    // Skipping uncheck items ***************.
                    // ---------------------------------------
                    if ($row['is_excluded'] === '1') {
                        
                        DB::table('cm_background_worker_messages')->insertGetId(array(
                            'worker_id' => $worker_id,
                            'message' => "[" . $line++ . "]-Member " . $row['member_id'] . ' has been excluded from the payout!'
                        ));
                        $i++;
                        continue;
                    } /* ($row['is_excluded'] === '1') */
                    
                    // -----------------------------------------------------------------
                    // Do not include if member don't have hyperwallet account ****************.
                    // -----------------------------------------------------------------
                    if (($row['gateway'] === null) || ($row['gateway'] === '')) {
                        
                        DB::table('cm_background_worker_messages')->insertGetId(array(
                            'worker_id' => $worker_id,
                            'message' => "[" . $line++ . "]-Member " . $row['member_id'] . ' has no hyperwallet account!'
                        ));
                        $i++;
                        continue;
                    }
                    
                    // ----------------------------------------------------------------------------------------
                    // Early exit if the user cancelled. *************
                    // Here we will return status = 00 as a successful but all paid member will be not included
                    // on the list of the user will re-run payment as it is mark as paid.
                    // ----------------------------------------------------------------------------------------
                    $bg = DB::table('cm_background_worker')->where('id', '=', $worker_id)->first();
                    if (($bg) && ($bg['is_running'] === 'CANCELLED')) {
                        
                        DB::table('cm_background_worker_messages')->insertGetId(array(
                            'worker_id' => $worker_id,
                            'message' => "[" . $line . "]-Process is cancelled by the user!"
                        ));
                        
                        DB::table('cm_background_worker')
                            ->where('id', '=', $worker_id)->update(array(
                                'total_task' => count($result),
                                'progress' => 100,
                                'total_task_done' => 0,
                                'is_running' => 'COMPLETED'
                            ));
                        
                        $app->response->headers->set('Content-Type', 'application/json');
                        $app->response->write(json_encode(array(
                            'status' => '00',
                            'message' => "Process is cancelled by the user!",
                            'data' => array('count' => count($result), 'successful' => $counter, 'failed' => $failedCtr)
                        ), JSON_PRETTY_PRINT));
                        return $app->response;
                    } /* (($bg) && ($bg['is_running'] === 'CANCELLED')) */
                    
                    // ----------------------------------------------------------------------
                    // Send the payment to hyperwallet.
                    // Here we use the wrapper to allow us to easily switch from live or uat.
                    // setIsDebug to true redirect us to testing environment else live.
                    // ----------------------------------------------------------------------
                    $response = (new HyperwalletWrapper())
                        ->setIsDebug(self::IS_DEBUG_HPW) // false = live, true = development
                        ->sendPayment(array(
                            'amount' => +$total,
                            'clientPaymentId' => self::getNextNumber(),
                            'user_id' => $row['member_id']
                        ));
                    
                    if (($response['token'] !== '') &&  ($response['status'] === 'COMPLETED')) {
                        
                        DB::table('cm_background_worker_messages')->insertGetId(array(
                            'worker_id' => $worker_id,
                            'message' => "[" . $line++ . "]-" . $total . ' is sent to [' . $row['member_id'] . ']-' . $row['member_name'] . '\'s hyperwallet. Token:' . $response['token']
                        ));
                        
                        DB::table('cm_payments')->insertGetId(array(
                            'user_id' => $row['member_id'], // user_id
                            'total' => $total,
                            'charge' => 0,
                            'mode_of_payment' => 'HYPERWALLET',
                            'trans_no' => $response['token']
                        ));
                        
                        // -----------------------------------------------------------------
                        // If sending is successful we mark payouts as paid and if there is
                        // balance forwarded we will also mark it as forwarded.
                        // We also sa the clientPaymentId number used above.
                        // -----------------------------------------------------------------
                        self::markAsPaid($period_id_listing, $row['member_id']);
                        self::markAsForwarded($row['member_id']);
                    } else {
                        
                        DB::table('cm_background_worker_messages')->insertGetId(array(
                            'worker_id' => $worker_id,
                            'message' => "[" . $line++ . "]-System failed to send " . $row['total'] . ' to ' . $row['member_name'] . ' hyperwallet account.'
                        ));
                    }
                    
                    DB::table('cm_background_worker')->where('id', '=', $worker_id)
                        ->update(array(
                            'total_task' => count($result),
                            'progress' => ceil(($i /  count($result)) * 100),
                            'total_task_done' => $i
                        ));

                    self::setNextNumber(); // save the new number.
                    sleep(1);
                    $i++;
                } /* foreach($result as $value) */
                
                DB::table('cm_background_worker_messages')->insertGetId(array(
                    'worker_id' => $worker_id,
                    'message' => "[" . $line . "]-Finished!"
                ));
                
                DB::table('cm_background_worker')->where('id', '=', $worker_id)->update(array(
                    'total_task' => count($result),
                    'progress' => 100,
                    'total_task_done' => 0,
                    'is_running' => 'COMPLETED'
                ));
                
                $app->response->headers->set('Content-Type', 'application/json');
                $app->response->write(json_encode(array(
                    'status' => '00',
                    'message' => "Finish!",
                    'data' => array('count' => count($result), 'successful' => $counter, 'failed' => $failedCtr)), JSON_PRETTY_PRINT
                ));
                return $app->response;
            } catch(\Exception $exception) {
                
                $app->response->headers->set('Content-Type', 'application/json');
                $app->response->write(json_encode(array(
                    'status' => '-2',
                    'message' => $exception->getMessage()), JSON_PRETTY_PRINT
                ));
                return $app->response;
            }
        }
        public static function routeSaveBackgroundWorker()
        {
            /*
            |---------------------------------------------------------------------------------
            | URL: https://office.realizevo.com:81/commission_payments
            | Status
            | 00 -> Successful!
            | 01 -> Successfully cancelled failed.
            | 02 -> Successfully added.
            | 03 -> Worker is busy for this value.
            |--------------------------------------------------------------------------------- */
            $app = \Slim\Slim::getInstance();
            $period_id = $app->request->post('period_id');
            $worker_id = $app->request->post('worker_id');
            $is_running = $app->request->post('is_running');
            
            // Process cancelled.
            // Here the user is cancelling the background process specified by worker_id.
            //
            if ($is_running === 'CANCELLED') {
                
                $ok = DB::table('cm_background_worker')
                    ->where('id', '=', $worker_id)
                    ->update(array('is_running' => 'CANCELLED'));
                if ($ok) {
                    
                    $result = DB::table('cm_background_worker')->where('id', '=', $worker_id)->get();
                    $data = array(
                        'status' => '00',
                        'message' => 'Worker is successfully CANCELLED.',
                        'data' => $result
                    );
                } else {
                    
                    $data = array(
                        'status' => '01',
                        'message' => 'Cancellation failed.',
                        'data' => array()
                    );
                }
                
                $app->response->header('content-Type: application/json');
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            } // ($is_running === 'CANCELLED')
            
            // Do not allow to run another period if it is already running.
            $value = json_encode(array('period_id' => $period_id, 'module' => 'PAY_COMMISSION'));
            $result = DB::table('cm_background_worker')
                ->where(function($qry) use($value) {
                    $qry->where('value', '=', $value);
                    $qry->where('is_running', '=', 'YES');
                })->orWhere(function($qry) use($value) {
                    $qry->where('value', '=', $value);
                    $qry->where('is_running', '=', 'PENDING');
                })->first();
            if ($result) {
                
                $data = array(
                    'status' => '03',
                    'message' => 'Background worker is currently busy for this value.',
                    'data' => array(
                        'id' => $result['id'],
                        'progress' => $result['progress'],
                        'total_task' => $result['total_task'],
                        'value' => $value,
                        'created_at' => $result['created_at'],
                        'updated_at' => $result['updated_at'],
                        'is_running' => $result['is_running']
                    )
                );
                
                $app->response->header('content-Type: application/json');
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            } // ($result)
            
            $id = DB::table('cm_background_worker')->insertGetId(array('value' => $value));
            $result = DB::table('cm_background_worker')->where('id', '=', $id)->first();
            
            $app->response->header('content-Type: application/json');
            $app->response->write(json_encode(array(
                'status' => '02',
                'message' => 'New background worker is successfully created.',
                'data' => $result
            ), JSON_PRETTY_PRINT));
            return $app->response;
        }
        public static function routeSaveSyncAccountBackgroundWorker() {
            /*
            |---------------------------------------------------------------------------------
            | URL: https://office.realizevo.com:81/commission_payments
            | Status
            | 00 -> Successful!
            | 01 -> Successfully cancelled failed.
            | 02 -> Successfully added.
            | 03 -> Worker is busy for this value.
            |--------------------------------------------------------------------------------- */
            $app = \Slim\Slim::getInstance();
            $period_id_listing = $app->request->post('period_id_listing');
            $worker_id = $app->request->post('worker_id');
            $is_running = $app->request->post('is_running');
            
            // Process cancelled.
            if ($is_running === 'CANCELLED') {
                
                $ok = DB::table('cm_background_worker')
                    ->where('id', '=', $worker_id)
                    ->update(array('is_running' => $is_running));
                if ($ok) {
                    
                    $result = DB::table('cm_background_worker')->where('id', '=', $worker_id)->get();
                    $data = array(
                        'status' => '00',
                        'message' => 'Worker is successfully CANCELLED.',
                        'data' => $result
                    );
                } else {
                    
                    $data = array(
                        'status' => '01',
                        'message' => 'Cancellation failed.',
                        'data' => array()
                    );
                }
                
                $app->response->header('content-Type: application/json');
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            } // ($is_running === 'CANCELLED')
            
            // Do not allow to run another period if it is already running.
            $value = json_encode(array(
                'period_id_listing' => $period_id_listing,
                'module' => 'AUTO_SYNC_ACCOUNT'
            ));
            
            $result = DB::table('cm_background_worker')
                ->where('value', '=', $value)
                ->where('is_running', '=', 'YES')
                ->orWhere('is_running', '=', 'PENDING')->first();
            if ($result) {
                
                $data = array(
                    'status' => '03',
                    'message' => 'Background worker is currently busy for this value.',
                    'data' => array(
                        'id' => $result['id'],
                        'progress' => $result['progress'],
                        'total_task' => $result['total_task'],
                        'value' => $value,
                        'created_at' => $result['created_at'],
                        'updated_at' => $result['updated_at'],
                        'is_running' => $result['is_running']
                    )
                );
                
                $app->response->header('content-Type: application/json');
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            } // ($result)
            
            $id = DB::table('cm_background_worker')->insertGetId(array('value' => $value));
            $result = DB::table('cm_background_worker')->where('id', '=', $id)->first();
            
            $app->response->header('content-Type: application/json');
            $app->response->write(json_encode(array(
                'status' => '02',
                'message' => 'New background worker is successfully created.',
                'data' => $result
            ), JSON_PRETTY_PRINT));
            return $app->response;
        }
        public static function routeGetBackgroundWorker()
        {
            // https://office.realizevo.com:81/commission_payments?method=getBackgroundWorker&worker_id=1
            $app = \Slim\Slim::getInstance();
            $worker_id = $app->request->get('worker_id');
            $result = DB::table('cm_background_worker')
                ->where('id', '=', $worker_id)
                ->selectRaw('
                id,  -- bigint(20) NOT NULL AUTO_INCREMENT
                coalesce(progress, 0) as progress, --  int(11) DEFAULT NULL
                coalesce(total_task, 0) as total_task, -- int(11) DEFAULT NULL
                coalesce(total_task_done, 0) as total_task_done, -- int(11) DEFAULT NULL
                coalesce(value, \'\'), --  varchar(2000) DEFAULT NULL
                coalesce(download_link, \'\') as download_link, --  varchar(2000) DEFAULT NULL COMMENT \'It will hold html string of two button download link.\'
                is_running, --  enum (\'YES\', \'COMPLETED\', \'PENDING\', \'NO\', \'CANCELLED\') DEFAULT \'YES\'
                updated_at, --  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                created_at --  datetime DEFAULT CURRENT_TIMESTAMP
            ')
                ->first();
            if ($result) {
                
                $messages = DB::table('cm_background_worker_messages')->where('worker_id', '=', $worker_id)->get();
                $data = array(
                    'status' => '00',
                    'background_worker' => $result,
                    'background_worker_messages' => $messages,
                    'self_url' => self::BASE_URI . '?method=getBackgroundWorker&worker_id=' . $worker_id
                );
                
                $app->response->header("content-Type: application/json");
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            } else {
                
                $data = array('status' => '01', 'data' => array());
                $app->response->header("content-Type: application/json");
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            }
        }
        public static function routeGetSyncAccountBackgroundWorker() {
            
            // https://office.realizevo.com:81/commission_payments?method=getSyncAccountBackgroundWorker&worker_id=1
            $app = \Slim\Slim::getInstance();
            $worker_id = $app->request->get('worker_id');
            $result = DB::table('cm_background_worker')
                ->where('id', '=', $worker_id)
                ->selectRaw('
                id,                                                 -- bigint(20) NOT NULL AUTO_INCREMENT
                coalesce(progress, 0) as progress,                  --  int(11) DEFAULT NULL
                coalesce(total_task, 0) as total_task,              -- int(11) DEFAULT NULL
                coalesce(total_task_done, 0) as total_task_done,    -- int(11) DEFAULT NULL
                coalesce(value, \'\'),                              --  varchar(2000) DEFAULT NULL
                coalesce(download_link, \'\') as download_link,     --  varchar(2000) DEFAULT NULL COMMENT \'It will hold html string of two button download link.\'
                is_running,                                         --  enum (\'YES\', \'COMPLETED\', \'PENDING\', \'NO\', \'CANCELLED\') DEFAULT \'YES\'
                updated_at,                                         --  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                created_at                                          --  datetime DEFAULT CURRENT_TIMESTAMP
            ')->first();
            if ($result) {
                
                $messages = DB::table('cm_background_worker_messages')
                    ->where('worker_id', '=', $worker_id)
                    ->get();
                $data = array(
                    'status' => '00',
                    'background_worker' => $result,
                    'background_worker_messages' => $messages,
                    'self_url' => self::BASE_URI . '?method=getSyncAccountBackgroundWorker&worker_id=' . $worker_id
                );
                
                $app->response->header("content-Type: application/json");
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            } else {
                
                $data = array('status' => '01', 'data' => array());
                $app->response->header("content-Type: application/json");
                $app->response->write(json_encode($data, JSON_PRETTY_PRINT));
                return $app->response;
            }
        }
        public static function routeSyncAccount() {
            
            $app = \Slim\Slim::getInstance();
            $app->response->header("content-Type: application/json");
            $app->response->write('Resource not found!');
            return $app->response;
        }
        public static function routeManualPayment() {
            
            $app = \Slim\Slim::getInstance();
            $period_listing = $app->request->post('period_id_listing');
            $user_id = $app->request->post('user_id');
            
            $rowsAffected = self::markAsPaid(
                $period_listing,    // List of period_id separated by comma.
                $user_id            // Member ID.
            );
            
            if ($rowsAffected > 0) {
                
                $response = array('status' => '00', 'message' => 'Successful!');
            } else {
                
                $response = array('status' => '01', 'message' => 'Some payout is not mark as paid');
            }
            $app->response->header("content-Type: application/json");
            $app->response->write(json_encode($response, JSON_PRETTY_PRINT));
            return $app->response;
        } /* routeManualPayment */
        public static function routeGetTotalPayment() {
            
            // https://office.myctfohub.com:81/commission_payments?method=getTotalPayment
            $app = \Slim\Slim::getInstance();
            $period_listing = $app->request->get('period_id_listing');
            $user_id = $app->request->get('user_id');
            
            $result = self::getPaymentList($period_listing, $user_id);
            $total = 0.00;
            foreach ($result as $row) {
                
                if (+$row['is_excluded'] === 0) {
                    
                    $total += $row['total'];
                }
            }
            
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->setStatus(200);
            $app->response->write(json_encode(array(
                'status' => '00',
                'total' => $total,
                'message' => 'Successful!'
            )));
            return $app->response;
        }
        public static function routeCancelBackgroundWorker() {
            
            $app = \Slim\Slim::getInstance();
            
            $rowsAffected = DB::table('cm_background_worker')
                ->where('id', '=', $app->request->post('worker_id'))
                ->update(array('is_running' => 'CANCELLED'));
            
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->setStatus(200);
            $app->response->write(json_encode(array(
                'status' => '00',
                'message' => $rowsAffected . ' rows affected.'
            )));
            return $app->response;
        }
        public static function routeMarkAllAsPaid() {
            
            $app = \Slim\Slim::getInstance();
            $period_listing = $app->request->post('period_id_listing');
            
            $rowsAffected = DB::table('cm_commission_payouts')
                ->select('id', 'is_paid')
                ->whereIn('commission_period_id', explode(',', $period_listing))
                ->update(array('is_paid' => 1));
            
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->setStatus(200);
            $app->response->write(json_encode(array(
                'status' => '00',
                'message' => $rowsAffected . ' rows affected!'
            )));
            return $app->response;
        }
        public static function routeDefault() {
        
            // https://office.trackmyripple.com:81/commission_payments
            $app = \Slim\Slim::getInstance();
            $app->response->headers->set('Content-Type', 'application/json');
            $app->response->write('');
            return $app->response;
        }
        
        public static function route($app) {
            
            // https://office.myctfohub.com:81/commission_payments
            $app = \Slim\Slim::getInstance();
            $app->get('/commission_payments', function() use($app) {
                
                if ($app->request->get('method') === 'getUnpaidList') return self::routeGetUnpaidList();
                if ($app->request->get('method') === 'getCommissionTypeList') return self::routeGetCommissionTypeList();
                if ($app->request->get('method') === 'getLockPeriods') return self::routeGetLockPeriods();
                if ($app->request->get('method') === 'getUnpaidPayoutsByPeriodId') return self::routeGetUnpaidPayoutsByPeriodId();
                if ($app->request->get('method') === 'getBackgroundWorker') return self::routeGetBackgroundWorker();
                if ($app->request->get('method') === 'getBackgroundWorkerSyncAccount') return self::routeGetSyncAccountBackgroundWorker();
                if ($app->request->get('method') === 'getTotalPayment') return self::routeGetTotalPayment();
                if ($app->request->get('method') === 'test') return self::routeTest();
                if ($app->request->get('method') === 'getUser') return self::routeGetUser();
                if ($app->request->get('method') === 'getHistory') return self::routeGetHistory();
                return self::routeDefault();
            });
            $app->post('/commission_payments', function () use ($app) {
                
                $app = \Slim\Slim::getInstance();
                if ($app->request->post('method') === 'payCommission') return self::routePayCommission();
                if ($app->request->post('method') === 'saveBackgroundWorker') return self::routeSaveBackgroundWorker();
                if ($app->request->post('method') === 'saveSyncAccountBackgroundWorker') return self::routeSaveSyncAccountBackgroundWorker();
                if ($app->request->post('method') === 'toggleCheckUncheckPayoutItem') return self::routeToggleCheckUncheckPayoutItem();
                if ($app->request->post('method') === 'syncAccount') return self::routeSyncAccount();
                if ($app->request->post('method') === 'manualPayment') return self::routeManualPayment();
                if ($app->request->post('method') === 'cancelBackgroundWorker') return self::routeCancelBackgroundWorker();
                if ($app->request->post('method') === 'markAllAsPaid') return self::routeMarkAllAsPaid();
                if ($app->request->post('method') === 'createHyperwalletUser') return self::routeCreateHyperwalletUser();
                return self::routeDefault();
            });
        }
    }