<?php

namespace Commissions\Exceptions;

use Throwable;

class CommissionException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return 400;
    }
}