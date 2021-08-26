<?php
    
    namespace Commissions\admin\Clawback;
    use \Illuminate\Database\Capsule\Manager as DB;
    use \PDO;
    
    class ClawbackController implements \Commissions\interfaces\RouteInterface
    {
        protected $db;
        function __construct() {
            
            $this->db = \Commissions\Database::getInstance()->getDB();
        }
        public function getRefundableTransactions() {
            
            $app = \Slim\Slim::getInstance();
            $draw = $app->request->get('draw');
            $start = $app->request->get('start');
            $length = $app->request->get('length');
            $search = $app->request->get('search');
            
            $member_id = $app->request->get('member_id');
            $dateFrom = ($app->request->get('from_date')) ? $app->request->get('from_date') : '';
            $dateTo = ($app->request->get('to_date')) ? $app->request->get('to_date') : '';
            
            $qryObject = DB::table('transactions as t')
                ->leftJoin('users as u', 'u.id', '=', 't.userid')
                ->leftJoin('cm_commission_payout_refunds as ccpr', 'ccpr.transaction_id', '=', 't.id');
            $qry1 = clone $qryObject;
            $qry2 = clone $qryObject;
            
            if ($app->request->get('member_id')) $qry1->where('u.id', '=', $member_id);
            if (($dateFrom !== '') && ($dateTo !== ''))  $qry1->whereRaw('date(t.transactiondate) between date(?) and date(?)', array($dateFrom, $dateTo));
            if (isset($search['value'])) {
                $qry1->where(function($query) use($search) {
                    if (is_numeric($search['value'])) {
                        $query->where('t.id', '=', $search['value']);
                    } else {
                        $query->where('u.fname', 'like', '%' . $search['value'] . '%')
                            ->orWhere('u.lname', 'like', '%' . $search['value'] . '%');
                    }
                });
            }
            $result = $qry1->selectRaw('
                    coalesce(concat(u.id, \': \', u.fname, \' \', u.lname), \'\') as purchaser,
                    concat(\'row-\', t.id) as tbl_row_id,
                    t.id as order_id,
                    getProductByTransactionId(t.id) as product,
                    date_format(t.transactiondate, \'%m/%d/%Y\') as purchased_date,
                    t.amount as amount_paid,
                    if(ccpr.id is null, 0, \'Refunded\') as status,
                    ifnull(date_format(ccpr.set_refund_date, \'%m/%d/%Y\'), \'N/A\') as date_refunded
                ')
                ->skip($start)
                ->take($length)
                ->get();
            
            if ($app->request->get('member_id')) $qry2->where('u.id', '=', $member_id);
            if (($dateFrom !== '') && ($dateTo !== ''))  $qry2->whereRaw('date(t.transactiondate) between date(?) and date(?)', array($dateFrom, $dateTo));
            if (isset($search['value'])) {
                $qry2->where(function($query) use($search) {
                    if (is_numeric($search['value'])) {
                        $query->where('t.id', '=', $search['value']);
                    } else {
                        $query->where('u.fname', 'like', '%' . $search['value'] . '%')
                            ->orWhere('u.lname', 'like', '%' . $search['value'] . '%');
                    }
                });
            }
            $count = $qry2->selectRaw('
                    coalesce(concat(u.id, \': \', u.fname, \' \', u.lname), \'\') as purchaser,
                    concat(\'row-\', t.id) as tbl_row_id,
                    t.id as order_id,
                    getProductByTransactionId(t.id) as product,
                    date_format(t.transactiondate, \'%m/%d/%Y\') as purchased_date,
                    t.amount as amount_paid,
                    if(ccpr.id is null, 0, \'Refunded\') as status,
                    ifnull(ccpr.set_refund_date, \'N/A\') as date_refunded
                ')->count();
            
            $response = array(
                'draw' => $draw,
                'recordsTotal' => $count,
                'recordsFiltered' => $count,
                'data' => $result
            );
            $app->response->header('Content-Type', 'application/json');
            $app->response->write(json_encode($response, JSON_PRETTY_PRINT));
            return $app->response;
        }
        public function setTransactionAsRefunded() {
            
            $app = \Slim\Slim::getInstance();
            try {
                $payouts = DB::table('cm_commission_payouts as cp')
                    ->leftJoin('cm_commission_payout_details as cpd', 'cpd.commission_payout_id', '=', 'cp.id')
                    ->leftJoin('cm_commission_periods as p', 'p.id', '=', 'cp.commission_period_id')
                    ->where('cpd.transaction_id', '=', $app->request->post('order_id'))
                    ->selectRaw('cp.id AS payout_id, p.is_locked')
                    ->first();
                if ($payouts) {
        
                    DB::table('cm_commission_payout_refunds')->insertGetId(array(
                        'commission_payout_id' => $payouts['payout_id'],
                        'transaction_id' => $app->request->post('order_id'),
                        'set_refund_date' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
                        'set_refunded_by' => $app->request->post('user_id')
                    ));
        
                    if ((int)$payouts['is_locked'] === 0) {
            
                        DB::table('cm_commission_payouts')->where('id', '=', $payouts['payout_id'])->delete();
                        DB::table('cm_commission_payout_details')->where('commission_payout_id', '=', $payouts['payout_id'])->delete();
                    }
                } else {
        
                    DB::table('cm_commission_payout_refunds')->insertGetId(array(
                        'transaction_id' => $app->request->post('order_id'),
                        'set_refund_date' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
                        'set_refunded_by' => $app->request->post('user_id')
                    ));
                }
    
                $response = array(
                    'status' => '00',
                    'message' => 'Successful!'
                );
    
                $app->response->header('Content-Type', 'application/json');
                $app->response->write(json_encode($response, JSON_PRETTY_PRINT));
                return $app->response;
            } catch (\Exception $exception) {
    
                $response = array(
                    'status' => '-1',
                    'message' => $exception->getMessage()
                );
                
                $app->response->header('Content-Type', 'application/json');
                $app->response->write(json_encode($response, JSON_PRETTY_PRINT));
                return $app->response;
            }
        }
        public function doCommissionRefunds($commission_type_id, $payouts, $periodID) {
            
            $sql1 = "SELECT
                    cpr.id
                    , cp.id AS payout_id
                    , cp.commission_type_id AS type_id
                    , p.is_locked
                    , p.start_date
                    , p.end_date
                    , cpd.amount
                    , cp.user_id AS sponsor_id
                    , cpd.user_id AS member_id
                    , cpr.transaction_id
                FROM cm_commission_payout_refunds cpr
                LEFT JOIN cm_commission_payouts cp ON cp.id = cpr.commission_payout_id
                LEFT JOIN cm_commission_payout_details cpd ON cp.id = cpd.commission_payout_id
                LEFT JOIN cm_commission_periods p ON cp.commission_period_id = p.id
                LEFT JOIN cm_commission_periods rp ON rp.id = cpr.refunded_in_period_id
                WHERE cp.amount > 0
                    AND (cpr.refunded_in_period_id IS NULL OR cpr.refunded_in_period_id = 0 OR rp.is_locked = 0)
                    AND p.is_locked = 1
                    AND p.commission_type_id = :type_id
                    AND cp.id IS NOT NULL";
            
            $stmt = $this->db->prepare($sql1);
            $stmt->bindParam(':type_id', $commission_type_id);
            $stmt->execute();
            $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $details = array();
            $totalPayoutPerUser = $this->getTotalPayoutPerUser($payouts);
            
            foreach ($refunds As $refund) {
                
                if (isset($totalPayoutPerUser[$refund['sponsor_id']]) && $refund['amount'] <= $totalPayoutPerUser[$refund['sponsor_id']]) {
                    $r_sql = "Update cm_commission_payout_refunds Set refunded_in_period_id = :period_id Where id = :refund_id";
                    $stmt = $this->_DB->prepare($r_sql);
                    $stmt->bindParam(':refund_id', $refund['id']);
                    $stmt->bindParam(':period_id', $periodID);
                    $stmt->execute();
                    
                    $details[] = array(
                        'sponsor_id' => $refund['sponsor_id'],
                        'business_center_id' => !empty($refund['member_id']) ? $refund['member_id'] : 0,
                        'order_id' => $refund['transaction_id'],
                        'payout_type' => $refund['type_id'],
                        'value' => 0, //commission value
                        'commission' => ($refund['amount'] * -1),
                        'percentage' => 0,
                        'remarks' => 'Refund from period:' . $refund['start_date'] . ' - ' . $refund['end_date']
                    );
                    $newTotalPayout = $totalPayoutPerUser[$refund['sponsor_id']] - $refund['amount'];
                    $totalPayoutPerUser[$refund['sponsor_id']] = $newTotalPayout;
                }
            }
            
            return $details;
        }
        public function getTotalPayoutPerUser($payouts) {
            
            $payoutBreakdown = array();
            foreach ($payouts as $userPayout) {
                
                $userID = $userPayout['sponsor_id'];
                $commission = $userPayout['commission'];
                
                if (!isset($payoutBreakdown[$userID])) {
                    
                    $payoutBreakdown[$userID] = $commission;
                } else {
                    
                    $currentTotal = $payoutBreakdown[$userID];
                    $payoutBreakdown[$userID] = $currentTotal + $userPayout['commission'];
                }
            }
            
            return $payoutBreakdown;
        }
        public function getUsersDatasource() {
            
            // https://office.trackmyripple.com:81/clawback?method=getUsersDatasource
            $app = \Slim\Slim::getInstance();
            $term = $app->request->get('term');
            $qry = DB::table('users');
            if (is_numeric($term)) {
                
                $qry->where('id', '=', $term);
            } else {
                
                $qry->where(function($qry) use($term) {
                    $qry->where('fname', 'like', '%' . $term . '%')
                        ->orwhere('lname', 'like', '%' . $term . '%')
                        ->orwhere('site', 'like', '%' . $term . '%');
                });
            }
            
            $result = $qry->selectRaw('id as `id`, concat(id, \'-\', fname, \' \', lname) as `label`')->take(10)->get();
            $app->response->write(json_encode($result, JSON_PRETTY_PRINT));
            return $app->response;
        }
        public static function routeDefault() {
            
            $app = \Slim\Slim::getInstance();
            $app->response->header('Content-Type', 'application/json');
            $app->response->write('Resource not found!');
            return $app->response;
        }
        public static function route($app) {
            
            $app = \Slim\Slim::getInstance();
            $app->get('/clawback', function() use($app) {
                if ($app->request->get('method') === 'getRefundableTransactions') return (new self())->getRefundableTransactions();
                if ($app->request->get('method') === 'getUsersDatasource') return (new self())->getUsersDatasource();
                return self::routeDefault();
            });
            $app->post('/clawback', function() use($app) {
                if ($app->request->post('method') === 'setTransactionAsRefunded') return (new self())->setTransactionAsRefunded();
                return self::routeDefault();
            });
        }
    }