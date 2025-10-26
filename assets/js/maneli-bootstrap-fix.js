/**
 * Maneli Bootstrap Path Fix
 * این فایل باید قبل از defaultmenu.min.js لود شود
 */

(function() {
    'use strict';
    
    // ذخیره مرجع اصلی
    const _querySelector = document.querySelector.bind(document);
    const _setAttribute = Element.prototype.setAttribute;
    
    // Override querySelector
    document.querySelector = function(selector) {
        const el = _querySelector(selector);
        
        if (selector === '#style' && el && !el.__maneli_patched) {
            el.__maneli_patched = true;
            
            el.setAttribute = function(attr, value) {
                if (attr === 'href' && typeof value === 'string') {
                    // فیکس هر نوع مسیر Bootstrap
                    if (value.includes('bootstrap.rtl.min.css') || value.includes('bootstrap/css/')) {
                        value = window.MANELI_PLUGIN_URL + 'assets/libs/bootstrap/css/bootstrap.rtl.min.css';
                        console.log('✓ Bootstrap path fixed to:', value);
                    }
                }
                return _setAttribute.call(this, attr, value);
            };
        }
        
        return el;
    };
    
    console.log('✓ Bootstrap fix loaded');
    
})();

