<?php


namespace Commissions\Contracts;


use App\Payment;
use App\PaymentHistory;

interface PaymentInterface
{
    public function getTable();
    public function getUserId();
    public function getUsername();
    public function getEmail();
    public function getFields();
    public function sentPayment(Payment $payment, $payout);
    public function onBeforePayment(PaymentHistory $history);
    public function onAfterPayment(PaymentHistory $history);
    public function isSetPaidPerPayment();
}