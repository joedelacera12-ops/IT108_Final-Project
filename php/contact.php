<?php
// Set page title
$page_title = 'Contact - AgriSea Marketplace';

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <div class="text-center mb-5">
        <h2 class="display-6 fw-bold text-success">Get in Touch</h2>
        <p class="lead text-muted">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
      </div>

      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
              <div class="text-success mb-3">
                <i class="fas fa-envelope fa-2x"></i>
              </div>
              <h5 class="card-title">Email Us</h5>
              <p class="card-text text-muted">support@agrisea.com</p>
              <p class="card-text text-muted">info@agrisea.com</p>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
              <div class="text-success mb-3">
                <i class="fas fa-phone fa-2x"></i>
              </div>
              <h5 class="card-title">Call Us</h5>
              <p class="card-text text-muted">+63 2 1234 5678</p>
              <p class="card-text text-muted">+63 917 123 4567</p>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-4">
          <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
              <div class="text-success mb-3">
                <i class="fas fa-map-marker-alt fa-2x"></i>
              </div>
              <h5 class="card-title">Visit Us</h5>
              <p class="card-text text-muted">Cabadbaran, Philippines</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="card-title mb-4">Send us a Message</h4>
          <form id="contactForm" onsubmit="return handleContactForm(event)">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="firstName" class="form-label">First Name *</label>
                <input type="text" class="form-control" id="firstName" required>
              </div>
              <div class="col-md-6">
                <label for="lastName" class="form-label">Last Name *</label>
                <input type="text" class="form-control" id="lastName" required>
              </div>
              <div class="col-md-6">
                <label for="email" class="form-label">Email Address *</label>
                <input type="email" class="form-control" id="email" required>
              </div>
              <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phone">
              </div>
              <div class="col-12">
                <label for="subject" class="form-label">Subject *</label>
                <select class="form-select" id="subject" required>
                  <option value="">Select a subject</option>
                  <option value="general">General Inquiry</option>
                  <option value="support">Technical Support</option>
                  <option value="partnership">Partnership Opportunity</option>
                  <option value="feedback">Feedback</option>
                  <option value="complaint">Complaint</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-12">
                <label for="message" class="form-label">Message *</label>
                <textarea class="form-control" id="message" rows="5" placeholder="Please describe your inquiry in detail..." required></textarea>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="newsletter">
                  <label class="form-check-label" for="newsletter">
                    Subscribe to our newsletter for updates and special offers
                  </label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="privacy" required>
                  <label class="form-check-label" for="privacy">
                    I agree to the <a href="#" class="text-success">Privacy Policy</a> and <a href="#" class="text-success">Terms of Service</a> *
                  </label>
                </div>
              </div>
            </div>
            <div class="mt-4">
              <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-paper-plane me-2"></i>Send Message
              </button>
              <span id="contactMsg" class="ms-3"></span>
            </div>
          </form>
        </div>
      </div>

      <div class="row mt-5">
        <div class="col-md-6">
          <h5 class="text-success mb-3">Business Hours</h5>
          <ul class="list-unstyled">
            <li><strong>Monday - Saturday:</strong> 8:00 AM - 6:00 PM</li>
            <li><strong>Sunday:</strong> 9:00 AM - 5:00 PM</li>
          </ul>
        </div>
        <div class="col-md-6">
          <h5 class="text-success mb-3">Response Time</h5>
          <ul class="list-unstyled">
            <li><strong>General Inquiries:</strong> Within 24 hours</li>
            <li><strong>Technical Support:</strong> Within 4 hours</li>
            <li><strong>Urgent Issues:</strong> Within 1 hour</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  async function handleContactForm(e) {
    e.preventDefault();
    
    // Get form values
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const subject = document.getElementById('subject').value;
    const message = document.getElementById('message').value;
    const privacy = document.getElementById('privacy').checked;
    
    // Simple form validation
    if (!firstName || !lastName || !email || !subject || !message || !privacy) {
      document.getElementById('contactMsg').innerHTML = '<span class="text-danger">Please fill in all required fields.</span>';
      return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      document.getElementById('contactMsg').innerHTML = '<span class="text-danger">Please enter a valid email address.</span>';
      return false;
    }
    
    // Disable submit button during submission
    const submitBtn = document.querySelector('#contactForm button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
    
    try {
      // Submit form data to backend
      const response = await fetch('/ecommerce_farmers_fishers/api/process_contact.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          firstName: firstName,
          lastName: lastName,
          email: email,
          phone: phone,
          subject: subject,
          message: message
        })
      });
      
      const result = await response.json();
      
      if (result.success) {
        document.getElementById('contactMsg').innerHTML = '<span class="text-success">' + result.data.message + '</span>';
        // Reset form
        document.getElementById('contactForm').reset();
      } else {
        document.getElementById('contactMsg').innerHTML = '<span class="text-danger">' + (result.error || 'Failed to send message. Please try again.') + '</span>';
      }
    } catch (error) {
      document.getElementById('contactMsg').innerHTML = '<span class="text-danger">Network error. Please check your connection and try again.</span>';
    } finally {
      // Re-enable submit button
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
    
    return false;
  }
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>