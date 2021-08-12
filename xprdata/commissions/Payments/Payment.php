<?php


namespace Commissions\Payments;


use Commissions\Contracts\PaymentInterface;

abstract class Payment implements PaymentInterface
{
    protected $user_id = "user_id";
    protected $username = "username";

    public function getTable()
    {
        if(!property_exists($this, "table"))
            throw new \Exception("Property table is missing.");

        return $this->table;
    }

    public function getUserId()
    {
        return $this->getTable() . ".$this->user_id";
    }

    public function getUsername()
    {
        return $this->getTable() . ".$this->username";
    }

    public function getEmail()
    {
        if(!property_exists($this, "email")) return "u.email";

        return $this->getTable() . ".$this->email";
    }

    public function getFields()
    {
        if(!property_exists($this, "fields")) return [];

        $fields = [];

        foreach($this->fields as $field) {

            $f = preg_split("/ AS /i", $field);
            $f = trim(strtolower($f[count($f) - 1]));

            if (!$f || in_array($f, ['ids','name','user_id','commission_type','amount'])) {
                throw new \Exception("Invalid field: $f");
            }

            $fields[] = $this->getTable() . ".$field";
        }

        return $fields;
    }
}