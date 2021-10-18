<?php


namespace Commissions\Clients;


use Commissions\Exceptions\IPayoutException;
use App\IPayoutUser;

class IPayout
{
    protected $merchant_guid;
    protected $merchant_password;
    protected $api_url;

    public function __construct($merchant_guid, $merchant_password, $api_url = "https://testewallet.com/eWalletWS/ws_JsonAdapter.aspx")
    {
        $this->merchant_guid = $merchant_guid;
        $this->merchant_password = $merchant_password;
        $this->api_url = $api_url;
    }

    public function registerUser(IPayoutUser $user)
    {
        $data = [
            'fn' => 'eWallet_RegisterUser',
            'MerchantGUID' => $this->merchant_guid,
            'MerchantPassword' => $this->merchant_password,
            'UserName' => $user->username,
            'FirstName' => $user->first_name,
            'LastName' => $user->last_name,
            'EmailAddress' => $user->email,
            'DateOfBirth' => $user->date_of_birth,
        ];

        $data['WebsitePassword'] = str_random(10);

        $this->assignIfNotNull($user->company_name, $data['CompanyName']);
        $this->assignIfNotNull($user->address_1, $data['Address1']);
        $this->assignIfNotNull($user->address_2, $data['Address2']);
        $this->assignIfNotNull($user->city, $data['City']);
        $this->assignIfNotNull($user->state, $data['State']);
        $this->assignIfNotNull($user->zip_code, $data['ZipCode']);
        $this->assignIfNotNull($user->country_code, $data['Country2xFormat']);
        // $this->assignIfNotNull($user->website_password, $data['WebsitePassword']);

        return $this->postJson($data);
    }

    public function loadPayout($batch_id, $username, $amount, $comment = null)
    {
        $data = [
            'fn' => 'eWallet_Load',
            'MerchantGUID' => $this->merchant_guid,
            'MerchantPassword' => $this->merchant_password,
            'PartnerBatchID' => $batch_id,
            'PoolID' => $batch_id,
            'arrAccounts' => [
                [
                    'UserName' => $username,
                    'Amount' => $amount,
                    'Comments' => $comment,
                    'MerchantReferenceID' => null,
                ]
            ],
            'CurrencyCode' => 'USD',
        ];

        return $this->postJson($data);
    }

    private function postJson($data)
    {
        $request = json_encode($data);

        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) throw new IPayoutException($err);

        $response = json_decode($response, true);

        if($response['response']['m_Code'] < 0) {
            throw new IPayoutException($response['response']['m_Text'], $response);
        }

        return $response['response'];
    }

    private function assignIfNotNull($parameter, &$data)
    {
        if ($parameter !== null) {
            $data = $parameter;
        }
    }
}