/**
 * Optimized Navigation System for AgriSea Dashboards
 * Provides smooth tab navigation with optimized loading
 */

// Simple navigation handler that improves user experience without AJAX overhead
document.addEventListener('DOMContentLoaded', function() {
    // Add loading indicators to all navigation links
    const allNavLinks = document.querySelectorAll('a[href*="dashboard.php?tab="]');
    
    allNavLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add visual feedback immediately
            this.classList.add('active');
            
            // Show loading state on the page
            showPageLoading();
        });
    });
    
    // Show page loading indicator
    function showPageLoading() {
        // Create a simple overlay loader
        const loader = document.createElement('div');
        loader.id = 'page-loader';
        loader.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255, 255, 255, 0.7); z-index: 9999; 
                        display: flex; justify-content: center; align-items: center;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(loader);
        
        // Remove loader after a short delay to prevent flickering
        setTimeout(() => {
            if (loader.parentNode) {
                loader.parentNode.removeChild(loader);
            }
        }, 100);
    }
    
    // Enhance tab navigation with smooth scrolling
    const tabLinks = document.querySelectorAll('.nav-link');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add active class immediately for visual feedback
            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

// Handle browser back/forward buttons for better UX
window.addEventListener('popstate', function(event) {
    // Add subtle transition effect
    document.body.style.transition = 'opacity 0.1s ease';
    document.body.style.opacity = '0.9';
    
    // Restore after navigation completes
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 50);
});