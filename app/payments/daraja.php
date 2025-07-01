<?php
require_once __DIR__ . '/../../config/mpesa_config.php';

class Daraja {
    private $accessToken;

    public function getAccessToken() {
        $credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);
        $ch = curl_init(MPESA_AUTH_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $credentials
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, MPESA_SSL_VERIFY);
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            return $this->accessToken;
        }
        throw new Exception('Failed to get access token: ' . $response);
    }

    public function initiateSTKPush($amount, $phone, $reference, $desc) {
        $accessToken = $this->getAccessToken();
        $timestamp = date('YmdHis');
        $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
        $uniqueSuffix = '-' . time() . '-' . rand(1000,9999);
        $data = [
            'BusinessShortCode' => MPESA_SHORTCODE,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => MPESA_SHORTCODE,
            'PhoneNumber' => $phone,
            'CallBackURL' => MPESA_CALLBACK_URL,
            'AccountReference' => $reference . $uniqueSuffix,
            'TransactionDesc' => $desc . $uniqueSuffix
        ];
        
        // Debug: Log the request data
        error_log("M-PESA STK Push Request: " . json_encode($data));
        
        $ch = curl_init(MPESA_STK_PUSH_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, MPESA_SSL_VERIFY);
        $response = curl_exec($ch);
        
        // Debug: Log the response
        error_log("M-PESA STK Push Response: " . $response);
        
        $result = json_decode($response, true);
        if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            error_log('STK Push successful. CheckoutRequestID: ' . $result['CheckoutRequestID']);
            return $result;
        }
        throw new Exception('STK Push failed: ' . $response);
    }

    public function initiatePayment($amount, $phone, $reference, $desc) {
        // M-PESA requires amount to be an integer (in shillings)
        // Convert decimal amount to integer, removing any decimal places
        $amount = (int)$amount;
        
        // Validate amount (M-PESA minimum is 1, maximum is typically 150,000)
        if ($amount < 1) {
            throw new Exception('Amount must be at least 1 KES');
        }
        if ($amount > 150000) {
            throw new Exception('Amount cannot exceed 150,000 KES');
        }
        
        // Debug: Log the amount being sent
        error_log("M-PESA Payment - Amount: " . $amount . ", Phone: " . $phone . ", Reference: " . $reference);
        
        return $this->initiateSTKPush($amount, $phone, $reference, $desc);
    }
} 