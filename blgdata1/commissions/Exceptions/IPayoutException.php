<?php


namespace Commissions\Exceptions;


use Throwable;

class IPayoutException extends \Exception
{
    protected $response;

    public function __construct($message = "", $response, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function getStatusCode()
    {
        return 400;
    }

    public function getResponse()
    {
        return $this->response;
    }

}