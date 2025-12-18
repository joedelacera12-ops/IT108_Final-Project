<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

function json_error(string $message, int $status = 500): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function json_success($data = null): void {
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ensure database connection
try {
    $pdo = get_db();
    // Test connection
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();
} catch (Throwable $e) {
    error_log('Database connection error in notifications.php: ' . $e->getMessage());
    json_error('Database connection failed. Please check server logs.', 500);
}

// Check if user is authenticated
$sessionUser = current_user();
if (!$sessionUser) {
    json_error('Authentication required', 401);
}

// Get full user data from database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$sessionUser['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        json_error('User not found', 404);
    }
} catch (Throwable $e) {
    json_error('Failed to retrieve user data: ' . $e->getMessage(), 500);
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        try {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;
            
            // Get notifications for the user
            $stmt = $pdo->prepare("
                SELECT id, title, message, type, is_read, created_at, read_at
                FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user['id'], $limit, $offset]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
            $countStmt->execute([$user['id']]);
            $totalCount = (int)$countStmt->fetchColumn();
            
            json_success([
                'notifications' => $notifications,
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount
            ]);
        } catch (Throwable $e) {
            json_error('Failed to retrieve notifications: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'mark_as_read':
        try {
            $notificationId = (int)($_POST['notification_id'] ?? 0);
            
            if (!$notificationId) {
                json_error('Notification ID is required', 400);
            }
            
            // Verify the notification belongs to the user
            $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $user['id']]);
            $exists = $stmt->fetchColumn();
            
            if (!$exists) {
                json_error('Notification not found or access denied', 404);
            }
            
            // Mark as read
            $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
            $updateStmt->execute([$notificationId]);
            
            json_success(['message' => 'Notification marked as read']);
        } catch (Throwable $e) {
            json_error('Failed to mark notification as read: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'mark_all_as_read':
        try {
            // Mark all notifications as read for the user
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['id']]);
            
            $rowCount = $stmt->rowCount();
            
            json_success(['message' => "Marked {$rowCount} notifications as read"]);
        } catch (Throwable $e) {
            json_error('Failed to mark notifications as read: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        json_error('Invalid action', 400);
}