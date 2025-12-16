/**
 * Visitor Tracking Script
 * ردیابی بازدیدکنندگان - client-side
 * 
 * @package Autopuzzle_Car_Inquiry
 */

(function($) {
    'use strict';
    
    // Track visit function
    function trackVisit() {
        // Check if tracking is disabled
        if (typeof maneliVisitorTracking === 'undefined' || !maneliVisitorTracking.enabled) {
            return;
        }
        
        // Get page information
        var pageUrl = window.location.href;
        var pageTitle = document.title;
        var referrer = document.referrer || null;
        
        // Extract product ID if on WooCommerce product page
        var productId = null;
        if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.product_id) {
            productId = parseInt(wc_add_to_cart_params.product_id);
        } else if ($('body').hasClass('single-product')) {
            // Try to get product ID from body class or data attribute
            var productIdMatch = $('body').attr('class').match(/postid-(\d+)/);
            if (productIdMatch) {
                productId = parseInt(productIdMatch[1]);
            }
        }
        
        // Send tracking request
        $.ajax({
            url: maneliVisitorTracking.ajaxUrl,
            type: 'POST',
            data: {
                action: 'autopuzzle_track_visit',
                nonce: maneliVisitorTracking.nonce,
                page_url: pageUrl,
                page_title: pageTitle,
                referrer: referrer,
                product_id: productId
            },
            timeout: 5000,
            success: function(response) {
                // Silently succeed
                if (typeof console !== 'undefined' && console.log && maneliVisitorTracking.debug) {
                    console.log('Visit tracked successfully');
                }
            },
            error: function(xhr, status, error) {
                // Silently fail - don't interrupt user experience
                if (typeof console !== 'undefined' && console.error && maneliVisitorTracking.debug) {
                    console.error('Failed to track visit:', error);
                }
            }
        });
    }
    
    // Track visit when DOM is ready
    $(document).ready(function() {
        // Small delay to ensure page is fully loaded
        setTimeout(trackVisit, 100);
    });
    
    // Track visit on page visibility change (for SPA-like behavior)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Page became visible again - might be a new visit
            setTimeout(trackVisit, 100);
        }
    });
    
})(jQuery);


