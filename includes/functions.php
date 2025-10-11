<?php
/**
 * General helper functions for the Maneli Car Inquiry plugin.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
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