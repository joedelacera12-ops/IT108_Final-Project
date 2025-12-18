<?php
/**
 * Subscription Reminder Script
 * Checks for expiring subscriptions and sends notifications
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

try {
    $pdo = get_db();
    
    // Find subscriptions expiring in the next 7 days
    $stmt = $pdo->prepare("
        SELECT s.*, u.first_name, u.last_name, u.email 
        FROM subscriptions s
        JOIN users u ON s.seller_id = u.id
        WHERE s.status = 'active' 
        AND s.end_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        AND s.end_date >= NOW()
    ");
    
    $stmt->execute();
    $expiringSubscriptions = $stmt->fetchAll();
    
    $notificationSystem = new NotificationSystem();
    
    foreach ($expiringSubscriptions as $subscription) {
        // Create notification for seller
        $notificationSystem->createSubscriptionRenewalNotification(
            $subscription['seller_id'],
            $subscription['end_date']
        );
        
        // Log the notification
        error_log("Subscription renewal notification sent to seller ID: " . $subscription['seller_id']);
    }
    
    echo "Processed " . count($expiringSubscriptions) . " expiring subscriptions.\n";
    
} catch (Exception $e) {
    error_log("Subscription reminder script error: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}
?>