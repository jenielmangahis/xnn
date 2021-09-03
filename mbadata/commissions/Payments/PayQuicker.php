<?php


namespace Commissions\Payments;


use App\Payment;
use Commissions\Clients\PayQuicker as PayQuickerClient;
use Commissions\Contracts\PaymentInterface;
use Commissions\Payments\Payment as BasePayment;

class PayQuicker extends BasePayment implements PaymentInterface
{
    const DEBUG = false;

    protected $table = "cm_payquicker_users";
    protected $username = "company_assigned_key";
    protected $email = "email";
    protected $pay_quicker;

    // field use for payout
    protected $fields = [
        "company_assigned_key",
        "email AS email",
    ];

    public function __construct(PayQuickerClient $pay_quicker)
    {
        $this->pay_quicker = $pay_quicker;
    }

    public function sentPayment(Payment $payment, $payout)
    {
        try
        {
            if(static::DEBUG) {
                $payout->company_assigned_key = 20;
                $payout->email = "comm@mymbatrading.com";
            }

            // $this->logger($history->id, "Sending \${$payout->amount} payment to {$payout->company_assigned_key}|{$payout->email}");
            $response = $this->pay_quicker->sendPayments([
                [
                    'monetary' => [
                        'amount' => $payout->amount
                    ],
                    'accountingId' => $payment->id,
                    'userCompanyAssignedUniqueKey' => $payout->company_assigned_key,
                    'userNotificationEmailAddress' => $payout->email
                ]
            ]);
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
                'message' => 'No response from PayQuicker',
                'trace' => '',
                'payout' => $payout
            ]);
            $payment->message = 'No response from PayQuicker';
            $payment->save();
            return $payment;
        }

        if(array_key_exists('severity', $response))
        {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($response);
            $payment->message = "Unable to process: " . $response['message'];
            $payment->save();
            return $payment;
        }

        if(!isset($response[0])
            || !isset($response[0]['payments'])
            || !isset($response[0]['payments'][0])
            || !isset($response[0]['payments'][0]['transactionStatusType'])
            || !isset($response[0]['payments'][0]['transactionPublicId'])
        )
        {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode([
                'message' => 'Invalid Response',
                'response' => $response
            ]);
            $payment->message = "Invalid Response";
            $payment->save();
            return $payment;
        }

        if($response[0]['payments'][0]['transactionStatusType'] === 'TransactionStatusType_Failed')
        {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($response[0]['payments'][0]);
            $payment->message = "Transaction failed";
            $payment->save();
            return $payment;
        }

        // IF SUCCESS
        $payment->status = Payment::STATUS_SUCCESS;
        $payment->transaction_no = $response[0]['payments'][0]['transactionPublicId'];
        $payment->response = json_encode($response[0]['payments'][0]);
        $payment->message = "Sent to {$payout->company_assigned_key}|{$payout->email}. Transaction No. {$payment->transaction_no}";
        $payment->save();

        return $payment;
    }

}