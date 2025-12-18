/**
 * Direct Page Navigation System for AgriSea Dashboards
 * Ensures all navigation links work with direct page loads
 */

// Disabled AJAX navigation functions to ensure direct page navigation
/*
function loadAdminTab(tabName) {
    // This function is disabled to ensure direct page navigation
    // All navigation will now work through direct page loads
}

function loadSellerTab(tabName) {
    // This function is disabled to ensure direct page navigation
}

function loadBuyerTab(tabName) {
    // This function is disabled to ensure direct page navigation
}

function loadDeliveryTab(tabName) {
    // This function is disabled to ensure direct page navigation
}
*/

// Handle browser back/forward buttons
// Disabled to ensure direct page navigation
/*
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.tab) {
        // Reload the appropriate tab based on current page
        const url = window.location.href;
        if (url.includes('/admin/')) {
            loadAdminTab(event.state.tab);
        } else if (url.includes('/seller/')) {
            loadSellerTab(event.state.tab);
        } else if (url.includes('/buyer/')) {
            loadBuyerTab(event.state.tab);
        } else if (url.includes('/delivery_partner/')) {
            loadDeliveryTab(event.state.tab);
        }
    }
});
*/

// Initialize navigation handlers when DOM is loaded
// Disabled to ensure direct page navigation
/*
document.addEventListener('DOMContentLoaded', function() {
    // Admin dashboard tab links
    const adminTabLinks = document.querySelectorAll('a[href^="/ecommerce_farmers_fishers/admin/dashboard.php?tab="]');
    adminTabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('href').split('tab=')[1];
            loadAdminTab(tabName);
        });
    });
    
    // Seller dashboard tab links
    const sellerTabLinks = document.querySelectorAll('a[href^="/ecommerce_farmers_fishers/seller/dashboard.php?tab="]');
    sellerTabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('href').split('tab=')[1];
            loadSellerTab(tabName);
        });
    });
    
    // Buyer dashboard tab links
    const buyerTabLinks = document.querySelectorAll('a[href^="/ecommerce_farmers_fishers/buyer/dashboard.php?tab="]');
    buyerTabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('href').split('tab=')[1];
            loadBuyerTab(tabName);
        });
    });
    
    // Delivery partner dashboard tab links
    const deliveryTabLinks = document.querySelectorAll('a[href^="/ecommerce_farmers_fishers/delivery_partner/dashboard.php?tab="]');
    deliveryTabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('href').split('tab=')[1];
            loadDeliveryTab(tabName);
        });
    });
});
*/