/**
 * Maneli Custom Fix
 * جلوگیری از خطاهای JavaScript
 */

(function() {
    'use strict';
    
    // Add missing elements that custom.js needs
    document.addEventListener('DOMContentLoaded', function() {
        // Add header-cart-items-scroll if doesn't exist
        if (!document.getElementById('header-cart-items-scroll')) {
            var cartScroll = document.createElement('ul');
            cartScroll.id = 'header-cart-items-scroll';
            cartScroll.className = 'list-unstyled mb-0 d-none';
            document.body.appendChild(cartScroll);
        }
    });
    
    // Override SimpleBar init to check for null - run when SimpleBar is loaded
    window.addEventListener('load', function() {
        if (typeof SimpleBar !== 'undefined' && SimpleBar.instances === undefined) {
            var OriginalSimpleBar = SimpleBar;
            window.SimpleBar = function(element, options) {
                if (!element) {
                    console.warn('SimpleBar: element is null, skipping initialization');
                    return {
                        recalculate: function() {},
                        getScrollElement: function() { return null; }
                    };
                }
                return new OriginalSimpleBar(element, options);
            };
            // Copy static methods and properties
            for (var key in OriginalSimpleBar) {
                if (OriginalSimpleBar.hasOwnProperty(key)) {
                    window.SimpleBar[key] = OriginalSimpleBar[key];
                }
            }
            // Copy prototype
            window.SimpleBar.prototype = OriginalSimpleBar.prototype;
        }
    });
    
})();

