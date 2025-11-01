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

    // Initialize dark mode toggle
    let layoutSetting = document.querySelector(".layout-setting");
    if (layoutSetting) {
        layoutSetting.addEventListener("click", toggleTheme);
    }

    // Load saved dark mode state from localStorage
    window.addEventListener("load", function() {
        let html = document.querySelector("html");
        
        // Restore dark mode from localStorage
        if (localStorage.getItem("xintradarktheme")) {
            html.setAttribute("data-theme-mode", "dark");
            html.setAttribute("data-menu-styles", "dark");
            html.setAttribute("data-header-styles", "dark");
        }
    });
})();

