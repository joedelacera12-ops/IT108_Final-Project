<?php
// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Initialize variables
$sellerCount = 0;
$buyerCount = 0;
$productCount = 0;
$totalValue = 0;

try {
    // Try to connect to database
    $pdo = get_db();
    
    // Fetch real statistics
    $sellerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
    $buyerCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    
    // Calculate total transaction value
    $totalValue = 0;
    try {
        $totalValue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
    } catch (Exception $e) {
        $totalValue = 0;
    }
} catch (Exception $e) {
    // Database connection failed - use default values
    $sellerCount = 120;
    $buyerCount = 850;
    $productCount = 340;
    $totalValue = 125000;
}

// Set page title
$page_title = 'AgriSea Marketplace - Empowering Farmers & Fishers';

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<style>
  :root {
    --primary-color: #0f8644;
    --primary-hover: #0a6b35;
    --secondary-color: #0d6efd;
    --accent-color: #ffc107;
  }
  
  /* Enhanced Hero Section */
  html, body {
    height: 100%;
    scroll-behavior: smooth;
    overflow-x: hidden;
    overflow-y: auto;
    max-width: 100vw;
  }
  .content-wrapper {
    min-height: 100vh;
    max-width: 100vw;
    overflow-x: hidden;
  }
  .hero {
    background: linear-gradient(rgba(15, 134, 68, 0.85), rgba(10, 107, 53, 0.9)), 
                url('/ecommerce_farmers_fishers/assets/images/hero/image.jpg') center center / cover no-repeat fixed;
    position: relative;
    overflow: hidden;
    padding: 150px 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    animation: fadeIn 1.5s ease-out;
    max-width: 100vw;
  }
  
  .hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="%23fff" opacity="0.05"/><circle cx="75" cy="75" r="1" fill="%23fff" opacity="0.05"/><circle cx="50" cy="10" r="0.5" fill="%23fff" opacity="0.05"/><circle cx="10" cy="60" r="0.5" fill="%23fff" opacity="0.05"/><circle cx="90" cy="40" r="0.5" fill="%23fff" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    pointer-events: none;
  }
  
  .hero-content {
    animation: fadeInUp 1s ease-out;
  }
  
  .hero h1 {
    font-weight: 800;
    font-size: 3.5rem;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    animation: slideInLeft 1s ease-out;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .hero p {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    max-width: 700px;
    animation: slideInLeft 1s ease-out 0.3s both;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  /* Features Section */
  .features-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #f8fafc 0%, #f0f5f9 100%);
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .feature-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    animation: fadeInUp 0.6s ease-out;
    height: 100%;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .feature-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
  }
  
  .feature-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    font-size: 2rem;
    margin: 0 auto 20px;
    animation: pulse 2s infinite;
    opacity: 1; /* Make icons solid */
    font-weight: 900; /* Make icons bold */
  }
  
  .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    display: block;
  }
  
  /* Testimonials Section */
  .testimonials-section {
    padding: 100px 0;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .testimonial-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    padding: 2rem;
    height: 100%;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .testimonial-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
  }
  
  /* CTA Section */
  .cta-section {
    padding: 100px 0;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .cta-section h2 {
    font-weight: 700;
  }
  
  .cta-section p {
    font-size: 1.2rem;
    opacity: 0.9;
  }
  
  /* Animations */
  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }
  
  @keyframes slideInLeft {
    from {
      opacity: 0;
      transform: translateX(-50px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }
  
  @keyframes pulse {
    0% {
      transform: scale(1);
    }
    50% {
      transform: scale(1.05);
    }
    100% {
      transform: scale(1);
    }
  }
  
  @keyframes float {
    0% {
      transform: translateY(0px);
    }
    50% {
      transform: translateY(-10px);
    }
    100% {
      transform: translateY(0px);
    }
  }
  
  .animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
  }
  
  .animate-on-scroll.visible {
    opacity: 1;
    transform: translateY(0);
  }
  
  /* Hero Stats */
  .hero-stats h3 {
    font-size: 1.2rem;
    font-weight: 700;
  }
  
  .hero-stats small {
    font-size: 0.8rem;
    opacity: 0.8;
  }
  
  /* Buttons */
  .btn-animate {
    position: relative;
    overflow: hidden;
  }
  
  .btn-animate::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%);
    transform-origin: 50% 50%;
  }
  
  .btn-animate:focus:not(:active)::after {
    animation: ripple 1s ease-out;
  }
  
  @keyframes ripple {
    0% {
      transform: scale(0, 0);
      opacity: 0.5;
    }
    100% {
      transform: scale(50, 50);
      opacity: 0;
    }
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .hero h1 {
      font-size: 2.5rem;
    }
    
    .hero p {
      font-size: 1rem;
    }
    
    .feature-card {
      margin-bottom: 2rem;
      max-width: 100vw;
      overflow-x: hidden;
    }
    
    .container {
      padding-left: 15px;
      padding-right: 15px;
      max-width: 100vw;
      overflow-x: hidden;
    }
    
    .row {
      margin-left: 0;
      margin-right: 0;
      max-width: 100vw;
      overflow-x: hidden;
    }
  }
  
  @media (max-width: 576px) {
    .hero h1 {
      font-size: 2rem;
    }
    
    .hero p {
      font-size: 0.9rem;
    }
    
    .container {
      padding-left: 10px;
      padding-right: 10px;
    }
    
    .btn-lg {
      font-size: 0.9rem;
      padding: 0.5rem 1rem;
    }
  }
</style>

<!-- Enhanced Hero Section -->
<header class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-8 hero-content">
        <h1 class="mb-4" style="animation: float 3s ease-in-out infinite;">Fresh from Farm to Table, Sea to Shore</h1>
        <p class="lead mb-5">Your direct connection to the freshest produce from local farmers and fishers. Shop directly from those who grow and catch your food.</p>
        <div class="d-flex flex-wrap gap-3">
          <a href="/ecommerce_farmers_fishers/php/register.php?accttype=buyer" class="btn btn-success btn-lg px-4 py-3 btn-animate shadow-lg" style="animation: float 3s ease-in-out infinite;">
            <i class="fas fa-shopping-cart me-2"></i>Register to Buy Fresh Products
          </a>
          <a href="/ecommerce_farmers_fishers/php/register.php?accttype=farmer" class="btn btn-outline-light btn-lg px-4 py-3 btn-animate shadow-lg" style="animation: float 3s ease-in-out infinite 0.5s;">
            <i class="fas fa-leaf me-2"></i>Sell from Farm
          </a>
          <a href="/ecommerce_farmers_fishers/php/register.php?accttype=fisher" class="btn btn-outline-light btn-lg px-4 py-3 btn-animate shadow-lg" style="animation: float 3s ease-in-out infinite 1s;">
            <i class="fas fa-water me-2"></i>Sell from Sea
          </a>
        </div>
        <div class="mt-5">
          <a href="/ecommerce_farmers_fishers/php/market.php" class="btn btn-outline-light btn-lg px-4 py-3">
            <i class="fas fa-store me-2"></i>Browse Marketplace
          </a>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Features Section -->
<section class="features-section py-5">
  <div class="container">
    <div class="row mb-5">
      <div class="col-12 text-center">
        <h2 class="display-5 fw-bold text-success mb-3">Why Choose AgriSea Marketplace?</h2>
        <p class="lead text-muted">Empowering Filipino farmers and fishers while connecting consumers with the freshest local produce</p>
      </div>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card feature-card h-100 p-4 text-center animate-on-scroll">
          <div class="feature-icon">
            <i class="fas fa-percentage"></i>
          </div>
          <h4 class="fw-bold mb-3">Support Local Producers</h4>
          <p class="text-muted">Directly support Filipino farmers and fishers by purchasing their fresh produce. Keep local agriculture thriving while enjoying quality products.</p>
          <div class="mt-3">
            <span class="badge bg-success">100% Local</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card h-100 p-4 text-center animate-on-scroll">
          <div class="feature-icon">
            <i class="fas fa-truck"></i>
          </div>
          <h4 class="fw-bold mb-3">Direct Delivery</h4>
          <p class="text-muted">Coordinate direct delivery from producers to your doorstep. Fresh from farm to table with minimal handling.</p>
          <div class="mt-3">
            <span class="badge bg-info">Flexible Delivery</span>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card feature-card h-100 p-4 text-center animate-on-scroll">
          <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h4 class="fw-bold mb-3">Verified Quality</h4>
          <p class="text-muted">All products are verified for quality and freshness. Transparent ratings help you make informed decisions.</p>
          <div class="mt-3">
            <span class="badge bg-warning">Verified Quality</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  // Animation on scroll
  document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, {
      threshold: 0.1
    });
    
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
      observer.observe(el);
    });
    
    // Enhance feature card hover effects
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px)';
        this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.1)';
      });
      
      card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.05)';
      });
    });
  });
  

  
  // Load featured products when page loads
  loadFeaturedProducts();

async function loadFeaturedProducts() {
  try {
    const response = await fetch('/ecommerce_farmers_fishers/api/products.php?limit=6');
    const data = await response.json();
    
    if (data.success && data.products.length > 0) {
      const container = document.getElementById('featuredProducts');
      container.innerHTML = '';
      
      data.products.forEach(product => {
        const col = document.createElement('div');
        col.className = 'col-lg-4 col-md-6';
        
        // Create badges
        const badges = [];
        if (product.organic) badges.push('<span class="badge bg-success me-1">Organic</span>');
        if (product.fresh) badges.push('<span class="badge bg-info me-1">Fresh</span>');
        if (product.local) badges.push('<span class="badge bg-warning me-1">Local</span>');
        
        col.innerHTML = `
          <div class="card h-100 shadow-sm product-card">
            <img src="${product.image}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
            <div class="card-body d-flex flex-column">
              <div class="mb-2">${badges.join('')}</div>
              <h5 class="card-title">${product.name}</h5>
              <p class="card-text text-muted small">${product.description}</p>
              <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="h5 text-success fw-bold mb-0">₱${product.price}</span>
                  <small class="text-muted">${product.seller}</small>
                </div>
                <button class="btn btn-success w-100" onclick="window.location.href='/ecommerce_farmers_fishers/php/market.php'">
                  <i class="fas fa-shopping-cart me-2"></i>View Details
                </button>
              </div>
            </div>
          </div>
        `;
        
        container.appendChild(col);
      });
    } else {
      document.getElementById('featuredProducts').innerHTML = `
        <div class="col-12 text-center">
          <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
          <h5 class="text-muted">No featured products available</h5>
          <p class="text-muted">Check back later for fresh products from our farmers and fishers</p>
        </div>
      `;
    }
  } catch (error) {
    console.error('Error loading featured products:', error);
    document.getElementById('featuredProducts').innerHTML = `
      <div class="col-12 text-center">
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h5 class="text-warning">Failed to load products</h5>
        <p class="text-muted">Please try again later</p>
      </div>
    `;
  }
}
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>