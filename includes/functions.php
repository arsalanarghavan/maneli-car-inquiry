<?php
/**
 * General helper functions for the Maneli Car Inquiry plugin.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.1 (Added maneli_get_template_part)
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('maneli_gregorian_to_jalali')) {
    /**
     * Converts a Gregorian date to a Jalali (Persian/Shamsi) date.
     *
     * @param int|string $gy     Gregorian year (e.g., 2023).
     * @param int|string $gm     Gregorian month (e.g., 10).
     * @param int|string $gd     Gregorian day (e.g., 26).
     * @param string     $format The desired output format (e.g., 'Y/m/d').
     * @return string The formatted Jalali date.
     */
    function maneli_gregorian_to_jalali($gy, $gm, $gd, $format = 'Y/m/d', $convert_digits = true) {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy = (int)$gy;
        $gm = (int)$gm;
        $gd = (int)$gd;

        $jy = ($gy <= 1600) ? 0 : 979;
        $gy -= ($gy <= 1600) ? 621 : 1600;
        
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) - 80 + $gd + $g_d_m[$gm - 1];
        
        $jy += 33 * (int)($days / 12053);
        $days %= 12053;
        
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));

        $formatted_date = str_replace(
            ['Y', 'm', 'd'],
            [$jy, sprintf('%02d', $jm), sprintf('%02d', $jd)],
            $format
        );

        if ($convert_digits) {
            if (function_exists('persian_numbers_no_separator')) {
                return persian_numbers_no_separator($formatted_date);
            }

            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            return str_replace($english_digits, $persian_digits, (string)$formatted_date);
        }

        return $formatted_date;
    }
}

if (!function_exists('maneli_convert_to_english_digits')) {
    /**
     * Convert Persian/Arabic digits within a string to English digits.
     *
     * @param string $value Input string.
     * @return string
     */
    function maneli_convert_to_english_digits($value) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $value = str_replace($persian, $english, (string) $value);
        $value = str_replace($arabic, $english, $value);
        return $value;
    }
}

if (!function_exists('maneli_normalize_jalali_date')) {
    /**
     * Normalize a Jalali date string (convert digits to English and ensure 4/2/2 format).
     *
     * @param string $value Jalali date string (possibly with Persian digits).
     * @return string|null Normalized date in Y/m/d format or null if invalid.
     */
    function maneli_normalize_jalali_date($value) {
        if (!is_string($value) || $value === '') {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(['-', '.'], '/', $value);
        $value = maneli_convert_to_english_digits($value);

        if (!preg_match('/^(\d{3,4})\/(\d{1,2})\/(\d{1,2})$/', $value, $matches)) {
            return null;
        }

        $year  = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $day   = str_pad($matches[3], 2, '0', STR_PAD_LEFT);

        // Basic validation
        if ((int) $month < 1 || (int) $month > 12 || (int) $day < 1 || (int) $day > 31) {
            return null;
        }

        return $year . '/' . $month . '/' . $day;
    }
}

if (!function_exists('maneli_get_current_url')) {
    /**
     * Gets the current page URL while preserving specific query parameters.
     * This is used for generating proper links within shortcodes.
     *
     * @param array $remove_params Optional. Array of query parameter names to remove from the URL.
     * @param array $preserve_params Optional. Array of query parameter names to always preserve (e.g., 'endp').
     * @return string The current URL with query parameters.
     */
    function maneli_get_current_url($remove_params = [], $preserve_params = ['endp']) {
        global $wp;
        
        // Get the base URL (without query string)
        // Make sure we only get the path, not the query string
        $path = $wp->request;
        // Remove any query string if it exists
        if (strpos($path, '?') !== false) {
            $path = substr($path, 0, strpos($path, '?'));
        }
        $base_url = home_url($path);
        
        // Build query parameters array
        $query_params = [];
        
        // Process all $_GET parameters
        foreach ($_GET as $key => $value) {
            // Skip if in remove list
            if (in_array($key, $remove_params)) {
                continue;
            }
            
            // Add parameter (preserve_params are automatically included if not in remove_params)
            $query_params[$key] = sanitize_text_field($value);
        }
        
        // Build the URL with preserved parameters
        if (!empty($query_params)) {
            return add_query_arg($query_params, $base_url);
        }
        
        return $base_url;
    }
}

if (!function_exists('maneli_get_template_part')) {
    /**
     * Includes a template file from the plugin's templates directory.
     * * @param string $template_name The template file name (e.g., 'shortcodes/inquiry-form/step-1-car-selection').
     * @param array $args Array of arguments to pass to the template file.
     * @param bool $echo Whether to echo the template output or return it as a string.
     * @return string|void
     */
    function maneli_get_template_part($template_name, $args = [], $echo = true) {
        // فرض بر تعریف MANELI_INQUIRY_PLUGIN_PATH است
        $template_file = MANELI_INQUIRY_PLUGIN_PATH . 'templates/' . $template_name . '.php';

        if (!file_exists($template_file)) {
            // گزارش خطا در صورت عدم وجود تمپلیت
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Maneli Template Error: Template file not found: ' . $template_file);
            }
            return '';
        }

        // در دسترس قرار دادن آرگومان‌ها به عنوان متغیر در محدوده تمپلیت
        if (is_array($args) && !empty($args)) {
            extract($args, EXTR_SKIP);
        }

        if ($echo) {
            include $template_file;
        } else {
            ob_start();
            include $template_file;
            return ob_get_clean();
        }
    }
}

if (!function_exists('maneli_number_format_persian')) {
    /**
     * Format number with Persian digits and thousand separators
     * 
     * @param int|float $number The number to format
     * @param int $decimals Number of decimal places
     * @return string Formatted number with Persian digits
     */
    function maneli_number_format_persian($number, $decimals = 0) {
        if (!is_numeric($number)) {
            return '۰';
        }
        
        $formatted = number_format((float)$number, $decimals, '.', ',');
        
        // Convert English digits to Persian
        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        // Convert digits to Persian
        $result = str_replace($english_digits, $persian_digits, $formatted);
        
        return $result;
    }
}

// Global helper function to convert any string with numbers to Persian
if (!function_exists('persian_numbers')) {
    /**
     * Convert English digits in a string to Persian digits
     * 
     * @param string|int|float $str The string or number to convert
     * @return string String with Persian digits
     */
    function persian_numbers($str) {
        // Use maneli_number_format_persian if it exists and input is numeric
        if (function_exists('maneli_number_format_persian') && is_numeric($str)) {
            return maneli_number_format_persian($str);
        }
        
        // Otherwise just convert digits
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $result = str_replace($english, $persian, (string)$str);
        
        return $result;
    }
}

// Global helper function to convert numbers to Persian without separators
if (!function_exists('persian_numbers_no_separator')) {
    /**
     * Convert English digits in a string to Persian digits (no thousand separators)
     * 
     * @param string|int|float $str The string or number to convert
     * @return string String with Persian digits
     */
    function persian_numbers_no_separator($str) {
        // Convert digits to Persian
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, (string)$str);
    }
}