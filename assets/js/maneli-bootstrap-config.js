/**
 * Maneli Bootstrap Configuration
 * تنظیمات لازم برای Bootstrap 5
 */

(function() {
    'use strict';
    
    // تنظیم پیش‌فرض برای Bootstrap offcanvas
    if (typeof bootstrap !== 'undefined') {
        // Set default backdrop config
        const defaultConfig = {
            backdrop: true,
            keyboard: true,
            scroll: false
        };
        
        // Override Offcanvas defaults
        if (bootstrap.Offcanvas && bootstrap.Offcanvas.Default) {
            Object.assign(bootstrap.Offcanvas.Default, defaultConfig);
        }
    }
    
    // Initialize offcanvas manually بعد از DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        const offcanvasEl = document.getElementById('switcher-canvas');
        if (offcanvasEl && typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
            try {
                // اطمینان از config صحیح
                new bootstrap.Offcanvas(offcanvasEl, {
                    backdrop: true,
                    keyboard: true,
                    scroll: false
                });
                console.log('✓ Offcanvas initialized');
            } catch (e) {
                console.log('Offcanvas init error (handled):', e.message);
            }
        }
    });
    
})();

