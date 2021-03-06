<?php


namespace Commissions\Payments;


use App\Billing;
use App\CommissionPayout;
use App\Ledger;
use App\Payment;
use App\PaymentHistory;
use Carbon\Carbon;
use Commissions\Contracts\PaymentInterface;
use Commissions\CsvReport;
use Commissions\Payments\Payment as BasePayment;
use Illuminate\Support\Facades\DB;


class Csv515 extends BasePayment implements PaymentInterface
{
    const CSV_PATH = "csv/admin/pay_commission";
    const TECH_FEE = 12.20;

    protected $table = "users";
    protected $username = "site";
    protected $user_id = "id";

    protected $db;

    // field use for payout
    protected $fields = [
        "site",
        "cf",
        "piva",
        "codice_sdi",
        "or_pec",
        "iban",
    ];

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function sentPayment(Payment $payment, $payout)
    {
        $payout->other_info = [];

        $is_already_billed_this_month = +$payout->is_already_billed_this_month;
        $no_charge_until = $payout->nochargeuntil;
        $affiliated_date = Carbon::createFromFormat("Y-m-d", $payout->affiliated_date)->endOfDay();

        if ($no_charge_until === null) {
            $no_charge_until = Carbon::today()->endOfDay();
        } else {
            $no_charge_until = Carbon::createFromFormat("Y-m-d", $no_charge_until)->endOfDay();
        }



        $is_2_months_after_enrollment = $affiliated_date->copy()->addMonths(2)->endOfDay()->lessThanOrEqualTo(Carbon::today()->endOfDay());

        $launch_date = Carbon::createFromFormat("Y-m-d", "2021-08-01")->endOfDay(); // To ensure that in the first 2 months, no associates will have tech fee deductions. Tentative Launch Date: 8/1/21
        if($is_2_months_after_enrollment) $is_2_months_after_enrollment = $launch_date->copy()->addMonths(2)->endOfDay()->lessThanOrEqualTo(Carbon::today()->endOfDay());


        // 1 month before sa no charge until kay pwede na sya ibill
        $can_be_bill = $no_charge_until->copy()->subMonth()->endOfDay()->lessThanOrEqualTo(Carbon::today()->endOfDay());

        $payout->other_info += compact('is_2_months_after_enrollment', 'can_be_bill');

        if ($is_2_months_after_enrollment && $can_be_bill && $payment->amount < static::TECH_FEE) {
            $payment->status = Payment::STATUS_FREEZE;
            $payment->response = json_encode($payout);
            $payment->message = "Freeze Payment for Tech Fee.";
            $payment->save();
            return $payment;
        }

        if ($is_2_months_after_enrollment && $can_be_bill && $payment->amount >= static::TECH_FEE) {
            $payment->amount = $payment->amount - static::TECH_FEE;
            $payment->techonology_fee_to_subtract = static::TECH_FEE;

            $billing = Billing::find($payment->user_id);

            if ($billing === null) { // imposible mahitabo pero cge lng
                return $this->failed($payment, $payout, "User's billing not found.");
            }

            $payout->old_billing = $billing->toArray();

            $bill_date = Carbon::createFromFormat("Y-m-d", $billing->billdate);
            $bill_day = $bill_date->day;

            $new_no_charge_until = null;
            $logic_applied = 0;

            if (false && $is_already_billed_this_month) { // not possible ni na scenario na nakabayad sya daan
                // jan 5 to feb 5 = no charge until march 5

                $date = Carbon::now()->addMonthsNoOverflow(2);

                $new_no_charge_until = Carbon::create(
                    $date->year,
                    $date->month,
                    min($bill_day, $date->daysInMonth)
                );

                $logic_applied = 1;

            } elseif ($no_charge_until->greaterThan(Carbon::now()->endOfDay())) {
                // old no charge until + 1 month

                $new_no_charge_until = $no_charge_until->copy()->addMonthNoOverflow();

                $logic_applied = 2;

            } else {

                $date = Carbon::now()->addMonthsNoOverflow(1);

                $new_no_charge_until = Carbon::create(
                    $date->year,
                    $date->month,
                    min($bill_day, $date->daysInMonth)
                );

                $logic_applied = 3;

            }

            $billing->nochargeuntil = $new_no_charge_until->format("Y-m-d");
            $billing->save();

            $tech_fee_description = $new_no_charge_until->copy();

            $payment->technology_fee_description = $tech_fee_description->subMonth(1);
            $payment->save();

            $payout->other_info += compact('logic_applied');
            $payout->new_billing = $billing->toArray();
        }

        $ledger_ids = explode(",", $payout->ledger_ids);

        // logger()->debug($payout->ledger_ids);
        // logger()->debug(print_r($ledger_ids, true));

        $ledgers = Ledger::whereIn("id", $ledger_ids)->get();

        foreach($ledgers as $ledger) {
            $l = $ledger->replicate();
            $l->amount = $l->amount * -1;
            $l->notes = $l->notes . " (Pay Commission)";
            $l->save();
        }

        $payment->cf = $payout->cf;
        $payment->piva = $payout->piva;
        $payment->codice_sdi = $payout->codice_sdi;
        $payment->or_pec = $payout->or_pec;
        $payment->iban = $payout->iban;

        $payment->paid_as_rank = $payout->paid_as_rank;
        $payment->gross_weekly_immediate_earnings = $payout->gross_weekly_immediate_earnings;
        $payment->gross_monthly_earnings_true_up = $payout->gross_monthly_earnings_true_up;
        $payment->gross_monthly_residual_personal_energy_account = $payout->gross_monthly_residual_personal_energy_account;
        $payment->gross_unilevel_residual = $payout->gross_unilevel_residual;
        $payment->gross_generation_residual = $payout->gross_generation_residual;
        $payment->other_income = $payout->other_income;
        $payment->total_gross = $payout->total_gross;
        $payment->year_to_date_gross = $payout->year_to_date_gross;
        $payment->has_reached_6200_and_has_no_piva = $payout->has_reached_6200_and_has_no_piva;
        $payment->period_of_reference = $payout->period_of_reference;



        $payment->status = Payment::STATUS_SUCCESS;
        $payment->transaction_no = "N/A";
        $payment->response = json_encode($payout);
        $payment->message = "The payment for {$payout->name} is successfully added to the CSV file.";
        $payment->save();

        return $payment;
    }

    public function onBeforePayment(PaymentHistory $history)
    {

    }

    public function onAfterPayment(PaymentHistory $history)
    {
        $csv_report = new CsvReport(static::CSV_PATH);

        $filename = "payout-" . $history->id . "-" . time();

        $data = $this->getPayouts($history);

        $csv_report->generateLink(
            $filename,
            $data,
            [
                "Payment ID",
                "User ID",
                "Name",
                "C.F.",
                "P.IVA",
                "CODICE SDI",
                "OR PEC",
                "IBAN",
                "Paid as Title",
                "Gross amount for commission Weekly Immediate earnings",
                "Gross amount for commission Monthly earnings True-up",
                "Gross amount for commission Monthly Residual on Personal Energy Account",
                "Gross amount for commission Unilevel Residual",
                "Gross amount for commission Generational Residual",
                "Other Income",
                "Total gross commission amount to be paid",
                "Year To Date Gross",
                "Has reached ???6200 and has no P.IVA",
                "Period of Reference",
                "Charged By Card",
                "Technology Fee to Subtract",
                "Taxes Ritenuta IRPEF (Income Tax)",
                "Taxes VAT (P.IVA)",
                "Taxes Trattenuta Previd. INPS (social security tax)",
                "Total net amount",
                "Actual Date when the bank sent the money to the Associate",
                "Batch Number from Plank to SDI",
                "Bank Transaction Code",
            ]
        );

        $history->csv_file = $filename;
        $history->save();
    }

    public function isSetPaidPerPayment()
    {
        return false;
    }

    protected function getPayouts(PaymentHistory $history)
    {
        $sql = "
			SELECT
                py.id AS payment_id,
                py.user_id,
                CONCAT(u.fname, ' ', u.lname) payee,
                py.cf,
                py.piva,
                py.codice_sdi,
                py.or_pec,
                py.iban,
                /*(SELECT r.name FROM cm_ranks r WHERE r.id = IFNULL(MAX(dr.paid_as_rank_id), 1)) AS paid_as_rank,
                SUM(IF(pr.commission_type_id = 1, p.amount, 0)) gross_weekly_immediate_earnings,
                SUM(IF(pr.commission_type_id = 2, p.amount, 0)) gross_monthly_earnings_true_up,
                SUM(IF(pr.commission_type_id = 4, p.amount, 0)) gross_monthly_residual_personal_energy_account,
                SUM(IF(pr.commission_type_id = 3, p.amount, 0)) gross_unilevel_residual,
                SUM(IF(pr.commission_type_id = 5, p.amount, 0)) gross_generation_residual,
                SUM(IF(pr.commission_type_id = 7, p.amount, 0)) other_income,
                py.amount total_gross,
                IFNULL((
                    SELECT SUM(_p.amount)
                    FROM cm_commission_payouts _p
                    JOIN cm_commission_periods _pr ON _pr.id = _p.commission_period_id AND _pr.is_locked = 1
                    WHERE _p.payee_id = py.user_id AND _pr.end_date BETWEEN DATE(CONCAT(YEAR(MAX(pr.end_date)),'-01-01')) AND DATE(CONCAT(YEAR(MAX(pr.end_date)),'-12-31'))
                ), 0) year_to_date_gross,
                IF(
                    u.piva IS NULL 
                    AND IFNULL((
                        SELECT SUM(_p.amount)
                        FROM cm_commission_payouts _p
                        JOIN cm_commission_periods _pr ON _pr.id = _p.commission_period_id AND _pr.is_locked = 1
                        WHERE _p.payee_id = py.user_id), 0)
                    >= 6200
                ,'Yes','No') AS has_reached_6200_and_has_no_piva,
                    
                GROUP_CONCAT(DISTINCT CONCAT(DATE_FORMAT(pr.start_date,'%d/%m/%Y'), '-', DATE_FORMAT(pr.end_date,'%d/%m/%Y')) ORDER BY pr.end_date) period_of_reference,*/
                py.paid_as_rank,
                py.gross_weekly_immediate_earnings,
                py.gross_monthly_earnings_true_up,
                py.gross_monthly_residual_personal_energy_account,
                py.gross_unilevel_residual,
                py.gross_generation_residual,
                py.other_income,
                py.amount total_gross,
                py.year_to_date_gross,
                py.has_reached_6200_and_has_no_piva,
                py.period_of_reference,
                
                NULL charged_by_card,
                py.techonology_fee_to_subtract,
                NULL taxes_ritenuta_irpef,
                NULL taxes_vat,
                NULL taxes_trattenuta_previd,
                NULL total_net_amount,
                NULL actual_date,
                NULL batch_number,
                NULL bank_transaction_code
            FROM cm_payment_history h
            JOIN cm_payments py ON py.history_id = h.id
            JOIN cm_payment_details d ON d.payment_id = py.id
            JOIN cm_commission_payouts p ON p.id = d.payout_id
            JOIN users u ON u.id = py.user_id
            JOIN cm_commission_periods pr ON pr.id = p.commission_period_id
            LEFT JOIN cm_daily_ranks dr ON dr.user_id = py.user_id AND dr.rank_date = pr.end_date
            WHERE h.id = {$history->id} AND py.status = 'SUCCESS'
            GROUP BY py.id
		";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function failed($payment, $payout, $message)
    {
        $payment->status = Payment::STATUS_FAILED;
        $payment->response = json_encode($payout);
        $payment->message = $message;
        $payment->save();
        return $payment;
    }
}