<?php
namespace App\Payments;

require_once __DIR__ . '/daraja.php';

class PaymentController {
    private $daraja;
    private $db;

    public function __construct() {
        $this->daraja = new Daraja();
        $this->db = new \Database();
    }

    public function initiatePayment($amount, $phone_number, $session_id) {
        try {
            // Get session details
            $stmt = $this->db->executeQuery("SELECT s.*, u.first_name, u.last_name 
                                            FROM sessions s 
                                            JOIN users u ON s.tutee_id = u.id 
                                            WHERE s.id = ?", [$session_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new \Exception('Session not found');
            }

            // Generate reference
            $reference = 'TUT-' . $session_id . '-' . time();

            // Initiate MPESA payment
            $payment = $this->daraja->initiatePayment(
                $amount,
                $phone_number,
                $reference,
                "Payment for tutoring session with " . $session['first_name'] . " " . $session['last_name']
            );

            // Save payment record
            $this->db->executeQuery("INSERT INTO payments 
                                    (session_id, amount, phone_number, reference, 
                                     merchant_request_id, checkout_request_id, 
                                     status, created_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())", [
                $session_id,
                $amount,
                $phone_number,
                $reference,
                $payment['MerchantRequestID'],
                $payment['CheckoutRequestID']
            ]);
            error_log('Inserted payment: session_id=' . $session_id . ', checkout_request_id=' . $payment['CheckoutRequestID']);

            return [
                'success' => true,
                'message' => 'Payment request initiated successfully',
                'data' => $payment
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function handleCallback($data) {
        try {
            $callback = $this->daraja->validateCallback($data);
            
            // Update payment status
            $this->db->executeQuery("UPDATE payments 
                                    SET status = ?, 
                                        updated_at = NOW()
                                    WHERE merchant_request_id = ?", [
                $callback['status'],
                $data['Body']['stkCallback']['MerchantRequestID']
            ]);

            // Update session status if payment is successful
            if ($callback['status'] === 'success') {
                $this->db->executeQuery("UPDATE sessions 
                                        SET status = 'paid', 
                                            updated_at = NOW()
                                        WHERE id IN (
                                            SELECT session_id 
                                            FROM payments 
                                            WHERE merchant_request_id = ?
                                        )", [
                    $data['Body']['stkCallback']['MerchantRequestID']
                ]);
            }

            return [
                'success' => true,
                'message' => 'Callback processed successfully',
                'data' => $callback
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
