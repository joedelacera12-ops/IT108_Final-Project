<?php
$page_title = "Register";
$additional_css = ['/ecommerce_farmers_fishers/assets/css/password.css'];
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/redirect_handler.php';

// Redirect to dashboard if already logged in
if (current_user()) {
    $user = current_user();
    switch ($user['role_id']) {
        case 1: // Admin
            redirect_to('/ecommerce_farmers_fishers/admin/dashboard.php');
            break;
        case 2: // Seller
            redirect_to('/ecommerce_farmers_fishers/seller/dashboard.php');
            break;
        default: // Buyer or any other role
            redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php');
            break;
    }
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="text-center mb-5">
        <h2 class="display-6 fw-bold text-success">Join AgriSea Marketplace</h2>
        <p class="lead text-muted">Create your account to start buying or selling fresh produce</p>
      </div>

      <div class="card shadow-sm">
        <div class="card-body p-4">
          <form id="registerForm" method="post" onsubmit="return handleRegister(event)">
            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-user me-2"></i>Personal Information</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="lastname" class="form-label">Last Name *</label>
                  <input type="text" id="lastname" name="last_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label for="firstname" class="form-label">First Name *</label>
                  <input type="text" id="firstname" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label for="middlename" class="form-label">Middle Name</label>
                  <input type="text" id="middlename" name="middle_name" class="form-control">
                </div>
                <div class="col-md-6">
                  <label for="extension" class="form-label">Extension (Jr., III, etc.)</label>
                  <input type="text" id="extension" name="extension" class="form-control" placeholder="Jr., III, Sr.">
                </div>
                <div class="col-md-6">
                  <label for="birthdate" class="form-label">Date of Birth</label>
                  <input type="date" id="birthdate" name="birthdate" class="form-control">
                </div>
                <div class="col-md-6">
                  <label for="gender" class="form-label">Gender</label>
                  <select id="gender" name="gender" class="form-select">
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                    <option value="prefer-not-to-say">Prefer not to say</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-address-book me-2"></i>Contact Information</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="email" class="form-label">Email Address *</label>
                  <input type="email" id="email" name="email" class="form-control" required>
                  <div class="form-text">We'll use this to send you important updates</div>
                </div>
                <div class="col-md-6">
                  <label for="phone" class="form-label">Phone Number *</label>
                  <input type="tel" id="phone" name="phone" class="form-control" placeholder="+639171234567" required>
                  <div class="form-text">Philippine mobile format: +639171234567 or 09171234567 (no spaces or dashes)</div>
                </div>
                <div class="col-md-6">
                  <label for="alternatePhone" class="form-label">Alternate Phone</label>
                  <input type="tel" id="alternatePhone" name="alternate_phone" class="form-control" placeholder="+639171234567">
                  <div class="form-text">Philippine format: +639171234567 or 09171234567 (no spaces or dashes)</div>
                </div>
                <div class="col-md-6">
                  <label for="preferredContact" class="form-label">Preferred Contact Method</label>
                  <select id="preferredContact" name="preferred_contact" class="form-select">
                    <option value="email">Email</option>
                    <option value="phone">Phone Call</option>
                    <option value="sms">SMS</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h5>
              <div class="row g-3">
                <div class="col-12">
                  <label for="streetAddress" class="form-label">Street Address *</label>
                  <input type="text" id="streetAddress" name="street_address" class="form-control" placeholder="123 Main Street, Barangay Name" required>
                </div>
                <div class="col-md-4">
                  <label for="city" class="form-label">City/Municipality *</label>
                  <input type="text" id="city" name="city" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label for="province" class="form-label">Province *</label>
                  <input type="text" id="province" name="province" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label for="postalCode" class="form-label">Postal Code</label>
                  <input type="text" id="postalCode" name="postal_code" class="form-control" placeholder="1000">
                </div>
                <div class="col-md-12">
                  <label for="country" class="form-label">Country *</label>
                  <input type="text" id="country" name="country" class="form-control" value="Philippines" required>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-briefcase me-2"></i>Account Type & Business Information</h5>
              <div class="row g-3">
                <div class="col-12">
                  <label for="accttype" class="form-label">Account Type *</label>
                  <select id="accttype" name="accttype" class="form-select" onchange="toggleBusinessFields()" required>
                    <option value="">Select Account Type</option>
                    <option value="buyer">Buyer (Individual Consumer)</option>
                    <option value="farmer">Farmer (Agricultural Producer)</option>
                    <option value="fisher">Fisher (Aquaculture/Fishing)</option>
                    <option value="retailer">Retailer (Store Owner)</option>
                    <option value="restaurant">Restaurant/Food Service</option>
                    <option value="delivery_partner">Delivery Partner (Logistics Service)</option>
                  </select>
                </div>
                
                <div id="businessInfo" class="col-12" style="display: none;">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="businessName" class="form-label">Business/Farm Name</label>
                      <input type="text" id="businessName" name="business_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                      <label for="businessType" class="form-label">Business Type</label>
                      <select id="businessType" name="business_type" class="form-select">
                        <option value="">Select Business Type</option>
                        <option value="individual">Individual</option>
                        <option value="partnership">Partnership</option>
                        <option value="corporation">Corporation</option>
                        <option value="cooperative">Cooperative</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label for="yearsInBusiness" class="form-label">Years in Business</label>
                      <input type="number" id="yearsInBusiness" name="years_in_business" class="form-control" min="0" max="100">
                    </div>
                    <div class="col-md-6">
                      <label for="businessLicense" class="form-label">Business License Number</label>
                      <input type="text" id="businessLicense" name="business_license" class="form-control">
                    </div>
                  </div>
                </div>

                <div id="farmerInfo" class="col-12" style="display: none;">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="farmSize" class="form-label">Farm Size (hectares)</label>
                      <input type="number" id="farmSize" name="farm_size" class="form-control" min="0" step="0.1">
                    </div>
                    <div class="col-md-6">
                      <label for="cropTypes" class="form-label">Main Crop Types</label>
                      <input type="text" id="cropTypes" name="crop_types" class="form-control" placeholder="Rice, Vegetables, Fruits">
                      <div class="form-text">Separate multiple crops with commas</div>
                    </div>
                  </div>
                </div>

                <div id="fisherInfo" class="col-12" style="display: none;">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label for="fishingMethod" class="form-label">Fishing Method</label>
                      <select id="fishingMethod" name="fishing_method" class="form-select">
                        <option value="">Select Method</option>
                        <option value="aquaculture">Aquaculture/Fish Farming</option>
                        <option value="commercial">Commercial Fishing</option>
                        <option value="artisanal">Artisanal Fishing</option>
                        <option value="recreational">Recreational Fishing</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label for="fishTypes" class="form-label">Main Fish/Seafood Types</label>
                      <input type="text" id="fishTypes" name="fish_types" class="form-control" placeholder="Tilapia, Bangus, Shrimp">
                      <div class="form-text">Separate multiple types with commas</div>
                    </div>
                  </div>
                </div>
                
                <div id="deliveryPartnerInfo" class="col-12" style="display: none;">
                  <div class="row g-3">
                    <div class="col-12">
                      <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Delivery Partner Information</h6>
                        <p class="mb-0">As a delivery partner, you'll be responsible for fulfilling orders from sellers to buyers. You'll receive notifications when orders are assigned to you and can update delivery statuses through your dashboard.</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-lock me-2"></i>Security</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="password" class="form-label">Password *</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword('password')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <!-- Password Strength Meter -->
                  <div class="password-strength-meter mt-2">
                    <div class="password-strength-fill" style="width: 0%;"></div>
                  </div>
                  <div class="password-strength-text small text-muted mt-1"></div>
                </div>
                <div class="col-md-6">
                  <label for="confirmPassword" class="form-label">Confirm Password *</label>
                  
                  <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                    <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword('confirmPassword')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <!-- Password Match Indicator -->
                  <div class="password-match-indicator mt-2"></div>
                  <div class="password-requirements mt-2">
                    <ul class="list-unstyled small">
                      <li id="passwordMatchIndicator" class="text-muted">
                        <span id="passwordMatchText">Enter password to confirm</span>
                      </li>
                    </ul>
                  </div>
                </div>
                <!-- Password Requirements -->
                <div class="col-12">
                  <div class="password-requirements mt-2">
                    <small class="text-muted">Password must contain:</small>
                    <ul class="list-unstyled small">
                      <li class="text-danger" id="req-length"><i class="fas fa-times-circle me-1"></i> At least 8 characters</li>
                      <li class="text-danger" id="req-uppercase"><i class="fas fa-times-circle me-1"></i> At least one uppercase letter</li>
                      <li class="text-danger" id="req-lowercase"><i class="fas fa-times-circle me-1"></i> At least one lowercase letter</li>
                      <li class="text-danger" id="req-number"><i class="fas fa-times-circle me-1"></i> At least one number</li>
                      <li class="text-danger" id="req-special"><i class="fas fa-times-circle me-1"></i> At least one special character</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-bell me-2"></i>Communication Preferences</h5>
              <div class="row g-3">
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                    <label class="form-check-label" for="newsletter">
                      Subscribe to our newsletter for updates and promotions
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="marketing" name="marketing">
                    <label class="form-check-label" for="marketing">
                      I agree to receive marketing communications
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <h5 class="text-success mb-3"><i class="fas fa-shield-alt me-2"></i>Security Questions</h5>
              <p class="text-muted">Please select security questions that will help you reset your password if forgotten.</p>
              <div class="row g-3">
                <div class="col-12">
                  <label for="securityQuestion1" class="form-label">Security Question 1 *</label>
                  <select id="securityQuestion1" name="security_question_1" class="form-select" required>
                    <option value="">Select a question</option>
                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                    <option value="What city were you born in?">What city were you born in?</option>
                    <option value="What was your first car?">What was your first car?</option>
                    <option value="What is your favorite color?">What is your favorite color?</option>
                    <option value="What is your favorite movie?">What is your favorite movie?</option>
                  </select>
                  <div class="mt-2">
                    <label for="securityAnswer1" class="form-label">Your Answer *</label>
                    <input type="text" id="securityAnswer1" name="security_answer_1" class="form-control" required>
                  </div>
                </div>
                <div class="col-12">
                  <label for="securityQuestion2" class="form-label">Security Question 2 *</label>
                  <select id="securityQuestion2" name="security_question_2" class="form-select" required>
                    <option value="">Select a question</option>
                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                    <option value="What street did you grow up on?">What street did you grow up on?</option>
                    <option value="What is your favorite book?">What is your favorite book?</option>
                    <option value="What is your favorite food?">What is your favorite food?</option>
                    <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                    <option value="What is your favorite teacher's name?">What is your favorite teacher's name?</option>
                  </select>
                  <div class="mt-2">
                    <label for="securityAnswer2" class="form-label">Your Answer *</label>
                    <input type="text" id="securityAnswer2" name="security_answer_2" class="form-control" required>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-user-plus me-2"></i>Create Account
              </button>
            </div>

            <div class="text-center mt-4">
              <p class="mb-0">Already have an account? <a href="login.php" class="text-success">Sign in here</a></p>
              <p class="mb-0"><a href="../registration_help.php" class="text-muted"><i class="fas fa-question-circle me-1"></i>Need help with registration?</a></p>
              <span id="registerMsg" class="text-success"></span>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
  // Function to toggle password visibility
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
  }
  
  // Function to toggle business fields based on account type
  function toggleBusinessFields() {
    const accountType = document.getElementById('accttype').value;
    const businessInfo = document.getElementById('businessInfo');
    const farmerInfo = document.getElementById('farmerInfo');
    const fisherInfo = document.getElementById('fisherInfo');
    const deliveryPartnerInfo = document.getElementById('deliveryPartnerInfo');
    
    // Hide all specialized fields first
    businessInfo.style.display = 'none';
    farmerInfo.style.display = 'none';
    fisherInfo.style.display = 'none';
    deliveryPartnerInfo.style.display = 'none';
    
    // Show relevant fields based on account type
    if (['farmer', 'fisher', 'retailer', 'restaurant', 'delivery_partner'].includes(accountType)) {
      businessInfo.style.display = 'block';
    }
    
    if (accountType === 'farmer') {
      farmerInfo.style.display = 'block';
    }
    
    if (accountType === 'fisher') {
      fisherInfo.style.display = 'block';
    }
    
    if (accountType === 'delivery_partner') {
      deliveryPartnerInfo.style.display = 'block';
    }
  }
  
  // Function to update password requirements display
  function updatePasswordRequirements() {
    const password = document.getElementById('password').value;
    
    // Check each requirement
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    
    // Update UI for each requirement
    updateRequirement('req-length', hasLength, 'At least 8 characters');
    updateRequirement('req-uppercase', hasUppercase, 'At least one uppercase letter');
    updateRequirement('req-lowercase', hasLowercase, 'At least one lowercase letter');
    updateRequirement('req-number', hasNumber, 'At least one number');
    updateRequirement('req-special', hasSpecial, 'At least one special character');
  }
  
  // Helper function to update individual requirement
  function updateRequirement(elementId, isValid, text) {
    const element = document.getElementById(elementId);
    const icon = element.querySelector('i');
    
    if (isValid) {
      element.className = 'text-success';
      icon.className = 'fas fa-check-circle me-1';
    } else {
      element.className = 'text-danger';
      icon.className = 'fas fa-times-circle me-1';
    }
    
    element.innerHTML = icon.outerHTML + text;
  }
  
  // Check for URL parameters and display messages
  document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const registerMsg = document.getElementById('registerMsg');
    if (registerMsg) {
      if (params.get('error')) {
        const err = params.get('error');
        switch(err) {
          case 'exists':
            registerMsg.textContent = 'An account with this email already exists.';
            registerMsg.className = 'text-danger';
            break;
          case 'server':
            registerMsg.textContent = 'Server error. Please try again later.';
            registerMsg.className = 'text-danger';
            break;
          default:
            registerMsg.textContent = 'An error occurred. Please try again.';
            registerMsg.className = 'text-danger';
        }
      } else if (params.get('registered') && params.get('pending')) {
        // Check if it's a delivery partner pending approval
        const acctTypeSelect = document.getElementById('accttype');
        const acctType = acctTypeSelect ? acctTypeSelect.value : '';
        if (acctType === 'delivery_partner') {
          registerMsg.textContent = 'Registration received. Your delivery partner account is pending admin approval.';
          registerMsg.className = 'text-warning';
        } else {
          registerMsg.textContent = 'Registration received. Your seller account is pending admin approval.';
          registerMsg.className = 'text-warning';
        }
      } else if (params.get('registered')) {
        registerMsg.textContent = 'Registration successful. You may now log in.';
        registerMsg.className = 'text-success';
      }
    }
    
    // Add event listeners for password fields
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirmPassword');
    
    if (passwordField) {
      passwordField.addEventListener('input', function() {
        updatePasswordRequirements();
        // Call the external updatePasswordStrength function if available
        if (typeof updatePasswordStrength === 'function') {
          updatePasswordStrength();
        }
      });
    }
    
    if (confirmPasswordField) {
      confirmPasswordField.addEventListener('input', function() {
        // Call the external updatePasswordStrength function if available
        if (typeof updatePasswordStrength === 'function') {
          updatePasswordStrength();
        }
      });
    }
    
    // Auto-select account type based on URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const acctType = urlParams.get('accttype');
    if (acctType) {
      const acctTypeSelect = document.getElementById('accttype');
      if (acctTypeSelect) {
        acctTypeSelect.value = acctType;
        toggleBusinessFields();
      }
    }
  });
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>