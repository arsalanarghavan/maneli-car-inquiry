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
$ajax_nonce = wp_create_nonce('maneli_ajax_nonce'); // Match the action name used in handler

// Render dynamic sidebar menu
ob_start();
include MANELI_PLUGIN_DIR . 'templates/dashboard/sidebar-menu.php';
$sidebar_menu = ob_get_clean();

// Get the dashboard HTML content
$dashboard_html = file_get_contents(MANELI_PLUGIN_DIR . 'templates/dashboard-base.html');

$preferred_language = method_exists($handler, 'get_preferred_language_slug')
    ? $handler->get_preferred_language_slug()
    : 'fa';

$preferred_dir = $preferred_language === 'fa' ? 'rtl' : 'ltr';
$preferred_lang_attr = $preferred_language === 'fa' ? 'fa' : 'en';

// Normalize existing dir/lang attributes and apply preference
$dashboard_html = preg_replace('/\sdir=["\'][^"\']*["\']/', '', $dashboard_html, 1);
$dashboard_html = preg_replace('/\slang=["\'][^"\']*["\']/', '', $dashboard_html, 1);
$html_language_attributes = sprintf(
    ' dir="%s" lang="%s"',
    esc_attr($preferred_dir),
    esc_attr($preferred_lang_attr)
);
$dashboard_html = preg_replace('/(<html[^>]*)>/i', '$1' . $html_language_attributes . '>', $dashboard_html, 1);

// Update bootstrap CSS path for LTR preference
if ($preferred_language !== 'fa') {
    $dashboard_html = str_replace(
        'assets/libs/bootstrap/css/bootstrap.rtl.min.css',
        'assets/libs/bootstrap/css/bootstrap.min.css',
        $dashboard_html
    );
}

// Extract the sidebar part from the HTML and replace with dynamic sidebar
// Find the sidebar section (between <!-- Start::main-sidebar --> and <!-- End::main-sidebar -->)
$pattern = '/(<!-- Start::main-sidebar -->)(.*?)(<!-- End::main-sidebar -->)/s';
$replacement = '$1' . PHP_EOL . $sidebar_menu . PHP_EOL . '$3';
$dashboard_html = preg_replace($pattern, $replacement, $dashboard_html);

// Replace asset paths to use WordPress URLs
$dashboard_html = str_replace('./assets/', MANELI_PLUGIN_URL . 'assets/', $dashboard_html);

// Ensure sidebar fixes stylesheet busts cache after updates
$sidebar_fixes_paths = [];
if (defined('MANELI_INQUIRY_PLUGIN_PATH')) {
    $sidebar_fixes_paths[] = trailingslashit(MANELI_INQUIRY_PLUGIN_PATH) . 'assets/css/sidebar-fixes.css';
}
$sidebar_fixes_paths[] = MANELI_PLUGIN_DIR . 'assets/css/sidebar-fixes.css';

$sidebar_fixes_version = null;
foreach ($sidebar_fixes_paths as $sidebar_path) {
    if (file_exists($sidebar_path)) {
        $sidebar_fixes_version = (string) filemtime($sidebar_path);
        break;
    }
}

if ($sidebar_fixes_version) {
    $dashboard_html = preg_replace(
        '/(sidebar-fixes\.css)(\?[^"\']*)?/',
        '$1?v=' . $sidebar_fixes_version,
        $dashboard_html,
        1
    );
}

// Inject translated header text
$header_replacements = [
    '%%MANELI_HEADER_SIDEBAR_TOGGLE_LABEL%%' => esc_attr__('Hide Sidebar', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_SEARCH_PLACEHOLDER%%' => esc_attr__('Search...', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_LANGUAGE_FA%%' => esc_html__('Persian', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_LANGUAGE_EN%%' => esc_html__('English', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATIONS_TITLE%%' => esc_html__('Alerts', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATIONS_UNREAD%%' => esc_html__('5 unread', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATION_SAMPLE_TITLE%%' => esc_html__('Message title', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATION_SAMPLE_DESCRIPTION%%' => esc_html__('Description', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATION_SAMPLE_TIME_NOW%%' => esc_html__('Just now', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATION_SAMPLE_TIME_TWO_HOURS%%' => esc_html__('2 hours ago', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATION_SAMPLE_TIME_ONE_DAY%%' => esc_html__('1 day ago', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NOTIFICATION_SAMPLE_TIME_FIVE_HOURS%%' => esc_html__('5 hours ago', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_VIEW_ALL%%' => esc_html__('View all', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NO_NOTIFICATIONS%%' => esc_html__('No alerts available', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_NAME%%' => esc_html__('User name', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_ROLE%%' => esc_html__('Role', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_VIEW_SITE_LABEL%%' => esc_html__('View site', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_VIEW_SITE_URL%%' => esc_url(home_url('/')),
    '%%MANELI_HEADER_MODAL_SEARCH_PLACEHOLDER%%' => esc_attr__('Search', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_MODAL_SEARCH_ARIA%%' => esc_attr__('Search', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_UNREAD_BADGE%%' => esc_html__('%s unread', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_NO_UNREAD%%' => esc_html__('No unread', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_ACCOUNT%%' => esc_html__('Account', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_EMAIL%%' => esc_html__('Email', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_FILE_MANAGER%%' => esc_html__('File manager', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_SETTINGS%%' => esc_html__('Settings', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_SUPPORT%%' => esc_html__('Support', 'maneli-car-inquiry'),
    '%%MANELI_HEADER_PROFILE_LOGOUT%%' => esc_html__('Log out', 'maneli-car-inquiry'),
];

$dashboard_html = str_replace(
    array_keys($header_replacements),
    array_values($header_replacements),
    $dashboard_html
);

// Inject translated footer text
$company_name = __('Puzzling Institute', 'maneli-car-inquiry');
$company_link = sprintf(
    '<a href="%1$s" target="_blank" rel="noopener"><span class="fw-medium text-primary">%2$s</span></a>',
    esc_url('https://puzzlingco.com'),
    esc_html($company_name)
);

/* translators: 1: year span HTML, 2: heart icon HTML, 3: linked company name */
$footer_text = sprintf(
    __('%1$s Designed with %2$s by %3$s', 'maneli-car-inquiry'),
    '<span id="year"></span>',
    '<span class="bi bi-heart-fill text-danger" aria-hidden="true"></span>',
    $company_link
);

$dashboard_html = str_replace(
    '%%MANELI_DASHBOARD_FOOTER%%',
    wp_kses_post($footer_text),
    $dashboard_html
);

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

// CRITICAL: Add inline dark mode initialization script in head before page renders
// This prevents flash of light mode when dark mode is enabled
$dark_mode_init_script = '<script>
(function() {
    // Initialize dark mode immediately before page renders
    try {
        if (typeof Storage !== "undefined" && localStorage.getItem("xintradarktheme")) {
            var html = document.documentElement;
            if (html) {
                html.setAttribute("data-theme-mode", "dark");
                if (localStorage.getItem("xintraHeader")) {
                    html.setAttribute("data-header-styles", localStorage.getItem("xintraHeader"));
                } else {
                    html.setAttribute("data-header-styles", "dark");
                }
                if (localStorage.getItem("xintraMenu")) {
                    html.setAttribute("data-menu-styles", localStorage.getItem("xintraMenu"));
                } else {
                    html.setAttribute("data-menu-styles", "dark");
                }
            }
        }
    } catch(e) {
        // Silently fail if localStorage is not available
    }
})();
</script>';
// Insert dark mode init script right after head tag
$dashboard_html = preg_replace(
    '/(<head[^>]*>)/i',
    '$1' . PHP_EOL . '        ' . $dark_mode_init_script . PHP_EOL,
    $dashboard_html,
    1
);

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

// Remove sales-dashboard.js and apexcharts when we're not on the home or visitor statistics pages
if ($dashboard_page !== 'home' && $dashboard_page !== 'visitor-statistics') {
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
$fixed_script = <<<'HTML'
<!-- Language Switching Function -->
<script>
    (function() {
        const COOKIE_NAME = 'maneli_language';
        const COOKIE_MAX_AGE = 60 * 60 * 24 * 30; // 30 days
        const SERVER_LANGUAGE = <?php echo wp_json_encode($preferred_language === 'fa' ? 'fa' : 'en'); ?>;

        function shouldUsePersianDates() {
            return document.documentElement && document.documentElement.lang === 'fa';
        }

        window.maneliShouldUsePersianDates = shouldUsePersianDates;

        function normalizeLanguage(lang) {
            const serverNormalized = (SERVER_LANGUAGE === 'en') ? 'en' : 'fa';
            if (!lang) {
                return serverNormalized;
            }
            const value = String(lang).toLowerCase().trim();
            if (value === '') {
                return serverNormalized;
            }
            if (value.startsWith('en') || value === 'english') {
                return 'en';
            }
            if (value.startsWith('fa') || value === 'persian') {
                return 'fa';
            }
            return serverNormalized;
        }

        function setLanguageCookie(lang) {
            try {
                const normalized = normalizeLanguage(lang);
                document.cookie = COOKIE_NAME + '=' + normalized + '; path=/; max-age=' + COOKIE_MAX_AGE + '; SameSite=Lax';
            } catch (error) {
                console.warn('[Maneli] Unable to persist language cookie', error);
            }
        }

        function applyLanguage(lang) {
            const normalized = normalizeLanguage(lang);
            const html = document.documentElement;
            const body = document.body;

            if (html) {
                html.lang = normalized === 'fa' ? 'fa' : 'en';
                html.dir = normalized === 'fa' ? 'rtl' : 'ltr';
                html.classList.toggle('rtl', normalized === 'fa');
                html.classList.toggle('ltr', normalized !== 'fa');
            }

            if (body) {
                body.classList.toggle('rtl', normalized === 'fa');
                body.classList.toggle('ltr', normalized !== 'fa');
            }

            const styleLink = document.getElementById('style');
            if (styleLink) {
                const currentHref = styleLink.getAttribute('href') || '';
                const storedRtl = styleLink.dataset.rtlHref || styleLink.getAttribute('data-rtl-href');
                const storedLtr = styleLink.dataset.ltrHref || styleLink.getAttribute('data-ltr-href');
                const rtlHref = storedRtl || (currentHref.includes('bootstrap.min.css') ? currentHref.replace('bootstrap.min.css', 'bootstrap.rtl.min.css') : currentHref);
                const ltrHref = storedLtr || (currentHref.includes('bootstrap.rtl.min.css') ? currentHref.replace('bootstrap.rtl.min.css', 'bootstrap.min.css') : currentHref);

                styleLink.dataset.rtlHref = rtlHref;
                styleLink.dataset.ltrHref = ltrHref;

                styleLink.setAttribute('href', normalized === 'fa' ? rtlHref : ltrHref);
            }

            return normalized;
        }

        window.maneliServerLanguage = SERVER_LANGUAGE;

        window.changeLanguage = function(lang) {
            const normalized = normalizeLanguage(lang);
            const currentLang = normalizeLanguage(document.documentElement.lang);
            const currentDir = (document.documentElement.dir || 'rtl').toLowerCase();

            localStorage.setItem('maneli_language', normalized);
            setLanguageCookie(normalized);

            // Apply immediately for visual feedback
            applyLanguage(normalized);

            const requiresReload = normalized !== currentLang ||
                (normalized === 'fa' && currentDir !== 'rtl') ||
                (normalized !== 'fa' && currentDir !== 'ltr');

            if (requiresReload) {
                localStorage.setItem('maneli_language_changing', 'true');
                window.location.reload();
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = normalizeLanguage(localStorage.getItem('maneli_language'));
            const appliedLang = applyLanguage(savedLang);
            setLanguageCookie(appliedLang);

            if (localStorage.getItem('maneli_language_changing') === 'true') {
                localStorage.removeItem('maneli_language_changing');
                return;
            }

            const currentLang = normalizeLanguage(document.documentElement.lang);
            const currentDir = (document.documentElement.dir || 'rtl').toLowerCase();

            const needsReload = appliedLang !== currentLang ||
                (appliedLang === 'fa' && currentDir !== 'rtl') ||
                (appliedLang !== 'fa' && currentDir !== 'ltr');

            if (needsReload) {
                localStorage.setItem('maneli_language_changing', 'true');
                window.location.reload();
            }
        });
    })();
</script>
<!-- Digit & Locale Utilities -->
<script>
    (function(window, document) {
        const SERVER_LANGUAGE = (typeof window !== 'undefined' && window.maneliServerLanguage) ? String(window.maneliServerLanguage).toLowerCase() : '';
        const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const ENGLISH_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        const PERSIAN_COMMA_REGEX = /[٬،]/g;

        function normalizeLanguage(value) {
            if (!value) {
                return SERVER_LANGUAGE || '';
            }
            const lower = String(value).toLowerCase();
            if (lower.startsWith('en') || lower === 'english' || lower === 'ltr') {
                return 'en';
            }
            if (lower.startsWith('fa') || lower === 'persian' || lower === 'rtl') {
                return 'fa';
            }
            return SERVER_LANGUAGE || '';
        }

        function toStringSafe(input) {
            if (input === null || input === undefined) {
                return '';
            }
            if (typeof input === 'string') {
                return input;
            }
            if (typeof input === 'number' || typeof input === 'boolean') {
                return String(input);
            }
            if (typeof input === 'object') {
                try {
                    const valueOf = input.valueOf();
                    if (valueOf !== input) {
                        return toStringSafe(valueOf);
                    }
                } catch (error) {
                    // ignore
                }
                if (typeof input.toString === 'function') {
                    try {
                        return input.toString();
                    } catch (error) {
                        return '';
                    }
                }
            }
            try {
                return JSON.stringify(input);
            } catch (error) {
                return '';
            }
        }

        function toEnglishDigits(input) {
            let str = toStringSafe(input);
            if (!str) {
                return '';
            }
            for (let i = 0; i < PERSIAN_DIGITS.length; i++) {
                str = str.split(PERSIAN_DIGITS[i]).join(ENGLISH_DIGITS[i]);
            }
            for (let i = 0; i < ARABIC_DIGITS.length; i++) {
                str = str.split(ARABIC_DIGITS[i]).join(ENGLISH_DIGITS[i]);
            }
            return str.replace(PERSIAN_COMMA_REGEX, ',');
        }

        function toPersianDigits(input) {
            let str = toStringSafe(input);
            if (!str) {
                return '';
            }
            str = str.replace(/\d/g, function(digit) {
                return PERSIAN_DIGITS[Number(digit)];
            });
            for (let i = 0; i < ARABIC_DIGITS.length; i++) {
                str = str.split(ARABIC_DIGITS[i]).join(PERSIAN_DIGITS[i]);
            }
            return str;
        }

        function parseNumber(input) {
            const normalized = toEnglishDigits(input).replace(/,/g, '').trim();
            if (normalized === '') {
                return NaN;
            }
            return Number(normalized);
        }

        function detectLanguage() {
            if (SERVER_LANGUAGE) {
                const normalizedServer = normalizeLanguage(SERVER_LANGUAGE);
                if (normalizedServer) {
                    return normalizedServer;
                }
            }
            const fromLocalStorage = function() {
                if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
                    return '';
                }
                try {
                    return normalizeLanguage(window.localStorage.getItem('maneli_language'));
                } catch (error) {
                    console.warn('[Maneli] Unable to read language from localStorage', error);
                    return '';
                }
            };

            const fromCookie = function() {
                if (typeof document === 'undefined') {
                    return '';
                }
                try {
                    const match = document.cookie.match(/(?:^|;\s*)maneli_language=([^;]+)/);
                    if (match && match[1]) {
                        return normalizeLanguage(decodeURIComponent(match[1]));
                    }
                } catch (error) {
                    console.warn('[Maneli] Unable to read language cookie', error);
                }
                return '';
            };

            const fromHtml = function() {
                if (typeof document === 'undefined') {
                    return '';
                }
                const langAttr = normalizeLanguage(document.documentElement.lang);
                if (langAttr) {
                    return langAttr;
                }
                const dirAttr = (document.documentElement.dir || '').toLowerCase();
                if (dirAttr === 'ltr') {
                    return 'en';
                }
                if (dirAttr === 'rtl') {
                    return 'fa';
                }
                return '';
            };

            const fromNavigator = function() {
                if (typeof navigator === 'undefined') {
                    return '';
                }
                return normalizeLanguage(navigator.language || navigator.userLanguage);
            };

            const sources = [fromLocalStorage(), fromCookie(), fromHtml(), fromNavigator()];
            for (let i = 0; i < sources.length; i++) {
                const candidate = sources[i];
                if (candidate === 'fa' || candidate === 'en') {
                    return candidate;
                }
            }
            return SERVER_LANGUAGE || 'fa';
        }

        function shouldUsePersianDigits() {
            return detectLanguage() === 'fa';
        }

        function ensureDigits(input, target) {
            const normalizedTarget = normalizeLanguage(target);
            if (normalizedTarget === 'fa') {
                return toPersianDigits(input);
            }
            if (normalizedTarget === 'en') {
                return toEnglishDigits(input);
            }
            return shouldUsePersianDigits() ? toPersianDigits(input) : toEnglishDigits(input);
        }

        function formatNumber(input, options) {
            const opts = options || {};
            const targetLocale = normalizeLanguage(opts.forceLocale) || (shouldUsePersianDigits() ? 'fa' : 'en');
            const numericValue = parseNumber(input);
            if (!Number.isFinite(numericValue)) {
                return ensureDigits(input, targetLocale);
            }
            const locale = targetLocale === 'fa' ? 'fa-IR' : 'en-US';
            const formatterOptions = {
                useGrouping: opts.useGrouping !== false
            };
            if (typeof opts.minimumFractionDigits === 'number') {
                formatterOptions.minimumFractionDigits = opts.minimumFractionDigits;
            }
            if (typeof opts.maximumFractionDigits === 'number') {
                formatterOptions.maximumFractionDigits = opts.maximumFractionDigits;
            }
            const formatted = numericValue.toLocaleString(locale, formatterOptions);
            return targetLocale === 'fa' ? toPersianDigits(formatted) : toEnglishDigits(formatted);
        }

        window.maneliLocale = Object.assign({}, window.maneliLocale || {}, {
            normalizeLanguage,
            detectLanguage,
            shouldUsePersianDigits,
            ensureDigits,
            toEnglishDigits,
            toPersianDigits,
            parseNumber,
            formatNumber
        });

        window.maneliDigits = window.maneliLocale;
        window.maneliShouldUsePersianDates = shouldUsePersianDigits;

        window.Apex = window.Apex || {};
        window.Apex.chart = window.Apex.chart || {};

        (function registerApexLocales() {
            const existingLocales = Array.isArray(window.Apex.chart.locales) ? window.Apex.chart.locales.slice() : [];

            function upsertLocale(locale) {
                const index = existingLocales.findIndex(item => item && item.name === locale.name);
                if (index >= 0) {
                    existingLocales[index] = locale;
                } else {
                    existingLocales.push(locale);
                }
            }

            upsertLocale({
                name: 'fa',
                options: {
                    months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
                    shortMonths: ['فرو', 'ارد', 'خرد', 'تیر', 'مرد', 'شهر', 'مهر', 'آبا', 'آذر', 'دی', 'بهم', 'اسف'],
                    days: ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'],
                    shortDays: ['ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش'],
                    toolbar: {
                        exportToSVG: 'دانلود SVG',
                        exportToPNG: 'دانلود PNG',
                        exportToCSV: 'دانلود CSV',
                        selection: 'انتخاب',
                        selectionZoom: 'بزرگنمایی انتخابی',
                        zoomIn: 'بزرگنمایی',
                        zoomOut: 'کوچک‌نمایی',
                        pan: 'جابجایی',
                        reset: 'بازنشانی زوم'
                    }
                }
            });

            upsertLocale({
                name: 'en',
                options: {
                    toolbar: {
                        exportToSVG: 'Download SVG',
                        exportToPNG: 'Download PNG',
                        exportToCSV: 'Download CSV',
                        selection: 'Selection',
                        selectionZoom: 'Selection Zoom',
                        zoomIn: 'Zoom In',
                        zoomOut: 'Zoom Out',
                        pan: 'Pan',
                        reset: 'Reset Zoom'
                    }
                }
            });

            window.Apex.chart.locales = existingLocales;
        })();

        window.Apex.chart.defaultLocale = shouldUsePersianDigits() ? 'fa' : 'en';
    })(window, document);
</script>
HTML;
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
        } elseif ($page_slug === 'notifications' && !empty($subpage)) {
            // Handle notification sub-pages: notifications/sms, notifications/email, etc.
            $valid_subpages = ['sms', 'email', 'telegram', 'app'];
            if (in_array($subpage, $valid_subpages)) {
                $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/notifications/' . $subpage . '-notifications.php';
                if (file_exists($page_template)) {
                    ob_start();
                    include $page_template;
                    $page_content = ob_get_clean();
                }
            } else {
                // Invalid subpage, redirect to notifications center
                wp_redirect(home_url('/dashboard/notifications-center'));
                exit;
            }
        } elseif ($page_slug === 'logs' && !empty($subpage)) {
            // Handle logs sub-pages: logs/system and logs/user
            $valid_subpages = ['system', 'user'];
            if (in_array($subpage, $valid_subpages)) {
                $page_template = MANELI_PLUGIN_DIR . 'templates/dashboard/' . $subpage . '-logs.php';
                if (file_exists($page_template)) {
                    ob_start();
                    include $page_template;
                    $page_content = ob_get_clean();
                }
            } else {
                // Invalid subpage, redirect to dashboard
                wp_redirect(home_url('/dashboard'));
                exit;
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
$dashboard_html .= '<script src="' . MANELI_PLUGIN_URL . 'assets/js/global-search.js"></script>';

// Visitor Tracking Script - For all dashboard pages (to track dashboard visits)
$options = get_option('maneli_inquiry_all_options', []);
$tracking_enabled = isset($options['enable_visitor_statistics']) && $options['enable_visitor_statistics'] == '1';
if ($tracking_enabled) {
    $tracking_script_path = MANELI_PLUGIN_DIR . 'assets/js/frontend/visitor-tracking.js';
    if (file_exists($tracking_script_path)) {
        $dashboard_html .= '<script>
var maneliVisitorTracking = {
    ajaxUrl: "' . esc_js(admin_url('admin-ajax.php')) . '",
    nonce: "' . esc_js(wp_create_nonce('maneli_visitor_stats_nonce')) . '",
    enabled: true,
    debug: ' . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . '
};
</script>' . PHP_EOL;
        $dashboard_html .= '<script src="' . esc_url(MANELI_PLUGIN_URL . 'assets/js/frontend/visitor-tracking.js?v=' . filemtime($tracking_script_path)) . '"></script>' . PHP_EOL;
    }
}

// Visitor Statistics Scripts - Direct Injection (for visitor-statistics page only)
if ($dashboard_page === 'visitor-statistics') {
    require_once MANELI_PLUGIN_DIR . 'includes/functions.php';
    
    // ApexCharts
    $apexcharts_path = MANELI_PLUGIN_DIR . 'assets/libs/apexcharts/apexcharts.min.js';
    if (file_exists($apexcharts_path)) {
        $dashboard_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.css') . '">' . PHP_EOL;
        $dashboard_html .= '<script src="' . esc_url(MANELI_PLUGIN_URL . 'assets/libs/apexcharts/apexcharts.min.js') . '"></script>' . PHP_EOL;
    } else {
        // Fallback to CDN
        $dashboard_html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.css">' . PHP_EOL;
        $dashboard_html .= '<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.44.0/dist/apexcharts.min.js"></script>' . PHP_EOL;
    }
    
    // Persian Datepicker
    $datepicker_css = MANELI_PLUGIN_DIR . 'assets/css/persianDatepicker-default.css';
    $datepicker_js = MANELI_PLUGIN_DIR . 'assets/js/persianDatepicker.min.js';
    if (file_exists($datepicker_css) && file_exists($datepicker_js)) {
        $dashboard_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_PLUGIN_URL . 'assets/css/persianDatepicker-default.css') . '">' . PHP_EOL;
        $dashboard_html .= '<script src="' . esc_url(MANELI_PLUGIN_URL . 'assets/js/persianDatepicker.min.js') . '"></script>' . PHP_EOL;
    }
    if ($preferred_language !== 'fa') {
        $dashboard_html = str_replace('<link rel="stylesheet" href="' . esc_url(MANELI_PLUGIN_URL . 'assets/css/persianDatepicker-default.css') . '">' . PHP_EOL, '', $dashboard_html);
        $dashboard_html = str_replace('<script src="' . esc_url(MANELI_PLUGIN_URL . 'assets/js/persianDatepicker.min.js') . '"></script>' . PHP_EOL, '', $dashboard_html);
    }
    
    // Visitor Statistics Dashboard Script
    $dashboard_js_path = MANELI_PLUGIN_DIR . 'assets/js/admin/visitor-statistics-dashboard.js';
    if (file_exists($dashboard_js_path)) {
        // Get date range from query parameters
        $start_date_input = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
        $end_date_input = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;
        
        // Convert Jalali to Gregorian if needed
        $start_date = $start_date_input ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $end_date_input ?: date('Y-m-d');
        
        if ($start_date_input && preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $start_date_input, $matches)) {
            if (!function_exists('maneli_jalali_to_gregorian')) {
                require_once MANELI_PLUGIN_DIR . 'includes/functions.php';
            }
            $start_date = maneli_jalali_to_gregorian($matches[1], $matches[2], $matches[3]);
        }
        
        if ($end_date_input && preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $end_date_input, $matches)) {
            if (!function_exists('maneli_jalali_to_gregorian')) {
                require_once MANELI_PLUGIN_DIR . 'includes/functions.php';
            }
            $end_date = maneli_jalali_to_gregorian($matches[1], $matches[2], $matches[3]);
        }
        
        // Get daily stats
        require_once MANELI_PLUGIN_DIR . 'includes/class-visitor-statistics.php';
        $daily_stats = Maneli_Visitor_Statistics::get_daily_visits($start_date, $end_date);
        
        // Convert dates to Jalali format
        if (function_exists('maneli_gregorian_to_jalali')) {
            foreach ($daily_stats as &$stat) {
                if (isset($stat->date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $stat->date)) {
                    $date_parts = explode('-', $stat->date);
                    $stat->date = maneli_gregorian_to_jalali($date_parts[0], $date_parts[1], $date_parts[2], 'Y/m/d');
                }
            }
            unset($stat);
        }
        
        // Localize script
        $dashboard_html .= '<script>
console.log("Visitor Statistics: Script block executed");
var maneliVisitorStats = ' . wp_json_encode([
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('maneli_visitor_stats_nonce'),
            'startDate' => $start_date,
            'endDate' => $end_date,
            'dailyStats' => $daily_stats,
            'translations' => [
                'loading' => esc_html__('Loading...', 'maneli-car-inquiry'),
                'error' => esc_html__('Error loading data', 'maneli-car-inquiry'),
                'noData' => esc_html__('No data available', 'maneli-car-inquiry'),
                'visits' => esc_html__('Visits', 'maneli-car-inquiry'),
                'uniqueVisitors' => esc_html__('Unique Visitors', 'maneli-car-inquiry'),
                'pages' => esc_html__('Pages', 'maneli-car-inquiry'),
                'date' => esc_html__('Date', 'maneli-car-inquiry'),
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';
console.log("maneliVisitorStats object:", maneliVisitorStats);
</script>' . PHP_EOL;
        
        $dashboard_html .= '<script src="' . esc_url(MANELI_PLUGIN_URL . 'assets/js/admin/visitor-statistics-dashboard.js?v=' . filemtime($dashboard_js_path)) . '"></script>' . PHP_EOL;
    }
    
    // Visitor Statistics CSS
    $dashboard_css_path = MANELI_PLUGIN_DIR . 'assets/css/visitor-statistics.css';
    if (file_exists($dashboard_css_path)) {
        $dashboard_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_PLUGIN_URL . 'assets/css/visitor-statistics.css?v=' . filemtime($dashboard_css_path)) . '">' . PHP_EOL;
    }
}
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
var maneliGlobalSearch = {
    texts: {
        users: "' . esc_js(__('Users', 'maneli-car-inquiry')) . '",
        cash_inquiries: "' . esc_js(__('Cash Inquiries', 'maneli-car-inquiry')) . '",
        installment_inquiries: "' . esc_js(__('Installment Inquiries', 'maneli-car-inquiry')) . '",
        no_results: "' . esc_js(__('No results found', 'maneli-car-inquiry')) . '"
    }
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

// CRITICAL: Add dark mode toggle initialization inline
// This ensures it runs even if custom.js has issues
$dark_mode_toggle_script = '<script>
(function() {
    console.log("Maneli: Initializing dark mode toggle from dashboard.php");
    
    function maneliToggleTheme() {
        var html = document.querySelector("html");
        if (!html) return;
        
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
            console.log("Maneli: Switched to light mode");
        } else {
            html.setAttribute("data-theme-mode", "dark");
            html.setAttribute("data-header-styles", "dark");
            html.setAttribute("data-menu-styles", "dark");
            if (!localStorage.getItem("primaryRGB")) {
                html.setAttribute("style", "");
            }
            
            // Set CSS variables for dark mode if not custom background is set (matching xintra template)
            if (!localStorage.getItem("bodyBgRGB")) {
                // Default dark mode colors from xintra template
                html.style.setProperty("--body-bg-rgb", "25, 25, 28");
                html.style.setProperty("--body-bg-rgb2", "45, 45, 48");
                html.style.setProperty("--light-rgb", "43, 46, 49");
                html.style.setProperty("--form-control-bg", "rgb(25, 25, 28)");
                html.style.setProperty("--input-border", "rgba(255, 255, 255, 0.1)");
                html.style.setProperty("--default-body-bg-color", "rgb(45, 45, 48)");
                html.style.setProperty("--menu-bg", "rgb(25, 25, 28)");
                html.style.setProperty("--header-bg", "rgb(25, 25, 28)");
                html.style.setProperty("--custom-white", "rgb(25, 25, 28)");
            }
            
            localStorage.setItem("xintradarktheme", "true");
            localStorage.setItem("xintraMenu", "dark");
            localStorage.setItem("xintraHeader", "dark");
            localStorage.removeItem("bodylightRGB");
            localStorage.removeItem("bodyBgRGB");
            console.log("Maneli: Switched to dark mode");
        }

        if (window.maneliUpdateHeaderIcons) {
            window.maneliUpdateHeaderIcons();
        }
    }
    
    function initManeliDarkModeToggle() {
        var layoutSetting = document.querySelector(".layout-setting");
        if (layoutSetting) {
            console.log("Maneli: Found .layout-setting element");
            
            // Clone to remove existing listeners
            var parent = layoutSetting.parentNode;
            if (parent) {
                var newLayoutSetting = layoutSetting.cloneNode(true);
                parent.replaceChild(newLayoutSetting, layoutSetting);
                
                newLayoutSetting.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Maneli: Dark mode toggle button clicked");
                    maneliToggleTheme();
                    return false;
                });
                
                console.log("Maneli: Dark mode toggle initialized successfully");
            }
        } else {
            console.warn("Maneli: .layout-setting element not found, will retry");
            setTimeout(initManeliDarkModeToggle, 100);
        }
    }
    
    // Try multiple times to ensure it works
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(initManeliDarkModeToggle, 100);
        });
    } else {
        setTimeout(initManeliDarkModeToggle, 100);
    }
    
    // Also try after window load
    window.addEventListener("load", function() {
        setTimeout(initManeliDarkModeToggle, 200);
    });
})();
</script>';
$dashboard_html .= $dark_mode_toggle_script;

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
    $page === 'cash-followups' ||
    $page === 'installment-followups' ||
    ($page === 'inquiries' && ($subpage === 'cash' || $subpage === 'installment')) ||
    $page === 'new-inquiry' ||
    $page === 'new-cash-inquiry' ||
    $page === 'profile-settings' ||
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
        // Persian Datepicker
        if ($preferred_language === 'fa') {
            $datepicker_css = MANELI_PLUGIN_DIR . 'assets/css/persianDatepicker-default.css';
            $datepicker_js = MANELI_PLUGIN_DIR . 'assets/js/persianDatepicker.min.js';
        if (file_exists($datepicker_css) && file_exists($datepicker_js)) {
                $scripts_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_PLUGIN_URL . 'assets/css/persianDatepicker-default.css') . '">' . PHP_EOL;
                $scripts_html .= '<script src="' . esc_url(MANELI_PLUGIN_URL . 'assets/js/persianDatepicker.min.js') . '"></script>' . PHP_EOL;
            }
        }
        // Inquiry form JS
        $inquiry_form_file = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js';
        if (file_exists($inquiry_form_file)) {
            $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js?v=' . filemtime($inquiry_form_file)) . '"></script>' . PHP_EOL;
        }
        
        // Add modal calculator for step 3 (car replacement)
        $current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
        if ($current_step === 3) {
            // Modal calculator CSS (reuse loan-calculator.css)
            $calculator_css = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/loan-calculator.css';
            if (file_exists($calculator_css)) {
                $scripts_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/loan-calculator.css?v=' . filemtime($calculator_css)) . '">' . PHP_EOL;
            }
            // Modal calculator JS
            $modal_calculator_js = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/modal-calculator.js';
            if (file_exists($modal_calculator_js)) {
                $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/js/modal-calculator.js?v=' . filemtime($modal_calculator_js)) . '"></script>' . PHP_EOL;
            }
            // Localize modal calculator
            $options = get_option('maneli_inquiry_all_options', []);
            $interest_rate = floatval($options['loan_interest_rate'] ?? 0.035);
            $scripts_html .= '<script>window.maneli_ajax_object={interestRate:' . esc_js($interest_rate) . ',ajax_url:"' . admin_url('admin-ajax.php') . '",nonce:"' . wp_create_nonce('maneli_ajax_nonce') . '"};</script>' . PHP_EOL;
        }
    }
    
    // Add datepicker scripts for profile-settings page
    if ($page === 'profile-settings') {
        $datepicker_css = MANELI_INQUIRY_PLUGIN_PATH . 'assets/css/persianDatepicker-default.css';
        $datepicker_js = MANELI_INQUIRY_PLUGIN_PATH . 'assets/js/persianDatepicker.min.js';
        if (file_exists($datepicker_css) && file_exists($datepicker_js)) {
            $scripts_html .= '<link rel="stylesheet" href="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/css/persianDatepicker-default.css') . '">' . PHP_EOL;
            $scripts_html .= '<script src="' . esc_url(MANELI_INQUIRY_PLUGIN_URL . 'assets/js/persianDatepicker.min.js') . '"></script>' . PHP_EOL;
        }
        // Inquiry form JS for Persian numbers
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
            'cash_assign_expert' => wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce'),
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
            'confirm_button' => esc_html__('Yes', 'maneli-car-inquiry'),
            'cancel_button' => esc_html__('Cancel', 'maneli-car-inquiry'),
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
            // SMS Related Translations
            'send_sms' => esc_html__('Send SMS', 'maneli-car-inquiry'),
            'recipient' => esc_html__('Recipient:', 'maneli-car-inquiry'),
            'message' => esc_html__('Message:', 'maneli-car-inquiry'),
            'enter_message' => esc_html__('Enter your message...', 'maneli-car-inquiry'),
            'please_enter_message' => esc_html__('Please enter a message', 'maneli-car-inquiry'),
            'sending' => esc_html__('Sending...', 'maneli-car-inquiry'),
            'please_wait' => esc_html__('Please wait', 'maneli-car-inquiry'),
            'sms_sent_successfully' => esc_html__('SMS sent successfully!', 'maneli-car-inquiry'),
            'failed_to_send_sms' => esc_html__('Failed to send SMS', 'maneli-car-inquiry'),
            'invalid_inquiry_id' => esc_html__('Invalid inquiry ID.', 'maneli-car-inquiry'),
            'sms_history_modal_not_found' => esc_html__('SMS history modal not found.', 'maneli-car-inquiry'),
            'no_sms_history' => esc_html__('No SMS messages have been sent for this inquiry yet.', 'maneli-car-inquiry'),
            'error_loading_history' => esc_html__('Error loading SMS history.', 'maneli-car-inquiry'),
            'missing_required_info' => esc_html__('Missing required information.', 'maneli-car-inquiry'),
            'resend_sms' => esc_html__('Resend SMS?', 'maneli-car-inquiry'),
            'resend_confirm' => esc_html__('Are you sure you want to resend this SMS?', 'maneli-car-inquiry'),
            'yes_resend' => esc_html__('Yes, Resend', 'maneli-car-inquiry'),
            'resend' => esc_html__('Resend', 'maneli-car-inquiry'),
            'sms_resent_successfully' => esc_html__('SMS resent successfully.', 'maneli-car-inquiry'),
            'failed_to_resend_sms' => esc_html__('Failed to resend SMS.', 'maneli-car-inquiry'),
            'check_status' => esc_html__('Check Status', 'maneli-car-inquiry'),
            'checking' => esc_html__('Checking...', 'maneli-car-inquiry'),
            'checking_status' => esc_html__('Checking status...', 'maneli-car-inquiry'),
            'failed_to_get_status' => esc_html__('Failed to get status.', 'maneli-car-inquiry'),
            'error_checking_status' => esc_html__('Error checking status.', 'maneli-car-inquiry'),
            'status_unavailable' => esc_html__('Status unavailable', 'maneli-car-inquiry'),
            'check_failed' => esc_html__('Check failed', 'maneli-car-inquiry'),
            'rate_limit_exceeded' => esc_html__('Rate limit exceeded or service temporarily unavailable', 'maneli-car-inquiry'),
            'delivered' => esc_html__('Delivered', 'maneli-car-inquiry'),
            'failed' => esc_html__('Failed', 'maneli-car-inquiry'),
            'pending' => esc_html__('Pending', 'maneli-car-inquiry'),
            'blocked' => esc_html__('Blocked', 'maneli-car-inquiry'),
            'rejected' => esc_html__('Rejected', 'maneli-car-inquiry'),
            'unknown_status' => esc_html__('Unknown status', 'maneli-car-inquiry'),
            'sent_by' => esc_html__('Sent By', 'maneli-car-inquiry'),
            'loading_sms_history' => esc_html__('Loading SMS history...', 'maneli-car-inquiry'),
            'no_sms_messages_sent_yet' => esc_html__('No SMS messages have been sent yet.', 'maneli-car-inquiry'),
            'date_time' => esc_html__('Date & Time', 'maneli-car-inquiry'),
            'sent' => esc_html__('Sent', 'maneli-car-inquiry'),
            'security_verification_failed' => esc_html__('Security verification failed. Please refresh the page and try again.', 'maneli-car-inquiry'),
            'unauthorized_access' => esc_html__('Unauthorized access.', 'maneli-car-inquiry'),
        ]
    ];
    
    // Add AJAX nonce for SMS
    $localize_data['nonces']['ajax'] = wp_create_nonce('maneli-ajax-nonce');
    
    // Update nonces based on page type (cash vs installment, including followups)
    if ($page === 'cash-inquiries' || $page === 'cash-followups' || ($page === 'inquiries' && $subpage === 'cash')) {
        // Cash inquiries and followups
        $localize_data['nonces']['cash_filter'] = wp_create_nonce('maneli_cash_inquiry_filter_nonce');
        $localize_data['nonces']['cash_details'] = wp_create_nonce('maneli_cash_inquiry_details_nonce');
        $localize_data['nonces']['cash_update'] = wp_create_nonce('maneli_cash_inquiry_update_nonce');
        $localize_data['nonces']['cash_assign_expert'] = wp_create_nonce('maneli_cash_inquiry_assign_expert_nonce');
        $localize_data['nonces']['save_expert_note'] = wp_create_nonce('maneli_save_expert_note');
        $localize_data['nonces']['update_cash_status'] = wp_create_nonce('maneli_update_cash_status');
    } else if ($page === 'installment-inquiries' || $page === 'installment-followups' || ($page === 'inquiries' && $subpage === 'installment')) {
        // Installment inquiries and followups
        $localize_data['nonces']['inquiry_filter'] = wp_create_nonce('maneli_inquiry_filter_nonce');
        $localize_data['nonces']['details'] = wp_create_nonce('maneli_inquiry_details_nonce');
        $localize_data['nonces']['assign_expert'] = wp_create_nonce('maneli_inquiry_assign_expert_nonce');
        $localize_data['nonces']['tracking_status'] = wp_create_nonce('maneli_tracking_status_nonce');
        $localize_data['nonces']['save_installment_note'] = wp_create_nonce('maneli_installment_note');
        $localize_data['nonces']['update_installment_status'] = wp_create_nonce('maneli_installment_status');
    }
    
    // Add cash rejection reasons if we're on cash inquiries page
    if ($page === 'cash-inquiries' || $page === 'cash-followups' || ($page === 'inquiries' && $subpage === 'cash')) {
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
        $select_car_nonce = wp_create_nonce('maneli_ajax_nonce');
        $inquiry_form_localize = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => [
                'confirm_catalog' => wp_create_nonce('maneli_confirm_car_catalog_nonce'),
                'select_car' => $select_car_nonce,
            ],
            'texts' => [
                'replace_car_confirm' => esc_html__('Are you sure you want to replace the current car with this one?', 'maneli-car-inquiry'),
                'car_replaced_success' => esc_html__('Car replaced successfully. Page is being refreshed...', 'maneli-car-inquiry'),
                'error_replacing_car' => esc_html__('Error replacing car', 'maneli-car-inquiry'),
                'server_error' => esc_html__('Server connection error. Please try again.', 'maneli-car-inquiry'),
                'confirm' => esc_html__('Yes', 'maneli-car-inquiry'),
                'cancel' => esc_html__('Cancel', 'maneli-car-inquiry'),
                'invalid_request' => esc_html__('Invalid security token. Please refresh the page and try again.', 'maneli-car-inquiry'),
                'please_login' => esc_html__('Please log in to continue.', 'maneli-car-inquiry'),
                'product_id_required' => esc_html__('Product ID is required.', 'maneli-car-inquiry'),
            ],
        ];
        $scripts_html .= 'window.maneliInquiryForm=' . json_encode($inquiry_form_localize, JSON_UNESCAPED_UNICODE) . ';';
    }
    
    $scripts_html .= 'console.log("✅ Inquiry scripts loaded");</script>' . PHP_EOL;
    
    // Simple wizard for new-inquiry
    if ($page === 'new-inquiry') {
        $scripts_html .= '
<script>
(function() {
    function waitForjQuery() {
        if (typeof jQuery !== "undefined" || typeof window.jQuery !== "undefined") {
            var $ = jQuery || window.jQuery;
            $(document).ready(function() {
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
        } else {
            setTimeout(waitForjQuery, 50);
        }
    }
    waitForjQuery();
})();
</script>
<style>
.wizard-container .wizard-step { display: none; }
.wizard-container .wizard-step.active { display: block; }
</style>
';
    }
    $dashboard_html = str_replace('</body>', $scripts_html . '</body>', $dashboard_html);
}

// Add global search styles
$global_search_styles = '<style id="maneli-global-search-styles">
.global-search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 500px;
    overflow-y: auto;
    z-index: 1050;
    margin-top: 5px;
}

[data-theme-mode=dark] .global-search-dropdown {
    background: rgb(25, 25, 28);
    border-color: rgba(255, 255, 255, 0.1);
}

.search-section {
    border-bottom: 1px solid #e9ecef;
    padding: 8px 0;
}

.search-section:last-child {
    border-bottom: none;
}

.search-section-title {
    font-weight: 600;
    font-size: 13px;
    color: #6c757d;
    padding: 8px 15px 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

[data-theme-mode=dark] .search-section-title {
    color: rgba(255, 255, 255, 0.7);
}

.search-section-items {
    padding: 0;
}

.search-result-item {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-item:hover {
    background-color: #f8f9fa;
    text-decoration: none;
    color: inherit;
}

[data-theme-mode=dark] .search-result-item {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

[data-theme-mode=dark] .search-result-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
}

.search-result-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.search-result-title {
    font-weight: 500;
    font-size: 14px;
    color: #212529;
}

[data-theme-mode=dark] .search-result-title {
    color: rgba(255, 255, 255, 0.9);
}

.search-result-meta {
    font-size: 12px;
    color: #6c757d;
}

[data-theme-mode=dark] .search-result-meta {
    color: rgba(255, 255, 255, 0.5);
}
</style>';

$dashboard_html = str_replace('</head>', $global_search_styles . PHP_EOL . '</head>', $dashboard_html);

// CRITICAL: Add inline CSS at the end to force dark mode text colors - Highest priority
$dark_mode_text_fix = '<style id="maneli-dark-mode-text-force">
/* Force all text colors in dark mode - Highest priority inline styles */
[data-theme-mode=dark] {
    --default-text-color: rgba(255, 255, 255, 0.9) !important;
    --text-muted: rgba(255, 255, 255, 0.5) !important;
}

[data-theme-mode=dark] * {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] body,
[data-theme-mode=dark] body div,
[data-theme-mode=dark] body p,
[data-theme-mode=dark] body span,
[data-theme-mode=dark] body h1,
[data-theme-mode=dark] body h2,
[data-theme-mode=dark] body h3,
[data-theme-mode=dark] body h4,
[data-theme-mode=dark] body h5,
[data-theme-mode=dark] body h6,
[data-theme-mode=dark] body td,
[data-theme-mode=dark] body th,
[data-theme-mode=dark] body li,
[data-theme-mode=dark] body label,
[data-theme-mode=dark] body strong,
[data-theme-mode=dark] body em,
[data-theme-mode=dark] body b,
[data-theme-mode=dark] body section,
[data-theme-mode=dark] body article,
[data-theme-mode=dark] body main,
[data-theme-mode=dark] body header,
[data-theme-mode=dark] body footer,
[data-theme-mode=dark] body nav,
[data-theme-mode=dark] body ul,
[data-theme-mode=dark] body ol {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme-mode=dark] button,
[data-theme-mode=dark] .btn,
[data-theme-mode=dark] input,
[data-theme-mode=dark] textarea,
[data-theme-mode=dark] select,
[data-theme-mode=dark] a,
[data-theme-mode=dark] .badge,
[data-theme-mode=dark] i,
[data-theme-mode=dark] svg,
[data-theme-mode=dark] .text-primary,
[data-theme-mode=dark] .text-success,
[data-theme-mode=dark] .text-warning,
[data-theme-mode=dark] .text-danger,
[data-theme-mode=dark] .text-info,
[data-theme-mode=dark] .text-secondary,
[data-theme-mode=dark] .text-muted {
    color: inherit !important;
}

[data-theme-mode=dark] .text-muted,
[data-theme-mode=dark] small,
[data-theme-mode=dark] .small {
    color: rgba(255, 255, 255, 0.5) !important;
}

[data-theme-mode=dark] .text-primary {
    color: var(--primary-color) !important;
}

[data-theme-mode=dark] .text-success {
    color: rgb(var(--success-rgb)) !important;
}

[data-theme-mode=dark] .text-warning {
    color: rgb(var(--warning-rgb)) !important;
}

[data-theme-mode=dark] .text-danger {
    color: rgb(var(--danger-rgb)) !important;
}

[data-theme-mode=dark] .text-info {
    color: rgb(var(--info-rgb)) !important;
}

[data-theme-mode=dark] .text-secondary {
    color: rgb(var(--secondary-rgb)) !important;
}
</style>';

// Insert before closing body tag - this ensures it loads last and has highest priority
$dashboard_html = str_replace('</body>', $dark_mode_text_fix . PHP_EOL . '</body>', $dashboard_html);

// CRITICAL: JavaScript fallback to force text colors directly via DOM manipulation
$dark_mode_js_fix = '<script>
(function() {
    function forceDarkModeTextColors() {
        if (document.documentElement.getAttribute("data-theme-mode") !== "dark") {
            return;
        }
        
        // Get all text elements (excluding buttons, inputs, etc.)
        var textElements = document.querySelectorAll("body *");
        
        textElements.forEach(function(el) {
            // Skip interactive elements and elements with specific classes
            if (el.tagName === "BUTTON" || 
                el.tagName === "INPUT" || 
                el.tagName === "TEXTAREA" || 
                el.tagName === "SELECT" || 
                el.tagName === "A" || 
                el.tagName === "I" || 
                el.tagName === "SVG" ||
                el.tagName === "PATH" ||
                el.tagName === "CIRCLE" ||
                el.classList.contains("badge") ||
                el.classList.contains("btn") ||
                el.classList.contains("text-primary") ||
                el.classList.contains("text-success") ||
                el.classList.contains("text-warning") ||
                el.classList.contains("text-danger") ||
                el.classList.contains("text-info") ||
                el.classList.contains("text-secondary") ||
                el.classList.contains("text-muted")) {
                return;
            }
            
            // Force white text color
            if (window.getComputedStyle(el).color !== "rgb(229, 229, 229)" && 
                window.getComputedStyle(el).color !== "rgba(255, 255, 255, 0.9)") {
                el.style.setProperty("color", "rgba(255, 255, 255, 0.9)", "important");
            }
        });
        
        // Handle text-muted separately
        var mutedElements = document.querySelectorAll(".text-muted, small, .small");
        mutedElements.forEach(function(el) {
            el.style.setProperty("color", "rgba(255, 255, 255, 0.5)", "important");
        });
    }
    
    // Run immediately
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(forceDarkModeTextColors, 100);
        });
    } else {
        setTimeout(forceDarkModeTextColors, 100);
    }
    
    // Also run after window load
    window.addEventListener("load", function() {
        setTimeout(forceDarkModeTextColors, 200);
    });
    
    // Watch for theme changes
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === "attributes" && mutation.attributeName === "data-theme-mode") {
                setTimeout(forceDarkModeTextColors, 50);
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["data-theme-mode"]
    });
})();
</script>';

$dashboard_html = str_replace('</body>', $dark_mode_js_fix . PHP_EOL . '</body>', $dashboard_html);

echo $dashboard_html;
?>