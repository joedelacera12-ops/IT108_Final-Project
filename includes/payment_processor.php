<?php
/**
 * Payment Processor for AgriSea Marketplace
 * Supports GCash, PayMaya, Debit/Credit Cards, and Bank Transfer
 */

require_once __DIR__ . '/db.php';

class PaymentProcessor {
    private $pdo;
    
    public function __construct() {
        $this->pdo = get_db();
    }
    
    /**
     * Process a payment
     * @param int $userId User ID making the payment
     * @param float $amount Payment amount
     * @param string $paymentMethod Payment method (gcash, paymaya, card, bank_transfer)
     * @param int $orderId Optional order ID
     * @param int $subscriptionId Optional subscription ID
     * @return array Payment result
     */
    public function processPayment($userId, $amount, $paymentMethod, $orderId = null, $subscriptionId = null) {
        try {
            // Validate payment method
            $validMethods = ['gcash', 'paymaya', 'card', 'bank_transfer'];
            if (!in_array($paymentMethod, $validMethods)) {
                return [
                    'success' => false,
                    'error' => 'Invalid payment method'
                ];
            }
            
            // Format payment method for display
            $methodLabels = [
                'gcash' => 'GCash',
                'paymaya' => 'PayMaya',
                'card' => 'Debit/Credit Card',
                'bank_transfer' => 'Bank Transfer'
            ];
            
            $displayMethod = $methodLabels[$paymentMethod];
            
            // Insert payment record
            $stmt = $this->pdo->prepare("
                INSERT INTO payment_history 
                (user_id, order_id, subscription_id, amount, payment_method, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([$userId, $orderId, $subscriptionId, $amount, $displayMethod]);
            $paymentId = $this->pdo->lastInsertId();
            
            // Simulate payment processing (in a real system, this would connect to payment gateways)
            // For demonstration, we'll assume all payments succeed
            $this->updatePaymentStatus($paymentId, 'completed');
            
            // If this is a subscription payment, update subscription
            if ($subscriptionId) {
                $this->renewSubscription($subscriptionId);
            }
            
            // Create notification for user
            $this->createPaymentNotification($userId, $amount, $displayMethod, $paymentId);
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'message' => 'Payment processed successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Payment processing error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment processing failed'
            ];
        }
    }
    
    /**
     * Update payment status
     * @param int $paymentId Payment ID
     * @param string $status New status (pending, completed, failed, refunded)
     * @return bool Success status
     */
    public function updatePaymentStatus($paymentId, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE payment_history 
                SET status = ?, processed_at = NOW(), updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$status, $paymentId]);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Renew a subscription
     * @param int $subscriptionId Subscription ID
     * @return bool Success status
     */
    public function renewSubscription($subscriptionId) {
        try {
            // Get subscription details
            $stmt = $this->pdo->prepare("
                SELECT seller_id, plan_type, end_date 
                FROM subscriptions 
                WHERE id = ?
            ");
            $stmt->execute([$subscriptionId]);
            $subscription = $stmt->fetch();
            
            if (!$subscription) {
                return false;
            }
            
            // Calculate new end date
            $currentEndDate = new DateTime($subscription['end_date']);
            if ($subscription['plan_type'] === 'monthly') {
                $newEndDate = $currentEndDate->modify('+1 month');
            } else {
                $newEndDate = $currentEndDate->modify('+1 year');
            }
            
            // Update subscription
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET end_date = ?, status = 'active', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$newEndDate->format('Y-m-d'), $subscriptionId]);
            
            // Create renewal notification
            $notificationSystem = new NotificationSystem();
            $notificationSystem->createSubscriptionRenewalNotification(
                $subscription['seller_id'], 
                $newEndDate->format('Y-m-d')
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Subscription renewal error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create payment notification
     * @param int $userId User ID
     * @param float $amount Payment amount
     * @param string $method Payment method
     * @param int $paymentId Payment ID
     * @return bool Success status
     */
    private function createPaymentNotification($userId, $amount, $method, $paymentId) {
        try {
            require_once __DIR__ . '/notification_system.php';
            $notificationSystem = new NotificationSystem();
            
            $title = "Payment Successful";
            $message = "Your payment of ₱" . number_format($amount, 2) . " via " . $method . " was processed successfully.";
            
            return $notificationSystem->createNotification($userId, 'payment', $title, $message, $paymentId, 'payment');
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get user's payment history
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Payment records
     */
    public function getUserPaymentHistory($userId, $limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM payment_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get payment methods with their statistics
     * @return array Payment method statistics
     */
    public function getPaymentMethodStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM payment_history 
                WHERE status = 'completed'
                GROUP BY payment_method
                ORDER BY total_amount DESC
            ");
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Create a subscription
     * @param int $sellerId Seller ID
     * @param string $planType Plan type (monthly, yearly)
     * @param float $amount Subscription amount
     * @param string $paymentMethod Payment method
     * @return array Subscription result
     */
    public function createSubscription($sellerId, $planType, $amount, $paymentMethod) {
        try {
            // Validate plan type
            if (!in_array($planType, ['monthly', 'yearly'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid plan type'
                ];
            }
            
            // Calculate dates
            $startDate = new DateTime();
            if ($planType === 'monthly') {
                $endDate = clone $startDate;
                $endDate->modify('+1 month');
            } else {
                $endDate = clone $startDate;
                $endDate->modify('+1 year');
            }
            
            // Insert subscription record
            $stmt = $this->pdo->prepare("
                INSERT INTO subscriptions 
                (seller_id, plan_type, amount, payment_method, status, start_date, end_date, created_at) 
                VALUES (?, ?, ?, ?, 'active', ?, ?, NOW())
            ");
            
            $methodLabels = [
                'gcash' => 'GCash',
                'paymaya' => 'PayMaya',
                'card' => 'Debit/Credit Card',
                'bank_transfer' => 'Bank Transfer'
            ];
            
            $displayMethod = $methodLabels[$paymentMethod];
            
            $stmt->execute([
                $sellerId, 
                $planType, 
                $amount, 
                $displayMethod, 
                $startDate->format('Y-m-d'), 
                $endDate->format('Y-m-d')
            ]);
            
            $subscriptionId = $this->pdo->lastInsertId();
            
            // Process payment for subscription
            $paymentResult = $this->processPayment($sellerId, $amount, $paymentMethod, null, $subscriptionId);
            
            if (!$paymentResult['success']) {
                // If payment fails, cancel subscription
                $this->cancelSubscription($subscriptionId);
                return $paymentResult;
            }
            
            // Create subscription notification
            $notificationSystem = new NotificationSystem();
            $notificationSystem->createNotification(
                $sellerId, 
                'subscription', 
                'Subscription Created', 
                'Your ' . ucfirst($planType) . ' subscription has been created successfully.', 
                $subscriptionId, 
                'subscription'
            );
            
            return [
                'success' => true,
                'subscription_id' => $subscriptionId,
                'end_date' => $endDate->format('Y-m-d'),
                'message' => 'Subscription created successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Subscription creation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Subscription creation failed'
            ];
        }
    }
    
    /**
     * Cancel a subscription
     * @param int $subscriptionId Subscription ID
     * @return bool Success status
     */
    public function cancelSubscription($subscriptionId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$subscriptionId]);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if subscription is expiring soon
     * @param int $sellerId Seller ID
     * @return array Expiring subscriptions
     */
    public function getExpiringSubscriptions($sellerId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM subscriptions 
                WHERE seller_id = ? 
                AND status = 'active' 
                AND end_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
                ORDER BY end_date ASC
            ");
            
            $stmt->execute([$sellerId]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get user's payment history (alias for getUserPaymentHistory)
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return array Payment records
     */
    public function getPaymentHistory($userId, $limit = 20) {
        return $this->getUserPaymentHistory($userId, $limit);
    }
    
    /**
     * Get seller's active subscription
     * @param int $sellerId Seller ID
     * @return array|null Subscription data or null if no active subscription
     */
    public function getSellerSubscription($sellerId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM subscriptions 
                WHERE seller_id = ? 
                AND status = 'active'
                AND end_date >= CURDATE()
                ORDER BY end_date DESC
                LIMIT 1
            ");
            
            $stmt->execute([$sellerId]);
            $subscription = $stmt->fetch();
            
            // If no active subscription, check for expired ones
            if (!$subscription) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM subscriptions 
                    WHERE seller_id = ? 
                    AND end_date < CURDATE()
                    ORDER BY end_date DESC
                    LIMIT 1
                ");
                
                $stmt->execute([$sellerId]);
                $subscription = $stmt->fetch();
                
                // Mark as expired if found
                if ($subscription) {
                    $subscription['status'] = 'expired';
                }
            }
            
            return $subscription;
            
        } catch (Exception $e) {
            error_log("Error getting seller subscription: " . $e->getMessage());
            return null;
        }
    }
}
?>