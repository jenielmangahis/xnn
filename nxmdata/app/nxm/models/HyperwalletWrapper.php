<?php
    namespace App\nxm\models;
    
    class HyperwalletWrapper
    {
        private $m_api_uri;
        private $m_is_debug;
        
        public function __construct($is_debug = false) {

            $this->m_is_debug = $is_debug;
            $this->m_api_uri = 'https://office.myfluentworlds.com/hyperwallet';
        }
        public function setIsDebug($is_debug) {

            $this->m_is_debug = $is_debug;
            return $this;
        }
        public function createAccount($value) {
            
            try {
                if ($this->m_is_debug === true) {
                    // UAT
                    // POST: https://office.trackmyripple.com/hyperwallet/users/:user_id/uat-payment
                    $apiUrl = $this->m_api_uri . '/uat-users/' . $value['user_id'] . '/add';
                } else {
                    // LIVE
                    // POST: https://office.trackmyripple.com/hyperwallet/users/:user_id/payment
                    $apiUrl = $this->m_api_uri . '/users/' . $value['user_id'] . '/add';
                }
                
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => array(
                        'clientUserId' => $value['clientUserId'],
                        'firstName' => $value['firstName'],
                        'lastName' => $value['lastName'],
                        'email' => $value['email'],
                        'addressLine1' => $value['addressLine1'],
                        'city' => $value['city'],
                        'stateProvince' => $value['stateProvince'],
                        'country' => $value['country'],
                        'postalCode' => $value['postalCode'],
                        'dateOfBirth' => $value['dateOfBirth']
                    )
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                
                if ($err) {
                    
                    return $err;
                } else {
                    
                    return $response;
                }
            } catch (\Exception $exception) {
        
                return null;
            }
        }
        public function sendPayment($value) {
            
            try {
                if ($this->m_is_debug === true) {
                    
                    // UAT
                    // POST: https://office.trackmyripple.com/hyperwallet/users/:user_id/uat-payment
                    $apiUrl = $this->m_api_uri . '/users/' . $value['user_id'] . '/uat-payment';
                } else {
                    
                    // LIVE
                    // POST: https://office.trackmyripple.com/hyperwallet/users/:user_id/payment
                    $apiUrl = $this->m_api_uri . '/users/' . $value['user_id'] . '/payment';
                }
                
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => array(
                        'clientPaymentId' => $value['clientPaymentId'],
                        'amount' => $value['amount'],
                        'currency' => 'USD',
                        'notes' => 'COMMISSION',
                        'memo' => 'COMMISSION',
                        'purpose' => 'OTHER'
                    )
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                
                if ($err) {
                    
                    return array(
                        'status' => '01',
                        'fieldName' => '',
                        'token' => '',
                        'status' => ''
                    );
                } else {
                    
                    $data = json_decode($response, true);
                    $fieldName = isset($data['errors'][0]['fieldName']) ? $data['errors'][0]['fieldName'] : '';
                    $token = isset($data['token']) ? $data['token'] : '';
                    $status = isset($data['status']) ? $data['status'] : '';
                    
                    return array(
                        'status' => '00',
                        'fieldName' => $fieldName,
                        'token' => $token,
                        'status' => $status
                    );
                }
            } catch (\Exception $exception) {
                
                return array(
                    'status' => '01',
                    'fieldName' => '',
                    'token' => '',
                    'status' => ''
                );
            }
        }
    }