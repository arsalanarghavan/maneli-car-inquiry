(function () {
    "use strict";

    // Dark mode toggle functionality
    function toggleTheme() {
        let html = document.querySelector("html");
        if (html.getAttribute("data-theme-mode") === "dark") {
            html.setAttribute("data-theme-mode", "light");
            html.setAttribute("data-header-styles", "light");
            html.setAttribute("data-menu-styles", "dark");
            if (!localStorage.getItem("primaryRGB")) {
                html.setAttribute("style", "");
            }
            html.style.removeProperty("--body-bg-rgb");
            html.style.removeProperty("--body-bg-rgb2");
            html.style.removeProperty("--light-rgb");
            html.style.removeProperty("--form-control-bg");
            html.style.removeProperty("--input-border");
            localStorage.removeItem("xintradarktheme");
            localStorage.removeItem("xintraMenu");
            localStorage.removeItem("xintraHeader");
            localStorage.removeItem("bodylightRGB");
            localStorage.removeItem("bodyBgRGB");
        } else {
            html.setAttribute("data-theme-mode", "dark");
            html.setAttribute("data-header-styles", "dark");
            html.setAttribute("data-menu-styles", "dark");
            if (!localStorage.getItem("primaryRGB")) {
                html.setAttribute("style", "");
            }
            localStorage.setItem("xintradarktheme", "true");
            localStorage.setItem("xintraMenu", "dark");
            localStorage.setItem("xintraHeader", "dark");
            localStorage.removeItem("bodylightRGB");
            localStorage.removeItem("bodyBgRGB");
        }
    }

    // Initialize sidebar toggle - only if defaultmenu.min.js is not available
    // defaultmenu.min.js already handles the sidebar toggle properly

    // Initialize dark mode - Run immediately and also on DOMContentLoaded
    function initDarkMode() {
        let html = document.querySelector("html");
        if (!html) return;
        
        // Restore dark mode from localStorage
        if (localStorage.getItem("xintradarktheme")) {
            html.setAttribute("data-theme-mode", "dark");
            html.setAttribute("data-menu-styles", "dark");
            html.setAttribute("data-header-styles", "dark");
        }
    }

    // Initialize dark mode as early as possible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDarkMode);
    } else {
        initDarkMode();
    }

    // Flag to track if toggle button is initialized
    let toggleButtonInitialized = false;

    // Initialize dark mode toggle button using event delegation
    // This ensures our handler runs even if custom.js also attaches a listener
    function initToggleButton() {
        if (toggleButtonInitialized) {
            return; // Already initialized
        }

        // Use event delegation on document to catch all clicks on .layout-setting
        // Using capture phase ensures our handler runs before others
        document.addEventListener("click", function(e) {
            // Check if the click target is .layout-setting or inside it
            const layoutSetting = e.target.closest(".layout-setting");
            if (layoutSetting) {
                // Only handle if it's the actual button, not dropdowns inside it
                if (layoutSetting === e.target || e.target.closest(".layout-setting") === layoutSetting) {
                    e.preventDefault();
                    e.stopImmediatePropagation(); // Stop all other handlers
                    toggleTheme();
                    return false;
                }
            }
        }, true); // Use capture phase to run before other handlers
        
        toggleButtonInitialized = true;
    }

    // Wait for DOM to be ready for button initialization
    function waitAndInit() {
        if (document.querySelector(".layout-setting") && !toggleButtonInitialized) {
            initToggleButton();
        } else if (!toggleButtonInitialized) {
            setTimeout(waitAndInit, 50);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitAndInit);
    } else {
        // Initialize immediately
        waitAndInit();
    }
})();

