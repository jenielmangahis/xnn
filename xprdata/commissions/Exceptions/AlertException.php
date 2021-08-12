<?php


namespace Commissions\Exceptions;

use Exception;

class AlertException extends Exception
{
    const TYPE_DANGER = 'danger';
    const TYPE_WARNING = 'warning';

    protected $data;
    protected $error_type;

    public function __construct($message = "", $error_type = self::TYPE_DANGER, $data = [])
    {
        parent::__construct($message);
        $this->error_type = $error_type;
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrorType()
    {
        return $this->error_type;
    }

}