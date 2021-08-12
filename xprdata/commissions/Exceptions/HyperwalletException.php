<?php

namespace Commissions\Exceptions;

use Throwable;

class HyperwalletException extends \Exception
{

    protected $hyperwallet_code = null;

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $messages = explode("||", $message);

        if(array_key_exists(1, $messages)) {
            $this->hyperwallet_code = $messages[1];
        }

        $message = $messages[0];

        if($this->hyperwallet_code != null) {
            $message .= " ({$this->hyperwallet_code})";
        }

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode()
    {
        return 400;
    }

    public function getHyperwalletCode()
    {
        return $this->hyperwallet_code;
    }

    public static function throwException(\Exception $exception)
    {
        if($exception instanceof \Hyperwallet\Exception\HyperwalletApiException) {

            $message = $exception->getMessage();

            if(array_key_exists(0, $exception->getErrorResponse()->getErrors())) {
                $error =  $exception->getErrorResponse()->getErrors()[0];

                $message = $error->getMessage();

                if(!!$error->getFieldName()) {
                    $message .= " ({$error->getFieldName()})";
                }
            }

            if(strpos($message, 'already registered with another user ') !== false)
            {
                $message = "The email address you provided is already registered with another user.";
            }

            throw new self($message);
        }
        elseif ($exception instanceof \Hyperwallet\Exception\HyperwalletException) {

            $message = $exception->getMessage();

            if(strpos($message, 'already registered with another user ') !== false)
            {
                $message = "The email address you provided is already registered with another user.";
            }

            throw new self($message);
        }

        throw new self($exception->getMessage());
    }

}