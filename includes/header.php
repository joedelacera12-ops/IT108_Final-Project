<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?php echo isset($title) ? htmlspecialchars($title) : 'AgriSea Marketplace'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/ecommerce_farmers_fishers/assets/css/style.css">
  <?php if (isset($additional_css)): ?>
    <?php foreach ($additional_css as $css): ?>
      <link rel="stylesheet" href="<?php echo $css; ?>">
    <?php endforeach; ?>
  <?php endif; ?>
  <style>
    /* Prevent horizontal scrolling */
    body, html {
      overflow-x: hidden;
      max-width: 100vw;
      box-sizing: border-box;
    }
    
    /* Ensure navbar doesn't cause overflow */
    .navbar {
      max-width: 100vw;
      width: 100%;
      box-sizing: border-box;
    }
    
    /* Override compact navbar styles to make it normal size */
    nav.navbar.navbar-expand-lg {
      padding: 0.75rem 1rem !important; /* Increased from 0.5rem 1rem */
    }
    
    .navbar-nav .nav-link {
      padding: 0.5rem 1rem !important; /* Bootstrap default - was 0.5rem 0.75rem */
    }
    
    /* Override responsive styles that make navbar compact on smaller screens */
    @media (max-width: 992px) {
      nav.navbar.navbar-expand-lg {
        padding: 0.75rem 1rem !important; /* Override 0.5rem */
      }
      
      .navbar-nav .nav-link {
        padding: 0.5rem 1rem !important; /* Override 0.5rem 0.5rem */
      }
    }
    
    @media (max-width: 768px) {
      nav.navbar.navbar-expand-lg {
        padding: 0.75rem 1rem !important; /* Override 0.5rem */
      }
      
      .navbar-nav .nav-link {
        padding: 0.5rem 1rem !important; /* Override 0.4rem 0.4rem */
      }
    }
    
    @media (max-width: 576px) {
      nav.navbar.navbar-expand-lg {
        padding: 0.75rem 1rem !important; /* Override 0.4rem */
      }
      
      .navbar-nav .nav-link {
        padding: 0.5rem 1rem !important; /* Override 0.3rem 0.3rem */
      }
    }
    
    .container, .container-fluid {
      max-width: 100vw;
      padding-left: 15px;
      padding-right: 15px;
      margin-left: auto;
      margin-right: auto;
      box-sizing: border-box;
    }
    
    /* Cart icon responsive */
    .cart-count {
      font-size: 0.7rem;
      min-width: 1.2rem;
      height: 1.2rem;
      line-height: 1.2rem;
      box-sizing: border-box;
    }
    
    /* Notification badge */
    .notification-badge {
      font-size: 0.7rem;
      min-width: 1.2rem;
      height: 1.2rem;
      line-height: 1.2rem;
      box-sizing: border-box;
    }
    
    /* Notification dropdown */
    .notification-dropdown {
      min-width: 300px;
      max-height: 400px;
      overflow-y: auto;
    }
    
    .notification-item {
      border-bottom: 1px solid #eee;
      padding: 10px 15px;
    }
    
    .notification-item:last-child {
      border-bottom: none;
    }
    
    .notification-item.unread {
      background-color: #f8f9fa;
    }
    
    .notification-icon {
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      margin-right: 10px;
    }
    
    /* Mobile adjustments */
    @media (max-width: 768px) {
      .cart-count, .notification-badge {
        font-size: 0.6rem;
        min-width: 1rem;
        height: 1rem;
        line-height: 1rem;
      }
      
      .container, .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
      }
      
      .notification-dropdown {
        min-width: 250px;
        right: 0;
        left: auto;
      }
    }
    
    @media (max-width: 576px) {
      .cart-count, .notification-badge {
        font-size: 0.5rem;
        min-width: 0.9rem;
        height: 0.9rem;
        line-height: 0.9rem;
      }
      
      .container, .container-fluid {
        padding-left: 5px;
        padding-right: 5px;
      }
    }
    
    .nav-link.active {
      background: linear-gradient(90deg, rgba(40, 167, 69, 0.2), rgba(32, 201, 151, 0.2));
      border-radius: 0.375rem;
      position: relative;
    }
    
    .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 50%;
      transform: translateX(-50%);
      width: 70%;
      height: 3px;
      background: #28a745;
      border-radius: 3px;
    }
    
    /* Ensure Font Awesome icons are solid and visible */
    .nav-link i.fas, .nav-link i.far, .nav-link i.fal, .nav-link i.fad {
      color: inherit !important;
      opacity: 1 !important;
      font-weight: 900; /* Make icons solid */
    }
    
    @media (max-width: 991px) {
      .nav-link.active::after {
        bottom: 0;
        left: 0;
        transform: none;
        width: 3px;
        height: 70%;
      }
    }
  </style>
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="padding: 0.75rem 1rem;">
    <div class="container-fluid">
      <a class="navbar-brand" href="/ecommerce_farmers_fishers/php/index.php"><strong class="text-success">Agrisea</strong> Marketplace</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a href="/ecommerce_farmers_fishers/php/index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">Home</a></li>
          <li class="nav-item"><a href="/ecommerce_farmers_fishers/php/market.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) === 'market.php') ? 'active' : ''; ?>">Marketplace</a></li>
          <?php if (isset($_SESSION['user'])): ?>
            <?php if (isset($_SESSION['user']['role_id'])): ?>
              <?php if ($_SESSION['user']['role_id'] == 1): ?>
                <li class="nav-item"><a href="/ecommerce_farmers_fishers/admin/dashboard.php" class="nav-link">Dashboard</a></li>
              <?php elseif ($_SESSION['user']['role_id'] == 2): ?>
                <li class="nav-item"><a href="/ecommerce_farmers_fishers/seller/dashboard.php" class="nav-link">Dashboard</a></li>
              <?php elseif ($_SESSION['user']['role_id'] == 4): ?>
                <li class="nav-item"><a href="/ecommerce_farmers_fishers/delivery_partner/dashboard.php" class="nav-link">Dashboard</a></li>
              <?php else: ?>
                <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="nav-link">Dashboard</a></li>
              <?php endif; ?>
            <?php else: ?>
              <li class="nav-item"><a href="/ecommerce_farmers_fishers/buyer/dashboard.php" class="nav-link">Dashboard</a></li>
            <?php endif; ?>
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/chat.php" class="nav-link">Chat</a></li>
            <li class="nav-item position-relative">
              <a href="/ecommerce_farmers_fishers/buyer/cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
              </a>
            </li>
            <li class="nav-item position-relative">
              <a href="#" class="nav-link" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-bell"></i>
                <span class="notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none;">0</span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                  <span>Notifications</span>
                  <a href="/ecommerce_farmers_fishers/notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <div id="notificationList">
                  <li class="text-center p-3">
                    <div class="spinner-border spinner-border-sm" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </li>
                </div>
              </ul>
            </li>
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/logout.php" class="nav-link">Logout</a></li>
          <?php else: ?>
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/php/login.php" class="nav-link">Login</a></li>
            <li class="nav-item"><a href="/ecommerce_farmers_fishers/php/register.php" class="nav-link">Register</a></li>
          <?php endif; ?>

        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="content-wrapper">
  
  <!-- Include scripts -->
  <script src="/ecommerce_farmers_fishers/assets/js/script.js"></script>
  <script src="/ecommerce_farmers_fishers/assets/js/notifications.js"></script>
  <script>
  // Global variable to indicate user is logged in
  const isLoggedIn = <?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>;
  
  // Update cart count on page load for all pages
  document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
  });
  </script>
</body>
</html>