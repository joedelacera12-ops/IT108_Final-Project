<?php
$page_title = "Access Denied";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-content">
                <h1 class="display-1 text-danger">403</h1>
                <h2>Access Denied</h2>
                <p class="lead">Sorry, you don't have permission to access this page.</p>
                <p>You might need to log in or contact an administrator for access.</p>
                
                <div class="mt-4">
                    <a href="/ecommerce_farmers_fishers/" class="btn btn-success btn-lg me-2">
                        <i class="fas fa-home me-2"></i>Go Home
                    </a>
                    <a href="/ecommerce_farmers_fishers/php/login.php" class="btn btn-outline-success btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/includes/footer.php';
?>