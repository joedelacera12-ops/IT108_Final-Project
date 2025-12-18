// Dashboard Loader - Handles dynamic content loading for all dashboards
$(document).ready(function() {
    // Global function to load content dynamically
    window.loadContent = function(contentType, params = {}) {
        // Show loading spinner if exists
        if ($('#loadingSpinner').length) {
            $('#loadingSpinner').show();
        }
        
        // Determine the role from the current URL or sidebar
        let role = 'buyer';
        if (window.location.href.includes('/admin/')) {
            role = 'admin';
        } else if (window.location.href.includes('/seller/')) {
            role = 'seller';
        } else if (window.location.href.includes('/delivery_partner/')) {
            role = 'delivery_partner';
        }
        
        // Update active tab in tab navigation
        $('.nav-tabs .nav-link').removeClass('active');
        $('.nav-tabs .nav-link').each(function() {
            const tabText = $(this).text().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
            if (tabText === contentType.toLowerCase()) {
                $(this).addClass('active');
            }
        });
        
        // Update active link in sidebar
        $('.dash-sidebar .nav-link').removeClass('active');
        $('.dash-sidebar .nav-link').each(function() {
            const linkText = $(this).text().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
            if (linkText === contentType.toLowerCase()) {
                $(this).addClass('active');
            }
        });
        
        // Determine URL based on role and content type
        let url;
        switch(role) {
            case 'admin':
                url = '/ecommerce_farmers_fishers/admin/content_' + contentType + '.php';
                break;
            case 'seller':
                url = '/ecommerce_farmers_fishers/seller/content_' + contentType + '.php';
                break;
            case 'delivery_partner':
                url = '/ecommerce_farmers_fishers/delivery_partner/content_' + contentType + '.php';
                break;
            default: // buyer
                url = '/ecommerce_farmers_fishers/buyer/content_' + contentType + '.php';
        }
        
        // Add parameters to URL if provided
        if (Object.keys(params).length > 0) {
            const queryParams = new URLSearchParams(params);
            url += '?' + queryParams.toString();
        }
        
        // Load content via AJAX
        $.get(url)
            .done(function(data) {
                if ($('#mainContent').length) {
                    $('#mainContent').html(data);
                } else {
                    // Fallback to replacing the first container-fluid
                    $('.container-fluid').first().html(data);
                }
                if ($('#loadingSpinner').length) {
                    $('#loadingSpinner').hide();
                }
            })
            .fail(function() {
                let errorMessage = '<div class="alert alert-danger">Failed to load content. Please try again.</div>';
                if ($('#mainContent').length) {
                    $('#mainContent').html(errorMessage);
                } else {
                    $('.container-fluid').first().html(errorMessage);
                }
                if ($('#loadingSpinner').length) {
                    $('#loadingSpinner').hide();
                }
            });
    };
    
    // Handle tab navigation clicks
    $(document).on('click', '.nav-tabs .nav-link, .dash-sidebar .nav-link, .unified-navbar .nav-link[data-tab]', function(e) {
        // Don't handle logout links or external links
        const href = $(this).attr('href');
        if (href && (href.includes('logout.php') || href.startsWith('http'))) {
            return;
        }
        
        e.preventDefault();
        
        // Get content type from the data-tab attribute for unified navbar
        if ($(this).data('tab')) {
            loadContent($(this).data('tab'));
            return;
        }
        
        // Get content type from the link text or data attribute
        let contentType = 'overview';
        const linkText = $(this).text().trim();
        
        // Map common link texts to content types
        const textToTypeMap = {
            'overview': 'overview',
            'marketplace': 'marketplace',
            'add product': 'addProduct',
            'my products': 'products',
            'orders': 'orders',
            'accounts': 'accounts',
            'manage users': 'accounts',
            'products': 'products',
            'analytics': 'analytics',
            'payments': 'payments',
            'contact messages': 'contact_messages',
            'user messages': 'user_messages',
            'reports': 'reports',
            'ratings': 'ratings',
            'enhanced analytics': 'enhanced_analytics',
            'assigned orders': 'orders',
            'assigned deliveries': 'overview',
            'delivery history': 'history',
            'profile': 'profile',
            'cart': 'cart',
            'favorites': 'favorites',
            'messages': 'messages'
        };
        
        const normalizedText = linkText.toLowerCase();
        if (textToTypeMap[normalizedText]) {
            contentType = textToTypeMap[normalizedText];
        } else {
            // Try to extract from URL parameters if it's a tab link
            if (href && href.includes('tab=')) {
                const urlParams = new URLSearchParams(href.split('?')[1]);
                const tabParam = urlParams.get('tab');
                if (tabParam) {
                    contentType = tabParam;
                }
            }
        }
        
        loadContent(contentType);
    });
    
    // Handle brand/home links
    $(document).on('click', '.navbar-brand', function(e) {
        e.preventDefault();
        loadContent('overview');
    });
});