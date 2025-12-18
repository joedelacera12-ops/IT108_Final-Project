<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">Marketplace</h3>
        <?php include __DIR__ . '/../includes/marketplace.php'; ?>
    </div>
</div>