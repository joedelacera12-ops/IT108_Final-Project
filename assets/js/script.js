// Enhanced AgriSea Marketplace JavaScript

// Global Variables
// Products now load from the server API; keep an empty array as default/fallback
let products = [];

let cart = JSON.parse(localStorage.getItem('agrisea_cart')) || [];
let currentUser = JSON.parse(localStorage.getItem('agrisea_user')) || null;
let currentPage = 1;
let itemsPerPage = 6;
let currentView = 'grid';

// Utility Functions
function saveToLocalStorage(key, data) {
  localStorage.setItem(key, JSON.stringify(data));
}

function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  notification.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  document.body.appendChild(notification);
  
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 5000);
}

function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

function validatePhone(phone) {
  const re = /^(\+63|0)[0-9]{10}$/;
  return re.test(phone.replace(/\s/g, ''));
}

// Enhanced form validation
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return false;
  
  // Check all required fields
  const requiredFields = form.querySelectorAll('[required]');
  let isValid = true;
  
  requiredFields.forEach(field => {
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      isValid = false;
    } else {
      field.classList.remove('is-invalid');
    }
  });
  
  // Special validation for email fields
  const emailFields = form.querySelectorAll('input[type="email"]');
  emailFields.forEach(field => {
    if (field.value && !validateEmail(field.value)) {
      field.classList.add('is-invalid');
      isValid = false;
    } else {
      field.classList.remove('is-invalid');
    }
  });
  
  // Special validation for phone fields
  const phoneFields = form.querySelectorAll('input[type="tel"]');
  phoneFields.forEach(field => {
    if (field.value && !validatePhone(field.value)) {
      field.classList.add('is-invalid');
      // Add custom error message for phone fields
      let errorMsg = field.parentNode.querySelector('.invalid-feedback');
      if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'invalid-feedback';
        errorMsg.textContent = 'Please enter a valid Philippine phone number (+639171234567 or 09171234567)';
        field.parentNode.appendChild(errorMsg);
      }
      isValid = false;
    } else {
      field.classList.remove('is-invalid');
      // Remove error message if it exists
      const errorMsg = field.parentNode.querySelector('.invalid-feedback');
      if (errorMsg) {
        errorMsg.remove();
      }
    }
  });
  
  // Special validation for password fields
  if (formId === 'registerForm') {
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmPassword');
    
    if (passwordField) {
      const passwordResult = validatePassword(passwordField.value);
      if (!passwordResult.isValid) {
        passwordField.classList.add('is-invalid');
        isValid = false;
      } else {
        passwordField.classList.remove('is-invalid');
      }
    }
    
    if (passwordField && confirmPasswordField) {
      if (passwordField.value !== confirmPasswordField.value) {
        confirmPasswordField.classList.add('is-invalid');
        // Also add custom error message
        let errorMsg = confirmPasswordField.parentNode.querySelector('.invalid-feedback');
        if (!errorMsg) {
          errorMsg = document.createElement('div');
          errorMsg.className = 'invalid-feedback';
          errorMsg.textContent = 'Passwords do not match';
          confirmPasswordField.parentNode.appendChild(errorMsg);
        }
        isValid = false;
      } else {
        confirmPasswordField.classList.remove('is-invalid');
        // Remove error message if it exists
        const errorMsg = confirmPasswordField.parentNode.querySelector('.invalid-feedback');
        if (errorMsg) {
          errorMsg.remove();
        }
      }
    }
  }
  
  return isValid;
}

// Enhanced password validation function
function validatePassword(password) {
  // Check if password is not empty
  if (!password || password.length === 0) {
    return {
      isValid: false,
      strength: 'weak',
      requirements: {
        length: false,
        uppercase: false,
        lowercase: false,
        number: false,
        special: false
      }
    };
  }
  
  // Enhanced password strength validation
  const requirements = {
    length: password.length >= 8,
    uppercase: /[A-Z]/.test(password),
    lowercase: /[a-z]/.test(password),
    number: /\d/.test(password),
    special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
  };
  
  const strengthScore = Object.values(requirements).filter(Boolean).length;
  let strength = 'weak';
  if (strengthScore >= 4) strength = 'strong';
  else if (strengthScore >= 3) strength = 'medium';
  
  return {
    isValid: Object.values(requirements).every(Boolean),
    strength: strength,
    requirements: requirements
  };
}

// Enhanced updatePasswordStrength function
function updatePasswordStrength() {
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirmPassword');
  const strengthMeter = document.querySelector('.password-strength-fill');
  const strengthText = document.querySelector('.password-strength-text');
  const matchIndicator = document.querySelector('.password-match-indicator');
  
  if (!passwordField || !strengthMeter || !strengthText) return;
  
  const password = passwordField.value;
  const result = validatePassword(password);
  
  // Update strength meter
  let width = 0;
  let bgColor = '';
  let text = '';
  
  if (password.length > 0) {
    if (result.strength === 'strong') {
      width = '100%';
      bgColor = 'bg-success';
      text = 'Strong';
    } else if (result.strength === 'medium') {
      width = '60%';
      bgColor = 'bg-info';
      text = 'Medium';
    } else {
      width = '30%';
      bgColor = 'bg-danger';
      text = 'Weak';
    }
  }
  
  strengthMeter.style.width = width;
  strengthMeter.className = 'password-strength-fill ' + bgColor;
  strengthText.textContent = text;
  
  // Update requirements display
  const reqItems = document.querySelectorAll('.password-requirements li');
  if (reqItems.length >= 5) {
    reqItems[0].className = result.requirements.length ? 'text-success' : 'text-danger';
    reqItems[0].innerHTML = (result.requirements.length ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-times-circle me-1"></i>') + 'At least 8 characters';
    
    reqItems[1].className = result.requirements.uppercase ? 'text-success' : 'text-danger';
    reqItems[1].innerHTML = (result.requirements.uppercase ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-times-circle me-1"></i>') + 'At least one uppercase letter';
    
    reqItems[2].className = result.requirements.lowercase ? 'text-success' : 'text-danger';
    reqItems[2].innerHTML = (result.requirements.lowercase ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-times-circle me-1"></i>') + 'At least one lowercase letter';
    
    reqItems[3].className = result.requirements.number ? 'text-success' : 'text-danger';
    reqItems[3].innerHTML = (result.requirements.number ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-times-circle me-1"></i>') + 'At least one number';
    
    reqItems[4].className = result.requirements.special ? 'text-success' : 'text-danger';
    reqItems[4].innerHTML = (result.requirements.special ? '<i class="fas fa-check-circle me-1"></i>' : '<i class="fas fa-times-circle me-1"></i>') + 'At least one special character';
  }
  
  // Check password match if confirm field exists
  if (confirmPasswordField && matchIndicator) {
    // Get all password match indicators (in case there are duplicates)
    const passwordMatchIndicators = document.querySelectorAll('#passwordMatchIndicator');
    const passwordMatchTexts = document.querySelectorAll('#passwordMatchText');
    
    if (confirmPasswordField.value.length > 0) {
      if (password === confirmPasswordField.value) {
        matchIndicator.className = 'password-match-indicator match text-success';
        matchIndicator.innerHTML = '<i class="fas fa-check-circle me-1"></i> Passwords match';
        matchIndicator.style.display = 'block';
        
        // Update all password match indicators
        passwordMatchIndicators.forEach((indicator, index) => {
          indicator.className = 'text-success mt-2';
          if (passwordMatchTexts[index]) {
            passwordMatchTexts[index].innerHTML = '<i class="fas fa-check-circle me-1"></i> Passwords match';
          }
        });
      } else {
        matchIndicator.className = 'password-match-indicator mismatch text-danger';
        matchIndicator.innerHTML = '<i class="fas fa-times-circle me-1"></i> Passwords do not match';
        matchIndicator.style.display = 'block';
        
        // Also add visual indication to the confirm password field
        confirmPasswordField.classList.add('is-invalid');
        
        // Update all password match indicators
        passwordMatchIndicators.forEach((indicator, index) => {
          indicator.className = 'text-danger mt-2';
          if (passwordMatchTexts[index]) {
            passwordMatchTexts[index].innerHTML = '<i class="fas fa-times-circle me-1"></i> Passwords do not match';
          }
        });
      }
    } else {
      matchIndicator.style.display = 'none';
      // Remove invalid class when field is empty
      confirmPasswordField.classList.remove('is-invalid');
      
      // Reset all password match indicators
      passwordMatchIndicators.forEach((indicator, index) => {
        indicator.className = 'text-muted mt-2';
        if (passwordMatchTexts[index]) {
          passwordMatchTexts[index].innerHTML = 'Enter password to confirm';
        }
      });
    }
  }
}

// Add event listeners for password fields
document.addEventListener('DOMContentLoaded', function() {
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirmPassword');
  
  if (passwordField) {
    passwordField.addEventListener('input', updatePasswordStrength);
  }
  
  if (confirmPasswordField) {
    confirmPasswordField.addEventListener('input', updatePasswordStrength);
  }
});

// Product Management
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
        return b.rating - a.rating;
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
      <div class="card product-card h-100" onclick="showProductModal(${product.id})">
        <img src="${product.image}" class="card-img-top product-image" alt="${product.name}">
        <div class="card-body d-flex flex-column">
          <div class="mb-2">${badges.join('')}</div>
          <h6 class="card-title">${product.name}</h6>
          <p class="card-text text-muted small">${product.description}</p>
          <div class="mt-auto">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="product-price">₱${product.price}</span>
              <small class="product-stock">${product.stock} available</small>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <small class="text-muted">${product.seller}</small>
              <div class="d-flex align-items-center">
                <i class="fas fa-star text-warning me-1"></i>
                <small>${product.rating} (${product.reviews})</small>
              </div>
            </div>
            <button class="btn btn-success w-100" onclick="event.stopPropagation(); addToCart(${product.id})">
              <i class="fas fa-shopping-cart me-2"></i>Add to Cart
            </button>
          </div>
        </div>
      </div>
    ` : `
      <div class="card product-card" onclick="showProductModal(${product.id})">
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
                    <span class="product-price">₱${product.price}</span>
                    <small class="text-muted">${product.seller} • ${product.location}</small>
                    <div class="d-flex align-items-center">
                      <i class="fas fa-star text-warning me-1"></i>
                      <small>${product.rating} (${product.reviews})</small>
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
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

function toggleView(view) {
  currentView = view;
  renderProducts();
}

// Product Modal
function showProductModal(productId) {
  const product = products.find(p => p.id === productId);
  if (!product) return;
  
  document.getElementById('modalProductName').textContent = product.name;
  document.getElementById('modalProductImage').src = product.image;
  document.getElementById('modalProductCategory').textContent = product.category.charAt(0).toUpperCase() + product.category.slice(1);
  document.getElementById('modalProductDescription').textContent = product.description;
  document.getElementById('modalProductPrice').textContent = `₱${product.price}`;
  document.getElementById('modalProductUnit').textContent = 'per unit';
  document.getElementById('modalProductStock').textContent = product.stock;
  document.getElementById('modalProductSeller').textContent = product.seller;
  document.getElementById('modalProductLocation').textContent = product.location;
  document.getElementById('modalProductDelivery').textContent = product.delivery;
  
  const modal = new bootstrap.Modal(document.getElementById('productModal'));
  modal.show();
}

function increaseQuantity() {
  const input = document.getElementById('quantityInput');
  input.value = parseInt(input.value) + 1;
}

function decreaseQuantity() {
  const input = document.getElementById('quantityInput');
  if (parseInt(input.value) > 1) {
    input.value = parseInt(input.value) - 1;
  }
}

// Cart Management
function addToCart(productId, quantity = 1) {
  const product = products.find(p => p.id === productId);
  if (!product) return;
  
  // For this implementation, we'll use server-side session cart
  // Send AJAX request to add item to cart
  fetch('/ecommerce_farmers_fishers/buyer/add_to_cart.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `product_id=${productId}&quantity=${quantity}`
  })
  .then(response => response.json())
  .then(data => {
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
    
    if (data.success) {
      showNotification(`${product.name} added to cart!`);
      updateCartCount();
    } else {
      showNotification(data.message || 'Error adding to cart', 'danger');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('Error adding to cart', 'danger');
  });
}

function updateCartCount() {
  // Fetch cart count from server
  fetch('/ecommerce_farmers_fishers/buyer/cart_count.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update cart badge in navbar if it exists
        const cartBadge = document.querySelector('.cart-count');
        if (cartBadge) {
          cartBadge.textContent = data.count;
          cartBadge.style.display = data.count > 0 ? 'inline-block' : 'none';
        }
      }
    })
    .catch(error => {
      console.error('Error fetching cart count:', error);
    });
}

// Form Handlers
function handleRegister(e) {
  e.preventDefault();

  // Get password fields for additional validation
  const passwordField = document.getElementById('password');
  const confirmPasswordField = document.getElementById('confirmPassword');
  
  // Check if passwords match before proceeding
  if (passwordField && confirmPasswordField && passwordField.value !== confirmPasswordField.value) {
    showNotification('Passwords do not match. Please make sure both password fields are identical.', 'danger');
    confirmPasswordField.classList.add('is-invalid');
    // Also add custom error message
    let errorMsg = confirmPasswordField.parentNode.querySelector('.invalid-feedback');
    if (!errorMsg) {
      errorMsg = document.createElement('div');
      errorMsg.className = 'invalid-feedback';
      errorMsg.textContent = 'Passwords do not match';
      confirmPasswordField.parentNode.appendChild(errorMsg);
    }
    return false;
  }

  if (!validateForm('registerForm')) {
    showNotification('Please fill in all required fields correctly.', 'danger');
    return false;
  }

  const form = document.getElementById('registerForm');
  const action = form.getAttribute('action') || window.location.pathname;
  const fd = new FormData(form);

  // Use relative path for registration to ensure it works correctly
  const registerAction = '../process_register.php';
  
  fetch(registerAction, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin'
  }).then(response => {
    if (response.status === 405) {
      showNotification('Your development server is not running PHP. Start Apache/XAMPP or run a PHP server and open the site via http://localhost (not Live Server).', 'danger');
      console.warn('Received 405 when posting to', action, '. If you are using VSCode Live Server, it serves static files and does not run PHP. Start a PHP-capable server like:');
      console.warn("php -S localhost:8000 -t 'c:\\xamppp\\htdocs\\ecommerce_farmers_fishers'");
      return;
    }

    // Handle JSON response
    return response.json().then(data => {
      if (data.success) {
        // Show success message
        showNotification(data.message || 'Registration successful!', 'success');
        
        // Redirect after a short delay
        setTimeout(() => {
          window.location.href = data.redirect || 'php/login.php?registered=1' + (data.pending ? '&pending=1' : '');
        }, 2000);
      } else {
        showNotification(data.error || 'Registration failed. Please try again.', 'danger');
      }
    });
  }).catch(err => {
    console.error('Register fetch error', err);
    // Check if we're in a development environment
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      showNotification('Registration failed. Please check that XAMPP is running and you are accessing the site via http://localhost.', 'danger');
    } else {
      showNotification('Registration failed. Please try again later.', 'danger');
    }
  });

  return false;
}

function handleLogin(e) {
  e.preventDefault();

  if (!validateForm('loginForm')) {
    showNotification('Please fill in all required fields correctly.', 'danger');
    return false;
  }

  // Attempt a fetch to detect whether the current server will accept POST for PHP files.
  // Some developers open the project with a static Live Server (port 5500) which does not
  // execute PHP and returns HTTP 405 on POST. Detect that and show a friendly message.
  const form = document.getElementById('loginForm');
  const action = form.getAttribute('action') || window.location.pathname;
  const fd = new FormData(form);

  // Use relative path for login to ensure it works correctly
  const loginAction = '../process_login.php';
  
  fetch(loginAction, {
    method: 'POST',
    body: fd,
    credentials: 'same-origin'
  }).then(response => {
    if (response.status === 405) {
      showNotification('Your development server is not running PHP. Start Apache/XAMPP or run a PHP server and open the site via http://localhost (not Live Server).', 'danger');
      // Provide a slightly more detailed tip in console for advanced users
      console.warn('Received 405 when posting to', action, '. If you are using VSCode Live Server, it serves static files and does not run PHP. Start a PHP-capable server:');
      console.warn("For example (PowerShell on Windows): php -S localhost:8000 -t 'c:\\xamppp\\htdocs\\ecommerce_farmers_fishers'");
      return;
    }

    // If server responded with a redirect, follow it
    if (response.redirected) {
      window.location.href = response.url;
      return;
    }

    // If OK (200) assume login succeeded or server returned page; navigate to response URL if provided
    if (response.ok) {
      // Try to parse JSON for a success redirect (optional), else reload to let server-set redirect happen
      response.json().then(data => {
        if (data && data.success && data.redirect) {
          // Redirect to the URL provided by the server
          window.location.href = data.redirect;
        } else if (data && data.success) {
          // Success but no redirect URL provided, reload the page
          window.location.reload();
        } else {
          // Server returned JSON but not success, show error
          showNotification(data.error || 'Login failed. Please try again.', 'danger');
        }
      }).catch(err => {
        // Not JSON, might be HTML page
        response.text().then(text => {
          // If server returned a full HTML page (login result), replace current document
          if (text && text.trim().startsWith('<')) {
            document.open();
            document.write(text);
            document.close();
          } else {
            // fallback: reload the page (server likely set cookie/session and will redirect)
            window.location.reload();
          }
        });
      });
      return;
    }

    // Other statuses (500, 403, etc.) -> show a friendly message
    showNotification('Login failed: server returned status ' + response.status + '. Check server logs or database connection.', 'danger');
  }).catch(err => {
    console.error('Login fetch error', err);
    // Check if we're in a development environment
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      showNotification('Login failed. Please check that XAMPP is running and you are accessing the site via http://localhost.', 'danger');
    } else {
      showNotification('Login failed. Please try again later.', 'danger');
    }
  });

  return false;
}

// Utility Functions for Forms
function toggleBusinessFields() {
  const accountType = document.getElementById('accttype').value;
  const businessInfo = document.getElementById('businessInfo');
  const farmerInfo = document.getElementById('farmerInfo');
  const fisherInfo = document.getElementById('fisherInfo');
  
  // Hide all specialized fields first
  businessInfo.style.display = 'none';
  farmerInfo.style.display = 'none';
  fisherInfo.style.display = 'none';
  
  // Show relevant fields based on account type
  if (['farmer', 'fisher', 'retailer', 'restaurant'].includes(accountType)) {
    businessInfo.style.display = 'block';
  }
  
  if (accountType === 'farmer') {
    farmerInfo.style.display = 'block';
  }
  
  if (accountType === 'fisher') {
    fisherInfo.style.display = 'block';
  }
}

function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const button = field.nextElementSibling;
  const icon = button.querySelector('i');
  
  if (field.type === 'password') {
    field.type = 'text';
    icon.className = 'fas fa-eye-slash';
    button.setAttribute('title', 'Hide password');
    button.setAttribute('aria-label', 'Hide password');
  } else {
    field.type = 'password';
    icon.className = 'fas fa-eye';
    button.setAttribute('title', 'Show password');
    button.setAttribute('aria-label', 'Show password');
  }
  
  // Add animation
  button.classList.add('btn-pop');
  setTimeout(() => button.classList.remove('btn-pop'), 200);
}

function toggleLoginMethod(method) {
  const label = document.getElementById('identifierLabel');
  const input = document.getElementById('loginIdentifier');
  const icon = document.getElementById('identifierIcon');
  
  if (method === 'email') {
    label.textContent = 'Email Address *';
    input.type = 'email';
    input.placeholder = 'Enter your email address';
    icon.className = 'fas fa-envelope';
  } else {
    label.textContent = 'Phone Number *';
    input.type = 'tel';
    input.placeholder = 'Enter your phone number';
    icon.className = 'fas fa-phone';
  }
}

function showForgotPassword() {
  const modal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
  modal.show();
}

function handleForgotPassword(e) {
  e.preventDefault();
  const email = document.getElementById('resetEmail').value;
  
  if (!validateEmail(email)) {
    showNotification('Please enter a valid email address.', 'danger');
    return false;
  }
  
  const msg = document.getElementById('resetMsg');
  msg.textContent = 'Password reset link sent to your email!';
  msg.className = 'text-success';
  
  showNotification('Password reset link sent!');
  e.target.reset();
  
  return false;
}

function socialLogin(provider) {
  showNotification(`${provider.charAt(0).toUpperCase() + provider.slice(1)} login coming soon!`, 'info');
}

function saveDraft() {
  const formData = new FormData(document.getElementById('productForm'));
  const draft = Object.fromEntries(formData);
  saveToLocalStorage('agrisea_product_draft', draft);
  showNotification('Draft saved successfully!');
}

function showLoginForm() {
  window.location.href = 'login.php';
}

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
  // Register form: auto-capitalize inputs for user-friendly data
  (function initRegisterAutoCapitalize(){
    var form = document.getElementById('registerForm');
    if (!form) return;

    function capitalizeFirstLetter(s){ if (!s) return s; return s.charAt(0).toUpperCase() + s.slice(1); }
    function titleCase(s){
      if (!s) return s;
      return s.replace(/\s+/g,' ').split(' ').map(function(w){
        if (!w) return w;
        return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
      }).join(' ');
    }

    // Fields that should use title case (names and locations)
    var titleCaseIds = [
      'firstname','middlename','lastname','extension',
      'streetAddress','city','province',
      'businessName','cropTypes','fishTypes'
    ];

    titleCaseIds.forEach(function(id){
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', function(e){ e.target.value = titleCase(e.target.value); });
      el.addEventListener('blur', function(e){ e.target.value = titleCase(e.target.value.trim()); });
    });

    // Generic text inputs in form: capitalize first letter only
    Array.prototype.forEach.call(form.querySelectorAll('input[type="text"]'), function(el){
      if (titleCaseIds.indexOf(el.id) !== -1) return; // already handled
      el.addEventListener('input', function(e){ e.target.value = capitalizeFirstLetter(e.target.value); });
      el.addEventListener('blur', function(e){ e.target.value = capitalizeFirstLetter(e.target.value.trim()); });
    });
  })();

  // Load saved products
  // Prefer live data from API; fall back to any locally saved products if API fails
  const savedProducts = localStorage.getItem('agrisea_products');
  if (savedProducts) {
    try { products = JSON.parse(savedProducts) || []; } catch(e){ products = []; }
  }
  
  // Initialize cart count
  updateCartCount();
  
  // Render products if on marketplace page
  // Skip loading products here since marketplace page has its own implementation
  if (document.getElementById('productGrid')) {
    // Marketplace page will handle product loading
    console.log('Marketplace page detected, skipping product loading in script.js');
  } else {
    // Not on marketplace; try to load products from API
    fetch('api/products.php', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(json => {
        if (json && json.success && Array.isArray(json.products)) {
          products = json.products;
          // Persist for quick reloads; purely optional
          saveToLocalStorage('agrisea_products', products);
        }
      })
      .catch(() => {
        // ignore network/API errors; fallback to any locally cached data
      })
      .finally(() => {
        renderProducts();
      });
  }
  
  // Add animation classes
  const cards = document.querySelectorAll('.card');
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
    card.classList.add('animate-fadeInUp');
  });
  
  // Add smooth scrolling to anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
  
  // Add loading states to buttons
  document.querySelectorAll('button[type="submit"]').forEach(button => {
    button.addEventListener('click', function() {
      this.classList.add('loading');
      setTimeout(() => {
        this.classList.remove('loading');
      }, 2000);
    });
  });
});
