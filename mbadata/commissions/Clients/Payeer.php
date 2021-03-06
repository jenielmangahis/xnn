<?php


namespace Commissions\Clients;


class Payeer
{
    protected $account_number;
    protected $client_id;
    protected $client_secret;
    protected $api_url;
    
	private $agent = 'Mozilla/5.0 (Windows NT 6.1; rv:12.0) Gecko/20100101 Firefox/12.0';


    public function __construct($account_number, $client_id, $client_secret, $api_url)
    {
        $this->account_number = $account_number;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->api_url = $api_url;

    }

    public function isAuth()
	{
        $data = [
            'account' => $this->account_number,
            'apiId' => $this->client_id,
            'apiPass' => $this->client_secret,
        ];

        $response = $this->postJson($data);

		if ($response['auth_error'] == 0 && empty($response['errors'])) {
            return true;
        } 
            
		return false;
	}

    public function sendPayment($payment_id, $user_account_number, $amount)
    {
        if (!$this->isAuth()) {
            $this->logError("Authorization error.");
        }

        $data = [
            'action' => 'transfer',
            'account' => $this->account_number,
            'apiId' => $this->client_id,
            'apiPass' => $this->client_secret,
            'curIn' => 'USD',
            'sum' => $amount,
            'curOut' => 'USD',
            'to' => $user_account_number,
            'referenceId' => $payment_id,
        ];

        return $this->postJson($data);
    }

    private function postJson($data)
    {
        // $request = json_encode($data);

        $request = array();
		foreach ($data as $k => $v)
		{
			$request[] = urlencode($k) . '=' . urlencode($v);
		}

        $request[] = 'language=en';
		$request = implode('&', $request);

        // return print_r($request);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if($err) {
            throw new \Exception($err);
        }

        $r = json_decode($response, true);

        if(!$response || !$r) {
            $error_message = $err ? $err : "The API response is empty.";
            logger()->error($error_message);
            throw new \Exception($error_message);
        }

        return $r;

        // $content = json_decode($response, true);

		// if (isset($content['errors']) && !empty($content['errors'])) {
		// 	$errors = $content['errors'];
		// }

		// return $content;
    }
    
    public function isAccountExist($user_account_number)
    {
        if (!$this->isAuth()) {
            $this->logError("Authorization error.");
        }

        $data = [
            'action' => 'checkUser',
            'account' => $this->account_number,
            'apiId' => $this->client_id,
            'apiPass' => $this->client_secret,
            'user' => $user_account_number,
        ];

        $response = $this->postJson($data);

        // return print_r($response);

        if (empty($response['errors'])) {
			return true;
		}
        
		return false;
    }

    public function logError($error_message) 
    {
        logger()->error($error_message);
        throw new \Exception($error_message);
    }

	public function getHistory()
	{
        if (!$this->isAuth()) {
            $this->logError("Authorization error.");
        }

        $data = [
            'action' => 'history',
            'account' => $this->account_number,
            'apiId' => $this->client_id,
            'apiPass' => $this->client_secret
        ];

        return $this->postJson($data);
	}

}