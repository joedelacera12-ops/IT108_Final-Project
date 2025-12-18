<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$pdo = get_db();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest($pdo, $user);
        break;
    case 'POST':
        handlePostRequest($pdo, $user);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        break;
}

function handleGetRequest($pdo, $user) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            getDeliveryPartners($pdo);
            break;
        case 'order_delivery':
            getOrderDelivery($pdo, $user);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function handlePostRequest($pdo, $user) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'assign_partner':
            assignDeliveryPartner($pdo, $user);
            break;
        case 'update_status':
            updateDeliveryStatus($pdo, $user);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function getDeliveryPartners($pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, name, phone, email FROM delivery_partners WHERE is_active = 1 ORDER BY name');
        $stmt->execute();
        $partners = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $partners]);
    } catch (Exception $e) {
        error_log("Error fetching delivery partners: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch delivery partners']);
    }
}

function getOrderDelivery($pdo, $user) {
    $order_id = $_GET['order_id'] ?? null;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        return;
    }
    
    try {
        // Check if the order belongs to this seller
        $stmt = $pdo->prepare('
            SELECT od.*, dp.name as partner_name, o.status as order_status
            FROM orders o
            LEFT JOIN order_deliveries od ON o.delivery_id = od.id
            LEFT JOIN delivery_partners dp ON od.delivery_partner_id = dp.id
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ? AND oi.seller_id = ?
            LIMIT 1
        ');
        $stmt->execute([$order_id, $user['id']]);
        $delivery = $stmt->fetch();
        
        if (!$delivery) {
            echo json_encode(['success' => false, 'error' => 'Order not found or unauthorized']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $delivery]);
    } catch (Exception $e) {
        error_log("Error fetching order delivery: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch order delivery information']);
    }
}

function assignDeliveryPartner($pdo, $user) {
    $order_id = $_POST['order_id'] ?? null;
    $partner_id = $_POST['partner_id'] ?? null;
    
    if (!$order_id || !$partner_id) {
        echo json_encode(['success' => false, 'error' => 'Order ID and Partner ID are required']);
        return;
    }
    
    try {
        // Check if the order belongs to this seller and is in approved status
        $stmt = $pdo->prepare('
            SELECT o.id, o.status
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ? AND oi.seller_id = ? AND o.status = ?
            LIMIT 1
        ');
        $stmt->execute([$order_id, $user['id'], 'approved']);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found, unauthorized, or not in approved status']);
            return;
        }
        
        // Check if delivery partner exists and is active
        $stmt = $pdo->prepare('SELECT id FROM delivery_partners WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$partner_id]);
        $partner = $stmt->fetch();
        
        if (!$partner) {
            echo json_encode(['success' => false, 'error' => 'Invalid delivery partner']);
            return;
        }
        
        // Create or update delivery record
        $pdo->beginTransaction();
        
        // Check if order already has a delivery record
        $stmt = $pdo->prepare('SELECT delivery_id FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$order_id]);
        $delivery_id = $stmt->fetchColumn();
        
        if ($delivery_id) {
            // Update existing delivery record
            $stmt = $pdo->prepare('
                UPDATE order_deliveries 
                SET delivery_partner_id = ?, status = ?, assigned_at = NOW(), updated_at = NOW() 
                WHERE id = ?
            ');
            $stmt->execute([$partner_id, 'assigned', $delivery_id]);
        } else {
            // Create new delivery record
            $stmt = $pdo->prepare('
                INSERT INTO order_deliveries (order_id, delivery_partner_id, status, assigned_at) 
                VALUES (?, ?, ?, NOW())
            ');
            $stmt->execute([$order_id, $partner_id, 'assigned']);
            $delivery_id = $pdo->lastInsertId();
            
            // Update order with delivery_id
            $stmt = $pdo->prepare('UPDATE orders SET delivery_id = ? WHERE id = ?');
            $stmt->execute([$delivery_id, $order_id]);
        }
        
        // Update order status to ready_to_ship
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute(['ready_to_ship', $order_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Delivery partner assigned successfully. Order status updated to Ready to Ship.']);
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error assigning delivery partner: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to assign delivery partner']);
    }
}

function updateDeliveryStatus($pdo, $user) {
    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$order_id || !$status) {
        echo json_encode(['success' => false, 'error' => 'Order ID and status are required']);
        return;
    }
    
    // Validate status
    $valid_statuses = ['assigned', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        return;
    }
    
    try {
        // Check if the order belongs to this seller
        $stmt = $pdo->prepare('
            SELECT o.id, o.status, o.delivery_id, o.user_id
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ? AND oi.seller_id = ?
            LIMIT 1
        ');
        $stmt->execute([$order_id, $user['id']]);
        $order = $stmt->fetch();
        
        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found or unauthorized']);
            return;
        }
        
        if (!$order['delivery_id']) {
            echo json_encode(['success' => false, 'error' => 'Order has no delivery assigned']);
            return;
        }
        
        // Update delivery status
        $timestamp_field = '';
        switch ($status) {
            case 'picked_up':
                $timestamp_field = 'picked_up_at';
                break;
            case 'out_for_delivery':
                $timestamp_field = 'out_for_delivery_at';
                break;
            case 'delivered':
                $timestamp_field = 'delivered_at';
                break;
        }
        
        $sql = "UPDATE order_deliveries SET status = ?, updated_at = NOW()";
        $params = [$status, $order['delivery_id']];
        
        if ($timestamp_field) {
            $sql .= ", $timestamp_field = NOW()";
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $order['delivery_id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // If status is delivered, also update order status and send notification
        if ($status === 'delivered') {
            // Update order status to shipped (buyer needs to confirm receipt)
            $stmt = $pdo->prepare('UPDATE orders SET status = ?, actual_delivery = NOW(), updated_at = NOW() WHERE id = ?');
            $stmt->execute(['shipped', $order_id]);
            
            // Send notification to buyer
            require_once __DIR__ . '/../includes/notification_system.php';
            $notif = new NotificationSystem();
            $notif->createDeliveryStatusNotification($order['user_id'], $order_id, 'delivered');
        }
        
        echo json_encode(['success' => true, 'message' => 'Delivery status updated successfully']);
    } catch (Exception $e) {
        error_log("Error updating delivery status: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to update delivery status']);
    }
}
?>