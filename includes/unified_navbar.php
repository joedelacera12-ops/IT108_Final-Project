<?php
// Unified Navbar Component
// This file provides a consistent navigation bar across all dashboards

// Get current user
$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
$roleId = $user['role_id'] ?? null;

// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_tab = $_GET['tab'] ?? 'overview';

// Define navigation links for each role
$nav_links = [];

if ($role === 'admin') {
    $nav_links = [
        ['title' => 'Manage Users', 'url' => '/ecommerce_farmers_fishers/admin/dashboard.php?tab=accounts', 'tab' => 'accounts', 'icon' => 'fas fa-users'],
        ['title' => 'Products', 'url' => '/ecommerce_farmers_fishers/admin/dashboard.php?tab=products', 'tab' => 'products', 'icon' => 'fas fa-box-open'],
        ['title' => 'Orders', 'url' => '/ecommerce_farmers_fishers/admin/dashboard.php?tab=orders', 'tab' => 'orders', 'icon' => 'fas fa-shopping-cart'],
        ['title' => 'Reports', 'url' => '/ecommerce_farmers_fishers/admin/dashboard.php?tab=reports', 'tab' => 'reports', 'icon' => 'fas fa-file-alt'],
        ['title' => 'Messages', 'url' => '/ecommerce_farmers_fishers/admin/dashboard.php?tab=messages', 'tab' => 'messages', 'icon' => 'fas fa-comments']
    ];
} elseif ($role === 'seller') {
    $nav_links = [
        ['title' => 'My Products', 'url' => '/ecommerce_farmers_fishers/seller/dashboard.php?tab=products', 'tab' => 'products', 'icon' => 'fas fa-box-open'],
        ['title' => 'Add Product', 'url' => '/ecommerce_farmers_fishers/seller/dashboard.php?tab=addProduct', 'tab' => 'addProduct', 'icon' => 'fas fa-plus-circle'],
        ['title' => 'Orders', 'url' => '/ecommerce_farmers_fishers/seller/dashboard.php?tab=orders', 'tab' => 'orders', 'icon' => 'fas fa-shopping-cart'],
        ['title' => 'Analytics', 'url' => '/ecommerce_farmers_fishers/seller/dashboard.php?tab=analytics', 'tab' => 'analytics', 'icon' => 'fas fa-chart-bar'],
        ['title' => 'Messages', 'url' => '/ecommerce_farmers_fishers/seller/dashboard.php?tab=messages', 'tab' => 'messages', 'icon' => 'fas fa-comments']
    ];
} elseif ($role === 'buyer') {
    $nav_links = [
        ['title' => 'Marketplace', 'url' => '/ecommerce_farmers_fishers/buyer/dashboard.php?tab=marketplace', 'tab' => 'marketplace', 'icon' => 'fas fa-store'],
        ['title' => 'Cart', 'url' => '/ecommerce_farmers_fishers/buyer/dashboard.php?tab=cart', 'tab' => 'cart', 'icon' => 'fas fa-shopping-cart'],
        ['title' => 'Orders', 'url' => '/ecommerce_farmers_fishers/buyer/dashboard.php?tab=orders', 'tab' => 'orders', 'icon' => 'fas fa-shopping-bag'],
        ['title' => 'Messages', 'url' => '/ecommerce_farmers_fishers/buyer/dashboard.php?tab=messages', 'tab' => 'messages', 'icon' => 'fas fa-comments']
    ];
} elseif ($role === 'delivery_partner') {
    $nav_links = [
        ['title' => 'Assigned Deliveries', 'url' => '/ecommerce_farmers_fishers/delivery_partner/dashboard.php?tab=overview', 'tab' => 'overview', 'icon' => 'fas fa-truck'],
        ['title' => 'Delivery History', 'url' => '/ecommerce_farmers_fishers/delivery_partner/dashboard.php?tab=history', 'tab' => 'history', 'icon' => 'fas fa-history'],
        ['title' => 'Messages', 'url' => '/ecommerce_farmers_fishers/delivery_partner/dashboard.php?tab=messages', 'tab' => 'messages', 'icon' => 'fas fa-comments']
    ];
} else {
    // Default links for other roles or guests
    $nav_links = [
        ['title' => 'Home', 'url' => '/ecommerce_farmers_fishers/php/index.php', 'icon' => 'fas fa-home'],
        ['title' => 'Marketplace', 'url' => '/ecommerce_farmers_fishers/php/market.php', 'icon' => 'fas fa-store']
    ];
}
?>

<!-- Unified Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm unified-navbar">
    <div class="container-fluid">
        <a class="navbar-brand" href="/ecommerce_farmers_fishers/<?php echo $role ? $role . '/dashboard.php' : 'php/index.php'; ?>">
            <strong class="text-success">Agrisea</strong> Marketplace
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#unifiedNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="unifiedNavbar">
            <ul class="navbar-nav me-auto">
                <?php foreach ($nav_links as $link): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_tab === $link['tab']) ? 'active' : ''; ?>" 
                           href="<?php echo $link['url']; ?>"
                           data-tab="<?php echo $link['tab']; ?>">
                            <?php if (isset($link['icon'])): ?>
                                <i class="<?php echo $link['icon']; ?> me-1"></i>
                            <?php endif; ?>
                            <?php echo $link['title']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if ($user): ?>
                <ul class="navbar-nav ms-auto">
                    <?php if ($role === 'buyer'): ?>
                        <li class="nav-item position-relative">
                            <a href="/ecommerce_farmers_fishers/buyer/dashboard.php?tab=cart" class="nav-link">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php 
                            // Get profile image for user
                            $profileImage = $user['profile_image'] ?? null;
                            if ($profileImage && file_exists(__DIR__ . '/..' . $profileImage)) {
                                $imageUrl = '/ecommerce_farmers_fishers' . $profileImage;
                            } else {
                                $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-placeholder.png';
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Profile" class="rounded-circle me-1" style="width: 24px; height: 24px; object-fit: cover;">
                            <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/ecommerce_farmers_fishers/<?php echo $role; ?>/<?php echo ($role === 'admin') ? 'dashboard.php?tab=profile' : 'profile.php'; ?>"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <li><a class="dropdown-item" href="/ecommerce_farmers_fishers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
                
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/ecommerce_farmers_fishers/php/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/ecommerce_farmers_fishers/php/register.php">Register</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Handle active state for navigation links without jQuery
document.addEventListener('DOMContentLoaded', function() {
    // Set active class based on current tab
    const currentTab = '<?php echo $current_tab; ?>';
    const activeLink = document.querySelector('.nav-link[data-tab="' + currentTab + '"]');
    if (activeLink) {
        activeLink.classList.add('active');
    }
    
    // Update cart count for buyers
    <?php if ($role === 'buyer'): ?>
    updateCartCount();
    <?php endif; ?>
});

<?php if ($role === 'buyer'): ?>
function updateCartCount() {
    fetch('/ecommerce_farmers_fishers/buyer/cart_count.php')
        .then(response => response.json())
        .then(data => {
            const count = parseInt(data.count) || 0;
            const badge = document.querySelector('.cart-count');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(() => {
            const badge = document.querySelector('.cart-count');
            if (badge) {
                badge.style.display = 'none';
            }
        });
}
<?php endif; ?>
</script>