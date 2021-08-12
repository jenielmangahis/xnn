<?php

namespace App\Mail;

use App\Ledger;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class LedgerAdded
 * @package App\Mail
 */
class LedgerAdded extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var Ledger
     */
    public $ledger;
    public $user;


    public function __construct(Ledger $ledger, User $user)
    {
        $this->ledger = $ledger;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->bcc("comm@naxum.com")->view('emails.ledger.added')->subject("Funds Added");
    }
}
