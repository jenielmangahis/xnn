<?php

namespace App\Mail;

use App\User;
use App\UserPlacement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class LedgerAdded
 * @package App\Mail
 */
class HoldingTankExpired extends Mailable
{
    use Queueable, SerializesModels;


    public $placement;
    public $user;
    public $sponsor;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(UserPlacement $placement, User $user, User $sponsor)
    {
        $this->placement = $placement;
        $this->user = $user;
        $this->sponsor = $sponsor;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->bcc("comm@mymbatrading.com")->view('emails.placement.expired')->subject("Unplaced Member");
    }
}
