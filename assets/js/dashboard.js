/**
 * Dashboard JavaScript
 * Handles dashboard functionality and interactions
 */

(function($) {
    'use strict';

    // Initialize dashboard when document is ready
    $(document).ready(function() {
        initDashboard();
    });

    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Initialize tooltips
        initTooltips();
        
        // Initialize sidebar
        initSidebar();
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    }

    /**
     * Initialize sidebar
     */
    function initSidebar() {
        // Check if defaultmenu.min.js is handling sidebar toggle
        if (typeof window.toggleSidemenu === 'function') {
            // defaultmenu.min.js already handles the sidebar toggle
            console.log('Sidebar toggle handled by defaultmenu.min.js');
            return;
        }
        
        // Sidebar toggle (fallback if defaultmenu.min.js is not loaded)
        $('.sidemenu-toggle').on('click', function() {
            $('body').toggleClass('sidebar-open');
            $('html').attr('data-toggled', function(index, attr){
                return attr === 'close' ? 'open' : 'close';
            });
            console.log('Sidebar toggled via dashboard.js fallback');
        });

        // Close sidebar on mobile when clicking outside
        $(document).on('click', function(e) {
            if ($(window).width() < 992) {
                if (!$(e.target).closest('.app-sidebar, .sidemenu-toggle').length) {
                    $('body').removeClass('sidebar-open');
                    $('html').attr('data-toggled', 'close');
                }
            }
        });
    }

    /**
     * Show loading state
     */
    function showLoading(element) {
        $(element).prop('disabled', true);
        var originalText = $(element).html();
        $(element).data('original-text', originalText);
        $(element).html('<i class="ri-loader-4-line me-1"></i>در حال بارگذاری...');
    }

    /**
     * Hide loading state
     */
    function hideLoading(element) {
        $(element).prop('disabled', false);
        var originalText = $(element).data('original-text');
        if (originalText) {
            $(element).html(originalText);
        }
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        var alertClass = 'alert-' + type;
        var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show position-fixed" style="top: 20px; left: 20px; z-index: 9999; min-width: 300px;">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>');
        
        $('body').append(notification);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            notification.alert('close');
        }, 5000);
    }

    // Global functions
    window.ManeliDashboard = {
        showLoading: showLoading,
        hideLoading: hideLoading,
        showNotification: showNotification
    };

})(jQuery);

