/**
 * Maneli Xintra Theme Initialization
 * فیکس مسیرها و مدیریت خطاها
 */

(function() {
    'use strict';
    
    // 1. مدیریت خطاها - جلوگیری از نمایش خطاهای null در console
    window.addEventListener('error', function(e) {
        if (e.message && (
            e.message.includes("Cannot read properties of null") ||
            e.message.includes("appendChild") ||
            e.message.includes("addEventListener")
        )) {
            e.preventDefault();
            return false;
        }
    }, true);
    
    // 2. فیکس مسیر Bootstrap - قبل از اجرای defaultmenu.min.js
    // Override کردن querySelector فقط برای element با id="style"
    (function() {
        const original_querySelector = Document.prototype.querySelector;
        
        Document.prototype.querySelector = function(selector) {
            const element = original_querySelector.call(this, selector);
            
            // فقط برای #style
            if (selector === '#style' && element && !element._maneli_fixed) {
                element._maneli_fixed = true;
                
                const original_setAttribute = Element.prototype.setAttribute;
                const boundSetAttribute = original_setAttribute.bind(element);
                
                element.setAttribute = function(name, value) {
                    // فیکس مسیر Bootstrap
                    if (name === 'href' && typeof value === 'string' && value.includes('./assets/libs/bootstrap')) {
                        value = window.MANELI_PLUGIN_URL + 'assets/libs/bootstrap/css/bootstrap.rtl.min.css';
                        console.log('✓ Fixed Bootstrap CSS path');
                    }
                    return boundSetAttribute.call(this, name, value);
                };
            }
            
            return element;
        };
    })();
    
    // 3. Initialize بعد از DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initManeli);
    } else {
        initManeli();
    }
    
    function initManeli() {
        console.log('Maneli Xintra Init started');
        
        // ایجاد responsive overlay اگر وجود نداشت
        if (!document.getElementById('responsive-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'responsive-overlay';
            overlay.className = 'responsive-overlay';
            document.body.appendChild(overlay);
            
            overlay.addEventListener('click', function() {
                document.documentElement.setAttribute('data-toggled', 'close');
                this.classList.remove('active');
            });
        }
        
        // فیکس toggle button - اطمینان از عملکرد صحیح
        const toggleBtn = document.querySelector('.sidemenu-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const html = document.documentElement;
                const overlay = document.getElementById('responsive-overlay');
                const currentState = html.getAttribute('data-toggled');
                
                if (currentState === 'close') {
                    html.setAttribute('data-toggled', 'open');
                    if (overlay) overlay.classList.add('active');
                } else {
                    html.setAttribute('data-toggled', 'close');
                    if (overlay) overlay.classList.remove('active');
                }
            }, true);
        }
        
        // بستن منو با کلیک روی main content در موبایل
        if (window.innerWidth < 992) {
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.addEventListener('click', function() {
                    const html = document.documentElement;
                    const overlay = document.getElementById('responsive-overlay');
                    if (html.getAttribute('data-toggled') === 'open') {
                        html.setAttribute('data-toggled', 'close');
                        if (overlay) overlay.classList.remove('active');
                    }
                });
            }
        }
        
        // Theme mode toggle
        const themeModeBtn = document.querySelector('.layout-setting');
        if (themeModeBtn) {
            themeModeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const html = document.documentElement;
                const currentMode = html.getAttribute('data-theme-mode');
                const newMode = currentMode === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme-mode', newMode);
                localStorage.setItem('xintraThemeMode', newMode);
            });
        }
        
        // بارگذاری تنظیمات theme از localStorage
        const savedTheme = localStorage.getItem('xintraThemeMode');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme-mode', savedTheme);
        }
        
        console.log('✓ Maneli Xintra Init completed');
    }
    
})();
