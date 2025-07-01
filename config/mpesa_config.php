<?php
// M-PESA Sandbox Credentials
// Replace with your own from the Safaricom Developer Portal if needed

if (!defined('MPESA_CONSUMER_KEY')) define('MPESA_CONSUMER_KEY', 'XyphGZAgiDrbjnWcVvwAd5sNoNscVcVQzr9kgyZF0DQYSLah');
if (!defined('MPESA_CONSUMER_SECRET')) define('MPESA_CONSUMER_SECRET', '8u58oz1Mrrk9siFMZJiKPZs2fP3VIzL2plx3E9P9nU0M70mc4CjwnJJ27Q1vFmPA');
if (!defined('MPESA_SHORTCODE')) define('MPESA_SHORTCODE', '174379'); // Sandbox shortcode
if (!defined('MPESA_PASSKEY')) define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_ENV', 'sandbox');
if (!defined('MPESA_CALLBACK_URL')) define('MPESA_CALLBACK_URL', 'https://0a90-197-232-77-95.ngrok-free.app/tutorapp/mpesa_callback.php'); // Change to your public callback URL
define('MPESA_AUTH_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
define('MPESA_STK_PUSH_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
define('MPESA_DEBUG', true);
define('MPESA_SSL_VERIFY', false); 