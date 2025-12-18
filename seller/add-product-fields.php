<?php
// Include the actual form fields
include __DIR__ . '/add-product-form-fields.php';

// Get current user info for auto-filling seller fields
require_once __DIR__ . '/../includes/auth.php';
$user = current_user();

// Auto-detect user role for seller-specific behavior
$userRole = '';
if (isset($user['role']) && $user['role'] === 'seller') {
  // Check if user has farmer or fisher role specifically
  // Safely check for first_name and last_name to avoid undefined index warnings
  $firstName = $user['first_name'] ?? '';
  $lastName = $user['last_name'] ?? '';
  
  if (strpos(strtolower($firstName), 'farmer') !== false || 
      strpos(strtolower($lastName), 'farmer') !== false) {
    $userRole = 'farmer';
  } else if (strpos(strtolower($firstName), 'fisher') !== false || 
             strpos(strtolower($lastName), 'fisher') !== false) {
    $userRole = 'fisher';
  }
}
?>
<script>
// Auto-fill seller information on page load
document.addEventListener('DOMContentLoaded', function() {
  const sellerEmail = document.getElementById('sellerEmail');
  const sellerPhone = document.getElementById('sellerPhone');
  
  if (sellerEmail && sellerPhone && <?php echo json_encode($user); ?>) {
    sellerEmail.value = <?php echo json_encode($user['email'] ?? ''); ?>;
    sellerPhone.value = <?php echo json_encode($user['phone'] ?? ''); ?>;
  }
  
  // Auto-select product type based on user role if applicable
  const productTypeSelect = document.getElementById('productType');
  if (productTypeSelect && '<?php echo $userRole; ?>' !== '') {
    productTypeSelect.value = '<?php echo $userRole; ?>';
    // Trigger change event to update category field
    productTypeSelect.dispatchEvent(new Event('change'));
  }
});

// Category options based on product type
const categories = {
  farmer: {
    'vegetables': 'Vegetables',
    'fruits': 'Fruits',
    'grains': 'Grains & Cereals',
    'dairy': 'Dairy Products',
    'poultry': 'Poultry & Eggs',
    'herbs': 'Herbs & Spices',
    'other': 'Other'
  },
  fisher: {
    'seafood': 'Seafood',
    'fresh_fish': 'Fresh Fish',
    'shellfish': 'Shellfish',
    'crustaceans': 'Crustaceans',
    'seaweed': 'Seaweed',
    'processed_seafood': 'Processed Seafood',
    'other': 'Other'
  }
};

// Update category dropdown when product type changes
document.getElementById('productType').addEventListener('change', function() {
  const categorySelect = document.getElementById('productCategory');
  const categoryField = document.getElementById('categoryField');
  const autoCategoryField = document.getElementById('autoCategory');
  const selectedType = this.value;
  
  if (selectedType === '') {
    // If no type selected, hide category field
    if (categoryField) categoryField.style.display = 'none';
    // Clear auto category
    if (autoCategoryField) autoCategoryField.value = '';
  } else {
    // For sellers, automatically assign category and hide dropdown
    if (selectedType === 'farmer' || selectedType === 'fisher') {
      // Hide category field for sellers
      if (categoryField) categoryField.style.display = 'none';
      
      // Set hidden autoCategory field
      if (autoCategoryField) {
        // Automatically assign the correct category based on seller type
        autoCategoryField.value = selectedType === 'farmer' ? 'vegetables' : 'seafood';
      }
    } else {
      // Show category field for other types (admin)
      if (categoryField) categoryField.style.display = 'block';
      
      // Clear and populate category options
      categorySelect.innerHTML = '';
      
      // Add default option
      const defaultOption = document.createElement('option');
      defaultOption.value = '';
      defaultOption.textContent = 'Select Category';
      categorySelect.appendChild(defaultOption);
      
      // Add category options based on selected type
      const typeCategories = categories[selectedType];
      for (const [value, label] of Object.entries(typeCategories)) {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        categorySelect.appendChild(option);
      }
    }
  }
});

// Add validation to ensure category is selected when form is submitted
// Handle both admin and seller forms
const forms = ['adminProductForm', 'productForm'];
forms.forEach(formId => {
  const form = document.getElementById(formId);
  if (form) {
    form.addEventListener('submit', function(e) {
      const productType = document.getElementById('productType').value;
      const productCategory = document.getElementById('productCategory').value;
      const autoCategory = document.getElementById('autoCategory')?.value;
      const productTerms = document.getElementById('productTerms')?.checked;
      const productStatus = document.getElementById('productStatus')?.value;
      
      console.log('Form validation started:', { productType, productCategory, autoCategory, productTerms, productStatus });
      
      // Validate product name is provided
      const productName = document.getElementById('productName')?.value.trim();
      if (!productName) {
        e.preventDefault();
        alert('Product name is required');
        return false;
      }
      
      // Validate other required fields
      const productDescription = document.getElementById('productDescription')?.value.trim();
      const price = document.getElementById('price')?.value;
      const unit = document.getElementById('unit')?.value;
      const stock = document.getElementById('stock')?.value;
      
      if (!productDescription) {
        e.preventDefault();
        alert('Product description is required');
        return false;
      }
      
      if (!price || parseFloat(price) <= 0) {
        e.preventDefault();
        alert('Valid price is required');
        return false;
      }
      
      if (!unit) {
        e.preventDefault();
        alert('Unit of measurement is required');
        return false;
      }
      
      if (!stock || parseInt(stock) < 0) {
        e.preventDefault();
        alert('Valid stock quantity is required');
        return false;
      }
      
      if (productType === '') {
        e.preventDefault();
        alert('Please select a product type (Farm Product or Sea Product)');
        return false;
      }
      
      // For sellers, category is auto-assigned, so we don't need to validate
      if (productType === 'farmer' || productType === 'fisher') {
        console.log('Validating seller form');
        // Category is auto-assigned, no validation needed
        // Validate terms are accepted (only for active listings, not drafts)
        if (productStatus === 'active' && !productTerms) {
          e.preventDefault();
          alert('Please accept the terms and conditions');
          return false;
        }
        console.log('Seller validation passed, allowing form submission');
        // Allow form to be submitted normally - don't prevent default
        return true;
      }
      
      // For admin, validate category is selected
      if (productCategory === '') {
        e.preventDefault();
        alert('Please select a product category');
        return false;
      }
      
      // Validate terms are accepted (only for active listings, not drafts)
      if (productStatus === 'active' && !productTerms) {
        e.preventDefault();
        alert('Please accept the terms and conditions');
        return false;
      }
      
      console.log('Admin validation passed, allowing form submission');
      // If we get here, validation passed - allow form submission to proceed
      // Allow the form to submit normally
      return true;
    });
  }
});

// Function to show Seller Terms modal
function showSellerTerms() {
  // Create modal if it doesn't exist
  let modal = document.getElementById('sellerTermsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'sellerTermsModal';
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Seller Terms and Conditions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <h6>1. Account Registration</h6>
            <p>By registering as a seller, you agree to provide accurate and complete information about yourself and your business.</p>
            
            <h6>2. Product Listings</h6>
            <p>You are responsible for ensuring that all product information, including descriptions, prices, and images, are accurate and not misleading.</p>
            
            <h6>3. Pricing and Fees</h6>
            <p>All prices must be in Philippine Pesos (₱). Agrisea charges a commission fee on each sale, which will be deducted from your earnings.</p>
            
            <h6>4. Product Quality</h6>
            <p>You warrant that all products sold through Agrisea are of satisfactory quality, fit for their intended purpose, and comply with all applicable laws and regulations.</p>
            
            <h6>5. Shipping and Delivery</h6>
            <p>You are responsible for packaging products securely and arranging timely delivery to buyers. Shipping costs and delivery times must be clearly stated.</p>
            
            <h6>6. Customer Service</h6>
            <p>You must respond to customer inquiries promptly and professionally. Agrisea reserves the right to mediate disputes between buyers and sellers.</p>
            
            <h6>7. Prohibited Items</h6>
            <p>You may not list illegal, hazardous, or prohibited items. This includes but is not limited to: weapons, drugs, counterfeit goods, and perishable items that cannot be shipped safely.</p>
            
            <h6>8. Account Suspension</h6>
            <p>Agrisea reserves the right to suspend or terminate your seller account for violations of these terms or for any reason deemed necessary for the platform's integrity.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }
  
  // Initialize and show Bootstrap modal
  const bootstrapModal = new bootstrap.Modal(modal);
  bootstrapModal.show();
}

// Function to show Marketplace Guidelines modal
function showMarketplaceGuidelines() {
  // Create modal if it doesn't exist
  let modal = document.getElementById('marketplaceGuidelinesModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'marketplaceGuidelinesModal';
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Marketplace Guidelines</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <h6>1. Fair Trading Practices</h6>
            <p>All sellers must engage in fair trading practices. Misleading advertising, false claims, or deceptive business practices are strictly prohibited.</p>
            
            <h6>2. Accurate Product Descriptions</h6>
            <p>Product descriptions must accurately reflect the item being sold. Include all relevant details such as size, weight, ingredients, and origin.</p>
            
            <h6>3. Competitive Pricing</h6>
            <p>Prices should be competitive and reflect the true market value. Price gouging or artificial inflation of prices is not permitted.</p>
            
            <h6>4. Quality Assurance</h6>
            <p>Maintain high standards of product quality. Fresh produce must meet quality standards and be properly packaged for shipping.</p>
            
            <h6>5. Timely Communication</h6>
            <p>Respond to buyer inquiries within 24 hours. Provide regular updates on order status and shipping information.</p>
            
            <h6>6. Secure Transactions</h6>
            <p>Use only Agrisea's secure payment system. Do not attempt to redirect buyers to external payment platforms.</p>
            
            <h6>7. Sustainable Practices</h6>
            <p>We encourage sustainable farming and fishing practices. Highlight eco-friendly practices in your product listings where applicable.</p>
            
            <h6>8. Community Engagement</h6>
            <p>Engage positively with the Agrisea community. Share knowledge, respond to reviews professionally, and contribute to building trust.</p>
            
            <h6>9. Compliance with Laws</h6>
            <p>All activities on Agrisea must comply with Philippine laws and regulations, including but not limited to food safety standards and business licensing requirements.</p>
            
            <h6>10. Continuous Improvement</h6>
            <p>We continuously update our guidelines to improve the marketplace. Stay informed about changes and adapt your practices accordingly.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  }
  
  // Initialize and show Bootstrap modal
  const bootstrapModal = new bootstrap.Modal(modal);
  bootstrapModal.show();
}
</script>