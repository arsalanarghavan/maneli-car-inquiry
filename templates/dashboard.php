<?php
/**
 * Dashboard Template
 * مانلی خودرو - داشبورد سیستم استعلام خودرو
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user from handler
$handler = Maneli_Dashboard_Handler::instance();
$user_data = $handler->get_current_user_for_template();
$current_user = $user_data['wp_user'];
$user_role = $user_data['role'];
$user_name = $user_data['name'];
$user_phone = $user_data['phone'];
$role_display = $user_data['role_display'];

// Get current page
$dashboard_page = get_query_var('maneli_dashboard_page');
if (empty($dashboard_page)) {
    $dashboard_page = 'home';
}

// Define AJAX URL and nonce
$ajax_url = admin_url('admin-ajax.php');
$ajax_nonce = wp_create_nonce('maneli-ajax-nonce');

// Render dynamic sidebar menu
ob_start();
include MANELI_PLUGIN_DIR . 'templates/dashboard/sidebar-menu.php';
$sidebar_menu = ob_get_clean();

// Get the dashboard HTML content
$dashboard_html = file_get_contents(MANELI_PLUGIN_DIR . 'templates/dashboard-base.html');

// Check localStorage for language preference (client-side)
// Default is RTL/Farsi, but JavaScript will override based on localStorage

// Force RTL for all users and roles by default
// JavaScript will handle the dynamic language switching based on localStorage
if (strpos($dashboard_html, 'dir=') === false) {
    $dashboard_html = preg_replace('/(<html[^>]*)>/i', '$1 dir="rtl" lang="fa">', $dashboard_html, 1);
} else {
    // Make sure both dir and lang are set
    if (strpos($dashboard_html, 'lang=') === false) {
        $dashboard_html = preg_replace('/(<html[^>]*dir=["\'][^"\']*["\'])/i', '$0 lang="fa"', $dashboard_html, 1);
    }
}

// Extract the sidebar part from the HTML and replace with dynamic sidebar
// Find the sidebar section (between <!-- Start::main-sidebar --> and <!-- End::main-sidebar -->)
$pattern = '/(<!-- Start::main-sidebar -->)(.*?)(<!-- End::main-sidebar -->)/s';
$replacement = '$1' . PHP_EOL . $sidebar_menu . PHP_EOL . '$3';
$dashboard_html = preg_replace($pattern, $replacement, $dashboard_html);

// Replace asset paths to use WordPress URLs
$dashboard_html = str_replace('./assets/', MANELI_PLUGIN_URL . 'assets/', $dashboard_html);

// Replace jQuery CDN with WordPress jQuery (more reliable)
// CRITICAL: jQuery must load first for everything to work
$jquery_url = includes_url('js/jquery/jquery.min.js');
$jquery_tag = '<script src="' . esc_url($jquery_url) . '"></script>';

// Strategy 1: Try to replace the existing jQuery script tag
$patterns = [
    '/<script\s+src=["\']https:\/\/code\.jquery\.com\/jquery[^"\']*["\'][^>]*><\/script>/is',
    '/<script[^>]*src\s*=\s*["\']https:\/\/code\.jquery\.com\/jquery[^"\']*["\'][^>]*><\/script>/is',
    '/<script[^>]*src=["\']https:\/\/code\.jquery\.com\/jquery[^"\']*["\'][^>]*\/>/is',
];

$jquery_replaced = false;
foreach ($patterns as $pattern) {
    if (preg_match($pattern, $dashboard_html)) {
        $dashboard_html = preg_replace($pattern, $jquery_tag, $dashboard_html);
        $jquery_replaced = true;
        break;
    }
}

// Strategy 2: If replacement didn't work, insert jQuery right after <head> or before first script
if (!$jquery_replaced) {
    // Try to insert right after head tag
    if (preg_match('/<head[^>]*>/i', $dashboard_html)) {
        $dashboard_html = preg_replace('/(<head[^>]*>)/i', '$1' . PHP_EOL . $jquery_tag, $dashboard_html, 1);
    } else {
        // Fallback: insert before first script tag
        $dashboard_html = preg_replace('/(<script[^>]*>)/i', $jquery_tag . PHP_EOL . '$1', $dashboard_html, 1);
    }
}

// Strategy 3: Simple string replace as last resort - remove integrity and crossorigin attributes
if (strpos($dashboard_html, 'code.jquery.com/jquery') !== false) {
    $dashboard_html = str_replace('https://code.jquery.com/jquery-3.7.1.min.js', $jquery_url, $dashboard_html);
    // Remove integrity attribute that causes issues
    $dashboard_html = preg_replace('/\s+integrity\s*=\s*["\'][^"\']*["\']/i', '', $dashboard_html);
    // Remove crossorigin attribute
    $dashboard_html = preg_replace('/\s+crossorigin\s*=\s*["\'][^"\']*["\']/i', '', $dashboard_html);
}

// Strategy 4: GUARANTEED jQuery injection - Force insert jQuery in head if not already present
// Check if jQuery script tag exists (WordPress or CDN)
if (strpos($dashboard_html, $jquery_url) === false && strpos($dashboard_html, 'jquery.min.js') === false) {
    // Insert jQuery right after <head> tag as guaranteed fallback
    $dashboard_html = preg_replace(
        '/(<head[^>]*>)/i',
        '$1' . PHP_EOL . '        ' . $jquery_tag . PHP_EOL,
        $dashboard_html,
        1
    );
}

// CRITICAL FIX: Wrap all inline scripts that use jQuery to wait for jQuery to load
// Replace $(document).ready with safe version that checks for jQuery first
$dashboard_html = preg_replace_callback(
    '/<script>\s*\$\(document\)\.ready\(function\(\)\s*\{/i',
    function($matches) {
        return '<script>
(function() {
    function waitForjQuery() {
        if (typeof jQuery !== "undefined") {
            jQuery(document).ready(function($) {';
    },
    $dashboard_html
);

// Close the wrapper - find closing }); of persianDatepicker script and add wrapper close
$dashboard_html = preg_replace(
    '/\}\);[\s\n]*<\/script>\s*<!-- Language Switching Function -->/i',
    '});
        } else {
            setTimeout(waitForjQuery, 50);
        }
    }
    waitForjQuery();
})();
</script>

        <!-- Language Switching Function -->',
    $dashboard_html
);

// Also update font paths in inline styles
$dashboard_html = str_replace('src: url(\'./font/', 'src: url(\'' . MANELI_PLUGIN_URL . 'assets/fonts/', $dashboard_html);
$dashboard_html = str_replace('src: url("./font/', 'src: url("' . MANELI_PLUGIN_URL . 'assets/fonts/', $dashboard_html);
$dashboard_html = str_replace('url(\'../fonts/', 'url(\'' . MANELI_PLUGIN_URL . 'assets/fonts/', $dashboard_html);
$dashboard_html = str_replace('url("../fonts/', 'url("' . MANELI_PLUGIN_URL . 'assets/fonts/', $dashboard_html);

// Remove sales-dashboard.js and apexcharts if not on home page
if ($dashboard_page !== 'home') {
    // Remove ApexCharts script
    $dashboard_html = preg_replace('/<script src=".*?apexcharts\.min\.js"><\/script>\s*/', '', $dashboard_html);
    $dashboard_html = preg_replace('/<!-- Apex Charts JS -->\s*/', '', $dashboard_html);
    // Remove sales-dashboard script  
    $dashboard_html = preg_replace('/<script src=".*?sales-dashboard\.js"><\/script>\s*/', '', $dashboard_html);
    $dashboard_html = preg_replace('/<!-- Sales Dashboard -->\s*/', '', $dashboard_html);
}

// Replace title and meta
// Replace xintra branding with مانلی خودرو
$dashboard_html = str_replace('قالب HTML داشبورد مدیریتی xintra', 'Car Inquiry System Dashboard', $dashboard_html);
$dashboard_html = str_replace('Xintra', 'Maneli Khodro', $dashboard_html);
$dashboard_html = str_replace('استعلام خودرو منلی', 'Maneli Khodro', $dashboard_html);

// Fix: Replace the problematic language switching script that causes infinite refresh
$pattern = '/(<!-- Language Switching Function -->)(.*?)(Load saved language preference on page load)(.*?)(<\/script>)/s';
$fixed_script = '
<!-- Language Switching Function -->
<script>
    function changeLanguage(lang) {
        // Store language preference in localStorage
        localStorage.setItem(\'maneli_language\', lang);
        
        // Mark that we are reloading to prevent infinite loop
        localStorage.setItem(\'maneli_language_changing\', \'true\');
        
        // Reload page to apply language changes
        window.location.reload();
    }
    
    // Load saved language preference on page load
    document.addEventListener(\'DOMContentLoaded\', function() {
        // Clear the changing flag if it exists
        if (localStorage.getItem(\'maneli_language_changing\') === \'true\') {
            localStorage.removeItem(\'maneli_language_changing\');
            return; // Don\'t check again after manual change
        }
        
        const savedLang = localStorage.getItem(\'maneli_language\') || \'fa\';
        const currentLang = document.documentElement.lang || \'fa\';
        const currentDir = document.documentElement.dir || \'rtl\';
        
        // Check if language needs to be changed
        const needsChange = savedLang !== currentLang || 
                            (savedLang === \'fa\' && currentDir !== \'rtl\') || 
                            (savedLang !== \'fa\' && currentDir !== \'ltr\');
        
        // Only reload if language is different and we haven\'t changed it manually
        if (needsChange) {
            setTimeout(function() {
                changeLanguage(savedLang);
            }, 100);
        }
    });
</script>';
$dashboard_html = preg_replace($pattern, $fixed_script, $dashboard_html);

$dashboard_html = str_replace('جهت:', 'Direction:', $dashboard_html);
$dashboard_html = str_replace('چپ به راست', 'Left to Right', $dashboard_html);
$dashboard_html = str_replace('راست به چپ', 'Right to Left', $dashboard_html);
$dashboard_html = str_replace('قالب ناوبری:', 'Navigation Layout:', $dashboard_html);
$dashboard_html = str_replace('عمودی', 'Vertical', $dashboard_html);
$dashboard_html = str_replace('افقی', 'Horizontal', $dashboard_html);
$dashboard_html = str_replace('سبک های منوی عمودی و افقی:', 'Vertical and Horizontal Menu Styles:', $dashboard_html);
$dashboard_html = str_replace('منو کلیک کنید', 'Click Menu', $dashboard_html);
$dashboard_html = str_replace('منو شناور', 'Hover Menu', $dashboard_html);
$dashboard_html = str_replace('نماد کلیک کنید', 'Icon Click', $dashboard_html);
$dashboard_html = str_replace('نماد شناور', 'Icon Hover', $dashboard_html);
$dashboard_html = str_replace('سبک های چیدمان منوی جانبی:', 'Sidebar Layout Styles:', $dashboard_html);
$dashboard_html = str_replace('پیش فرض', 'Default', $dashboard_html);
$dashboard_html = str_replace('منو بسته', 'Closed Menu', $dashboard_html);
$dashboard_html = str_replace('آیکن متنی', 'Icon Text', $dashboard_html);
$dashboard_html = str_replace('>آیکن<', '>' . 'Icon Only' . '<', $dashboard_html);
$dashboard_html = str_replace('جدا شده', 'Detached', $dashboard_html);
$dashboard_html = str_replace('منو جفتی', 'Doubled', $dashboard_html);
$dashboard_html = str_replace('سبک های صفحه:', 'Page Styles:', $dashboard_html);
$dashboard_html = str_replace('منظم', 'Regular', $dashboard_html);
$dashboard_html = str_replace('کلاسیک', 'Classic', $dashboard_html);
$dashboard_html = str_replace('مدرن', 'Modern', $dashboard_html);
$dashboard_html = str_replace('سبک های عرض طرح:', 'Layout Width Styles:', $dashboard_html);
$dashboard_html = str_replace('تمام صفحه', 'Full Width', $dashboard_html);
$dashboard_html = str_replace('>جعبه ای<', '>' . 'Boxed' . '<', $dashboard_html);
$dashboard_html = str_replace('موقعیت های منو:', 'Menu Positions:', $dashboard_html);
$dashboard_html = str_replace('>ثابت<', '>' . 'Fixed' . '<', $dashboard_html);
$dashboard_html = str_replace('قابل پیمایش', 'Scrollable', $dashboard_html);
$dashboard_html = str_replace('موقعیت های سرصفحه:', 'Header Positions:', $dashboard_html);
$dashboard_html = str_replace('لودر:', 'Loader:', $dashboard_html);
$dashboard_html = str_replace('>فعال<', '>' . 'Active' . '<', $dashboard_html);
$dashboard_html = str_replace('غیرفعال', 'Inactive', $dashboard_html);

// Translate dashboard content
$dashboard_html = str_replace('برای امکانات بیشتر ارتقا دهید', 'Upgrade for more features', $dashboard_html);
$dashboard_html = str_replace('بینش فروش را به حداکثر برسانید. عملکرد را بهینه کنید. با نسخه حرفه‌ای به موفقیت برسید.', 'Maximize sales insights. Optimize performance. Reach success with the professional version.', $dashboard_html);
$dashboard_html = str_replace('ارتقا به نسخه حرفه‌ای', 'Upgrade to Professional Version', $dashboard_html);
$dashboard_html = str_replace('دسته‌های پرفروش', 'Top Categories', $dashboard_html);
$dashboard_html = str_replace('مرتب‌سازی بر اساس', 'Sort by', $dashboard_html);
$dashboard_html = str_replace('هفته جاری', 'This Week', $dashboard_html);
$dashboard_html = str_replace('هفته گذشته', 'Last Week', $dashboard_html);
$dashboard_html = str_replace('ماه جاری', 'This Month', $dashboard_html);
$dashboard_html = str_replace('ناخالص', 'Gross', $dashboard_html);

// Get user profile data for header - Works for all roles (customer, expert, manager, admin)
$profile_image = '';
$profile_name = $user_name;
$profile_role = $role_display;

// Get profile image from WordPress user if available
if ($current_user && !empty($current_user->ID)) {
    $profile_image_id = get_user_meta($current_user->ID, 'profile_image_id', true);
    if (!empty($profile_image_id)) {
        $profile_image = wp_get_attachment_image_url($profile_image_id, 'thumbnail');
    }
}

// Default image if no profile image
if (empty($profile_image)) {
    $profile_image = get_avatar_url($current_user ? $current_user->ID : 0, ['size' => 32]);
    if (empty($profile_image) || strpos($profile_image, 'gravatar.com') !== false) {
        $profile_image = MANELI_PLUGIN_URL . 'assets/images/faces/15.jpg';
    }
}

// Replace profile section in header - Show for all users (customer, expert, manager, admin)
$avatar_html = '';
$profile_html = '';
if (!empty($profile_image) && !empty($profile_name)) {
    $avatar_html = '<img src="' . esc_url($profile_image) . '" alt="' . esc_attr($profile_name) . '" class="avatar avatar-sm">';
    $logout_url = $current_user ? wp_logout_url(home_url()) : home_url('/logout');
    $profile_html = '<li class="header-element dropdown">
								<!-- Start::header-link|dropdown-toggle -->
								<a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
									<div class="d-flex align-items-center">
										<div>
											' . $avatar_html . '
										</div>
									</div>
								</a>
								<!-- End::header-link|dropdown-toggle -->
								<ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
									<li>
										<div class="dropdown-item text-center border-bottom">
											<span>' . esc_html($profile_name) . '</span>
											<span class="d-block fs-12 text-muted">' . esc_html($profile_role) . '</span>
										</div>
									</li>
									<li><a class="dropdown-item d-flex align-items-center" href="' . home_url('/dashboard/profile-settings') . '"><i class="fe fe-user p-1 rounded-circle bg-primary-transparent me-2 fs-16"></i>' . __('My Account', 'maneli-car-inquiry') . '</a></li>
									<li class="border-top bg-light"><a class="dropdown-item d-flex align-items-center" href="' . $logout_url . '"><i class="fe fe-log-out p-1 rounded-circle bg-primary-transparent me-2 fs-16"></i>' . __('Logout', 'maneli-car-inquiry') . '</a></li>
								</ul>
							</li>';
}

// Get page-specific content
$page_content = '';
$page_slug = $dashboard_page ?: 'home';

// Check for view_payment query var
$view_payment = get_query_var('view_payment');
if (!empty($view_payment)) {
    $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/payment-details.php';
    if (file_exists($page_template)) {
        ob_start();
        include $page_template;
        $page_content = ob_get_clean();
    }
} else {
    // Check for add-product page or edit_product query var
    $edit_product = isset($_GET['edit_product']) ? absint($_GET['edit_product']) : 0;
    if ($page_slug === 'add-product' || $edit_product > 0) {
        $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/add-edit-product.php';
        if (file_exists($page_template)) {
            ob_start();
            include $page_template;
            $page_content = ob_get_clean();
        }
    } else {
        // Handle unified inquiry pages: inquiries/cash and inquiries/installment
        $subpage = get_query_var('maneli_dashboard_subpage');
        if ($page_slug === 'inquiries' && !empty($subpage)) {
            if ($subpage === 'cash') {
                $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/cash-inquiries.php';
            } elseif ($subpage === 'installment') {
                $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/installment-inquiries.php';
            } else {
                // Invalid subpage, redirect to home
                wp_redirect(home_url('/dashboard'));
                exit;
            }
            
            if (file_exists($page_template)) {
                ob_start();
                include $page_template;
                $page_content = ob_get_clean();
            }
        } else {
            // Try to load page-specific content
            $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/' . $page_slug . '.php';
            if (file_exists($page_template)) {
                ob_start();
                include $page_template;
                $page_content = ob_get_clean();
            }
        }
    }
}

// Replace the profile section in header - Remove demo content and add real profile
if (!empty($profile_html)) {
    // Remove demo profile content and replace with real profile
    $pattern = '/<!-- Start::header-element -->\s*<li class="header-element dropdown">.*?<!-- End::header-element -->/s';
    $replacement = '<!-- Start::header-element -->' . PHP_EOL . '							' . $profile_html . PHP_EOL . '							<!-- End::header-element -->';
    $dashboard_html = preg_replace($pattern, $replacement, $dashboard_html);
    
    // Also remove any remaining demo text
    $dashboard_html = str_replace('نام کاربر', esc_html($profile_name), $dashboard_html);
    $dashboard_html = str_replace('نقش', esc_html($profile_role), $dashboard_html);
}

// Replace notifications dropdown - Remove demo notifications and make it load real notifications
$alerts_text = esc_html__('Alerts', 'maneli-car-inquiry');
$unread_text = esc_html__('unread', 'maneli-car-inquiry');
$view_all_text = esc_html__('View All', 'maneli-car-inquiry');

$notifications_html = '
							<li class="header-element notifications-dropdown d-xl-block d-none dropdown">
								<!-- Start::header-link|dropdown-toggle -->
								<a href="javascript:void(0);" class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" id="messageDropdown" aria-expanded="false">
									<svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 header-link-icon" fill="none" viewbox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5"></path>
									</svg>
									<span class="header-icon-pulse bg-primary2 rounded pulse pulse-secondary maneli-initially-hidden" id="header-notification-count"></span>
								</a>
								<!-- End::header-link|dropdown-toggle -->
								<!-- Start::main-header-dropdown -->
								<div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
									<div class="p-3">
										<div class="d-flex align-items-center justify-content-between">
											<p class="mb-0 fs-15 fw-medium">' . $alerts_text . '</p>
											<span class="badge bg-secondary text-fixed-white" id="notifiation-data">0 ' . $unread_text . '</span>
										</div>
									</div>
									<div class="dropdown-divider"></div>
									<ul class="list-unstyled mb-0" id="header-notification-scroll">
										<!-- Notifications will be loaded here by notifications.js -->
										<li class="dropdown-item text-center py-3">
											<div class="spinner-border spinner-border-sm text-primary" role="status">
												<span class="visually-hidden">در حال بارگذاری...</span>
											</div>
										</li>
									</ul>
									<div class="p-3 empty-header-item1 border-top">
										<div class="d-grid">
											<a href="' . home_url('/dashboard/notifications') . '" class="btn btn-primary btn-wave">' . $view_all_text . '</a>
										</div>
									</div>
								</div>
							</li>
';
// Replace notifications dropdown - Find and replace the entire notifications section
$notifications_pattern = '/<li class="header-element notifications-dropdown.*?<\/li>\s*(?=<!-- End::header-element -->)/s';
if (preg_match($notifications_pattern, $dashboard_html)) {
    $dashboard_html = preg_replace($notifications_pattern, $notifications_html, $dashboard_html);
}

// Replace the main content area with page-specific content
if (!empty($page_content)) {
    // Replace everything between Start::app-content and End::app-content
    $pattern = '/(<!-- Start::app-content -->)(.*?)(<!-- End::app-content -->)/s';
    $replacement = '<!-- Start::app-content -->' . PHP_EOL . $page_content . PHP_EOL . '<!-- End::app-content -->';
    $dashboard_html = preg_replace($pattern, $replacement, $dashboard_html);
} else {
    // If no content found, show 404 error page
    ob_start();
    include MANELI_PLUGIN_DIR . 'templates/dashboard/error-404.php';
    $error_content = ob_get_clean();
    
    $pattern = '/(<!-- Start::app-content -->)(.*?)(<!-- End::app-content -->)/s';
    $replacement = '<!-- Start::app-content -->' . PHP_EOL . $error_content . PHP_EOL . '<!-- End::app-content -->';
    $dashboard_html = preg_replace($pattern, $replacement, $dashboard_html);
}

// Add current user data to script
$user_id = 0;
if (is_user_logged_in()) {
    $user_id = $current_user->ID;
}

// Add Chart.js for home page (needed for daily trend chart)
// Insert Chart.js after jQuery but before other scripts
if ($dashboard_page === 'home') {
    $chart_js_path = MANELI_PLUGIN_URL . 'assets/libs/chart.js/chart.umd.js';
    // Insert Chart.js script after jQuery loading
    $chart_script = '<script src="' . esc_url($chart_js_path) . '"></script>' . PHP_EOL;
    
    // Find jQuery script tag and insert Chart.js right after it
    $pattern = '/(<script[^>]*jquery[^>]*><\/script>)/i';
    $replacement = '$1' . PHP_EOL . $chart_script;
    $dashboard_html = preg_replace($pattern, $replacement, $dashboard_html, 1);
    
    // Also add fallback check before closing body tag
    $fallback_script = '<script>
        window.addEventListener("DOMContentLoaded", function() {
            if (typeof Chart === "undefined") {
                console.error("Chart.js failed to load from: ' . esc_js($chart_js_path) . '");
            } else {
                console.log("Chart.js loaded successfully");
            }
        });
    </script>';
    $dashboard_html = str_replace('</body>', $fallback_script . PHP_EOL . '</body>', $dashboard_html);
}

$dashboard_html .= '<script src="' . MANELI_PLUGIN_URL . 'assets/js/notifications.js"></script>';
$dashboard_html .= '<script>
var maneli_current_user = ' . json_encode(array(
    'user_id' => $user_id,
    'role' => $user_role
)) . ';
var maneli_ajax = {
    url: "' . $ajax_url . '",
    nonce: "' . $ajax_nonce . '",
    plugin_url: "' . MANELI_PLUGIN_URL . '",
    notifications_nonce: "' . wp_create_nonce('maneli_notifications_nonce') . '"
};

// Initialize notifications
(function() {
    function waitForJQueryAndInit() {
        if (typeof jQuery !== "undefined" && typeof maneliNotifications !== "undefined") {
            jQuery(document).ready(function($) {
                maneliNotifications.init();
            });
        } else {
            setTimeout(waitForJQueryAndInit, 100);
        }
    }
    waitForJQueryAndInit();
})();

// Suppress null reference errors
window.addEventListener("error", function(e) {
    if (e && e.message && typeof e.message === \'string\') {
        if (e.message.includes("Cannot read properties of null") || 
            e.message.includes("reading \\"classList\\"") ||
            e.message.includes("reading \\"addEventListener\\"")) {
            console.log("Suppressed error:", e.message);
            e.preventDefault();
            return true;
        }
    }
    return false;
}, true);
</script>';

// CRITICAL FIX: Print scripts manually since wp_footer is not called
// This ensures inquiry scripts are loaded on inquiry pages
$page = get_query_var('maneli_dashboard_page');
$subpage = get_query_var('maneli_dashboard_subpage');
$inquiry_id = isset($_GET['inquiry_id']) ? intval($_GET['inquiry_id']) : 0;
$cash_inquiry_id = isset($_GET['cash_inquiry_id']) ? intval($_GET['cash_inquiry_id']) : 0;

// Check if we need to output inquiry scripts
$need_inquiry_scripts = (
    $page === 'cash-inquiries' || 
    $page === 'installment-inquiries' ||
    ($page === 'inquiries' && ($subpage === 'cash' || $subpage === 'installment')) ||
    $page === 'new-inquiry' ||
    $page === 'new-cash-inquiry' ||
    $inquiry_id > 0 ||
    $cash_inquiry_id > 0
);

if ($need_inquiry_scripts) {
    // CRITICAL: wp_print_scripts doesn't work, inject scripts directly
    $scripts_html = PHP_EOL . '<!-- Inquiry Scripts Direct Injection -->' . PHP_EOL;
    // Use local SweetAlert2 if available
    $sweetalert2_path = MANELI_INQUIRY_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
    if (file_exists($sweetalert2_path)) {
        $scripts_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css') . '">' . PHP_EOL;
        $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js') . '"></script>' . PHP_EOL;
    } else {
        // Fallback to CDN
        $scripts_html .= '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>' . PHP_EOL;
    }
    // Note: Select2 is not available locally, keep CDN (but not blocked)
    $scripts_html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">' . PHP_EOL;
    $scripts_html .= '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>' . PHP_EOL;
    
    // Add wizard scripts for new-inquiry page
    if ($page === 'new-inquiry') {
        $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/libs/vanilla-wizard/js/wizard.min.js') . '"></script>' . PHP_EOL;
        $form_wizard_file = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/form-wizard.js';
        if (file_exists($form_wizard_file)) {
            $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/js/form-wizard.js?v=' . filemtime($form_wizard_file)) . '"></script>' . PHP_EOL;
        }
        // Datepicker for identity form - use persianDatepicker like in expert reports
        $datepicker_css = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/persianDatepicker-default.css';
        $datepicker_js = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/persianDatepicker.min.js';
        if (file_exists($datepicker_css) && file_exists($datepicker_js)) {
            $scripts_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css') . '">' . PHP_EOL;
            $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js') . '"></script>' . PHP_EOL;
        }
        // Inquiry form JS
        $inquiry_form_file = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js';
        if (file_exists($inquiry_form_file)) {
            $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js?v=' . filemtime($inquiry_form_file)) . '"></script>' . PHP_EOL;
        }
    }
    
    $scripts_html .= '<script src="' . MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js?v=' . time() . '"></script>' . PHP_EOL;
    if ($inquiry_id > 0) {
        $scripts_html .= '<script src="' . MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/installment-report.js?v=' . time() . '"></script>' . PHP_EOL;
    }
    if ($cash_inquiry_id > 0) {
        $scripts_html .= '<script src="' . MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/cash-report.js?v=' . time() . '"></script>' . PHP_EOL;
    }
    
    $experts = get_users(['role' => 'maneli_expert']);
    $experts_list = array_map(function($e) { return ['id' => $e->ID, 'name' => $e->display_name ?: $e->user_login]; }, $experts);
    $options = get_option('maneli_inquiry_all_options', []);
    
    $localize_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'experts' => $experts_list,
        'nonces' => [
            'assign_expert' => wp_create_nonce('maneli_inquiry_assign_expert_nonce'),
            'inquiry_delete' => wp_create_nonce('maneli_inquiry_delete_nonce'),
            'cash_assign_expert' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
            'cash_delete' => wp_create_nonce('maneli_cash_inquiry_delete_nonce'),
            'installment_status' => wp_create_nonce('maneli_installment_status'),
            'cash_filter' => wp_create_nonce('maneli_cash_inquiry_filter_nonce'),
            'inquiry_filter' => wp_create_nonce('maneli_inquiry_filter_nonce'),
            'details' => wp_create_nonce('maneli_inquiry_details_nonce'),
            'tracking_status' => wp_create_nonce('maneli_tracking_status_nonce'),
            'update_cash_status' => wp_create_nonce('maneli_update_cash_status'),
        ],
        'text' => [
            'error' => esc_html__('Error!', 'maneli-car-inquiry'),
            'success' => esc_html__('Success!', 'maneli-car-inquiry'),
            'server_error' => esc_html__('Server error. Please try again.', 'maneli-car-inquiry'),
            'unknown_error' => esc_html__('Unknown error', 'maneli-car-inquiry'),
            'no_experts_available' => esc_html__('No experts available.', 'maneli-car-inquiry'),
            'select_expert_required' => esc_html__('Please select an expert.', 'maneli-car-inquiry'),
            'select_expert_placeholder' => esc_html__('Select Expert', 'maneli-car-inquiry'),
            'auto_assign' => esc_html__('-- Auto Assign (Round Robin) --', 'maneli-car-inquiry'),
            'assign_title' => esc_html__('Assign to Expert', 'maneli-car-inquiry'),
            'assign_label' => esc_html__('Select Expert:', 'maneli-car-inquiry'),
            'assign_button' => esc_html__('Assign', 'maneli-car-inquiry'),
            'assign_text' => esc_html__('Select an expert for this inquiry:', 'maneli-car-inquiry'),
            'assign_success' => esc_html__('Expert assigned successfully.', 'maneli-car-inquiry'),
            'assign_failed' => esc_html__('Error assigning expert.', 'maneli-car-inquiry'),
            'delete_title' => esc_html__('Deleted', 'maneli-car-inquiry'),
            'delete_text' => esc_html__('Are you sure you want to delete this request? This action cannot be undone.', 'maneli-car-inquiry'),
            'delete_list_title' => esc_html__('Delete this inquiry?', 'maneli-car-inquiry'),
            'delete_list_text' => esc_html__('This action cannot be undone.', 'maneli-car-inquiry'),
            'confirm_delete_title' => esc_html__('Are you sure?', 'maneli-car-inquiry'),
            'confirm_delete_text' => esc_html__('Do you want to delete this inquiry? This action cannot be undone.', 'maneli-car-inquiry'),
            'confirm_button' => esc_html__('Yes', 'maneli-car-inquiry'),
            'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
            'deleting' => esc_html__('Deleting...', 'maneli-car-inquiry'),
            'confirm_delete' => esc_html__('Confirm Delete', 'maneli-car-inquiry'),
            'delete' => esc_html__('Delete', 'maneli-car-inquiry'),
            'error_deleting' => esc_html__('Error deleting', 'maneli-car-inquiry'),
            'edit_title' => esc_html__('Edit Inquiry', 'maneli-car-inquiry'),
            'placeholder_name' => esc_html__('First Name', 'maneli-car-inquiry'),
            'placeholder_last_name' => esc_html__('Last Name', 'maneli-car-inquiry'),
            'placeholder_mobile' => esc_html__('Mobile Number', 'maneli-car-inquiry'),
            'placeholder_color' => esc_html__('Color', 'maneli-car-inquiry'),
            'save_button' => esc_html__('Save', 'maneli-car-inquiry'),
            'downpayment_title' => esc_html__('Set Down Payment', 'maneli-car-inquiry'),
            'downpayment_placeholder' => esc_html__('Down Payment Amount', 'maneli-car-inquiry'),
            'downpayment_button' => esc_html__('Submit Down Payment', 'maneli-car-inquiry'),
            'reject_title' => esc_html__('Reject Inquiry', 'maneli-car-inquiry'),
            'reject_label' => esc_html__('Rejection Reason:', 'maneli-car-inquiry'),
            'reject_placeholder_custom' => esc_html__('Enter custom reason...', 'maneli-car-inquiry'),
            'reject_option_default' => esc_html__('-- Select Reason --', 'maneli-car-inquiry'),
            'reject_option_custom' => esc_html__('-- Custom Reason --', 'maneli-car-inquiry'),
            'reject_submit_button' => esc_html__('Reject Inquiry', 'maneli-car-inquiry'),
            'rejection_reason_required' => esc_html__('Please select or enter a rejection reason.', 'maneli-car-inquiry'),
            // Cash inquiry specific text
            'start_progress_title' => esc_html__('Start Progress', 'maneli-car-inquiry'),
            'start_progress_confirm' => esc_html__('Are you sure you want to start follow-up for this inquiry?', 'maneli-car-inquiry'),
            'approve_title' => esc_html__('Approve Inquiry', 'maneli-car-inquiry'),
            'approve_confirm' => esc_html__('Are you sure you want to approve this inquiry?', 'maneli-car-inquiry'),
            'approve_button' => esc_html__('Approve', 'maneli-car-inquiry'),
            'schedule_meeting_title' => esc_html__('Schedule Meeting', 'maneli-car-inquiry'),
            'meeting_date_label' => esc_html__('Meeting Date', 'maneli-car-inquiry'),
            'meeting_time_label' => esc_html__('Meeting Time', 'maneli-car-inquiry'),
            'select_date' => esc_html__('Select Date', 'maneli-car-inquiry'),
            'meeting_required' => esc_html__('Please enter meeting date and time', 'maneli-car-inquiry'),
            'schedule_button' => esc_html__('Schedule', 'maneli-car-inquiry'),
            'schedule_followup_title' => esc_html__('Schedule Follow-up', 'maneli-car-inquiry'),
            'followup_date_label' => esc_html__('Follow-up Date', 'maneli-car-inquiry'),
            'note_label_optional' => esc_html__('Note (Optional)', 'maneli-car-inquiry'),
            'enter_note' => esc_html__('Enter your note...', 'maneli-car-inquiry'),
            'schedule_followup_button' => esc_html__('Schedule Follow-up', 'maneli-car-inquiry'),
            'set_downpayment_title' => esc_html__('Set Down Payment Amount', 'maneli-car-inquiry'),
            'downpayment_amount_label' => esc_html__('Down Payment Amount (Toman):', 'maneli-car-inquiry'),
            'downpayment_amount_required' => esc_html__('Please enter down payment amount', 'maneli-car-inquiry'),
            'request_downpayment_title' => esc_html__('Request Down Payment?', 'maneli-car-inquiry'),
            'amount' => esc_html__('Amount', 'maneli-car-inquiry'),
            'toman' => esc_html__('Toman', 'maneli-car-inquiry'),
            'send_button' => esc_html__('Send', 'maneli-car-inquiry'),
            'ok_button' => esc_html__('OK', 'maneli-car-inquiry'),
            'status_updated' => esc_html__('Status updated successfully', 'maneli-car-inquiry'),
            'status_update_error' => esc_html__('Error updating status', 'maneli-car-inquiry'),
        ]
    ];
    
    // Add cash rejection reasons if we're on cash inquiries page
    if ($page === 'cash-inquiries' || ($page === 'inquiries' && $subpage === 'cash')) {
        $cash_rejection_reasons = array_filter(array_map('trim', explode("\n", $options['cash_inquiry_rejection_reasons'] ?? '')));
        $localize_data['cash_rejection_reasons'] = $cash_rejection_reasons;
    }
    
    $scripts_html .= '<script>window.maneliInquiryLists=' . json_encode($localize_data, JSON_UNESCAPED_UNICODE) . ';';
    if ($inquiry_id > 0) {
        $scripts_html .= 'window.maneliInstallmentReport={ajax_url:"' . admin_url('admin-ajax.php') . '",nonces:{update_status:"' . wp_create_nonce('maneli_installment_status') . '"}};';
    }
    if ($cash_inquiry_id > 0) {
        $scripts_html .= 'window.maneliCashReport={ajax_url:"' . admin_url('admin-ajax.php') . '",nonces:{update_status:"' . wp_create_nonce('maneli_update_cash_status') . '"}};';
    }
    
    // Add localization for new-inquiry wizard
    if ($page === 'new-inquiry') {
        $scripts_html .= 'window.maneliInquiryForm={ajax_url:"' . admin_url('admin-ajax.php') . '",nonces:{confirm_catalog:"' . wp_create_nonce('maneli_confirm_car_catalog_nonce') . '"}};';
    }
    
    $scripts_html .= 'console.log("✅ Inquiry scripts loaded");</script>' . PHP_EOL;
    
    // Simple wizard for new-inquiry
    if ($page === 'new-inquiry') {
        $scripts_html .= '
<script>
jQuery(document).ready(function($) {
    var currentStep = 0;
    var $wizard = $("#inquiry-wizard");
    var $steps = $(".wizard-step", $wizard);
    var totalSteps = $steps.length;
    
    // Find active step
    $steps.each(function(index) {
        if ($(this).hasClass("active")) currentStep = index;
    });
    
    function showStep(step) {
        $steps.removeClass("active").hide();
        $steps.eq(step).addClass("active").show();
        
        var $prev = $(".wizard-btn.prev");
        var $next = $(".wizard-btn.next");
        var $finish = $(".wizard-btn.finish");
        
        $prev.prop("disabled", step === 0);
        $next.toggle(step < totalSteps - 1);
        $finish.toggle(step === totalSteps - 1).css("display", step === totalSteps - 1 ? "inline-block" : "none");
    }
    
    showStep(currentStep);
    
    $(".wizard-btn.next").on("click", function() {
        if (currentStep < totalSteps - 1) {
            currentStep++;
            showStep(currentStep);
        }
    });
    
    $(".wizard-btn.prev").on("click", function() {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    });
});
</script>
<style>
.wizard-container .wizard-step { display: none; }
.wizard-container .wizard-step.active { display: block; }
</style>
';
    }
    $dashboard_html = str_replace('</body>', $scripts_html . '</body>', $dashboard_html);
}

echo $dashboard_html;
?>