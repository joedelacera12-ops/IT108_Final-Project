<?php
$page_title = "Registration Help";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <h4 class="mb-0"><i class="fas fa-question-circle me-2"></i>Registration Troubleshooting</h4>
        </div>
        <div class="card-body">
          <h5 class="text-success">Common Registration Issues and Solutions</h5>
          
          <div class="accordion" id="registrationHelpAccordion">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                  <i class="fas fa-exclamation-triangle text-warning me-2"></i>Network or Server Error Message
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#registrationHelpAccordion">
                <div class="accordion-body">
                  <p>If you see "Network or server error while attempting registration", check these requirements:</p>
                  <ul>
                    <li><strong>Must use a PHP server</strong>: Access via <code>http://localhost/...</code>, not file://</li>
                    <li><strong>XAMPP must be running</strong>: Apache service should be started</li>
                    <li><strong>Don't use VS Code Live Server</strong>: It doesn't execute PHP code</li>
                    <li><strong>Correct URL</strong>: Use <code>http://localhost/ecommerce_farmers_fishers/php/register.php</code></li>
                  </ul>
                  <div class="alert alert-info">
                    <strong>Tip:</strong> Start XAMPP Control Panel, click "Start" for Apache, then open your browser to the correct localhost URL.
                  </div>
                </div>
              </div>
            </div>
            
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                  <i class="fas fa-mobile-alt text-primary me-2"></i>Phone Number Format Requirements
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#registrationHelpAccordion">
                <div class="accordion-body">
                  <p>Phone numbers must be in valid Philippine format:</p>
                  <ul>
                    <li><strong>Mobile format:</strong> <code>+639171234567</code> or <code>09171234567</code></li>
                    <li><strong>Landline format:</strong> <code>+6321234567</code> or <code>021234567</code></li>
                    <li>No spaces, dashes, or parentheses</li>
                    <li>Exactly 10 digits after +63 or 0</li>
                  </ul>
                  <div class="alert alert-warning">
                    <strong>Examples of INVALID formats:</strong> +1234567890, 917-123-4567, (0917) 123 4567
                  </div>
                </div>
              </div>
            </div>
            
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                  <i class="fas fa-key text-info me-2"></i>Password Requirements
                </button>
              </h2>
              <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#registrationHelpAccordion">
                <div class="accordion-body">
                  <p>Passwords must meet these security requirements:</p>
                  <ul>
                    <li>At least 8 characters long</li>
                    <li>Contain at least one uppercase letter (A-Z)</li>
                    <li>Contain at least one lowercase letter (a-z)</li>
                    <li>Contain at least one number (0-9)</li>
                    <li>Contain at least one special character (!@#$%^&* etc.)</li>
                  </ul>
                  <p><strong>Example valid password:</strong> <code>@Abcde12345</code></p>
                </div>
              </div>
            </div>
            
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                  <i class="fas fa-database text-danger me-2"></i>Database Connection Issues
                </button>
              </h2>
              <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#registrationHelpAccordion">
                <div class="accordion-body">
                  <p>If the database isn't working:</p>
                  <ol>
                    <li>Ensure MySQL service is running in XAMPP</li>
                    <li>Check that the database <code>agrisea</code> exists</li>
                    <li>Verify database credentials in <code>/includes/db.php</code></li>
                    <li>Run the initialization script if needed</li>
                  </ol>
                  <a href="/ecommerce_farmers_fishers/initialize_db.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-sync me-1"></i>Initialize Database
                  </a>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-4">
            <h5 class="text-success">Quick Test Links</h5>
            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
              <a href="/ecommerce_farmers_fishers/php/register.php" class="btn btn-success">
                <i class="fas fa-user-plus me-2"></i>Registration Page
              </a>
              <a href="/ecommerce_farmers_fishers/php/login.php" class="btn btn-outline-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Login Page
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>