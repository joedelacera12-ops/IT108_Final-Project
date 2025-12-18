<?php
$page_title = "Page Not Found";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-content">
                <h1 class="display-1 text-danger">404</h1>
                <h2>Page Not Found</h2>
                <p class="lead">Sorry, the page you are looking for could not be found.</p>
                <p>The requested URL was not found on this server.</p>
                
                <div class="mt-4">
                    <a href="/ecommerce_farmers_fishers/" class="btn btn-success btn-lg me-2">
                        <i class="fas fa-home me-2"></i>Go Home
                    </a>
                    <a href="/ecommerce_farmers_fishers/php/register.php" class="btn btn-outline-success btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/includes/footer.php';
?>