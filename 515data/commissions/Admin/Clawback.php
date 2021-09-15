<?php

namespace Commissions\Admin;

use Illuminate\Support\Facades\DB as DB;
use Commissions\Validator;
use App\Clawback as ClawbackModel;
use App\ClawbackPayout;
use App\ClawbackProduct;
use App\CommissionPeriod as CommissionPeriod;
use App\TransactionProduct;
use App\CommissionProduct;
use App\OpenCartProducts as OpenCartProduct;
use Request;

class Clawback
{
    const ALLOW_ZERO_CV = false;

    public function datatables($request)
    {
        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $order = $request['order'];
        $columns = $request['columns'];

        // custom filters
        $startDate = $request['startDate'];
        $endDate = $request['endDate'];
        $memberId = $request['memberId'];

        // build the query
        $query = DB::table('transactions AS t')
            ->select(
                DB::raw("DISTINCT t.id AS order_id"),
                't.invoice',
                'u.id AS purchaser_id',
                'sponsor.id AS sponsor_id',
                DB::raw("CONCAT(u.fname, ' ', u.lname) AS purchaser"),
                DB::raw("CONCAT(sponsor.fname, ' ', sponsor.lname) AS sponsor"),
                DB::raw("DATE_FORMAT(t.transactiondate, '%Y-%m-%d') AS transaction_date"),
                'c.is_per_product',
                't.sub_total',
                DB::raw('t.sub_total - IFNULL(c.amount_to_deduct_price, 0) AS amount_paid'),
                't.amount AS total',
                't.description',
                't.tax',
                't.shipping_fee',
                'c.amount AS amount_off',
                'c.percent AS percentage_off',
                DB::raw('IFNULL(c.is_full_order, 0) AS is_full_order'),
                DB::raw("IFNULL(c.amount_to_deduct, 'N/A') AS amount_to_deduct"),
                DB::raw('getVolume(t.id) AS commission_value'),
                DB::raw("IF(c.set_user_id IS NULL, NULL, CONCAT(s.fname, ' ', s.lname)) set_by"),
                DB::raw('IF(c.id IS NOT NULL, 1 ,0) AS is_clawback'),
                DB::raw('null AS new_purchaser_id'),
                DB::raw('null AS action')
            )
            ->join('users AS u', 'u.id', '=', 't.userid')
            ->leftJoin('oc_product AS op', 'op.product_id', '=', 't.itemid')
            ->leftJoin('transaction_products AS tp', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('cm_clawbacks AS c', 'c.transaction_id', '=', 't.id')
            ->leftJoin('users AS s', 's.id', '=', 'c.set_user_id')
            ->leftJoin('users AS sponsor', 'sponsor.id', '=', 't.sponsorid')
            ->where('t.status', 'Approved')
            ->where('t.type', 'product')
            // ->where(function($query) {
            //     $query->whereNull('t.authcode')
            //         ->orWhere('t.authcode', '!=', 'No Charge');
            // })
            ->where(function($query) {
                $query->whereNull('t.credited')
                    ->orWhere('t.credited', '=', '');
            })
            ->whereBetween('t.commission_date', [$startDate, $endDate]);

        // custom filter
        if ($memberId) {
            $query = $query->where('u.id', $memberId);
        }

        // count total records
        $recordsTotal = $query->count(DB::raw("1"));

        // apply where
        if (isset($search) && $search['value'] != '') {
            $value = trim($search['value']);
            $query =
                $query->where(function ($query) use ($value) {
                    $query->where('t.id', 'LIKE', "%{$value}%")
                        ->orWhere('t.invoice', 'LIKE', "%{$value}%")
                        ->orWhereRaw("DATE_FORMAT(t.transactiondate, '%Y-%m-%d') LIKE ?", ["%{$value}%"])
                        ->orWhereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ["%{$value}%"]);
                });
        }

        // count total filtered records
        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("t.id", "desc");

        // apply limit
        $query = $query->take($take);

        // apply offset
        if ($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'sql');
    }

    public function orderProducts($orderID)
    {
        $orderProducts = DB::table('transactions AS t')
            ->selectRaw("
                t.id AS transaction_id,
                tp.id AS transaction_product_id,
                0 AS transaction_product_x_id,
                cp.product_id,
                cp.model AS name,
                tp.quantity - IFNULL(wp.quantity, 0) AS quantity,
                (tp.computed_cv / tp.quantity) AS price,
                (tp.quantity - IFNULL(wp.quantity, 0)) * (tp.computed_cv / tp.quantity)  AS total,
                1 AS is_oc_order_product,
                0 AS refund_quantity,
                IFNULL(wp.quantity, 0) AS refunded_quantity,
                IFNULL(wp.amount_to_deduct, 0) AS refunded_amount
            ")
            ->join('transaction_products AS tp', 'tp.transaction_id', '=', 't.id')
            ->join('oc_product AS cp', 'cp.product_id', '=', 'tp.shoppingcart_product_id')
            ->leftJoin('cm_clawbacks AS c', 'c.transaction_id', '=', 't.id')
            ->leftJoin('cm_clawback_products AS wp', function($join){
                $join->on('wp.clawback_id','=','c.id')
                    ->on('wp.transaction_product_id', '=','tp.id');
            })
            ->where('t.id', $orderID)
            ->orderBy('tp.id')
            ->get();

        return $orderProducts;
    }

    public function refundOrder($data)
    {
        // validation
		$data['is_full_order'] = 1; // full order only
		$data['percent'] = 0;

        $data = Validator::sanitizeThenValidate($data, [
            //'transaction_id' => 'required|integer|exists,transactions',
            'is_full_order' => 'required|integer|contains_list,0;1',
            'type' => 'required|contains_list,merchant;commission',
            'percent' => 'min_numeric,0',
            'amount' => 'min_numeric,0',
            'set_user_id' => 'required|integer|exists,users',
        ]);

        if (!$data['percent'] && !$data['is_full_order'] && !$data['amount']) {
            throw new \Exception("Input some data.");
		}
		
		//check in ClawbackPayout if the payout already exists
		$clawback = ClawbackPayout::where('commission_payout_id', '=', $data['payout_id'])->first();

		/*
        $clawback = ClawbackModel::where('transaction_id', '=', $data['transaction_id'])->first();

        if ($clawback != null && $clawback->is_per_product) {
            throw new \Exception("Cannot be processed. This order has been applied for per product refund.");
		}
		*/

        if ($clawback != null) {
            throw new \Exception("A refund has already been applied for this order.");
        }

		/*
        $transaction = $this->getTransaction($data['transaction_id']);

        $cv = +$transaction->commission_value;

        if (!static::ALLOW_ZERO_CV && $cv == 0) {
            throw new \Exception("The commission value is zero.");
        }

        if (!static::ALLOW_ZERO_CV && +$data['amount'] > $cv) {
            throw new \Exception("Amount should be equal or less than to the total commission value.");
		}

        $data['is_per_product'] = 0;
        $data['amount_to_deduct'] = 0;
        $data['amount_to_deduct_price'] = 0; // for retail and fast start

        if (+$data['is_full_order']) {
            $data['amount_to_deduct'] = $cv;
            $data['amount_to_deduct_price'] = +$transaction->sub_total;
        } else if (false && +$data['percent'] > 0) {
            $data['amount_to_deduct'] = $cv * ($data['percent'] / 100.0);
        } else if (false && +$data['amount'] > 0) {
            $data['amount_to_deduct'] = +$data['amount'];
        } else {
            throw new \Exception("Something went wrong!");
		}
		*/
		
		$data['amount_to_deduct'] = +$data['amount'];

		/*
        if (!static::ALLOW_ZERO_CV && +$data['amount_to_deduct'] > $cv) {
            throw new \Exception("Amount to be deducted is higher than the total commission value.");
		}
		*/

        //$payouts = $this->getPayoutsThatAreAlreadyBeenApplied(+$data['transaction_id']);

		$clawbackPayouts = [];
			
		$clawbackPayout = new ClawbackPayout();
		$clawbackPayout->commission_payout_id = $data['payout_id'];
		$clawbackPayout->amount_to_deduct = $data['amount'];

		$clawbackPayouts[] = $clawbackPayout;

		/*
        foreach ($payouts as $payout) {
            if (+$data['is_full_order']) {
                $payout->amount_to_deduct = $payout->amount;
            } else if (false && +$data['percent'] > 0) {
                $payout->amount_to_deduct = $payout->amount * ($data['percent'] / 100.0);
            } else if (false && +$data['amount'] > 0) {
                $payout->amount_to_deduct = +$data['amount'] * ($payout->percent / 100.0);
            } else {
                throw new \Exception("Something went wrong!");
            }

            $payout->amount_deducted = 0;
            $payout = (array)$payout;
            $clawbackPayouts[] = new ClawbackPayout($payout);
		}
		*/

        $clawback = DB::transaction(function () use ($data, $clawbackPayouts) {

            $clawback = ClawbackModel::create($data);

            if (count($clawbackPayouts)) {
                $clawback->payouts()->saveMany($clawbackPayouts);
			}

            $clawback->load('payouts', 'products');

            // throw new \Exception("Success: " . json_encode($clawback));

            return $clawback;
        });

        return $clawback;
    }

    public function refundOrderProducts($data)
    {
        $data = Validator::sanitizeThenValidate($data, [
            'transaction_id' => 'required|integer|exists,transactions',
            'set_user_id' => 'required|integer|exists,users',
            'products' => 'required|array',
        ]);

        $clawback = ClawbackModel::where('transaction_id', '=', $data['transaction_id'])->first();

        if ($clawback != null && !+$clawback->is_per_product) {
            throw new \Exception("A refund has already been applied for this order.");
        }

        $transaction = $this->getTransaction($data['transaction_id']);

        if (!static::ALLOW_ZERO_CV && +$transaction->commission_value == 0)
        {
            throw new \Exception("The commission value is zero.");
        }

        // starts here

        $clawback = DB::transaction(function () use ($clawback, $data, $transaction) {

            if($clawback == null)
            {
                $data['is_per_product'] = 1;
                $data['percent'] = 0;
                $data['amount'] = 0;
                $data['is_full_order'] = 0;
                $data['amount_to_deduct'] = 0;
                $clawback = ClawbackModel::create($data);
            }

            $total_refund_quantity = 0;

            $percentage = 1;

            $refund = 0;

            foreach ($data['products'] as $product)
            {
                $product = Validator::sanitizeThenValidate($product, [
                    'transaction_product_id' => 'required|integer',
                    'refund_quantity' => 'min_numeric,0',
                    'is_oc_order_product' => 'required|integer|contains_list,0;1',
                ]);

                if (+$product['refund_quantity'] <= 0) continue;

                $total_refund_quantity += $product['refund_quantity'];

                $transaction_product = TransactionProduct::where(['transaction_id' => $data['transaction_id'], 'id' => $product['transaction_product_id']])->first();
                $oc_product = OpenCartProduct::find($transaction_product->shoppingcart_product_id);

                if ($transaction_product == null || $oc_product == null)
                {
                    throw new \Exception("Product not found");
                }

                $clawbackProduct = ClawbackProduct::where(['clawback_id' => $clawback->id, 'transaction_product_id' => $product['transaction_product_id']])->first();

                $cv = $transaction_product->computed_cv / $transaction_product->quantity;
                $price = $transaction_product->price;

                if($clawbackProduct == null)
                {
                    $clawbackProduct = new ClawbackProduct;
                    $clawbackProduct->clawback_id = $clawback->id;
                    $clawbackProduct->quantity = +$product['refund_quantity'];
                    $clawbackProduct->amount_to_deduct = ($clawbackProduct->quantity * $cv) * $percentage;
                    $clawbackProduct->amount_to_deduct_price = ($clawbackProduct->quantity * $price) * $percentage;
                    $clawbackProduct->transaction_product_id =  +$product['transaction_product_id'];
                    $clawbackProduct->transaction_product_x_id = 0;
                }
                else
                {
                    $clawbackProduct->quantity = $clawbackProduct->quantity + $product['refund_quantity'];
                    $clawbackProduct->amount_to_deduct = ($clawbackProduct->quantity * $cv) * $percentage;
                    $clawbackProduct->amount_to_deduct_price = ($clawbackProduct->quantity * $price) * $percentage;
                }

                $refund += ($product['refund_quantity'] * $cv) * $percentage;


                if (+$clawbackProduct->quantity > $transaction_product->quantity) {
                    throw new \Exception('Order Product ID ' . $product['transaction_product_id'] . ' refund qty should be equal or less than ' . $product['quantity']);
                }

                $clawbackProduct->save();

            }

            if($total_refund_quantity == 0)
            {
                throw new \Exception("At least 1 product to refund is required");
            }

            $total_amount_to_deduct = +$clawback->products()->sum('amount_to_deduct');
            $total_amount_to_deduct_price = +$clawback->products()->sum('amount_to_deduct_price');

            if ($total_amount_to_deduct <= 0)
            {
                throw new \Exception("At least 1 product to refund is required");
            }

            $clawback->is_per_product = 1;
            $clawback->amount_to_deduct = $total_amount_to_deduct;
            $clawback->amount_to_deduct_price = $total_amount_to_deduct_price;
            $clawback->save();

            $payouts = $this->getPayoutsThatAreAlreadyBeenApplied(+$data['transaction_id']);

            foreach ($payouts as $payout) {

                $clawbackPayout = ClawbackPayout::where(['clawback_id' => $clawback->id, 'commission_payout_id' => $payout->commission_payout_id])->first();

                $a = $total_amount_to_deduct > $payout->commission_value ? $payout->commission_value : $total_amount_to_deduct;

                if($clawbackPayout == null)
                {
                    $clawbackPayout = new ClawbackPayout;
                    $clawbackPayout->clawback_id = $clawback->id;
                    $clawbackPayout->amount_deducted = 0;
                    $clawbackPayout->amount_to_deduct =  $a * ($payout->percent / 100.0);
                    $clawbackPayout->commission_payout_id = $payout->commission_payout_id;
                }
                else
                {
                    $clawbackPayout->amount_to_deduct =  $a * ($payout->percent / 100.0);
                }


                $clawbackPayout->save();
            }
            $clawback->load('payouts', 'products');
            // throw new \Exception("Success: " . json_encode($clawback));
            return $clawback;
        });

        // $clawback->load('payouts', 'products');

        return $clawback;
    }

    public function getPayoutsThatAreAlreadyBeenApplied($transactionID)
    {
        $payouts = DB::table('cm_commission_payouts AS p')
            ->select(
                'p.id AS commission_payout_id',
                'pr.commission_type_id',
                'p.commission_period_id',
                'p.user_id',
                'p.commission_value',
                'p.percent',
                'p.amount'
            )
            ->join('cm_commission_periods AS pr', 'pr.id', '=', 'p.commission_period_id')
            ->where('p.transaction_id', $transactionID)
            ->where('pr.is_locked', 1)
            ->get();

        return $payouts;

    }

    private function getTransaction($transaction_id)
    {
        return DB::table('transactions AS t')
            ->selectRaw("
                t.id,
                getVolume(t.id) AS commission_value,
                -- IFNULL(p.is_special, 0) AS is_special,
                t.sub_total
            ")
            ->leftJoin('oc_product AS p', 'p.product_id', '=', 't.itemid')
            ->where('t.id', '=', $transaction_id)
            ->first();
    }

    public function getClawbackPayouts($commission_period_id)
    {
        $payouts = DB::table("cm_commission_payouts AS p")
            ->selectRaw("
                pr.commission_type_id AS payout_type,
                p.payee_id AS sponsor_id,
                p.amount AS commission
            ")
            ->join('cm_commission_periods AS pr', 'pr.id', '=', 'p.commission_period_id')
            ->where("p.commission_period_id", "=", $commission_period_id)
            ->get();

        $commissionPeriod = CommissionPeriod::findOrFail($commission_period_id);
        $commissionTypeID = $commissionPeriod->commission_type_id;

        if (count($payouts)) {

            $totalCommPerUsers = $this->getTotalCommissionPerUser($payouts);

            $pendingClawbacks = $this->getPendingClawbacks($commissionTypeID, implode(',', array_keys($totalCommPerUsers)));
            $payoutsForClawback = [];

            foreach ($pendingClawbacks as $clawback) {
                $clawback = (array) $clawback;
                $total = $totalCommPerUsers[$clawback['payee_id']];

                if ($total < 1) continue;

                $remaining = $clawback['remaining_amount'];

                if ($total > $remaining) {
                    $clawback['amount_to_refund'] = $remaining;
                } else {
                    $clawback['amount_to_refund'] = $total;
                }

                $payoutsForClawback[] = [
                    'payee_id' => $clawback['payee_id'],
                    'sponsor_id' => $clawback['sponsor_id'],
                    'user_id' => $clawback['user_id'],
                    'transaction_id' => $clawback['transaction_id'],
                    'level' => $clawback['level'],

                    'payout_type' => $commissionTypeID,
                    'value' => 0, //commission value
                    'commission' => ($clawback['amount_to_refund'] * -1),
                    'percentage' => 1,
                    'remarks' => 'Refund from period: ' . $clawback['start_date'] . ' - ' . $clawback['end_date'],

                    'clawback_payout_id' => $clawback['clawback_payout_id'],
                    'amount_to_refund' => $clawback['amount_to_refund'],
                ];

                $totalCommPerUsers[$clawback['payee_id']] -= $clawback['amount_to_refund'];
            }

            return $payoutsForClawback;
        }

        return [];
    }

    public function processClawbacks($commission_period_id)
    {
        $payouts = $this->getClawbackPayouts($commission_period_id);

        $db = DB::connection()->getPdo();

        for ($i = 0; $i < count($payouts); $i++) {

            $detail = $payouts[$i];

            $stmt = $db->prepare("
                INSERT INTO cm_commission_payouts(payee_id, user_id, sponsor_id, commission_period_id, level, amount, transaction_id, commission_value, percent, remarks)
				VALUES(:payee_id, :user_id, :sponsor_id," . $commission_period_id . ",:level,:amount,:order_id,:value,:percent,:remarks)
			");


            $remarks = !empty($detail['remarks']) ? $detail['remarks'] : "";
            $percent = $detail ['percentage'] * 100;

            $stmt->bindParam('payee_id', $detail ['payee_id']);
            $stmt->bindParam('user_id', $detail ['user_id']);
            $stmt->bindParam('sponsor_id', $detail ['sponsor_id']);
            $stmt->bindParam('level', $detail ['level']);
            $stmt->bindParam('amount', $detail ['commission']);
            $stmt->bindParam('order_id', $detail ['transaction_id']);

            $stmt->bindParam('level', $detail ['level']);
            $stmt->bindParam('value', $detail ['value']);
            $stmt->bindParam('percent', $percent);
            $stmt->bindParam('amount', $detail ['commission']);
            $stmt->bindParam('remarks', $remarks);
            $stmt->execute();

            $stmt = $db->prepare("
                INSERT INTO cm_clawback_pending_payouts (commission_payout_id, clawback_payout_id, amount_to_refund)
                VALUES (:commission_payout_id, :clawback_payout_id, :amount_to_refund)
            ");

            $payout_id = $db->lastInsertId();

            $stmt->bindParam('commission_payout_id', $payout_id);
            $stmt->bindParam('clawback_payout_id', $detail['clawback_payout_id']);
            $stmt->bindParam('amount_to_refund', $detail['amount_to_refund']);

            $stmt->execute();
        }

    }

    public function getTotalCommissionPerUser($payouts)
    {
        $sum = [];

        foreach ($payouts as $payout) {

            if (!array_key_exists($payout->sponsor_id, $sum)) {
                $sum[$payout->sponsor_id] = 0;
            }

            $sum[$payout->sponsor_id] += +$payout->commission;
        }

        return $sum;
    }

    public function getPendingClawbacks($commissionTypeID, $commaDelimitedUserIDs)
    {

        if (!$commaDelimitedUserIDs) return [];

        return DB::select("
            SELECT 
                cp.id AS clawback_payout_id,
                p.payee_id,
                p.user_id,
                p.sponsor_id,
                p.transaction_id,
                p.level,
                (cp.amount_to_deduct - cp.amount_deducted) remaining_amount,
                pr.start_date,
                pr.end_date
            FROM cm_clawback_payouts cp
            JOIN cm_commission_payouts p ON p.id = cp.commission_payout_id
            JOIN cm_commission_periods pr ON pr.id = p.commission_period_id
            WHERE (cp.amount_to_deduct - cp.amount_deducted) > 0
                AND FIND_IN_SET(p.payee_id, ?)
                AND pr.commission_type_id = ?;
        ", [$commaDelimitedUserIDs, $commissionTypeID]);
    }

    public function getPEA($request)
    {
		$sql = "
			SELECT
				cea.reference_id,
				CONCAT(REPEAT('*', CHAR_LENGTH(cea.reference_id) - 4), SUBSTR(cea.reference_id, CHAR_LENGTH(cea.reference_id) - 4)) AS por,
				cea.sponsor_id AS associate_id,
				CONCAT(u.fname, ' ', LEFT(u.lname, 1), '.') AS associate_name,
				cea.customer_id AS customer_id,
				CONCAT(u2.fname, ' ', LEFT(u2.lname, 1), '.') AS customer_name,
				ceat.display_text AS account_type,
				COALESCE((SELECT 
					DATE_FORMAT(created_at, '%d/%m/%Y') 
				FROM cm_energy_account_logs 
				WHERE customer_id = ca.user_id
					AND current_status = 4
				ORDER BY created_at ASC LIMIT 1),'N/A') AS date_accepted,
				COALESCE((SELECT 
					DATE_FORMAT(created_at, '%d/%m/%Y') 
				FROM cm_energy_account_logs 
				WHERE customer_id = ca.user_id
					AND current_status IN (5,6) 
				ORDER BY created_at ASC LIMIT 1),'N/A') AS date_started_flowing,
				ceatt.display_text AS `status`
			FROM cm_affiliates ca
			LEFT JOIN cm_energy_accounts cea ON ca.user_id = cea.sponsor_id
			LEFT JOIN users u ON u.id = cea.sponsor_id	#associate related
			LEFT JOIN customers u2 ON u2.id = cea.customer_id 	#customer related
			LEFT JOIN cm_energy_account_types ceat ON cea.account_type = ceat.id
			LEFT JOIN cm_energy_account_status_types ceatt ON cea.`status` = ceatt.id
		";

        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $order = $request['order'];
        $columns = $request['columns'];

        // custom filters
        $startDate = $request['startDate'];
        $endDate = $request['endDate'];
		$memberId = $request['memberId'];
		$pod = $request['pod'];

		$query = DB::table('cm_affiliates AS ca')
						->select(
							'cea.id AS transaction_id',
							'reference_id AS some_id',
							DB::raw("CONCAT(REPEAT('*', CHAR_LENGTH(cea.reference_id) - 4), SUBSTR(cea.reference_id, CHAR_LENGTH(cea.reference_id) - 4)) AS reference_id"),
							'cea.sponsor_id AS associate_id',
							DB::raw("CONCAT(u.fname, ' ',u.lname) AS associate_name"),
							'cea.customer_id AS customer_id',
							DB::raw("IF(u2.fname IS NULL OR u2.fname = '', CONCAT(LEFT(u2.business, 5),'***'), CONCAT(u2.fname, ' ', LEFT(u2.lname, 1), '.')) AS customer_name"),
							'ceat.display_text AS account_type',
							DB::raw("COALESCE((SELECT 
										DATE_FORMAT(created_at, '%d/%m/%Y') 
									FROM cm_energy_account_logs 
									WHERE energy_account_id = cea.id
										AND current_status = 4
									ORDER BY created_at ASC LIMIT 1),'N/A') AS date_accepted"),
							DB::raw("COALESCE((SELECT 
										DATE_FORMAT(created_at, '%d/%m/%Y') 
									FROM cm_energy_account_logs 
									WHERE energy_account_id = cea.id
										AND current_status IN (5,6) 
									ORDER BY created_at ASC LIMIT 1),'N/A') AS date_started_flowing"),
							'ceatt.display_text AS status')

							->leftJoin('cm_energy_accounts AS cea', 'ca.user_id', '=', 'cea.sponsor_id')
							->leftJoin('users AS u', 'u.id', '=', 'cea.sponsor_id')
							->leftJoin('customers AS u2', 'u2.id', '=', 'cea.customer_id')
							->leftJoin('cm_energy_types AS cet', 'cet.id', '=', 'cea.energy_type')
							->leftJoin('cm_energy_account_types AS ceat', 'ceat.id', '=', 'cea.account_type')
							->leftJoin('cm_energy_account_status_types AS ceatt', 'ceatt.id', '=', 'cea.status')
						//	->join('cm_commission_payouts AS ccp', 'cea.id', '=', 'ccp.transaction_id')

							->whereRaw("EXISTS (SELECT 1 FROM cm_commission_payouts pay JOIN cm_commission_periods per ON per.id = pay.commission_period_id 
                                        WHERE pay.transaction_id = cea.id AND per.end_date BETWEEN '$startDate' AND '$endDate' AND per.is_locked = 1)");

		// custom filter
		if ($memberId) {
			$query = $query->where('cea.sponsor_id', $memberId);
		}
		
		// custom pod filter
		if ($pod) {
			$query = $query->where('cea.reference_id', $pod);
		}

		// count total records
		$recordsTotal = $query->count(DB::raw("1"));

		// apply where
        if (isset($search) && $search['value'] != '') {
            $value = trim($search['value']);
            $query =
                $query->where(function ($query) use ($value) {
                    $query->where('cea.sponsor_id', 'LIKE', "%{$value}%")
                        ->orWhere('cea.customer_id', 'LIKE', "%{$value}%")
                        ->orWhereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ["%{$value}%"])
                        ->orWhereRaw("CONCAT(u2.fname, ' ', u2.lname) LIKE ?", ["%{$value}%"]);
                });
		}

        // count total filtered records
        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        $query->orderBy("cea.sponsor_id", "desc");

        // apply limit
        $query = $query->take($take);

        // apply offset
        if ($skip) $query = $query->skip($skip);

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'sql');
    }

    public function getPayouts()
    {
        $startDate = $_GET['startDate'];
		$endDate = $_GET['endDate'];
		
		$user_id = $_GET['user_id'];
		if (empty($user_id) || empty($startDate) || empty($endDate)) {
			return ['data' => []];
		}

		$sql = "
			SELECT
				ccp.transaction_id,
				ccp.id AS commission_payout_id,
				cclp.clawback_id,
				ccp.payee_id AS associate,
				CONCAT(u.fname, ' ', LEFT(u.lname, 1), '.') AS associate_name,
                cct.name AS commission_type,
				CONCAT(DATE_FORMAT(ccpe.start_date, '%d/%m/%Y'), ' - ' ,DATE_FORMAT(ccpe.end_date, '%d/%m/%Y')) AS commission_period,
                ccp.amount as commission_value,
                IFNULL(cc.amount, 0) AS amount,
                IFNULL(DATE_FORMAT(cc.updated_at, '%d/%m/%Y'), 'N/A') AS date_clawed
			FROM cm_commission_payouts ccp
			INNER JOIN users u ON ccp.payee_id = u.id
            INNER JOIN cm_commission_periods ccpe ON ccp.commission_period_id = ccpe.id
            INNER JOIN cm_commission_types cct ON ccpe.commission_type_id = cct.id
			LEFT JOIN cm_clawback_payouts cclp ON cclp.commission_payout_id = ccp.id
			LEFT JOIN cm_clawbacks cc ON cc.id = cclp.clawback_id
			WHERE EXISTS (SELECT 1 FROM cm_commission_periods WHERE end_date BETWEEN '$startDate' AND '$endDate' AND id = ccp.commission_period_id AND is_locked = 1)
			AND ccp.transaction_id = :user_id
		";

		/*
        $sql = "
			SELECT 
				ccp.id AS commission_payout_id,
                ccp.payee_id AS associate,
				CONCAT(u.fname, ' ', LEFT(u.lname, 1), '.') AS associate_name,
                cct.description AS commission_type,
				CONCAT(DATE_FORMAT(ccpe.start_date, '%d/%m/%Y'), ' - ' ,DATE_FORMAT(ccpe.end_date, '%d/%m/%Y')) AS commission_period,
                ccp.commission_value,
                IFNULL(cc.amount, 0) AS amount,
                IFNULL(DATE_FORMAT(cc.updated_at, '%d/%m/%Y'), 'N/A') AS date_clawed
			FROM cm_commission_payouts ccp
			INNER JOIN users u ON ccp.payee_id = u.id
            INNER JOIN cm_commission_periods ccpe ON ccp.commission_period_id = ccpe.id
            INNER JOIN cm_commission_types cct ON ccpe.commission_type_id = cct.id
            LEFT JOIN cm_clawbacks cc ON ccp.transaction_id = cc.transaction_id
            WHERE ccp.transaction_id = :user_id
		";
		*/

        $db = DB::connection()->getPdo();

        $stmt = $db->prepare($sql);
        $stmt->bindParam('user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return ['data' => $result];
    }

}