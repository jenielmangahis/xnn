<?php


namespace Commissions\Contracts;


use App\Payment;

interface PaymentInterface
{
    public function getTable();
    public function getUserId();
    public function getUsername();
    public function getEmail();
    public function getFields();
    public function sentPayment(Payment $payment, $payout);
}