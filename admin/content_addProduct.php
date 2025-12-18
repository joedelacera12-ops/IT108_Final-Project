<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only admins may access
require_role('admin');
$user = current_user();
$pdo = get_db();

// Get all sellers and delivery partners for dropdown
try {
    // Get all user roles for mapping
    $rolesMap = [];
    $rolesStmt = $pdo->query("SELECT id, name FROM user_roles");
    while ($role = $rolesStmt->fetch()) {
        $rolesMap[$role['id']] = $role['name'];
    }
    
    // Get all sellers and delivery partners
    $sql = "SELECT u.*, ur.name as role_name FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.id WHERE ur.name IN ('seller', 'delivery_partner') ORDER BY u.first_name, u.last_name";
    $usersStmt = $pdo->prepare($sql);
    $usersStmt->execute();
    $allSellersAndPartners = $usersStmt->fetchAll();
    
    // Ensure each user has proper role name
    foreach ($allSellersAndPartners as &$user) {
        if (empty($user['role_name']) && !empty($user['role'])) {
            // Fallback to legacy role column
            $user['role_name'] = $user['role'];
        } elseif (!empty($user['role_id']) && isset($rolesMap[$user['role_id']])) {
            // Map from role_id
            $user['role_name'] = $rolesMap[$user['role_id']];
        } elseif (empty($user['role_name'])) {
            $user['role_name'] = 'unknown';
        }
    }
    unset($user); // Break the reference
    
    // Convert to JSON for JavaScript
    $sellersJson = json_encode($allSellersAndPartners);
} catch (Exception $e) {
    error_log("Error fetching sellers: " . $e->getMessage());
    $allSellersAndPartners = [];
    $sellersJson = '[]';
}
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title">Add Product</h3>
        
        <div class="card p-3 shadow-sm">
            <h5 class="mb-3">Add Product</h5>
            <form id="adminProductForm" onsubmit="return adminHandleProductSubmit(event)">
                <?php include __DIR__ . '/add-product-form-fields.php'; ?>
                
                <!-- Seller Selection -->
                <div class="mb-3">
                    <label for="sellerSearch" class="form-label">Select Seller *</label>
                    <div class="seller-dropdown-container">
                        <input type="text" class="form-control" id="sellerSearch" placeholder="Search for seller by name or email..." autocomplete="off">
                        <input type="hidden" id="seller_id" name="seller_id" required>
                        <div class="seller-dropdown-menu" id="sellerDropdown"></div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-outline-secondary btn-lg me-2" onclick="adminSaveDraft()">
                        <i class="fas fa-save me-2"></i>Save Draft
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-plus me-2"></i>List Product
                    </button>
                </div>
                <div class="mt-3">
                    <span id="adminProductMsg" class="text-success"></span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Store sellers and delivery partners data from PHP
const sellersData = <?php echo $sellersJson; ?>;

// Category options based on product type
const categories = {
    farmer: {
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

// Initialize seller search functionality
document.addEventListener('DOMContentLoaded', function() {
    const productTypeSelect = document.getElementById('productType');
    const sellerSearch = document.getElementById('sellerSearch');
    const sellerDropdown = document.getElementById('sellerDropdown');
    const sellerIdInput = document.getElementById('seller_id');
    const sellerEmail = document.getElementById('sellerEmail');
    const sellerPhone = document.getElementById('sellerPhone');
    
    let currentIndex = -1;
    let filteredSellers = [];
    
    // Filter sellers based on product type
    productTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        sellerSearch.value = '';
        sellerIdInput.value = '';
        sellerEmail.value = '';
        sellerPhone.value = '';
        sellerDropdown.style.display = 'none';
        currentIndex = -1;
        
        // Filter sellers based on product type
        if (selectedType === 'farmer') {
            filteredSellers = sellersData.filter(seller => seller.seller_type === 'farmer');
        } else if (selectedType === 'fisher') {
            filteredSellers = sellersData.filter(seller => seller.seller_type === 'fisher');
        } else {
            filteredSellers = sellersData;
        }
    });
    
    // Filter sellers based on search input
    sellerSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const selectedType = productTypeSelect.value;
        
        if (searchTerm.length === 0) {
            sellerDropdown.style.display = 'none';
            currentIndex = -1;
            return;
        }
        
        // Filter sellers based on both product type and search term
        let sellersToShow = filteredSellers.length > 0 ? filteredSellers : sellersData;
        
        const searchFilteredSellers = sellersToShow.filter(seller => 
            seller.first_name.toLowerCase().includes(searchTerm) ||
            seller.last_name.toLowerCase().includes(searchTerm) ||
            seller.email.toLowerCase().includes(searchTerm)
        );
        
        if (searchFilteredSellers.length === 0) {
            sellerDropdown.innerHTML = '<div class="seller-option">No sellers found</div>';
            sellerDropdown.style.display = 'block';
            currentIndex = -1;
            return;
        }
        
        let dropdownHTML = '';
        searchFilteredSellers.forEach((seller, index) => {
            const sellerType = seller.seller_type || 'unknown';
            const typeBadge = sellerType === 'farmer' ? 
                '<span class="badge bg-success ms-2">Farmer</span>' : 
                sellerType === 'fisher' ? 
                '<span class="badge bg-info ms-2">Fisher</span>' : 
                '<span class="badge bg-secondary ms-2">Unknown</span>';
            
            dropdownHTML += `<div class="seller-option" data-id="${seller.id}" data-email="${seller.email}" data-phone="${seller.phone || ''}">
                ${seller.first_name} ${seller.last_name} (${seller.email}) ${typeBadge}
            </div>`;
        });
        
        sellerDropdown.innerHTML = dropdownHTML;
        sellerDropdown.style.display = 'block';
        currentIndex = -1;
    });
    
    // Handle keyboard navigation
    sellerSearch.addEventListener('keydown', function(e) {
        const options = sellerDropdown.querySelectorAll('.seller-option');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentIndex = Math.min(currentIndex + 1, options.length - 1);
            updateHighlight(options);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentIndex = Math.max(currentIndex - 1, -1);
            updateHighlight(options);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentIndex >= 0 && options[currentIndex]) {
                selectSeller(options[currentIndex]);
            }
        } else if (e.key === 'Escape') {
            sellerDropdown.style.display = 'none';
            currentIndex = -1;
        }
    });
    
    // Handle click on seller option
    sellerDropdown.addEventListener('click', function(e) {
        const option = e.target.closest('.seller-option');
        if (option) {
            selectSeller(option);
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!sellerSearch.contains(e.target) && !sellerDropdown.contains(e.target)) {
            sellerDropdown.style.display = 'none';
            currentIndex = -1;
        }
    });
    
    // Helper function to update highlighted option
    function updateHighlight(options) {
        options.forEach((option, index) => {
            if (index === currentIndex) {
                option.classList.add('highlight');
            } else {
                option.classList.remove('highlight');
            }
        });
    }
    
    // Helper function to select a seller
    function selectSeller(option) {
        const sellerId = option.getAttribute('data-id');
        const sellerEmailValue = option.getAttribute('data-email');
        const sellerPhoneValue = option.getAttribute('data-phone');
        
        sellerSearch.value = option.textContent.trim().replace(/\s*<.*?>.*?<.*?>\s*/g, '');
        sellerIdInput.value = sellerId;
        sellerEmail.value = sellerEmailValue;
        sellerPhone.value = sellerPhoneValue;
        
        sellerDropdown.style.display = 'none';
        currentIndex = -1;
    }
});

// Update category dropdown when product type changes
// Modified to not reset category selection and preserve existing values
document.getElementById('productType').addEventListener('change', function() {
    const categorySelect = document.getElementById('productCategory');
    const categoryField = document.getElementById('categoryField');
    const selectedType = this.value;
    
    // Don't clear existing options, just update based on selection
    if (selectedType === '') {
        // If no type selected, show placeholder
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select Product Type First';
        
        // Clear and add placeholder only
        categorySelect.innerHTML = '';
        categorySelect.appendChild(placeholder);
        // Show category field for admin
        if (categoryField) categoryField.style.display = 'block';
    } else {
        // For sellers, automatically assign category and hide dropdown
        if (selectedType === 'farmer' || selectedType === 'fisher') {
            // Hide category field for sellers
            if (categoryField) categoryField.style.display = 'none';
            
            // Set hidden autoCategory field
            const autoCategoryField = document.getElementById('autoCategory');
            if (autoCategoryField) {
                // Automatically assign the correct category based on seller type
                autoCategoryField.value = selectedType === 'farmer' ? 'vegetables' : 'seafood';
            }
        } else {
            // Show category field for other types (admin)
            if (categoryField) categoryField.style.display = 'block';
            
            // Preserve existing selection if it's still valid for this type
            const currentValue = categorySelect.value;
            
            // Clear options except the current one if it's valid
            categorySelect.innerHTML = '';
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Category';
            categorySelect.appendChild(defaultOption);
            
            // Add category options based on selected type
            const typeCategories = categories[selectedType];
            let validCurrent = false;
            for (const [value, label] of Object.entries(typeCategories)) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                if (value === currentValue) {
                    option.selected = true;
                    validCurrent = true;
                }
                categorySelect.appendChild(option);
            }
            
            // If current value wasn't valid for this type, keep it selected but add it as an option
            if (currentValue && !validCurrent) {
                const currentOption = document.createElement('option');
                currentOption.value = currentValue;
                currentOption.textContent = currentValue;
                currentOption.selected = true;
                categorySelect.appendChild(currentOption);
            }
        }
    }
});

// Admin form submission handler
function adminHandleProductSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const msgEl = document.getElementById('adminProductMsg');
    
    // Validate required fields
    if (!document.getElementById('seller_id').value) {
        msgEl.textContent = 'Please select a seller';
        msgEl.className = 'text-danger';
        return false;
    }
    
    // Disable submit button during processing
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
    submitBtn.disabled = true;
    
    fetch('/ecommerce_farmers_fishers/admin/add_product.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            msgEl.textContent = 'Product added successfully!';
            msgEl.className = 'text-success';
            form.reset();
            // Reset the email and phone fields
            document.getElementById('sellerEmail').value = '';
            document.getElementById('sellerPhone').value = '';
            document.getElementById('sellerSearch').value = '';
            document.getElementById('seller_id').value = '';
            // Reset product type to trigger category reset
            document.getElementById('productType').value = '';
            document.getElementById('productType').dispatchEvent(new Event('change'));
        } else {
            msgEl.textContent = data.error || 'Failed to add product';
            msgEl.className = 'text-danger';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        msgEl.textContent = 'An error occurred while adding the product';
        msgEl.className = 'text-danger';
    })
    .finally(() => {
        // Re-enable submit button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
    
    return false;
}

function adminSaveDraft() {
    document.getElementById('adminProductMsg').textContent = 'Draft saving not implemented yet.';
}
</script>

<style>
.seller-dropdown-container {
    position: relative;
}

.seller-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(76, 175, 80, 0.15);
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

.seller-dropdown-menu.show {
    display: block;
}

.seller-option {
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.seller-option:hover {
    background-color: #E8F5E9;
}

.seller-option:last-child {
    border-bottom: none;
}

.seller-type-badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
    border-radius: 0.25rem;
    background-color: #E8F5E9;
    color: #4CAF50;
}

.highlight {
    background-color: #0d6efd;
    color: white;
}
</style>