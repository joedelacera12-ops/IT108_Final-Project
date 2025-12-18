<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notification_system.php';

header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $notificationSystem = new NotificationSystem();
    $notifications = $notificationSystem->getUnreadNotifications($user['id'], 5);
    
    // Format notifications for display
    $formattedNotifications = [];
    foreach ($notifications as $notification) {
        $typeLabels = [
            'sale' => 'Sale',
            'subscription' => 'Subscription',
            'low_stock' => 'Low Stock',
            'new_order' => 'New Order',
            'payment' => 'Payment',
            'delivery' => 'Delivery',
            'rating' => 'Rating',
            'system' => 'System'
        ];
        
        $icons = [
            'sale' => 'fa-shopping-cart',
            'subscription' => 'fa-crown',
            'low_stock' => 'fa-exclamation-triangle',
            'new_order' => 'fa-box',
            'payment' => 'fa-credit-card',
            'delivery' => 'fa-truck',
            'rating' => 'fa-star',
            'system' => 'fa-bell'
        ];
        
        $formattedNotifications[] = [
            'id' => $notification['id'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'type' => $notification['type'],
            'type_label' => isset($typeLabels[$notification['type']]) ? $typeLabels[$notification['type']] : 'General',
            'icon' => isset($icons[$notification['type']]) ? $icons[$notification['type']] : 'fa-bell',
            'created_at' => date('M j, Y g:i A', strtotime($notification['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $formattedNotifications,
        'count' => count($formattedNotifications)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}