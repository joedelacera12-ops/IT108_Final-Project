<?php
$page_title = "Login";
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
        case 4: // Delivery Partner
            redirect_to('/ecommerce_farmers_fishers/delivery_partner/dashboard.php');
            break;
        default: // Buyer or any other role
            redirect_to('/ecommerce_farmers_fishers/buyer/dashboard.php');
            break;
    }
}

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-header text-center bg-success text-white">
          <h3><i class="fas fa-leaf me-2"></i>AgriSea Login</h3>
        </div>
        <div class="card-body">
          <form id="loginForm" method="post" onsubmit="return handleLogin(event)">
            <div class="mb-3">
              <label for="loginIdentifier" class="form-label">Email or Phone *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" id="loginIdentifier" name="identifier" class="form-control" placeholder="Enter email or phone" required>
              </div>
            </div>
            
            <div class="mb-3">
              <label for="loginPassword" class="form-label">Password *</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" id="loginPassword" name="password" class="form-control" placeholder="Enter password" required>
                <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword('loginPassword')">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            
            <div class="mb-3 form-check">
              <input class="form-check-input" type="checkbox" id="rememberMe">
              <label class="form-check-label" for="rememberMe">
                Remember me
              </label>
            </div>
            
            <div class="mb-3 text-end">
              <a href="#" class="text-muted small" onclick="showForgotPassword()">Forgot Password?</a>
            </div>
            
            <div class="d-grid mb-3">
              <button type="submit" class="btn btn-success">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
              </button>
            </div>
            
            <div class="text-center">
              <p class="mb-0">Don't have an account? <a href="register.php" class="text-success">Register here</a></p>
              <span id="loginMsg" class="text-success"></span>
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
  
  // Function to show forgot password modal
  function showForgotPassword() {
    // Create modal element
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'forgotPasswordModal';
    modal.tabIndex = '-1';
    modal.setAttribute('data-bs-backdrop', 'static');
    modal.setAttribute('data-bs-keyboard', 'false');
    modal.innerHTML = `
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reset Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="forgotPasswordStep1">
              <p>Enter your email address to begin the password reset process.</p>
              <form id="forgotPasswordForm">
                <div class="mb-3">
                  <label for="resetEmail" class="form-label">Email Address</label>
                  <input type="email" class="form-control" id="resetEmail" required>
                </div>
                <div id="resetMsg1" class="mb-3"></div>
              </form>
            </div>
            <div id="forgotPasswordStep2" style="display: none;">
              <p id="securityQuestionText"></p>
              <form id="securityQuestionForm">
                <div class="mb-3">
                  <label for="securityAnswer" class="form-label">Your Answer</label>
                  <input type="text" class="form-control" id="securityAnswer" required>
                </div>
                <div id="resetMsg2" class="mb-3"></div>
              </form>
            </div>
            <div id="forgotPasswordStep3" style="display: none;">
              <p>Set your new password.</p>
              <form id="newPasswordForm">
                <div class="mb-3">
                  <label for="newPassword" class="form-label">New Password</label>
                  <div class="input-group">
                    <input type="password" class="form-control" id="newPassword" required>
                    <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword('newPassword')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <!-- Password Requirements -->
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
                <div class="mb-3">
                  <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                  <div class="input-group">
                    <input type="password" class="form-control" id="confirmNewPassword" required>
                    <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword('confirmNewPassword')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                  <div id="passwordMatchIndicator" class="mt-2 text-muted">Enter password to confirm</div>
                </div>
                <div id="resetMsg3" class="mb-3"></div>
              </form>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success" id="nextButton" onclick="handleForgotPasswordStep1()">Next</button>
          </div>
        </div>
      </div>
    `;
    
    document.body.appendChild(modal);
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Remove modal from DOM when closed
    modal.addEventListener('hidden.bs.modal', function () {
      document.body.removeChild(modal);
    });
    
    // Add event listeners for password fields in step 3
    modal.addEventListener('shown.bs.modal', function () {
      const newPasswordField = document.getElementById('newPassword');
      const confirmNewPasswordField = document.getElementById('confirmNewPassword');
      
      if (newPasswordField) {
        newPasswordField.addEventListener('input', function() {
          updatePasswordRequirements();
          checkPasswordMatch();
        });
      }
      
      if (confirmNewPasswordField) {
        confirmNewPasswordField.addEventListener('input', checkPasswordMatch);
      }
    });
}

// Function to update password requirements display
function updatePasswordRequirements() {
  const password = document.getElementById('newPassword').value;
  
  // Check each requirement
  const hasLength = password.length >= 8;
  const hasUppercase = /[A-Z]/.test(password);
  const hasLowercase = /[a-z]/.test(password);
  const hasNumber = /\d/.test(password);
  const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password);
  
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

// Function to check password match
function checkPasswordMatch() {
  const newPassword = document.getElementById('newPassword').value;
  const confirmPassword = document.getElementById('confirmNewPassword').value;
  const matchIndicator = document.getElementById('passwordMatchIndicator');
  
  if (confirmPassword === '') {
    matchIndicator.className = 'mt-2 text-muted';
    matchIndicator.textContent = 'Enter password to confirm';
    return;
  }
  
  if (newPassword === confirmPassword) {
    matchIndicator.className = 'mt-2 text-success';
    matchIndicator.innerHTML = '<i class="fas fa-check-circle me-1"></i> Passwords match';
  } else {
    matchIndicator.className = 'mt-2 text-danger';
    matchIndicator.innerHTML = '<i class="fas fa-times-circle me-1"></i> Passwords do not match';
  }
}

// Global variables to store user data during the reset process
let resetUserId = null;
let resetSecurityQuestion = null;

// Step 1: Validate email and get security question
function handleForgotPasswordStep1() {
  const email = document.getElementById('resetEmail').value;
  const msg = document.getElementById('resetMsg1');
  
  // Simple email validation
  if (!email || !email.includes('@')) {
    msg.textContent = 'Please enter a valid email address.';
    msg.className = 'text-danger';
    return;
  }
  
  // Make AJAX request to get security question
  fetch('/ecommerce_farmers_fishers/process_reset_password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=get_security_question&email=' + encodeURIComponent(email)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      resetUserId = data.user_id;
      resetSecurityQuestion = data.question;
      
      // Show step 2
      document.getElementById('forgotPasswordStep1').style.display = 'none';
      document.getElementById('forgotPasswordStep2').style.display = 'block';
      document.getElementById('securityQuestionText').textContent = resetSecurityQuestion;
      document.getElementById('nextButton').onclick = handleForgotPasswordStep2;
      document.getElementById('nextButton').textContent = 'Next';
    } else {
      msg.textContent = data.error;
      msg.className = 'text-danger';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    msg.textContent = 'An error occurred. Please try again.';
    msg.className = 'text-danger';
  });
}

// Step 2: Validate security answer
function handleForgotPasswordStep2() {
  const answer = document.getElementById('securityAnswer').value;
  const msg = document.getElementById('resetMsg2');
  
  if (!answer) {
    msg.textContent = 'Please enter your answer.';
    msg.className = 'text-danger';
    return;
  }
  
  // Make AJAX request to validate security answer
  fetch('/ecommerce_farmers_fishers/process_reset_password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=validate_security_answer&user_id=' + encodeURIComponent(resetUserId) + '&answer=' + encodeURIComponent(answer)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show step 3
      document.getElementById('forgotPasswordStep2').style.display = 'none';
      document.getElementById('forgotPasswordStep3').style.display = 'block';
      document.getElementById('nextButton').onclick = handleForgotPasswordStep3;
      document.getElementById('nextButton').textContent = 'Reset Password';
    } else {
      msg.textContent = data.error;
      msg.className = 'text-danger';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    msg.textContent = 'An error occurred. Please try again.';
    msg.className = 'text-danger';
  });
}

// Step 3: Set new password
function handleForgotPasswordStep3() {
  const newPassword = document.getElementById('newPassword').value;
  const confirmNewPassword = document.getElementById('confirmNewPassword').value;
  const msg = document.getElementById('resetMsg3');
  
  if (!newPassword || !confirmNewPassword) {
    msg.textContent = 'Please fill in both password fields.';
    msg.className = 'text-danger';
    return;
  }
  
  if (newPassword !== confirmNewPassword) {
    msg.textContent = 'Passwords do not match.';
    msg.className = 'text-danger';
    return;
  }
  
  // Enhanced password validation
  if (newPassword.length < 8) {
    msg.textContent = 'Password must be at least 8 characters long.';
    msg.className = 'text-danger';
    return;
  }
  
  if (!/[A-Z]/.test(newPassword)) {
    msg.textContent = 'Password must contain at least one uppercase letter.';
    msg.className = 'text-danger';
    return;
  }
  
  if (!/[a-z]/.test(newPassword)) {
    msg.textContent = 'Password must contain at least one lowercase letter.';
    msg.className = 'text-danger';
    return;
  }
  
  if (!/\d/.test(newPassword)) {
    msg.textContent = 'Password must contain at least one number.';
    msg.className = 'text-danger';
    return;
  }
  
  if (!/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(newPassword)) {
    msg.textContent = 'Password must contain at least one special character.';
    msg.className = 'text-danger';
    return;
  }
  
  // Make AJAX request to reset password
  fetch('/ecommerce_farmers_fishers/process_reset_password.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=reset_password&user_id=' + encodeURIComponent(resetUserId) + '&new_password=' + encodeURIComponent(newPassword) + '&confirm_password=' + encodeURIComponent(confirmNewPassword)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      msg.textContent = data.message;
      msg.className = 'text-success';
      
      // Close the modal after a short delay
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal'));
        if (modal) modal.hide();
      }, 2000);
    } else {
      msg.textContent = data.error;
      msg.className = 'text-danger';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    msg.textContent = 'An error occurred. Please try again.';
    msg.className = 'text-danger';
  });
}

// Check for URL parameters and display messages
document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const loginMsg = document.getElementById('loginMsg');
  if (loginMsg) {
    if (params.get('error')) {
      const err = params.get('error');
      switch(err) {
        case 'invalid':
          loginMsg.textContent = 'Invalid credentials. Please try again.';
          loginMsg.className = 'text-danger';
          break;
        case 'missing':
          loginMsg.textContent = 'Please fill in both identifier and password.';
          loginMsg.className = 'text-danger';
          break;
        default:
          loginMsg.textContent = 'An error occurred. Please try again.';
          loginMsg.className = 'text-danger';
      }
    }
  }
});
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>