<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 9/19/2019
 * Time: 2:17 AM
 */

namespace Commissions;


class NetworkMerchants
{
    const APPROVED = 1;
    const DECLINED = 2;
    const ERROR = 3;

    public $responses = [];

    public function __construct($username = "demo", $password = "password")
    {
        $this->setLogin($username, $password);
    }

    public function setLogin($username, $password)
    {
        $this->login['username'] = $username;
        $this->login['password'] = $password;
    }

    public function setOrder(
        $order_id,
        $order_description,
        $tax,
        $shipping,
        $po_number,
        $ip_address)
    {
        $this->order['orderid'] = $order_id;
        $this->order['orderdescription'] = $order_description;
        $this->order['tax'] = $tax;
        $this->order['shipping'] = $shipping;
        $this->order['ponumber'] = $po_number;
        $this->order['ipaddress'] = $ip_address;
    }

    public function setBilling(
        $first_name,
        $last_name,
        $company,
        $address1,
        $address2,
        $city,
        $state,
        $zip,
        $country,
        $phone,
        $fax,
        $email,
        $website)
    {
        $this->billing['firstname'] = $first_name;
        $this->billing['lastname'] = $last_name;
        $this->billing['company'] = $company;
        $this->billing['address1'] = $address1;
        $this->billing['address2'] = $address2;
        $this->billing['city'] = $city;
        $this->billing['state'] = $state;
        $this->billing['zip'] = $zip;
        $this->billing['country'] = $country;
        $this->billing['phone'] = $phone;
        $this->billing['fax'] = $fax;
        $this->billing['email'] = $email;
        $this->billing['website'] = $website;
    }

    public function setShipping(
        $first_name,
        $last_name,
        $company,
        $address1,
        $address2,
        $city,
        $state,
        $zip,
        $country,
        $email)
    {
        $this->shipping['firstname'] = $first_name;
        $this->shipping['lastname'] = $last_name;
        $this->shipping['company'] = $company;
        $this->shipping['address1'] = $address1;
        $this->shipping['address2'] = $address2;
        $this->shipping['city'] = $city;
        $this->shipping['state'] = $state;
        $this->shipping['zip'] = $zip;
        $this->shipping['country'] = $country;
        $this->shipping['email'] = $email;
    }

    public function sale($amount, $cc_number, $cc_exp, $cvv = "")
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Sales Information
        $query .= "ccnumber=" . urlencode($cc_number) . "&";
        $query .= "ccexp=" . urlencode($cc_exp) . "&";
        $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        $query .= "cvv=" . urlencode($cvv) . "&";
        // Order Information
        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
        $query .= "tax=" . urlencode(number_format($this->order['tax'], 2, ".", "")) . "&";
        $query .= "shipping=" . urlencode(number_format($this->order['shipping'], 2, ".", "")) . "&";
        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
        // Billing Information
        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
        $query .= "company=" . urlencode($this->billing['company']) . "&";
        $query .= "address1=" . urlencode($this->billing['address1']) . "&";
        $query .= "address2=" . urlencode($this->billing['address2']) . "&";
        $query .= "city=" . urlencode($this->billing['city']) . "&";
        $query .= "state=" . urlencode($this->billing['state']) . "&";
        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
        $query .= "country=" . urlencode($this->billing['country']) . "&";
        $query .= "phone=" . urlencode($this->billing['phone']) . "&";
        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
        $query .= "email=" . urlencode($this->billing['email']) . "&";
        $query .= "website=" . urlencode($this->billing['website']) . "&";
        // Shipping Information
        $query .= "shipping_firstname=" . urlencode($this->shipping['firstname']) . "&";
        $query .= "shipping_lastname=" . urlencode($this->shipping['lastname']) . "&";
        $query .= "shipping_company=" . urlencode($this->shipping['company']) . "&";
        $query .= "shipping_address1=" . urlencode($this->shipping['address1']) . "&";
        $query .= "shipping_address2=" . urlencode($this->shipping['address2']) . "&";
        $query .= "shipping_city=" . urlencode($this->shipping['city']) . "&";
        $query .= "shipping_state=" . urlencode($this->shipping['state']) . "&";
        $query .= "shipping_zip=" . urlencode($this->shipping['zip']) . "&";
        $query .= "shipping_country=" . urlencode($this->shipping['country']) . "&";
        $query .= "shipping_email=" . urlencode($this->shipping['email']) . "&";
        $query .= "type=sale";
        return $this->_post($query);
    }

    public function auth($amount, $cc_number, $cc_exp, $cvv = "")
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Sales Information
        $query .= "ccnumber=" . urlencode($cc_number) . "&";
        $query .= "ccexp=" . urlencode($cc_exp) . "&";
        $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        $query .= "cvv=" . urlencode($cvv) . "&";
        // Order Information
        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
        $query .= "tax=" . urlencode(number_format($this->order['tax'], 2, ".", "")) . "&";
        $query .= "shipping=" . urlencode(number_format($this->order['shipping'], 2, ".", "")) . "&";
        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
        // Billing Information
        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
        $query .= "company=" . urlencode($this->billing['company']) . "&";
        $query .= "address1=" . urlencode($this->billing['address1']) . "&";
        $query .= "address2=" . urlencode($this->billing['address2']) . "&";
        $query .= "city=" . urlencode($this->billing['city']) . "&";
        $query .= "state=" . urlencode($this->billing['state']) . "&";
        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
        $query .= "country=" . urlencode($this->billing['country']) . "&";
        $query .= "phone=" . urlencode($this->billing['phone']) . "&";
        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
        $query .= "email=" . urlencode($this->billing['email']) . "&";
        $query .= "website=" . urlencode($this->billing['website']) . "&";
        // Shipping Information
        $query .= "shipping_firstname=" . urlencode($this->shipping['firstname']) . "&";
        $query .= "shipping_lastname=" . urlencode($this->shipping['lastname']) . "&";
        $query .= "shipping_company=" . urlencode($this->shipping['company']) . "&";
        $query .= "shipping_address1=" . urlencode($this->shipping['address1']) . "&";
        $query .= "shipping_address2=" . urlencode($this->shipping['address2']) . "&";
        $query .= "shipping_city=" . urlencode($this->shipping['city']) . "&";
        $query .= "shipping_state=" . urlencode($this->shipping['state']) . "&";
        $query .= "shipping_zip=" . urlencode($this->shipping['zip']) . "&";
        $query .= "shipping_country=" . urlencode($this->shipping['country']) . "&";
        $query .= "shipping_email=" . urlencode($this->shipping['email']) . "&";
        $query .= "type=auth";
        return $this->_post($query);
    }

    public function credit($amount, $cc_number, $cc_exp)
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Sales Information
        $query .= "ccnumber=" . urlencode($cc_number) . "&";
        $query .= "ccexp=" . urlencode($cc_exp) . "&";
        $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        // Order Information
        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
        $query .= "tax=" . urlencode(number_format($this->order['tax'], 2, ".", "")) . "&";
        $query .= "shipping=" . urlencode(number_format($this->order['shipping'], 2, ".", "")) . "&";
        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
        // Billing Information
        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
        $query .= "company=" . urlencode($this->billing['company']) . "&";
        $query .= "address1=" . urlencode($this->billing['address1']) . "&";
        $query .= "address2=" . urlencode($this->billing['address2']) . "&";
        $query .= "city=" . urlencode($this->billing['city']) . "&";
        $query .= "state=" . urlencode($this->billing['state']) . "&";
        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
        $query .= "country=" . urlencode($this->billing['country']) . "&";
        $query .= "phone=" . urlencode($this->billing['phone']) . "&";
        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
        $query .= "email=" . urlencode($this->billing['email']) . "&";
        $query .= "website=" . urlencode($this->billing['website']) . "&";
        $query .= "type=credit";
        return $this->_post($query);
    }

    public function offline($authorization_code, $amount, $cc_number, $cc_exp)
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Sales Information
        $query .= "ccnumber=" . urlencode($cc_number) . "&";
        $query .= "ccexp=" . urlencode($cc_exp) . "&";
        $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        $query .= "authorizationcode=" . urlencode($authorization_code) . "&";
        // Order Information
        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
        $query .= "tax=" . urlencode(number_format($this->order['tax'], 2, ".", "")) . "&";
        $query .= "shipping=" . urlencode(number_format($this->order['shipping'], 2, ".", "")) . "&";
        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
        // Billing Information
        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
        $query .= "company=" . urlencode($this->billing['company']) . "&";
        $query .= "address1=" . urlencode($this->billing['address1']) . "&";
        $query .= "address2=" . urlencode($this->billing['address2']) . "&";
        $query .= "city=" . urlencode($this->billing['city']) . "&";
        $query .= "state=" . urlencode($this->billing['state']) . "&";
        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
        $query .= "country=" . urlencode($this->billing['country']) . "&";
        $query .= "phone=" . urlencode($this->billing['phone']) . "&";
        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
        $query .= "email=" . urlencode($this->billing['email']) . "&";
        $query .= "website=" . urlencode($this->billing['website']) . "&";
        // Shipping Information
        $query .= "shipping_firstname=" . urlencode($this->shipping['firstname']) . "&";
        $query .= "shipping_lastname=" . urlencode($this->shipping['lastname']) . "&";
        $query .= "shipping_company=" . urlencode($this->shipping['company']) . "&";
        $query .= "shipping_address1=" . urlencode($this->shipping['address1']) . "&";
        $query .= "shipping_address2=" . urlencode($this->shipping['address2']) . "&";
        $query .= "shipping_city=" . urlencode($this->shipping['city']) . "&";
        $query .= "shipping_state=" . urlencode($this->shipping['state']) . "&";
        $query .= "shipping_zip=" . urlencode($this->shipping['zip']) . "&";
        $query .= "shipping_country=" . urlencode($this->shipping['country']) . "&";
        $query .= "shipping_email=" . urlencode($this->shipping['email']) . "&";
        $query .= "type=offline";
        return $this->_post($query);
    }

    public function capture($transaction_id, $amount = 0)
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Transaction Information
        $query .= "transactionid=" . urlencode($transaction_id) . "&";
        if ($amount > 0) {
            $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        }
        $query .= "type=capture";
        return $this->_post($query);
    }

    public function void($transaction_id)
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Transaction Information
        $query .= "transactionid=" . urlencode($transaction_id) . "&";
        $query .= "type=void";
        return $this->_post($query);
    }

    public function refund($transaction_id, $amount = 0)
    {

        $query = "";
        // Login Information
        $query .= "username=" . urlencode($this->login['username']) . "&";
        $query .= "password=" . urlencode($this->login['password']) . "&";
        // Transaction Information
        $query .= "transactionid=" . urlencode($transaction_id) . "&";
        if ($amount > 0) {
            $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        }
        $query .= "type=refund";
        return $this->_post($query);
    }

    private function _post($query)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://secure.basecommercegateway.com/api/transact.php");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_POST, 1);

        if (!($data = curl_exec($ch))) {
            return self::ERROR;
        }
        curl_close($ch);
        unset($ch);
        // print "\n$data\n";
        $data = explode("&", $data);
        for ($i = 0; $i < count($data); $i++) {
            $rdata = explode("=", $data[$i]);
            $this->responses[$rdata[0]] = $rdata[1];
        }

        return +$this->responses['response'];
    }
}