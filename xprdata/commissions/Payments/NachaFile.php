<?php


namespace Commissions\Payments;


use App\CommissionPayout;
use App\Payment;
use App\PaymentHistory;
use Commissions\Contracts\PaymentInterface;
use Commissions\Payments\Payment as BasePayment;
use Commissions\NachaFile as NachaFileGenerator;

class NachaFile extends BasePayment implements PaymentInterface
{
    protected $table = "users";
    protected $username = "account_number";
    protected $user_id = "id";

    protected $ach_file;

    // field use for payout
    protected $fields = [
        "account_number",
        "routing_number",
        "account_type",
    ];

    public function __construct()
    {
        $this->ach_file = new NachaFileGenerator();
    }

    public function sentPayment(Payment $payment, $payout)
    {

        if(!in_array(strtoupper($payout->account_type), ["CHECKING", "SAVINGS"])) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($payout);
            $payment->message = "Account Type not found ($payout->account_type).";
            $payment->save();
            return $payment;
        }

        $p = [
            "AccountNumber" => $payment->user_id, // The customer's CRM account number (not bank account number)
            "TotalAmount" => $payment->amount, // Amount they are paying you if it's a debit - or that you're paying them if it's a credit.
            "BankAccountNumber" => $payout->account_number, // Customer's bank account number
            "RoutingNumber" => $payout->routing_number, // Customer's bank routing number
            "FormattedName" => $payout->name, // Customer's name
            "AccountType" => strtoupper($payout->account_type) // Could be 'CHECKING' or 'SAVINGS' - customer's bank account type
        ];

        $success = $this->ach_file->addCredit($p);

        if (!$success) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->response = json_encode($payout);
            $payment->message = "Unable to add in the ACH file";
            $payment->save();
            return $payment;
        }

        $payment->status = Payment::STATUS_SUCCESS;
        $payment->transaction_no = "N/A";
        $payment->response = json_encode($payout);
        $payment->message = "The payment for {$payout->name} is successfully added to the txt file.";
        $payment->save();

        return $payment;
    }

    public function onBeforePayment(PaymentHistory $history)
    {
        // para sure sure na bago
        $this->ach_file = new NachaFileGenerator();

        $this->ach_file
            ->setBankRT(config('services.nacha.bank_rt'))
            ->setFileID(config('services.nacha.file_id'))
            // ->setFileModifier('A')
            // ->setRecordSize("094")
            // ->setBlockingFactor("10")
            // ->setFormatCode("1")
            ->setOriginatingBank(config('services.nacha.originating_bank'))
            ->setCompanyName(config('services.nacha.company_name'))
            ->setReferenceCode($history->id)

            ->setServiceClassCode(config('services.nacha.service_class_code'))
            ->setBatchInfo("")
            ->setCompanyId(config('services.nacha.company_id'))
            ->setSECCode(config('services.nacha.sec_code'))
            ->setDescription(config('services.nacha.description'), date("Y-m-d"))
            ->setEntryDate(date("Y-m-d"));



    }

    public function onAfterPayment(PaymentHistory $history)
    {
        $download_links = [];
        $download_links[] = [
            'id' => 1,
            'name' => 'Nacha',
            'filename' => $this->ach_file->generateFilename("PAY_" . $history->id)
        ];

        $history->download_links = $download_links;
        $history->save();
    }

    public function isSetPaidPerPayment()
    {
        return false;
    }
}