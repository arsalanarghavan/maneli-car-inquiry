/**
 * Installment Inquiry Report - Expert Status Management
 * 
 * Handles all expert/admin actions on single installment inquiry report pages:
 * - Start Progress
 * - Schedule Meeting
 * - Schedule Follow-up
 * - Cancel
 * - Complete
 * - Reject
 * - Save Expert Notes
 */

(function() {
    'use strict';
    
    console.log('🔵 installment-report.js loaded');
    
    // Wait for jQuery
    function initInstallmentReport() {
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery not loaded yet, retrying...');
            setTimeout(initInstallmentReport, 100);
            return;
        }
        
        var $ = jQuery;
        
        // Wait for document ready
        $(document).ready(function() {
            console.log('🔵 installment-report.js: document ready');
            console.log('🔵 .frontend-expert-report elements:', $('.frontend-expert-report').length);
            console.log('🔵 #installment-inquiry-details:', $('#installment-inquiry-details').length);
            
            // بررسی که در صفحه گزارش اقساطی هستیم
            if (!$('.frontend-expert-report').length && !$('#installment-inquiry-details').length) {
                console.warn('🔵 Not on installment report page, exiting');
                return;
            }

            const inquiryId = $('#installment-inquiry-details').data('inquiry-id') || $('.frontend-expert-report').data('inquiry-id');
        
            console.log('🔵 Inquiry ID:', inquiryId);
        
            if (!inquiryId) {
                console.error('❌ Installment Inquiry ID not found');
                return;
            }

            // Check if maneliInstallmentReport is available
            if (typeof maneliInstallmentReport === 'undefined') {
                console.error('❌ maneliInstallmentReport is not defined! Make sure the script is localized.');
                console.log('Available globals:', Object.keys(window).filter(k => k.includes('maneli')));
                return;
            }
            
            console.log('✅ maneliInstallmentReport:', maneliInstallmentReport);
            console.log('🔵 All expert button handlers are now in inquiry-lists.js to avoid conflicts');
            
            // NOTE: All installment status button handlers have been moved to inquiry-lists.js
            // This file is now kept for potential future use or for scripts that need maneliInstallmentReport
            
        }); // End document.ready
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInstallmentReport);
    } else {
        initInstallmentReport();
    }
    
})();
