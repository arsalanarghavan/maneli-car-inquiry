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
    function maneli_gregorian_to_jalali($gy, $gm, $gd, $format = 'Y/m/d') {
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

        return $formatted_date;
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
            error_log('Maneli Template Error: Template file not found: ' . $template_file);
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