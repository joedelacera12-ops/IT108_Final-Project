<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">My Profile</h3>
        <?php 
        // Check if profile.php exists and include it
        $profileFile = __DIR__ . '/profile.php';
        if (file_exists($profileFile)) {
            include $profileFile;
        } else {
            echo '<div class="alert alert-info">Profile management is being prepared.</div>';
        }
        ?>
    </div>
</div>