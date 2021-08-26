<?php


namespace Commissions\Payments;

use App\Payment;
use Commissions\Contracts\PaymentInterface;
use Commissions\Payments\Payment as BasePayment;

class Hyperwallet extends BasePayment implements PaymentInterface
{
    protected $table = "cm_hyperwallet_users";
    protected $username = "client_user_id";
    protected $email = "email";
    protected $hyperwallet;

    public function __construct(\Hyperwallet\Hyperwallet $hyperwallet)
    {
        $this->hyperwallet = $hyperwallet;
    }

    // field use for payout
    protected $fields = [
        "token AS user_token",
        "email AS email",
    ];

    public function sentPayment(Payment $payment, $payout)
    {
        $hyperwallet_payment = null;

        try {

            $hyperwallet_payment = $this->hyperwallet->createPayment((new \Hyperwallet\Model\Payment())
                ->setAmount(+$payout->amount)
                ->setClientPaymentId($payment->id)
                ->setCurrency("USD")
                ->setDestinationToken($payout->user_token)
                ->setPurpose("GP0002")
            );

            $response = $hyperwallet_payment->getProperties();

        } catch (\Hyperwallet\Exception\HyperwalletException $ex) {
            $response = [
                'error' => [
                    'message' => 'Hyperwallet: ' . $ex->getMessage(),
                    'trace' => $ex->getTraceAsString()
                ]
            ];
        } catch (\Exception $ex) {
            $response = [
                'error' => [
                    'message' => 'System: ' .$ex->getMessage(),
                    'trace' => $ex->getTraceAsString()
                ]
            ];
        }

        if (array_key_exists('error', $response)) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($response);
            $payment->message = $response['error']['message'];
            $payment->save();
            return $payment;
        }

        // IF SUCCESS
        $payment->status = Payment::STATUS_SUCCESS;
        $payment->transaction_no = $hyperwallet_payment->getToken();
        $payment->response = json_encode($response);
        $payment->message = "Successfully sent to Member {$payment->user_id} ({$payout->user_token} | {$payout->email}). Transaction No. {$payment->transaction_no}";
        $payment->save();
        return $payment;
    }
}