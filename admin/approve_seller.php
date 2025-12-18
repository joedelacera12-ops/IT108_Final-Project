<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/redirect_handler.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/ecommerce_farmers_fishers/admin/dashboard.php');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'reject'])) {
    redirect_with_error('/ecommerce_farmers_fishers/admin/dashboard.php', 'invalid');
}

$pdo = get_db();

// Resolve seller role id if available
$sellerRoleId = null;
try {
    $r = $pdo->prepare('SELECT id FROM user_roles WHERE name = ? LIMIT 1');
    $r->execute(['seller']);
    $sellerRoleId = $r->fetchColumn();
} catch (Exception $e) {
    // ignore
}

try {
    $status = $action === 'approve' ? 'approved' : 'rejected';
    if ($sellerRoleId) {
        $stmt = $pdo->prepare('UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND role_id = ?');
        $stmt->execute([$status, $id, $sellerRoleId]);
    } else {
        // legacy: role column
        $stmt = $pdo->prepare('UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND role = "seller"');
        $stmt->execute([$status, $id]);
    }

    redirect_with_success('/ecommerce_farmers_fishers/admin/dashboard.php', 'updated=1');

} catch (Exception $e) {
    redirect_with_error('/ecommerce_farmers_fishers/admin/dashboard.php', 'server');
}