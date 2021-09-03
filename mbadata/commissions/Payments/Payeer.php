<?php


namespace Commissions\Payments;


use App\Payment;
use Commissions\Clients\Payeer as PayeerClient;
use Commissions\Contracts\PaymentInterface;
use Commissions\Payments\Payment as BasePayment;

class Payeer extends BasePayment implements PaymentInterface
{
    const DEBUG = true;

    protected $table = "cm_payeer_users";
    protected $username = "account_number";
    protected $email = "email";
    protected $payeer;

    // field use for payout
    protected $fields = [
        "account_number",
        "email AS email",
    ];

    public function __construct(PayeerClient $payeer)
    {
        $this->payeer = $payeer;
    }

    public function sentPayment(Payment $payment, $payout)
    {
        try
        {
            if(static::DEBUG) {
                $payout->account_number = 20;
            }

            $response = $this->payeer->sendPayment($payment->id, $payout->account_number, $payout->amount);
        }
        catch(\Exception $ex)
        {
            $response = [
                'severity' => 'Critical',
                'system' => true,
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
                'payout' => $payout
            ];
        }

        if($response === null) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode([
                'severity' => 'Critical',
                'system' => true,
                'message' => 'No response from Payeer',
                'trace' => '',
                'payout' => $payout
            ]);
            $payment->message = 'No response from Payeer';
            $payment->save();
            return $payment;
        }

        if(array_key_exists('severity', $response)) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($response);
            $payment->message = "Unable to process: " . $response['message'];
            $payment->save();
            return $payment;
        }

        if($response['auth_error'] <> 0 || !empty($response['errors']) || $response['success'] <> 1) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($response);
            $payment->message = "Unable to process: " . $response['message'];
            $payment->save();
            return $payment;
        }

        // IF SUCCESS
        $payment->status = Payment::STATUS_SUCCESS;
        $payment->transaction_no = $response['historyId'];
        $payment->response = $response['errors'];
        $payment->message = "Sent to {$payout->account_number}. Transaction No. {$payment->transaction_no}";
        $payment->save();

        return $payment;

    }
}