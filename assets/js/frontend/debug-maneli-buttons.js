/**
 * DEBUG SCRIPT - بررسی کامل JavaScript files و دکمه‌ها
 * این اسکریپت باید قبل از سایر اسکریپت‌ها load شود تا بتواند وضعیت را بررسی کند
 */
(function() {
    'use strict';
    
    console.log('=====================================');
    console.log('🔍 MANELI BUTTONS DEBUG SCRIPT');
    console.log('=====================================');
    
    // Wait for DOM ready
    function runDebug() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runDebug);
            return;
        }
        
        console.log('📅 DOM State:', document.readyState);
        console.log('📦 jQuery loaded:', typeof jQuery !== 'undefined');
        console.log('📦 Swal loaded:', typeof Swal !== 'undefined');
        
        // Check for scripts
        const scripts = Array.from(document.querySelectorAll('script[src]'));
        const relevantScripts = scripts.filter(s => 
            s.src.includes('inquiry-lists') || 
            s.src.includes('installment-report') || 
            s.src.includes('cash-report') ||
            s.src.includes('jquery') ||
            s.src.includes('sweetalert2')
        );
        console.log('📜 Relevant Scripts:', relevantScripts.map(s => s.src));
        
        // Check for localization objects
        console.log('🌐 maneliInquiryLists:', typeof window.maneliInquiryLists !== 'undefined' ? window.maneliInquiryLists : 'NOT DEFINED');
        console.log('🌐 maneliInstallmentReport:', typeof window.maneliInstallmentReport !== 'undefined' ? window.maneliInstallmentReport : 'NOT DEFINED');
        console.log('🌐 maneliCashReport:', typeof window.maneliCashReport !== 'undefined' ? window.maneliCashReport : 'NOT DEFINED');
        
        // Wait for jQuery
        if (typeof jQuery === 'undefined') {
            console.error('❌ jQuery is NOT loaded!');
            setTimeout(runDebug, 500);
            return;
        }
        
        var $ = jQuery;
        
        // Check buttons
        setTimeout(function() {
            console.log('=====================================');
            console.log('🔘 BUTTON CHECKS');
            console.log('=====================================');
            
            const buttonSelectors = [
                '.assign-expert-btn',
                '.delete-inquiry-report-btn',
                '.delete-cash-list-btn',
                '.delete-installment-list-btn',
                '.installment-status-btn',
                '#set-in-progress-btn',
                '#request-downpayment-btn',
                '#schedule-meeting-btn',
                '#approve-inquiry-btn',
                '#reject-inquiry-btn',
                '.cash-status-btn[data-action="schedule_followup"]'
            ];
            
            buttonSelectors.forEach(function(selector) {
                const buttons = $(selector);
                console.log(`${selector}: ${buttons.length} element(s) found`);
                if (buttons.length > 0) {
                    buttons.each(function(index) {
                        const btn = $(this);
                        console.log(`  [${index}] Text: "${btn.text().trim()}", Visible: ${btn.is(':visible')}, Disabled: ${btn.prop('disabled')}`);
                    });
                }
            });
            
            // Check event listeners (jQuery events)
            console.log('=====================================');
            console.log('🎯 EVENT HANDLERS CHECK');
            console.log('=====================================');
            
            // Try to find if events are attached
            const testBtn = $('.assign-expert-btn').first();
            if (testBtn.length) {
                const events = $._data(testBtn[0], 'events');
                console.log('Events on first .assign-expert-btn:', events);
                
                // Test click manually
                console.log('Testing manual click binding...');
                testBtn.off('click.debug-test');
                testBtn.on('click.debug-test', function() {
                    console.log('✅ Click event fired on .assign-expert-btn!');
                    alert('Button click works! The handler should work too.');
                });
            }
            
            // Check if inquiry-lists.js loaded
            console.log('=====================================');
            console.log('📄 SCRIPT LOADING CHECK');
            console.log('=====================================');
            
            if (typeof window.maneliInquiryLists !== 'undefined') {
                console.log('✅ maneliInquiryLists is defined');
                console.log('  - ajax_url:', window.maneliInquiryLists.ajax_url);
                console.log('  - nonces:', window.maneliInquiryLists.nonces);
                console.log('  - experts count:', window.maneliInquiryLists.experts ? window.maneliInquiryLists.experts.length : 0);
            } else {
                console.error('❌ maneliInquiryLists is NOT defined!');
                console.log('This means inquiry-lists.js did not load or localization failed.');
            }
            
            // Final check - try to bind a test handler
            console.log('=====================================');
            console.log('🧪 TEST HANDLER BINDING');
            console.log('=====================================');
            
            $(document.body).on('click.debug-maneli', '.assign-expert-btn', function() {
                console.log('✅ TEST: assign-expert-btn clicked via delegation!');
                const btn = $(this);
                console.log('  - inquiry-id:', btn.data('inquiry-id'));
                console.log('  - inquiry-type:', btn.data('inquiry-type'));
            });
            
            console.log('Test handler bound. Click an "assign-expert-btn" to test.');
            
        }, 2000); // Wait 2 seconds for all scripts to load
    }
    
    runDebug();
    
})();

