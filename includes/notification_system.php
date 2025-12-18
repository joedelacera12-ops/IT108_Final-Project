<?php
/**
 * Notification System for AgriSea Marketplace
 * Handles notifications for sales, subscriptions, low stocks, and new orders
 */

require_once __DIR__ . '/db.php';

class NotificationSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = get_db();
    }
    
    /**
     * Create a notification for a user
     * @param int $userId User ID to send notification to
     * @param string $type Type of notification (e.g., 'sale', 'subscription', 'low_stock', 'new_order')
     * @param string $title Notification title
     * @param string $message Notification message
     * @param int $relatedId Optional related record ID
     * @param string $relatedType Optional related record type
     * @return bool Success status
     */
    public function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, related_id, related_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType]);
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notifications for a user
     * @param int $userId User ID
     * @param int $limit Number of notifications to return
     * @return array Notification records
     */
    public function getUnreadNotifications($userId, $limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
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
     * Get all notifications for a user
     * @param int $userId User ID
     * @param int $limit Number of notifications to return
     * @return array Notification records
     */
    public function getAllNotifications($userId, $limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
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
     * Mark a notification as read
     * @param int $notificationId Notification ID
     * @return bool Success status
     */
    public function markAsRead($notificationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$notificationId]);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * @param int $userId User ID
     * @return bool Success status
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            
            $stmt->execute([$userId]);
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get notification count for a user
     * @param int $userId User ID
     * @param bool $unreadOnly Only count unread notifications
     * @return int Notification count
     */
    public function getNotificationCount($userId, $unreadOnly = true) {
        try {
            if ($unreadOnly) {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            } else {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
            }
            
            $stmt->execute([$userId]);
            return (int) $stmt->fetchColumn();
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Create a sales notification for a seller
     * @param int $sellerId Seller ID
     * @param int $orderId Order ID
     * @param float $amount Sale amount
     * @return bool Success status
     */
    public function createSalesNotification($sellerId, $orderId, $amount) {
        $title = "New Sale!";
        $message = "You have a new sale for ₱" . number_format($amount, 2) . ". Order #" . $orderId;
        return $this->createNotification($sellerId, 'sale', $title, $message, $orderId, 'order');
    }
    
    /**
     * Create a subscription renewal notification for a seller
     * @param int $sellerId Seller ID
     * @param string $endDate Subscription end date
     * @return bool Success status
     */
    public function createSubscriptionRenewalNotification($sellerId, $endDate) {
        $daysLeft = ceil((strtotime($endDate) - time()) / (60 * 60 * 24));
        $title = "Subscription Renewal Reminder";
        
        if ($daysLeft > 0) {
            $message = "Your subscription expires in $daysLeft days. Please renew to continue selling.";
        } else {
            $message = "Your subscription has expired. Please renew to continue selling.";
        }
        
        return $this->createNotification($sellerId, 'subscription', $title, $message);
    }
    
    /**
     * Create a low stock notification for a seller
     * @param int $sellerId Seller ID
     * @param int $productId Product ID
     * @param string $productName Product name
     * @param int $stock Current stock level
     * @return bool Success status
     */
    public function createLowStockNotification($sellerId, $productId, $productName, $stock) {
        $title = "Low Stock Alert";
        $message = "Your product '$productName' is running low on stock ($stock remaining).";
        return $this->createNotification($sellerId, 'low_stock', $title, $message, $productId, 'product');
    }
    
    /**
     * Create a new order notification for a seller
     * @param int $sellerId Seller ID
     * @param int $orderId Order ID
     * @return bool Success status
     */
    public function createNewOrderNotification($sellerId, $orderId) {
        $title = "New Order Received";
        $message = "You have received a new order (#$orderId). Please process it soon.";
        return $this->createNotification($sellerId, 'new_order', $title, $message, $orderId, 'order');
    }
    
    /**
     * Create a payment confirmation notification for a buyer
     * @param int $userId User ID
     * @param int $orderId Order ID
     * @param float $amount Payment amount
     * @return bool Success status
     */
    public function createPaymentConfirmationNotification($userId, $orderId, $amount) {
        $title = "Payment Confirmed";
        $message = "Your payment of ₱" . number_format($amount, 2) . " for order #$orderId has been confirmed.";
        return $this->createNotification($userId, 'payment', $title, $message, $orderId, 'order');
    }
    
    /**
     * Create a delivery status notification for a buyer
     * @param int $userId User ID
     * @param int $orderId Order ID
     * @param string $status Delivery status
     * @return bool Success status
     */
    public function createDeliveryStatusNotification($userId, $orderId, $status) {
        $title = "Order Update";
        $message = "Your order #$orderId has been updated to: $status.";
        return $this->createNotification($userId, 'delivery', $title, $message, $orderId, 'order');
    }
    
    /**
     * Create a delivery status notification for a seller
     * @param int $sellerId Seller ID
     * @param int $orderId Order ID
     * @param string $status Delivery status
     * @return bool Success status
     */
    public function createSellerDeliveryStatusNotification($sellerId, $orderId, $status) {
        $title = "Delivery Update";
        $message = "Delivery status for order #$orderId has been updated to: $status.";
        return $this->createNotification($sellerId, 'delivery', $title, $message, $orderId, 'order');
    }
    
    /**
     * Create a delivery assignment notification for a delivery partner
     * @param int $deliveryPartnerId Delivery Partner ID
     * @param int $orderId Order ID
     * @param string $buyerName Buyer name
     * @return bool Success status
     */
    public function createDeliveryAssignmentNotification($deliveryPartnerId, $orderId, $buyerName) {
        $title = "New Delivery Assignment";
        $message = "You have been assigned to deliver order #$orderId to $buyerName.";
        return $this->createNotification($deliveryPartnerId, 'delivery', $title, $message, $orderId, 'order');
    }
    
    /**
     * Create a product review notification for a seller
     * @param int $sellerId Seller ID
     * @param int $productId Product ID
     * @param string $productName Product name
     * @param int $rating Review rating
     * @return bool Success status
     */
    public function createProductReviewNotification($sellerId, $productId, $productName, $rating) {
        $title = "New Product Review";
        $message = "Your product '$productName' received a $rating-star review.";
        return $this->createNotification($sellerId, 'rating', $title, $message, $productId, 'product');
    }
    
    /**
     * Create a general system notification
     * @param int $userId User ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @return bool Success status
     */
    public function createSystemNotification($userId, $title, $message) {
        return $this->createNotification($userId, 'system', $title, $message);
    }
}