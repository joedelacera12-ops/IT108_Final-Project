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
        <h3 class="section-title">My Favorites</h3>
        <?php 
        // Check if favorites.php exists and include it
        $favoritesFile = __DIR__ . '/favorites.php';
        if (file_exists($favoritesFile)) {
            include $favoritesFile;
        } else {
            echo '<div class="alert alert-info">Favorites functionality is being prepared.</div>';
        }
        ?>
    </div>
</div>