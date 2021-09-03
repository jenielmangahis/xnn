<?php
/**
 * Created by PhpStorm.
 * User: Vienzent
 * Date: 7/27/2019
 * Time: 8:53 AM
 */

namespace Commissions\Clients;


class PayQuicker
{
    protected $client_id;
    protected $client_secret;
    protected $funding_account_public_id;
    protected $tenant_login_uri;
    protected $api_uri;
    protected $token_uri;

    public function __construct(
        $client_id,
        $client_secret,
        $funding_account_public_id,
        $tenant_login_uri = 'https://naxum-demo.mypayquicker.com',
        $api_uri = 'https://platform.mypayquicker.com',
        $token_uri = 'https://identity.mypayquicker.com/core/connect/token')
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->tenant_login_uri = $tenant_login_uri;
        $this->funding_account_public_id = $funding_account_public_id;
        $this->api_uri = $api_uri;
        $this->token_uri = $token_uri;
    }

    public function getInvitationLink($invitation_key)
    {
        return $this->tenant_login_uri . "/Welcome?invitationId=" . $invitation_key;
    }

    public function getAccessToken()
    {
        $token = base64_encode($this->client_id . ':' . $this->client_secret);
        $fields = 'grant_type=client_credentials&scope=api%20useraccount_balance%20useraccount_debit%20useraccount_payment%20useraccount_invitation';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->token_uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $token,
                'Cache-Control: no-cache',
                'Content-Type: application/x-www-form-urlencoded',
                "X-MyPayQuicker-Version: 01-15-2018"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if($err)
        {
            throw new \Exception($err);
        }

        $result = json_decode($response, true);

        if(array_key_exists('error', $result)) {
            throw new \Exception($result['error']);
        }

        return $result['access_token'];
    }

    public function sendInvitation($param, $access_token = null)
    {
        if($access_token === null)
        {
            $access_token = $this->getAccessToken();
        }

        if(!array_key_exists('fundingAccountPublicId', $param))
        {
            $param['fundingAccountPublicId'] = $this->funding_account_public_id;
        }

        $url = $this->api_uri . '/api/v1/companies/users/invitations';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($param, JSON_PRETTY_PRINT),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json; charset=utf-8',
                'Authorization: Bearer ' . $access_token,
                'Cache-Control: no-cache',
                'Content-Type: application/json',
                'X-MyPayQuicker-Version: 01-15-2018'
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err)
        {
            throw new \Exception($err);
        }

        $result = json_decode($response, true);

        if(isset($result['severity']) && isset($result['message']))
        {
            throw new \Exception($result['message']);
        }

        return $result;
    }

    public function sendPayments($payments, $access_token = null)
    {
        if($access_token === null)
        {
            $access_token = $this->getAccessToken();
        }

        foreach($payments as $key => $payment)
        {
            if(!array_key_exists('fundingAccountPublicId', $payment))
            {
                $payments[$key]['fundingAccountPublicId'] = $this->funding_account_public_id;
            }
        }

        /*$payments = array();
        $payments[] = array(
            'fundingAccountPublicId' => $this->getFundingAccountPublicId(),
            'monetary' => array('amount' => $param['amount']),
            'accountingId' => $param['accountingId'],
            'userCompanyAssignedUniqueKey' => $this->getCompanyAssingedKey(),
            'userNotificationEmailAddress' => $param['userNotificationEmailAddress']
        );*/

        $url = $this->api_uri . '/api/v1/companies/accounts/payments';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 1800,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(['payments' => $payments], JSON_PRETTY_PRINT),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json; charset=utf-8',
                'Authorization: Bearer ' . $access_token,
                'Cache-Control: no-cache',
                'Content-Type: application/json',
                'X-MyPayQuicker-Version: 01-15-2018'
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err)
        {
            throw new \Exception($err);
        }

        $r = json_decode($response, true);

        if(!$response || !$r) {
            $error_message = $err ? $err : "The API response is empty.";
            logger()->error($error_message);
            throw new \Exception($error_message);
        }

        return $r;
    }
}