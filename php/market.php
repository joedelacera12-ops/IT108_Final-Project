<?php
// Set page title
$page_title = 'Marketplace - AgriSea';

// Additional CSS
$additional_css = ['/ecommerce_farmers_fishers/assets/css/enhanced_modern_with_tracking.css'];

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<style>
  /* Clean Light Theme for Marketplace */
  body {
    background: #f5f5f5;
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    color: #333;
  }
  
  .container {
    max-width: 100vw;
    overflow-x: hidden;
    padding-left: 15px;
    padding-right: 15px;
  }
  
  .row {
    margin-left: 0;
    margin-right: 0;
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .col, .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12, .col-auto, .col-lg, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12, .col-lg-auto, .col-md, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, .col-md-auto, .col-sm, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm-auto, .col-xl, .col-xl-1, .col-xl-2, .col-xl-3, .col-xl-4, .col-xl-5, .col-xl-6, .col-xl-7, .col-xl-8, .col-xl-9, .col-xl-10, .col-xl-11, .col-xl-12, .col-xl-auto {
    padding-left: 15px;
    padding-right: 15px;
    max-width: 100vw;
    box-sizing: border-box;
    overflow-x: hidden;
  }
  
  .card {
    max-width: 100vw;
    overflow-x: hidden;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    border: 1px solid #e0e0e0;
    background: #ffffff;
    transition: all 0.3s ease;
    padding: 1.5rem;
  }
  
  .card:hover {
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);
    border-color: rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }
  
  .form-control, .form-select {
    max-width: 100vw;
    overflow-x: hidden;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    padding: 1rem;
    transition: all 0.3s ease;
    background: #ffffff;
  }
  
  .form-control:focus, .form-select:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
    outline: none;
  }
  
  .input-group {
    max-width: 100vw;
    overflow-x: hidden;
  }
  
  .btn {
    max-width: 100vw;
    overflow-x: hidden;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-success {
    background: #4CAF50;
    border: none;
  }
  
  .btn-success:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
  }
  
  .btn-outline-secondary {
    border-radius: 10px;
  }
  
  .display-6 {
    color: #4CAF50;
    font-weight: 700;
  }
  
  @media (max-width: 768px) {
    .container {
      padding-left: 10px;
      padding-right: 10px;
    }
    
    .col, .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12, .col-auto, .col-lg, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12, .col-lg-auto, .col-md, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, .col-md-auto, .col-sm, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm-auto, .col-xl, .col-xl-1, .col-xl-2, .col-xl-3, .col-xl-4, .col-xl-5, .col-xl-6, .col-xl-7, .col-xl-8, .col-xl-9, .col-xl-10, .col-xl-11, .col-xl-12, .col-xl-auto {
      padding-left: 10px;
      padding-right: 10px;
    }
    
    .d-flex {
      flex-direction: column;
      align-items: stretch;
    }
    
    .gap-2 {
      gap: 0.5rem;
    }
    
    .mb-4 {
      margin-bottom: 1rem;
    }
  }
  
  @media (max-width: 576px) {
    .container {
      padding-left: 5px;
      padding-right: 5px;
    }
    
    .col, .col-1, .col-2, .col-3, .col-4, .col-5, .col-6, .col-7, .col-8, .col-9, .col-10, .col-11, .col-12, .col-auto, .col-lg, .col-lg-1, .col-lg-2, .col-lg-3, .col-lg-4, .col-lg-5, .col-lg-6, .col-lg-7, .col-lg-8, .col-lg-9, .col-lg-10, .col-lg-11, .col-lg-12, .col-lg-auto, .col-md, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-7, .col-md-8, .col-md-9, .col-md-10, .col-md-11, .col-md-12, .col-md-auto, .col-sm, .col-sm-1, .col-sm-2, .col-sm-3, .col-sm-4, .col-sm-5, .col-sm-6, .col-sm-7, .col-sm-8, .col-sm-9, .col-sm-10, .col-sm-11, .col-sm-12, .col-sm-auto, .col-xl, .col-xl-1, .col-xl-2, .col-xl-3, .col-xl-4, .col-xl-5, .col-xl-6, .col-xl-7, .col-xl-8, .col-xl-9, .col-xl-10, .col-xl-11, .col-xl-12, .col-xl-auto {
      padding-left: 5px;
      padding-right: 5px;
    }
    
    .btn {
      font-size: 0.8rem;
      padding: 0.375rem 0.75rem;
    }
    
    .form-control, .form-select {
      font-size: 0.9rem;
      padding: 0.375rem 0.75rem;
    }
    
    .display-6 {
      font-size: 1.5rem;
    }
  }
  
  /* Make marketplace search bar fully white */
  #search, .input-group-text, .btn-success {
    background-color: white !important;
    color: #333 !important;
  }
  
  .input-group-text {
    border-right: none;
  }
  
  #search {
    border-left: none;
    border-right: none;
  }
  
  .btn-success {
    border-left: none;
  }
</style>

<div class="container py-5" style="max-width: 100vw; overflow-x: hidden;">
  <div class="row" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
    <div class="col-12" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
      <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 100vw; overflow-x: hidden;">
        <h2 class="display-6 fw-bold text-success">Marketplace</h2>
      </div>
    </div>
  </div>

  <div class="row mb-4" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
    <div class="col-lg-8" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
      <div class="input-group" style="max-width: 100vw; overflow-x: hidden;">
        <span class="input-group-text" style="max-width: 100vw; overflow-x: hidden;"><i class="fas fa-search"></i></span>
        <input type="text" id="search" class="form-control" placeholder="Search products, farmers, or locations..." oninput="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
        <button class="btn btn-success" type="button" onclick="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
    <div class="col-lg-4" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
      <div class="d-flex gap-2" style="max-width: 100vw; overflow-x: hidden;">
        <select id="sortBy" class="form-select" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
          <option value="name">Sort by Name</option>
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
          <option value="newest">Newest First</option>
          <option value="rating">Highest Rated</option>
        </select>
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" style="max-width: 100vw; overflow-x: hidden;">
          <i class="fas fa-filter"></i>
        </button>
      </div>
    </div>
  </div>

  <div class="collapse mb-4" id="filterCollapse">
    <div class="card" style="max-width: 100vw; overflow-x: hidden;">
      <div class="card-body" style="max-width: 100vw; overflow-x: hidden;">
        <div class="row g-3" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
          <div class="col-md-3" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <label for="categoryFilter" class="form-label" style="max-width: 100vw; overflow-x: hidden;">Category</label>
            <select id="categoryFilter" class="form-select" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
              <option value="">All Categories</option>
              <option value="vegetables">Vegetables</option>
              <option value="fruits">Fruits</option>
              <option value="grains">Grains & Cereals</option>
              <option value="seafood">Seafood</option>
              <option value="poultry">Poultry & Eggs</option>
              <option value="dairy">Dairy Products</option>
              <option value="herbs">Herbs & Spices</option>
            </select>
          </div>
          <div class="col-md-3" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <label for="priceRange" class="form-label" style="max-width: 100vw; overflow-x: hidden;">Price Range</label>
            <select id="priceRange" class="form-select" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
              <option value="">Any Price</option>
              <option value="0-50">₱0 - ₱50</option>
              <option value="50-100">₱50 - ₱100</option>
              <option value="100-200">₱100 - ₱200</option>
              <option value="200-500">₱200 - ₱500</option>
              <option value="500+">₱500+</option>
            </select>
          </div>
          <div class="col-md-3" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <label for="locationFilter" class="form-label" style="max-width: 100vw; overflow-x: hidden;">Location</label>
            <select id="locationFilter" class="form-select" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
              <option value="">All Locations</option>
              <option value="metro-manila">Metro Manila</option>
              <option value="luzon">Luzon</option>
              <option value="visayas">Visayas</option>
              <option value="mindanao">Mindanao</option>
            </select>
          </div>
          <div class="col-md-3" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <label for="deliveryFilter" class="form-label" style="max-width: 100vw; overflow-x: hidden;">Delivery</label>
            <select id="deliveryFilter" class="form-select" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
              <option value="">Any Delivery</option>
              <option value="delivery">Delivery Available</option>
              <option value="pickup">Pickup Only</option>
              <option value="both">Both Available</option>
            </select>
          </div>
        </div>
        <div class="row g-3 mt-2" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
          <div class="col-12" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <label class="form-label" style="max-width: 100vw; overflow-x: hidden;">Certifications</label>
            <div class="row g-2" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
              <div class="col-md-2" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
                <div class="form-check" style="max-width: 100vw; overflow-x: hidden;">
                  <input class="form-check-input" type="checkbox" id="organicFilter" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
                  <label class="form-check-label" for="organicFilter" style="max-width: 100vw; overflow-x: hidden;">Organic</label>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
                <div class="form-check" style="max-width: 100vw; overflow-x: hidden;">
                  <input class="form-check-input" type="checkbox" id="gmoFreeFilter" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
                  <label class="form-check-label" for="gmoFreeFilter" style="max-width: 100vw; overflow-x: hidden;">GMO Free</label>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
                <div class="form-check" style="max-width: 100vw; overflow-x: hidden;">
                  <input class="form-check-input" type="checkbox" id="freshFilter" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
                  <label class="form-check-label" for="freshFilter" style="max-width: 100vw; overflow-x: hidden;">Fresh Daily</label>
                </div>
              </div>
              <div class="col-md-2" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
                <div class="form-check" style="max-width: 100vw; overflow-x: hidden;">
                  <input class="form-check-input" type="checkbox" id="localFilter" onchange="renderProducts()" style="max-width: 100vw; overflow-x: hidden;">
                  <label class="form-check-label" for="localFilter" style="max-width: 100vw; overflow-x: hidden;">Local</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
    <div class="col-12" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
      <div class="d-flex justify-content-between align-items-center" style="max-width: 100vw; overflow-x: hidden;">
        <span id="resultsCount" class="text-muted" style="max-width: 100vw; overflow-x: hidden;">Loading products...</span>
        <div class="btn-group" role="group" style="max-width: 100vw; overflow-x: hidden;">
          <input type="radio" class="btn-check" name="viewMode" id="gridView" autocomplete="off" checked onchange="toggleView('grid')" style="max-width: 100vw; overflow-x: hidden;">
          <label class="btn btn-outline-secondary" for="gridView" style="max-width: 100vw; overflow-x: hidden;"><i class="fas fa-th"></i></label>
          <input type="radio" class="btn-check" name="viewMode" id="listView" autocomplete="off" onchange="toggleView('list')" style="max-width: 100vw; overflow-x: hidden;">
          <label class="btn btn-outline-secondary" for="listView" style="max-width: 100vw; overflow-x: hidden;"><i class="fas fa-list"></i></label>
        </div>
      </div>
    </div>
  </div>

  <div class="row" id="productGrid" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;"></div>

  <div class="row mt-4" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
    <div class="col-12" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
      <nav aria-label="Product pagination" style="max-width: 100vw; overflow-x: hidden;">
        <ul class="pagination justify-content-center" id="pagination" style="max-width: 100vw; overflow-x: hidden;">
        </ul>
      </nav>
    </div>
  </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-lg" style="max-width: 100vw; margin: 1rem; overflow-x: hidden;">
    <div class="modal-content" style="max-width: 100vw; overflow-x: hidden;">
      <div class="modal-header" style="max-width: 100vw; overflow-x: hidden;">
        <h5 class="modal-title" id="modalProductName" style="max-width: 100vw; overflow-x: hidden;">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" style="max-width: 100vw; overflow-x: hidden;"></button>
      </div>
      <div class="modal-body" style="max-width: 100vw; overflow-x: hidden;">
        <div class="row" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
          <div class="col-md-6" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <img id="modalProductImage" src="https://via.placeholder.com/400x300" class="img-fluid rounded" alt="Product Image" style="max-width: 100vw; overflow-x: hidden;">
          </div>
          <div class="col-md-6" style="padding-left: 15px; padding-right: 15px; max-width: 100vw; overflow-x: hidden;">
            <div class="mb-3" style="max-width: 100vw; overflow-x: hidden;">
              <span class="badge bg-success" id="modalProductCategory" style="max-width: 100vw; overflow-x: hidden;">Category</span>
            </div>
            <h5 id="modalProductName" style="max-width: 100vw; overflow-x: hidden;">Product Name</h5>
            <p id="modalProductDescription" class="text-muted" style="max-width: 100vw; overflow-x: hidden;">Product description</p>
            <div class="mb-3" style="max-width: 100vw; overflow-x: hidden;">
              <span class="h3 text-success fw-bold" id="modalProductPrice" style="max-width: 100vw; overflow-x: hidden;">₱0</span>
              <span class="text-muted" id="modalProductUnit" style="max-width: 100vw; overflow-x: hidden;">per unit</span>
            </div>
            <div class="mb-3" style="max-width: 100vw; overflow-x: hidden;">
              <div class="d-flex align-items-center mb-2" style="max-width: 100vw; overflow-x: hidden;">
                <i class="fas fa-box me-2 text-muted" style="max-width: 100vw; overflow-x: hidden;"></i>
                <strong style="max-width: 100vw; overflow-x: hidden;">Stock:</strong> <span id="modalProductStock" class="ms-1" style="max-width: 100vw; overflow-x: hidden;">0</span> available
              </div>
              <div class="d-flex align-items-center mb-2" style="max-width: 100vw; overflow-x: hidden;">
                <i class="fas fa-user me-2 text-muted" style="max-width: 100vw; overflow-x: hidden;"></i>
                <strong style="max-width: 100vw; overflow-x: hidden;">Seller:</strong> <span id="modalProductSeller" class="ms-1" style="max-width: 100vw; overflow-x: hidden;">Seller Name</span>
              </div>
              <div class="d-flex align-items-center mb-2" style="max-width: 100vw; overflow-x: hidden;">
                <i class="fas fa-envelope me-2 text-muted" style="max-width: 100vw; overflow-x: hidden;"></i>
                <strong style="max-width: 100vw; overflow-x: hidden;">Email:</strong> <span id="modalProductSellerEmail" class="ms-1" style="max-width: 100vw; overflow-x: hidden;">seller@example.com</span>
              </div>
              <div class="d-flex align-items-center mb-2" style="max-width: 100vw; overflow-x: hidden;">
                <i class="fas fa-phone me-2 text-muted" style="max-width: 100vw; overflow-x: hidden;"></i>
                <strong style="max-width: 100vw; overflow-x: hidden;">Phone:</strong> <span id="modalProductSellerPhone" class="ms-1" style="max-width: 100vw; overflow-x: hidden;">+63 000 000 0000</span>
              </div>
              <div class="d-flex align-items-center mb-2" style="max-width: 100vw; overflow-x: hidden;">
                <i class="fas fa-map-marker-alt me-2 text-muted" style="max-width: 100vw; overflow-x: hidden;"></i>
                <strong style="max-width: 100vw; overflow-x: hidden;">Location:</strong> <span id="modalProductLocation" class="ms-1" style="max-width: 100vw; overflow-x: hidden;">Location</span>
              </div>
              <div class="d-flex align-items-center mb-2" style="max-width: 100vw; overflow-x: hidden;">
                <i class="fas fa-truck me-2 text-muted" style="max-width: 100vw; overflow-x: hidden;"></i>
                <strong style="max-width: 100vw; overflow-x: hidden;">Delivery:</strong> <span id="modalProductDelivery" class="ms-1" style="max-width: 100vw; overflow-x: hidden;">Delivery info</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="max-width: 100vw; overflow-x: hidden;">
        <div class="row w-100" style="margin-left: 0; margin-right: 0; max-width: 100vw; overflow-x: hidden;">
          <div class="col-md-6">
            <div class="input-group">
              <button class="btn btn-outline-secondary" type="button" onclick="decreaseQuantity()">-</button>
              <input type="number" class="form-control text-center" id="quantityInput" value="1" min="1">
              <button class="btn btn-outline-secondary" type="button" onclick="increaseQuantity()">+</button>
            </div>
          </div>
          <div class="col-md-6">
            <button type="button" class="btn btn-success w-100" onclick="addToCart()">
              <i class="fas fa-shopping-cart me-2"></i>Add to Cart
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="/ecommerce_farmers_fishers/assets/js/script.js"></script>
<script>
// Override the products array to fetch from API
let products = [];
let currentPage = 1;
let itemsPerPage = 6;
let currentView = 'grid';
let totalProducts = 0;

// Fetch products from API
async function fetchProducts() {
  try {
    console.log('Fetching products from API...');
    console.log('Current location:', window.location.href);
    console.log('Current path:', window.location.pathname);
    
    // Try different API paths
    const apiPaths = [
      '/ecommerce_farmers_fishers/api/products.php',
      './api/products.php',
      '/api/products.php'
    ];
    
    let response;
    let lastError;
    
    for (const path of apiPaths) {
      try {
        console.log('Trying API path:', path);
        response = await fetch(path);
        console.log('Response for', path, ':', response.status);
        
        if (response.ok) {
          break;
        } else {
          lastError = `HTTP error! status: ${response.status}`;
        }
      } catch (error) {
        console.log('Error with path', path, ':', error.message);
        lastError = error.message;
      }
    }
    
    if (!response || !response.ok) {
      throw new Error(lastError || 'All API paths failed');
    }
    
    const data = await response.json();
    console.log('API response data:', data);
    
    if (data.success) {
      products = data.products;
      totalProducts = data.total;
      renderProducts();
    } else {
      console.error('Failed to fetch products:', data.error);
      document.getElementById('resultsCount').textContent = 'Failed to load products: ' + (data.error || 'Unknown error');
    }
  } catch (error) {
    console.error('Error fetching products:', error);
    document.getElementById('resultsCount').textContent = 'Failed to load products: ' + error.message;
  }
}

// Initialize product loading
document.addEventListener('DOMContentLoaded', function() {
  fetchProducts();
  updateCartCount();
});

// Update renderProducts to work with API data
function renderProducts() {
  const grid = document.getElementById('productGrid');
  if (!grid) return;
  
  const search = document.getElementById('search')?.value.toLowerCase() || '';
  const category = document.getElementById('categoryFilter')?.value || '';
  const priceRange = document.getElementById('priceRange')?.value || '';
  const location = document.getElementById('locationFilter')?.value || '';
  const delivery = document.getElementById('deliveryFilter')?.value || '';
  const sortBy = document.getElementById('sortBy')?.value || 'name';
  
  // Filter products
  let filtered = products.filter(product => {
    const matchesSearch = product.name.toLowerCase().includes(search) ||
                         product.description.toLowerCase().includes(search) ||
                         product.seller.toLowerCase().includes(search);
    const matchesCategory = !category || product.category === category;
    const matchesLocation = !location || product.location.toLowerCase().includes(location.toLowerCase());
    const matchesDelivery = !delivery || product.delivery.toLowerCase().includes(delivery.toLowerCase());
    
    // Price range filter
    let matchesPrice = true;
    if (priceRange) {
      const [min, max] = priceRange.split('-').map(p => p.replace('₱', '').replace('+', ''));
      if (max) {
        matchesPrice = product.price >= parseInt(min) && product.price <= parseInt(max);
      } else {
        matchesPrice = product.price >= parseInt(min);
      }
    }
    
    // Certification filters
    const organicFilter = document.getElementById('organicFilter')?.checked;
    const gmoFreeFilter = document.getElementById('gmoFreeFilter')?.checked;
    const freshFilter = document.getElementById('freshFilter')?.checked;
    const localFilter = document.getElementById('localFilter')?.checked;
    
    const matchesOrganic = !organicFilter || product.organic;
    const matchesGmoFree = !gmoFreeFilter || product.organic; // Assuming organic means GMO-free
    const matchesFresh = !freshFilter || product.fresh;
    const matchesLocal = !localFilter || product.local;
    
    return matchesSearch && matchesCategory && matchesPrice && matchesLocation && 
           matchesDelivery && matchesOrganic && matchesGmoFree && matchesFresh && matchesLocal;
  });
  
  // Sort products
  filtered.sort((a, b) => {
    switch (sortBy) {
      case 'price-low':
        return a.price - b.price;
      case 'price-high':
        return b.price - a.price;
      case 'newest':
        return b.id - a.id;
      case 'rating':
        return (b.rating || 0) - (a.rating || 0);
      default:
        return a.name.localeCompare(b.name);
    }
  });
  
  // Update results count
  const resultsCount = document.getElementById('resultsCount');
  if (resultsCount) {
    resultsCount.textContent = `${filtered.length} product${filtered.length !== 1 ? 's' : ''} found`;
  }
  
  // Pagination
  const totalPages = Math.ceil(filtered.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const paginatedProducts = filtered.slice(startIndex, endIndex);
  
  // Render products
  grid.innerHTML = '';
  if (paginatedProducts.length === 0) {
    grid.innerHTML = `
      <div class="col-12 text-center py-5">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">No products found</h5>
        <p class="text-muted">Try adjusting your search criteria</p>
      </div>
    `;
    return;
  }
  
  paginatedProducts.forEach(product => {
    const col = document.createElement('div');
    col.className = currentView === 'grid' ? 'col-lg-4 col-md-6 mb-4' : 'col-12 mb-3';
    
    const badges = [];
    if (product.organic) badges.push('<span class="badge badge-organic me-1">Organic</span>');
    if (product.fresh) badges.push('<span class="badge badge-fresh me-1">Fresh</span>');
    if (product.local) badges.push('<span class="badge badge-local me-1">Local</span>');
    
    col.innerHTML = currentView === 'grid' ? `
      <div class="card product-card h-100 shadow-sm" onclick="showProductModal(${product.id})">
        <img src="${product.image}" class="card-img-top product-image" alt="${product.name}" style="height: 200px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
          <div class="mb-2">${badges.join('')}</div>
          <h6 class="card-title">${product.name}</h6>
          <p class="card-text text-muted small">${product.description}</p>
          <div class="mt-auto">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="product-price fw-bold text-success">₱${product.price}</span>
              <small class="product-stock text-muted">${product.stock} available</small>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <small class="text-muted">${product.seller}</small>
              <div class="d-flex align-items-center">
                <i class="fas fa-star text-warning me-1"></i>
                <small>${product.rating ? product.rating : 'No ratings'} ${product.reviews ? `(${product.reviews})` : ''}</small>
              </div>
            </div>
            <button class="btn btn-success w-100" onclick="event.stopPropagation(); addToCart(${product.id})">
              <i class="fas fa-shopping-cart me-2"></i>Add to Cart
            </button>
          </div>
        </div>
      </div>
    ` : `
      <div class="card product-card shadow-sm" onclick="showProductModal(${product.id})">
        <div class="row g-0">
          <div class="col-md-3">
            <img src="${product.image}" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="${product.name}">
          </div>
          <div class="col-md-9">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                  <div class="mb-2">${badges.join('')}</div>
                  <h6 class="card-title">${product.name}</h6>
                  <p class="card-text text-muted small">${product.description}</p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="product-price fw-bold text-success">₱${product.price}</span>
                    <small class="text-muted">${product.seller} • ${product.location}</small>
                    <div class="d-flex align-items-center">
                      <i class="fas fa-star text-warning me-1"></i>
                      <small>${product.rating ? product.rating : 'No ratings'} ${product.reviews ? `(${product.reviews})` : ''}</small>
                    </div>
                  </div>
                </div>
                <button class="btn btn-success ms-3" onclick="event.stopPropagation(); addToCart(${product.id})">
                  <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    
    grid.appendChild(col);
  });
  
  // Render pagination
  renderPagination(totalPages);
}

function renderPagination(totalPages) {
  const pagination = document.getElementById('pagination');
  if (!pagination || totalPages <= 1) {
    if (pagination) pagination.innerHTML = '';
    return;
  }
  
  pagination.innerHTML = '';
  
  // Previous button
  const prevLi = document.createElement('li');
  prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
  prevLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>`;
  pagination.appendChild(prevLi);
  
  // Page numbers
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
      const li = document.createElement('li');
      li.className = `page-item ${i === currentPage ? 'active' : ''}`;
      li.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
      pagination.appendChild(li);
    } else if (i === currentPage - 3 || i === currentPage + 3) {
      const li = document.createElement('li');
      li.className = 'page-item disabled';
      li.innerHTML = '<span class="page-link">...</span>';
      pagination.appendChild(li);
    }
  }
  
  // Next button
  const nextLi = document.createElement('li');
  nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
  nextLi.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>`;
  pagination.appendChild(nextLi);
}

function changePage(page) {
  const totalPages = Math.ceil(products.length / itemsPerPage);
  if (page >= 1 && page <= totalPages) {
    currentPage = page;
    renderProducts();
  }
}

function toggleView(view) {
  currentView = view;
  renderProducts();
}

function showProductModal(productId) {
  const product = products.find(p => p.id === productId);
  if (!product) return;
  
  document.getElementById('modalProductName').textContent = product.name;
  document.getElementById('modalProductCategory').textContent = product.category_name || product.category;
  document.getElementById('modalProductDescription').textContent = product.description;
  document.getElementById('modalProductPrice').textContent = `₱${product.price}`;
  document.getElementById('modalProductUnit').textContent = product.unit || 'unit';
  document.getElementById('modalProductStock').textContent = product.stock;
  document.getElementById('modalProductSeller').textContent = product.seller;
  document.getElementById('modalProductSellerEmail').textContent = product.seller_email || 'Not provided';
  document.getElementById('modalProductSellerPhone').textContent = product.seller_phone || 'Not provided';
  document.getElementById('modalProductImage').src = product.image;
  
  // Set location and delivery (currently not in data model)
  document.getElementById('modalProductLocation').textContent = product.location || 'Not specified';
  document.getElementById('modalProductDelivery').textContent = product.delivery || 'Not specified';
  
  const modal = new bootstrap.Modal(document.getElementById('productModal'));
  modal.show();
}

async function addToCart(productId) {
  try {
    const response = await fetch('/ecommerce_farmers_fishers/buyer/add_to_cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `product_id=${productId}&quantity=1`
    });
    
    const data = await response.json();
    
    // If user is not logged in, show login prompt
    if (data.login_required) {
      const loginModal = document.createElement('div');
      loginModal.className = 'modal fade';
      loginModal.id = 'loginPromptModal';
      loginModal.tabIndex = '-1';
      loginModal.innerHTML = `
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Login Required</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p>Please log in or register to continue shopping.</p>
            </div>
            <div class="modal-footer">
              <a href="/ecommerce_farmers_fishers/php/login.php" class="btn btn-primary">Login</a>
              <a href="/ecommerce_farmers_fishers/php/register.php" class="btn btn-outline-primary">Register</a>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(loginModal);
      
      const modal = new bootstrap.Modal(loginModal);
      modal.show();
      
      // Remove modal from DOM when closed
      loginModal.addEventListener('hidden.bs.modal', function () {
        document.body.removeChild(loginModal);
      });
      
      return;
    }
    
    // Show notification
    const notification = document.createElement('div');
    notification.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
      ${data.message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
    
    // Update cart count if successful
    if (data.success) {
      updateCartCount();
    }
  } catch (error) {
    console.error('Error adding to cart:', error);
    
    // Show error notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
      Failed to add product to cart. Please try again.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }
}

function decreaseQuantity() {
  const input = document.getElementById('quantityInput');
  if (input.value > 1) {
    input.value = parseInt(input.value) - 1;
  }
}

function increaseQuantity() {
  const input = document.getElementById('quantityInput');
  input.value = parseInt(input.value) + 1;
}

// Update cart count badge
async function updateCartCount() {
  try {
    const response = await fetch('/ecommerce_farmers_fishers/buyer/cart_count.php');
    const data = await response.json();
    
    if (data.success) {
      const cartCountElement = document.querySelector('.cart-count');
      if (cartCountElement) {
        cartCountElement.textContent = data.count;
        cartCountElement.style.display = data.count > 0 ? 'inline' : 'none';
      }
    }
  } catch (error) {
    console.error('Error updating cart count:', error);
  }
}
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>