<?php


namespace Commissions\Payments;


use App\Payment;
use Commissions\Contracts\PaymentInterface;
use Commissions\Exceptions\IPayoutException;
use Commissions\Payments\Payment as BasePayment;

class IPayout extends BasePayment implements PaymentInterface
{

    protected $table = "cm_ipayout_users";
    protected $username = "username";
    protected $email = "email";

    // field use for payout
    protected $fields = [
        "username",
        "email AS email",
    ];

    protected $ipayout;

    public function __construct(\Commissions\Clients\IPayout $ipayout)
    {
        $this->ipayout = $ipayout;
    }

    public function sentPayment(Payment $payment, $payout)
    {
        try {
            $response = $this->ipayout->loadPayout($payment->history_id, $payout->username, $payout->amount, $payment->id);
        }
        catch (IPayoutException $ex) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($ex->getResponse());
            $payment->message = "IPayout: " . $ex->getMessage();
            $payment->save();
            return $payment;
        }
        catch (\Exception $ex) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode(['message' => $ex->getMessage()]);
            $payment->message = "System: " . $ex->getMessage();
            $payment->save();
            return $payment;
        }

        // IF SUCCESS
        $payment->status = Payment::STATUS_SUCCESS;
        $payment->transaction_no = $response['TransactionRefID'];
        $payment->response = json_encode($response);
        $payment->message = "Successfully sent to {$payout->username}|{$payout->email}. Transaction No. {$payment->transaction_no}";
        $payment->save();
        return $payment;
    }
}