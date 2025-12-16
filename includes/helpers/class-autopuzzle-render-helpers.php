<?php
/**
 * Helper class for rendering various HTML elements and data in AutoPuzzle Car Inquiry plugin.
 *
 * This class centralizes all rendering, formatting, and loan calculation logic, 
 * ensuring consistency and fixing the missing function dependencies.
 *
 * @package Autopuzzle_Car_Inquiry/Includes/Helpers
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Comprehensive Frontend Helper)
 */

if (!defined('ABSPATH')) {
    exit;
}

// توجه: فضای نام (Namespace) حذف شد تا با سایر کلاس‌های پلاگین که از فضای نام استفاده نمی‌کنند، سازگار باشد.
// توابع autopuzzle_get_template_part و autopuzzle_gregorian_to_jalali از طریق includes/functions.php در دسترس هستند.
// کلاس‌های Autopuzzle_CPT_Handler، WP_User، WC_Product و غیره باید قبل از استفاده بارگذاری شده باشند.

class Autopuzzle_Render_Helpers {

    /**
     * Calculates the monthly installment amount using the formula based on the configured monthly rate.
     * * Formula: M = [ P * r * (1 + r)^n ] / [ (1 + r)^n - 1 ]
     * The plugin uses a simplified formula for interest calculation:
     * Total_Repayment = Loan_Amount + (Loan_Amount * Monthly_Rate * (Months + 1))
     * Installment = Total_Repayment / Months
     *
     * @param int $loan_amount The total loan amount (Total Price - Down Payment).
     * @param int $term_months The repayment term in months.
     * @return int The calculated monthly installment amount.
     */
    public static function calculate_installment_amount($loan_amount, $term_months) {
        if ($loan_amount <= 0 || $term_months <= 0) {
            return 0;
        }

        // نرخ سود ماهانه را از تنظیمات می‌خواند
        $monthly_rate = floatval(Autopuzzle_Options_Helper::get_option('loan_interest_rate', 0.035)); 
        
        // منطق محاسبه ساده شده (مطابق با JS و منطق بک‌اند)
        $total_interest = $loan_amount * $monthly_rate * ($term_months + 1);
        $total_repayment = $loan_amount + $total_interest;
        $installment = $total_repayment / $term_months;

        return (int)round($installment);
    }

    /**
     * Formats an integer amount with thousand separators.
     * @param int $amount The amount to format.
     * @return string The formatted string.
     */
    public static function format_money($amount) {
        // Use autopuzzle_number_format_persian for proper Persian formatting with Persian comma
        if (function_exists('autopuzzle_number_format_persian')) {
            return autopuzzle_number_format_persian((int)$amount);
        }
        return number_format_i18n((int)$amount);
    }

    /**
     * Validates Iranian National Code (کد ملی)
     * Implements the Luhn-like algorithm for Iranian National Code validation
     * 
     * @param string $national_code The national code to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_national_code($national_code) {
        // Remove whitespace and convert to string
        $national_code = trim((string)$national_code);
        
        // Must be exactly 10 digits
        if (!preg_match('/^\d{10}$/', $national_code)) {
            return false;
        }
        
        // Check for all same digits (invalid codes like 0000000000, 1111111111)
        if (preg_match('/^(\d)\1{9}$/', $national_code)) {
            return false;
        }
        
        // Extract check digit (last digit)
        $check_digit = (int)$national_code[9];
        
        // Calculate checksum using Iranian National Code algorithm
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$national_code[$i] * (10 - $i);
        }
        
        $remainder = $sum % 11;
        
        // Check digit should be either remainder or 11 - remainder
        // If remainder < 2, check digit should equal remainder
        // If remainder >= 2, check digit should equal 11 - remainder
        if ($remainder < 2) {
            return $check_digit === $remainder;
        } else {
            return $check_digit === (11 - $remainder);
        }
    }
    
    /**
     * Validates Iranian mobile number
     * 
     * @param string $mobile Mobile number to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_mobile_number($mobile) {
        // Remove whitespace, dashes, and other characters
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        // Must start with 09 and be 11 digits total, or start with 9 and be 10 digits
        if (preg_match('/^09\d{9}$/', $mobile)) {
            return true;
        }
        if (preg_match('/^9\d{9}$/', $mobile)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validates input field length
     * 
     * @param string $value The value to validate
     * @param int $max_length Maximum allowed length
     * @param int $min_length Minimum allowed length (default 0)
     * @return array ['valid' => bool, 'error' => string|null] Validation result
     */
    public static function validate_input_length($value, $max_length, $min_length = 0) {
        $length = mb_strlen($value, 'UTF-8');
        
        if ($length < $min_length) {
            return [
                'valid' => false,
                'error' => sprintf(esc_html__('This field must be at least %d characters long.', 'autopuzzle'), $min_length)
            ];
        }
        
        if ($length > $max_length) {
            return [
                'valid' => false,
                'error' => sprintf(esc_html__('This field must not exceed %d characters.', 'autopuzzle'), $max_length)
            ];
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Validates name field (first name, last name)
     * 
     * @param string $name The name to validate
     * @return array ['valid' => bool, 'error' => string|null] Validation result
     */
    public static function validate_name_field($name) {
        // Name must be between 2 and 100 characters
        return self::validate_input_length($name, 100, 2);
    }
    
    /**
     * Validates address field
     * 
     * @param string $address The address to validate
     * @return array ['valid' => bool, 'error' => string|null] Validation result
     */
    public static function validate_address_field($address) {
        // Address must be between 10 and 500 characters
        return self::validate_input_length($address, 500, 10);
    }
    
    /**
     * Validates description/notes field
     * 
     * @param string $description The description to validate
     * @return array ['valid' => bool, 'error' => string|null] Validation result
     */
    public static function validate_description_field($description) {
        // Description must not exceed 2000 characters (no minimum)
        return self::validate_input_length($description, 2000, 0);
    }
    
    /**
     * Validates email field
     * 
     * @param string $email The email to validate
     * @return array ['valid' => bool, 'error' => string|null] Validation result
     */
    public static function validate_email_field($email) {
        // Check if empty
        if (empty($email)) {
            return [
                'valid' => false,
                'error' => esc_html__('Email is required.', 'autopuzzle')
            ];
        }
        
        // Check length (RFC 5321: max 254 characters for email address)
        $length = mb_strlen($email, 'UTF-8');
        if ($length > 254) {
            return [
                'valid' => false,
                'error' => esc_html__('Email address must not exceed 254 characters.', 'autopuzzle')
            ];
        }
        
        // Use WordPress built-in email validation
        if (!is_email($email)) {
            return [
                'valid' => false,
                'error' => esc_html__('Invalid email format.', 'autopuzzle')
            ];
        }
        
        // Additional regex validation for stricter format checking
        $email_pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        if (!preg_match($email_pattern, $email)) {
            return [
                'valid' => false,
                'error' => esc_html__('Invalid email format.', 'autopuzzle')
            ];
        }
        
        // Check domain validity using DNS lookup (if available)
        $domain = substr(strrchr($email, "@"), 1);
        if (!empty($domain) && function_exists('checkdnsrr')) {
            // Check MX record (preferred) or A record as fallback
            if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
                // DNS check failed - but don't block, just log in debug mode
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('AutoPuzzle Warning: Email domain DNS check failed for: ' . $domain);
                }
                // We don't fail validation for DNS issues, as DNS might be temporarily unavailable
            }
        }
        
        return ['valid' => true, 'error' => null];
    }
    
    /**
     * Converts Gregorian date to Jalali date. (Wrapper for global function)
     *
     * @param string $gregorian_date_time The date string.
     * @param string $format The output format.
     * @return string The formatted Jalali date.
     */
    public static function autopuzzle_gregorian_to_jalali($gregorian_date_time, $format = 'Y/m/d H:i') {
        $timestamp = strtotime($gregorian_date_time);
        if ($timestamp === false) {
            return (string) $gregorian_date_time;
        }

        $use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;

        if (function_exists('autopuzzle_gregorian_to_jalali')) {
            $base_date = autopuzzle_gregorian_to_jalali(
                date('Y', $timestamp),
                date('m', $timestamp),
                date('d', $timestamp),
                'Y/m/d'
            );

            $needs_time = strpos($format, 'H:i') !== false || strpos($format, 'H:i:s') !== false;

            if ($needs_time) {
                $time_format = strpos($format, 'H:i:s') !== false ? 'H:i:s' : 'H:i';
                $time = function_exists('wp_date') ? wp_date($time_format, $timestamp) : date($time_format, $timestamp);
                if ($use_persian_digits && function_exists('persian_numbers_no_separator')) {
                    $time = persian_numbers_no_separator($time);
                }
                return trim($base_date . ' ' . $time);
            }

            return $base_date;
        }

        return function_exists('wp_date') ? wp_date($format, $timestamp) : date($format, $timestamp);
    }

    /**
     * Translate field values to Persian
     * 
     * @param string $field_name The field name (job_type, residency_status, workplace_status)
     * @param string $value The English value
     * @return string The Persian translation
     */
    public static function translate_field_value($field_name, $value) {
        $translations = [
            'job_type' => [
                'self' => esc_html__('Freelance', 'autopuzzle'),
                'employee' => esc_html__('Employee', 'autopuzzle'),
            ],
            'residency_status' => [
                'owner' => esc_html__('Owner', 'autopuzzle'),
                'tenant' => esc_html__('Tenant', 'autopuzzle'),
            ],
            'workplace_status' => [
                'owned' => esc_html__('Owned', 'autopuzzle'),
                'rented' => esc_html__('Rented', 'autopuzzle'),
            ],
        ];
        
        return $translations[$field_name][$value] ?? $value;
    }
    
    /**
     * رندر نوار وضعیت رنگی چک صیادی و توضیحات مرتبط.
     * این تابع در گزارش‌های فرانت‌اند استفاده می‌شود.
     *
     * @param int $cheque_color_code کد رنگ (1=سفید، 5=قرمز، 0=نامشخص).
     * @return string خروجی HTML.
     */
    public static function render_cheque_status_info($cheque_color_code) {
        static $styles_printed = false;

        $palette = [
            1 => ['name' => esc_html__('White', 'autopuzzle'), 'class' => 'white'],
            2 => ['name' => esc_html__('Yellow', 'autopuzzle'), 'class' => 'yellow'],
            3 => ['name' => esc_html__('Orange', 'autopuzzle'), 'class' => 'orange'],
            4 => ['name' => esc_html__('Brown', 'autopuzzle'), 'class' => 'brown'],
            5 => ['name' => esc_html__('Red', 'autopuzzle'), 'class' => 'red'],
            0 => ['name' => esc_html__('Undetermined', 'autopuzzle'), 'class' => 'undetermined'],
        ];

        $cheque_color_map = [
            '1' => ['text' => esc_html__('White', 'autopuzzle'), 'desc' => esc_html__('No history of bounced cheques.', 'autopuzzle')],
            '2' => ['text' => esc_html__('Yellow', 'autopuzzle'), 'desc' => esc_html__('One bounced cheque or a maximum of 50 million Rials in returned commitments.', 'autopuzzle')],
            '3' => ['text' => esc_html__('Orange', 'autopuzzle'), 'desc' => esc_html__('Two to four bounced cheques or a maximum of 200 million Rials in returned commitments.', 'autopuzzle')],
            '4' => ['text' => esc_html__('Brown', 'autopuzzle'), 'desc' => esc_html__('Five to ten bounced cheques or a maximum of 500 million Rials in returned commitments.', 'autopuzzle')],
            '5' => ['text' => esc_html__('Red', 'autopuzzle'), 'desc' => esc_html__('More than ten bounced cheques or more than 500 million Rials in returned commitments.', 'autopuzzle')],
            '0' => ['text' => esc_html__('Undetermined', 'autopuzzle'), 'desc' => esc_html__('Information was not received from Finotex or the inquiry was unsuccessful.', 'autopuzzle')],
        ];

        $cheque_color_code = (string) ($cheque_color_code ?? '0');
        if (!isset($cheque_color_map[$cheque_color_code])) {
            $cheque_color_code = '0';
        }

        $is_fa_locale = function_exists('get_locale') ? (get_locale() === 'fa_IR') : false;
        if ($is_fa_locale) {
            if (function_exists('persian_numbers')) {
                foreach ($cheque_color_map as &$info) {
                    $info['text'] = persian_numbers($info['text']);
                    $info['desc'] = persian_numbers($info['desc']);
                }
                unset($info);
            } elseif (function_exists('persian_numbers_no_separator')) {
                foreach ($cheque_color_map as &$info) {
                    $info['text'] = persian_numbers_no_separator($info['text']);
                    $info['desc'] = persian_numbers_no_separator($info['desc']);
                }
                unset($info);
            }
        }

        $active_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map['0'];
        $active_palette = $palette[(int) $cheque_color_code] ?? $palette[0];
        $status_class = 'status-color-' . $active_palette['class'];

        $output = '';
        if (!$styles_printed) {
            $styles_printed = true;
            $output .= '<style>
                .autopuzzle-cheque-status-card {border:1px solid rgba(15,23,42,0.08);border-radius:18px;padding:22px;background:var(--bs-card-bg,#fff);}
                .autopuzzle-cheque-status-main {display:flex;align-items:center;gap:1.25rem;padding:20px;border-radius:16px;position:relative;overflow:hidden;color:#0f172a;}
                .autopuzzle-cheque-status-main .status-icon {width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;background:rgba(255,255,255,0.35);box-shadow:0 10px 25px rgba(15,23,42,0.08);}
                .autopuzzle-cheque-status-main .status-body {flex:1;min-width:0;}
                .autopuzzle-cheque-status-main .status-title {margin-bottom:.4rem;font-size:1rem;font-weight:600;}
                .autopuzzle-cheque-status-main .status-chip {display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:999px;font-weight:600;background:rgba(255,255,255,0.35);backdrop-filter:blur(4px);box-shadow:0 5px 18px rgba(15,23,42,0.12);}
                .autopuzzle-cheque-status-main .status-desc {margin-top:.75rem;color:rgba(15,23,42,0.75);font-size:.9rem;line-height:1.65;}
                .autopuzzle-cheque-status-legend {display:flex;flex-wrap:wrap;gap:.6rem;margin-top:1.5rem;}
                .autopuzzle-cheque-chip {display:inline-flex;align-items:center;gap:.45rem;padding:.42rem .95rem;border-radius:999px;font-size:.85rem;font-weight:500;border:1px solid transparent;transition:all .2s ease;}
                .autopuzzle-cheque-chip .chip-dot {width:10px;height:10px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px rgba(15,23,42,0.06);}
                .autopuzzle-cheque-chip.active {transform:translateY(-2px);box-shadow:0 10px 22px rgba(15,23,42,0.14);border-color:currentColor;background:#fff;}
                .autopuzzle-cheque-chip.chip-white {color:#475569;background:linear-gradient(135deg,#f8fafc,#fefefe);}
                .autopuzzle-cheque-chip.chip-yellow {color:#b7791f;background:linear-gradient(135deg,#fff7d6,#fde68a);}
                .autopuzzle-cheque-chip.chip-orange {color:#c2410c;background:linear-gradient(135deg,#ffe0c2,#fed7aa);}
                .autopuzzle-cheque-chip.chip-brown {color:#92400e;background:linear-gradient(135deg,#f5d7b8,#f3c98b);}
                .autopuzzle-cheque-chip.chip-red {color:#b91c1c;background:linear-gradient(135deg,#fecaca,#f87171);}
                .autopuzzle-cheque-chip.chip-undetermined {color:#475569;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);}
                .autopuzzle-cheque-status-main.status-color-white {background:linear-gradient(135deg,#f8fafc,#ffffff);color:#1f2937;}
                .autopuzzle-cheque-status-main.status-color-yellow {background:linear-gradient(135deg,#fffbeb,#fef08a);color:#854d0e;}
                .autopuzzle-cheque-status-main.status-color-orange {background:linear-gradient(135deg,#fff7ed,#fed7aa);color:#9a3412;}
                .autopuzzle-cheque-status-main.status-color-brown {background:linear-gradient(135deg,#f9eadf,#f3c999);color:#78350f;}
                .autopuzzle-cheque-status-main.status-color-red {background:linear-gradient(135deg,#fee2e2,#fca5a5);color:#7f1d1d;}
                .autopuzzle-cheque-status-main.status-color-undetermined {background:linear-gradient(135deg,#eef2f7,#cbd5f5);color:#1f2937;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-card {border-color:rgba(148,163,184,0.25);background:rgba(15,23,42,0.85);}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main {color:#e2e8f0;box-shadow:0 10px 30px rgba(2,6,23,0.35);}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main .status-icon {background:rgba(15,23,42,0.65);box-shadow:0 10px 25px rgba(2,6,23,0.45);color:#f8fafc;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main .status-chip {background:rgba(15,23,42,0.55);color:#e2e8f0;box-shadow:0 5px 18px rgba(2,6,23,0.45);}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main .status-desc {color:rgba(226,232,240,0.78);}
                [data-theme-mode=dark] .autopuzzle-cheque-status-legend {gap:.75rem;}
                [data-theme-mode=dark] .autopuzzle-cheque-chip {background:rgba(15,23,42,0.65);color:#cbd5f5;border-color:rgba(148,163,184,0.35);box-shadow:none;}
                [data-theme-mode=dark] .autopuzzle-cheque-chip .chip-dot {box-shadow:0 0 0 3px rgba(15,23,42,0.35);}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.active {transform:translateY(-2px);box-shadow:0 10px 22px rgba(2,6,23,0.55);background:rgba(15,23,42,0.8);}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.chip-white {color:#e2e8f0;background:linear-gradient(135deg,rgba(148,163,184,0.15),rgba(30,41,59,0.25));}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.chip-yellow {color:#fde68a;background:linear-gradient(135deg,rgba(234,179,8,0.22),rgba(133,77,14,0.24));}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.chip-orange {color:#fdba74;background:linear-gradient(135deg,rgba(251,146,60,0.22),rgba(194,65,12,0.26));}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.chip-brown {color:#facc15;background:linear-gradient(135deg,rgba(214,158,46,0.25),rgba(120,53,15,0.3));}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.chip-red {color:#fecaca;background:linear-gradient(135deg,rgba(248,113,113,0.22),rgba(185,28,28,0.3));}
                [data-theme-mode=dark] .autopuzzle-cheque-chip.chip-undetermined {color:#cbd5f5;background:linear-gradient(135deg,rgba(148,163,184,0.22),rgba(30,41,59,0.28));}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main.status-color-white {background:linear-gradient(135deg,#1e293b,#0f172a);color:#f8fafc;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main.status-color-yellow {background:linear-gradient(135deg,#422006,#7c2d12);color:#fde68a;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main.status-color-orange {background:linear-gradient(135deg,#431407,#7c2d12);color:#fdba74;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main.status-color-brown {background:linear-gradient(135deg,#3f2510,#78350f);color:#facc15;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main.status-color-red {background:linear-gradient(135deg,#450a0a,#991b1b);color:#fecaca;}
                [data-theme-mode=dark] .autopuzzle-cheque-status-main.status-color-undetermined {background:linear-gradient(135deg,#1e293b,#334155);color:#cbd5f5;}
                @media (max-width: 576px){.autopuzzle-cheque-status-main{flex-direction:column;text-align:center;}.autopuzzle-cheque-status-main .status-icon{margin-bottom:.75rem;}}
            </style>';
        }

        $output .= '<div class="autopuzzle-cheque-status-card">';
        $output .= '<div class="autopuzzle-cheque-status-main ' . esc_attr($status_class) . '">';
        $output .= '<div class="status-icon"><i class="la la-shield-alt"></i></div>';
        $output .= '<div class="status-body">';
        $output .= '<div class="status-title">' . esc_html__('Sadad Cheque Status Inquiry', 'autopuzzle') . '</div>';
        $output .= '<div class="status-chip"><span class="chip-dot"></span><span>' . esc_html($active_info['text']) . '</span></div>';
        $output .= '<p class="status-desc">' . esc_html($active_info['desc']) . '</p>';
        $output .= '</div>';
        $output .= '</div>';

        $output .= '<div class="autopuzzle-cheque-status-legend">';
        foreach ([1, 2, 3, 4, 5] as $code) {
            $info = $palette[$code];
            $output .= '<div class="autopuzzle-cheque-chip chip-' . esc_attr($info['class']) . ' ' . ($cheque_color_code === (string)$code ? 'active' : '') . '"><span class="chip-dot"></span><span>' . esc_html($info['name']) . '</span></div>';
        }
        $output .= '<div class="autopuzzle-cheque-chip chip-undetermined ' . ($cheque_color_code === '0' ? 'active' : '') . '"><span class="chip-dot"></span><span>' . esc_html($palette[0]['name']) . '</span></div>';
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }

    /**
     * رندر یک ردیف برای جدول ویرایش محصولات (شورت‌کد فرانت‌اند).
     *
     * @param WC_Product $product شیء محصول.
     */
    public static function render_product_editor_row($product) {
        if (!($product instanceof WC_Product)) return;
        
        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $use_persian_digits = function_exists('autopuzzle_should_use_persian_digits') ? autopuzzle_should_use_persian_digits() : true;
        
        $product_id = $product->get_id();
        $installment_price = get_post_meta($product_id, 'installment_price', true);
        $min_downpayment = get_post_meta($product_id, 'min_downpayment', true);
        $car_colors = get_post_meta($product_id, '_maneli_car_colors', true);
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        $manufacture_year = get_post_meta($product_id, '_maneli_car_year', true);
        $manufacture_year_raw = is_scalar($manufacture_year) ? trim((string) $manufacture_year) : '';
        $manufacture_year_clean = '';
        if ($manufacture_year_raw !== '') {
            $normalized_year = str_replace($persian_digits, $english_digits, $manufacture_year_raw);
            $manufacture_year_clean = preg_replace('/[^0-9]/', '', $normalized_year);
        }
        $manufacture_year_display = '';
        if ($manufacture_year_clean !== '') {
            $manufacture_year_display = $use_persian_digits
                ? str_replace($english_digits, $persian_digits, $manufacture_year_clean)
                : $manufacture_year_clean;
        }
        
        // Get product categories
        $categories = wc_get_product_category_list($product_id, ', ', '', '');
        if (empty($categories)) {
            $categories = '-';
        }
        
        // Get product image
        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
        if (!$image_url) {
            $image_url = wc_placeholder_img_src('thumbnail');
        }
        
        // Status labels in Persian
        $statuses = [
            'special_sale' => ['label' => esc_html__('Special Sale', 'autopuzzle'), 'class' => 'primary-transparent'],
            'unavailable'  => ['label' => esc_html__('Unavailable', 'autopuzzle'), 'class' => 'warning-transparent'],
            'disabled'     => ['label' => esc_html__('Disabled', 'autopuzzle'), 'class' => 'danger-transparent'],
        ];
        $current_status = $statuses[$car_status] ?? $statuses['special_sale'];
        
        // Format prices for display (Persian numbers with thousand separators)
        $regular_price = $product->get_regular_price();
        $regular_price_numeric = $regular_price !== '' ? str_replace($persian_digits, $english_digits, (string) $regular_price) : '';
        $installment_price_numeric = $installment_price !== '' ? str_replace($persian_digits, $english_digits, (string) $installment_price) : '';
        $min_downpayment_numeric = $min_downpayment !== '' ? str_replace($persian_digits, $english_digits, (string) $min_downpayment) : '';

        $format_display_number = function($value) use ($use_persian_digits) {
            if ($value === '' || $value === null) {
                return '';
            }
            if ($use_persian_digits && function_exists('autopuzzle_number_format_persian')) {
                return autopuzzle_number_format_persian($value, 0);
            }
            return number_format((float) str_replace(',', '', (string) $value), 0, '.', ',');
        };

        $regular_price_formatted = $format_display_number($regular_price_numeric);
        $installment_price_formatted = $format_display_number($installment_price_numeric);
        $min_downpayment_formatted = $format_display_number($min_downpayment_numeric);

        $product_name = $product->get_name();
        $product_name_display = $use_persian_digits
            ? str_replace($english_digits, $persian_digits, $product_name)
            : str_replace($persian_digits, $english_digits, $product_name);
        ?>
        <tr class="product-list">
            <td>
                <div class="d-flex">
                    <span class="avatar avatar-md avatar-square bg-light me-2">
                        <img src="<?php echo esc_url($image_url); ?>" class="w-100 h-100" alt="<?php echo esc_attr($product->get_name()); ?>">
                    </span>
                    <div>
                        <p class="fw-semibold mb-0 d-flex align-items-center">
                            <a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank" class="text-primary">
                                <?php echo esc_html($product_name_display); ?>
                            </a>
                            <?php if ($manufacture_year_display !== '') : ?>
                                <span class="badge autopuzzle-year-badge ms-2"><?php echo esc_html($manufacture_year_display); ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </td>
            <td>
                <span class="text-muted"><?php echo wp_kses_post($categories); ?></span>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input manli-price-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="regular_price" 
                           data-raw-value="<?php echo esc_attr($regular_price_numeric); ?>"
                           value="<?php echo esc_attr($regular_price_formatted); ?>" 
                           placeholder="<?php echo esc_attr__('Cash Price', 'autopuzzle'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon autopuzzle-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input manli-price-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="installment_price" 
                           data-raw-value="<?php echo esc_attr($installment_price_numeric); ?>"
                           value="<?php echo esc_attr($installment_price_formatted); ?>" 
                           placeholder="<?php echo esc_attr__('Installment Price', 'autopuzzle'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon autopuzzle-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input manli-price-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="min_downpayment" 
                           data-raw-value="<?php echo esc_attr($min_downpayment_numeric); ?>"
                           value="<?php echo esc_attr($min_downpayment_formatted); ?>" 
                           placeholder="<?php echo esc_attr__('Minimum Down Payment', 'autopuzzle'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon autopuzzle-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="car_colors" 
                           value="<?php echo esc_attr($car_colors); ?>" 
                           placeholder="<?php echo esc_attr__('Colors (e.g., White, Black, Silver)', 'autopuzzle'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon autopuzzle-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <select class="manli-data-input form-select form-select-sm flex-grow-1" 
                            data-product-id="<?php echo esc_attr($product_id); ?>" 
                            data-field-type="car_status">
                        <?php foreach ($statuses as $key => $status_info) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($car_status, $key); ?>>
                                <?php echo esc_html($status_info['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="spinner"></span>
                    <span class="save-status-icon autopuzzle-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="hstack gap-2 fs-15">
                    <a href="<?php echo esc_url(home_url('/dashboard/add-product?edit_product=' . $product_id)); ?>" 
                       class="btn btn-icon btn-sm btn-primary-light" 
                       title="<?php esc_attr_e('Edit', 'autopuzzle'); ?>">
                        <i class="ri-edit-line"></i>
                    </a>
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>" 
                       class="btn btn-icon btn-sm btn-info-light" 
                       target="_blank" 
                       title="<?php esc_attr_e('View', 'autopuzzle'); ?>">
                        <i class="ri-eye-line"></i>
                    </a>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * رندر یک ردیف برای جدول لیست کاربران (شورت‌کد فرانت‌اند).
     *
     * @param WP_User $user شیء کاربر.
     * @param string $current_url آدرس پایه برای ساخت لینک‌های عملیاتی.
     */
    public static function render_user_list_row($user, $current_url) {
        if (!($user instanceof WP_User)) return;
        
        global $wp_roles;
        // مطمئن می‌شویم که نقش‌ها به زبان فارسی ترجمه شده‌اند.
        $role_names = array_map(function($role) use ($wp_roles) {
            return $wp_roles->roles[$role]['name'] ?? $role;
        }, (array) $user->roles);
        $role_display = esc_html(implode(', ', $role_names));
        $edit_url = esc_url(add_query_arg('edit_user', $user->ID, $current_url));
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('Display Name', 'autopuzzle'); ?>">
                <strong><?php echo esc_html($user->display_name); ?></strong>
            </td>
            <td data-title="<?php esc_attr_e('Username', 'autopuzzle'); ?>"><?php echo esc_html($user->user_login); ?></td>
            <td data-title="<?php esc_attr_e('Email', 'autopuzzle'); ?>"><?php echo esc_html($user->user_email); ?></td>
            <td data-title="<?php esc_attr_e('Role', 'autopuzzle'); ?>"><?php echo esc_html($role_display); ?></td>
            <td class="woocommerce-orders-table__cell-order-actions">
                <a href="<?php echo esc_url($edit_url); ?>" class="button view"><?php esc_html_e('Edit', 'autopuzzle'); ?></a>
                <button class="button delete-user-btn autopuzzle-btn-delete" data-user-id="<?php echo esc_attr($user->ID); ?>">
                    <?php esc_html_e('Delete', 'autopuzzle'); ?>
                </button>
            </td>
        </tr>
        <?php
    }
    
    /**
     * رندر یک ردیف برای جدول لیست استعلام اقساطی (شورت‌کد فرانت‌اند).
     *
     * @param int $inquiry_id شناسه پست استعلام.
     * @param string $base_url آدرس پایه برای لینک گزارش.
     */
    public static function render_inquiry_row($inquiry_id, $base_url, $show_followup_date = false) {
        $inquiry_post      = get_post($inquiry_id);
        if (!$inquiry_post) return;
        // از آنجایی که در حالت فرانت‌اند تنها هستیم، از توابع عمومی وردپرس استفاده می‌کنیم.
        $product_id        = get_post_meta( $inquiry_id, 'product_id', true );
        $inquiry_status    = get_post_meta( $inquiry_id, 'inquiry_status', true );
        $expert_status     = get_post_meta( $inquiry_id, 'expert_status', true );
        $customer          = get_userdata( $inquiry_post->post_author );
        $expert_name       = get_post_meta( $inquiry_id, 'assigned_expert_name', true );
        // Use correct dashboard URL for report link
        $report_url        = add_query_arg('inquiry_id', $inquiry_id, home_url('/dashboard/installment-inquiries'));
        // از کلاس CPT Handler برای گرفتن لیبل وضعیت استفاده می‌کنیم (باید بارگذاری شده باشد).
        $status_label      = Autopuzzle_CPT_Handler::get_status_label($inquiry_status); 
        $is_admin          = current_user_can( 'manage_autopuzzle_inquiries' );
        
        // Check if current user is assigned expert
        $is_assigned_expert = false;
        if (!$is_admin) {
            require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-permission-helpers.php';
            $is_assigned_expert = Autopuzzle_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        }
        
        // Get expert status info and tracking status
        $expert_status_info = self::get_expert_status_info($expert_status);
        $tracking_status = get_post_meta($inquiry_id, 'tracking_status', true);
        $follow_up_date = get_post_meta($inquiry_id, 'follow_up_date', true);
        
        // Check Finnotech API data availability
        $options = Autopuzzle_Options_Helper::get_all_options();
        $credit_risk_data = get_post_meta($inquiry_id, '_finnotech_credit_risk_data', true);
        $credit_score_data = get_post_meta($inquiry_id, '_finnotech_credit_score_data', true);
        $collaterals_data = get_post_meta($inquiry_id, '_finnotech_collaterals_data', true);
        $cheque_color_data = get_post_meta($inquiry_id, '_finnotech_cheque_color_data', true);
        
        $credit_risk_enabled = !empty($options['finnotech_credit_risk_enabled']) && $options['finnotech_credit_risk_enabled'] === '1';
        $credit_score_enabled = !empty($options['finnotech_credit_score_enabled']) && $options['finnotech_credit_score_enabled'] === '1';
        $collaterals_enabled = !empty($options['finnotech_collaterals_enabled']) && $options['finnotech_collaterals_enabled'] === '1';
        $cheque_color_enabled = !empty($options['finnotech_cheque_color_enabled']) && $options['finnotech_cheque_color_enabled'] === '1';
        
        $has_finnotech_data = false;
        $finnotech_indicators = [];
        if ($credit_risk_enabled && !empty($credit_risk_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-exclamation-circle text-danger" title="' . esc_attr__('Credit Risk Available', 'autopuzzle') . '"></i>';
        }
        if ($credit_score_enabled && !empty($credit_score_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-chart-line text-warning" title="' . esc_attr__('Credit Score Available', 'autopuzzle') . '"></i>';
        }
        if ($collaterals_enabled && !empty($collaterals_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-file-contract text-info" title="' . esc_attr__('Contracts Available', 'autopuzzle') . '"></i>';
        }
        if ($cheque_color_enabled && !empty($cheque_color_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-shield-alt text-primary" title="' . esc_attr__('Cheque Status Available', 'autopuzzle') . '"></i>';
        }
        
        // Get status badge color class
        $status_badge_class = 'bg-secondary-transparent';
        switch ($inquiry_status) {
            case 'pending':
                $status_badge_class = 'bg-warning-transparent';
                break;
            case 'user_confirmed':
            case 'approved':
                $status_badge_class = 'bg-success-transparent';
                break;
            case 'rejected':
            case 'failed':
                $status_badge_class = 'bg-danger-transparent';
                break;
            case 'more_docs':
                $status_badge_class = 'bg-info-transparent';
                break;
            default:
                $status_badge_class = 'bg-secondary-transparent';
        }
        
        // Helper function to convert numbers to Persian without separators
        if (!function_exists('persian_numbers_no_separator')) {
            function persian_numbers_no_separator($str) {
                $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                return str_replace($english, $persian, (string)$str);
            }
        }
        
        // Format date with Persian numbers
        $formatted_date = self::autopuzzle_gregorian_to_jalali($inquiry_post->post_date, 'Y/m/d');
        $formatted_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($formatted_date) : $formatted_date;
        ?>
        <tr class="crm-contact">
            <td data-title="<?php esc_attr_e('ID', 'autopuzzle'); ?>">#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'autopuzzle'); ?>"><?php echo esc_html($customer->display_name ?? '—'); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'autopuzzle'); ?>">
                <div class="d-flex align-items-center">
                    <?php echo esc_html(get_the_title($product_id)); ?>
                    <?php if ($has_finnotech_data && $is_admin): ?>
                        <span class="ms-2" style="font-size: 14px;" title="<?php esc_attr_e('Credit Information Available', 'autopuzzle'); ?>">
                            <?php echo implode(' ', $finnotech_indicators); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </td>
            <?php if ($show_followup_date): ?>
                <td data-title="<?php esc_attr_e('Follow-up Date', 'autopuzzle'); ?>">
                    <strong class="autopuzzle-text-danger"><?php echo esc_html($follow_up_date ? (function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($follow_up_date) : $follow_up_date) : '—'); ?></strong>
                </td>
            <?php endif; ?>
            <td class="inquiry-status-cell-installment" data-title="<?php esc_attr_e('Status', 'autopuzzle'); ?>">
                <?php
                // Determine latest/current status to display
                // Priority: tracking_status > expert_status > inquiry_status
                if ($tracking_status) {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html(Autopuzzle_CPT_Handler::get_tracking_status_label($tracking_status)) . '</span>';
                } elseif ($expert_status_info) {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html($expert_status_info['label']) . '</span>';
                } else {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html($status_label) . '</span>';
                }
                ?>
            </td>
            <?php if ($is_admin) : ?>
                <td data-title="<?php esc_attr_e('Assigned', 'autopuzzle'); ?>">
                    <?php if (!empty($expert_name)) : ?>
                        <span class="assigned-expert-name"><?php echo esc_html($expert_name); ?></span>
                    <?php else : ?>
                        <button class="btn btn-sm btn-info-light assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment" title="<?php esc_attr_e('Assign Expert', 'autopuzzle'); ?>">
                            <i class="la la-user-plus me-1"></i><?php esc_html_e('Assign', 'autopuzzle'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="<?php esc_attr_e('Date', 'autopuzzle'); ?>"><?php echo esc_html($formatted_date); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'autopuzzle'); ?>">
                <div class="btn-list">
                    <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View Details', 'autopuzzle'); ?>">
                        <i class="la la-eye"></i>
                    </a>
                    <?php if ($is_admin || $is_assigned_expert) : 
                        $customer_mobile = get_user_meta($customer->ID, 'billing_phone', true);
                        if (empty($customer_mobile)) {
                            $customer_mobile = get_user_meta($customer->ID, 'mobile_number', true);
                        }
                        if (empty($customer_mobile)) {
                            $customer_mobile = $customer->user_login;
                        }
                        if (!empty($customer_mobile)): ?>
                            <button class="btn btn-sm btn-success-light btn-icon send-sms-report-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-phone="<?php echo esc_attr($customer_mobile); ?>"
                                    data-customer-name="<?php echo esc_attr($customer->display_name ?? ''); ?>"
                                    data-inquiry-type="installment"
                                    title="<?php esc_attr_e('Send SMS', 'autopuzzle'); ?>">
                                <i class="la la-sms"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-info-light btn-icon view-sms-history-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-inquiry-type="installment" 
                                title="<?php esc_attr_e('SMS History', 'autopuzzle'); ?>">
                            <i class="la la-history"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * رندر یک ردیف برای جدول لیست درخواست‌های نقدی (شورت‌کد فرانت‌اند).
     *
     * @param int $inquiry_id شناسه پست درخواست نقدی.
     * @param string $base_url آدرس پایه برای لینک گزارش.
     */
    public static function render_cash_inquiry_row($inquiry_id, $base_url) {
        $cash_post = get_post($inquiry_id);
        if (!$cash_post) return;
        
        // CRITICAL: Ensure this is a cash_inquiry post type, not an installment inquiry
        if (get_post_type($inquiry_id) !== 'cash_inquiry') {
            return; // Skip if not a cash inquiry
        }
        
        $product_id     = get_post_meta( $inquiry_id, 'product_id', true );
        $inquiry_status = get_post_meta( $inquiry_id, 'cash_inquiry_status', true );
        // Handle empty status or 'pending' status - default to 'new' for display purposes
        if (empty($inquiry_status) || $inquiry_status === 'pending') {
            $inquiry_status = 'new';
            // Update the database to use 'new' instead of 'pending'
            update_post_meta($inquiry_id, 'cash_inquiry_status', 'new');
        }
        $expert_status  = get_post_meta( $inquiry_id, 'expert_status', true );
        $customer_name  = get_post_meta( $inquiry_id, 'cash_first_name', true ) . ' ' . get_post_meta( $inquiry_id, 'cash_last_name', true );
        $customer_mobile= get_post_meta( $inquiry_id, 'mobile_number', true );
        $expert_name    = get_post_meta( $inquiry_id, 'assigned_expert_name', true );
        // Use correct dashboard URL for report link
        $report_url     = add_query_arg('cash_inquiry_id', $inquiry_id, home_url('/dashboard/cash-inquiries'));
        // از کلاس CPT Handler برای گرفتن لیبل وضعیت استفاده می‌کنیم.
        $status_label   = Autopuzzle_CPT_Handler::get_cash_inquiry_status_label($inquiry_status); 
        $is_admin       = current_user_can( 'manage_autopuzzle_inquiries' );
        
        // Check if current user is assigned expert
        $is_assigned_expert = false;
        if (!$is_admin) {
            require_once AUTOPUZZLE_PLUGIN_PATH . 'includes/helpers/class-autopuzzle-permission-helpers.php';
            $is_assigned_expert = Autopuzzle_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        }

        // Get expert status info
        $expert_status_info = self::get_expert_status_info($expert_status);

        // Get status badge color class for cash inquiries
        $status_badge_class = 'bg-secondary-transparent';
        switch ($inquiry_status) {
            case 'new':
                $status_badge_class = 'bg-primary-transparent';
                break;
            case 'referred':
                $status_badge_class = 'bg-info-transparent';
                break;
            case 'in_progress':
            case 'follow_up_scheduled':
            case 'awaiting_downpayment':
                $status_badge_class = 'bg-warning-transparent';
                break;
            case 'downpayment_received':
            case 'meeting_scheduled':
            case 'completed':
            case 'approved':
                $status_badge_class = 'bg-success-transparent';
                break;
            case 'rejected':
                $status_badge_class = 'bg-danger-transparent';
                break;
            case 'pending':
                $status_badge_class = 'bg-secondary-transparent';
                break;
            default:
                $status_badge_class = 'bg-secondary-transparent';
        }

        // Helper function to convert numbers to Persian without separators
        if (!function_exists('persian_numbers_no_separator')) {
            function persian_numbers_no_separator($str) {
                $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                return str_replace($english, $persian, (string)$str);
            }
        }
        
        // Format date with Persian numbers
        $formatted_date = self::autopuzzle_gregorian_to_jalali($cash_post->post_date, 'Y/m/d');
        $formatted_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($formatted_date) : $formatted_date;
        ?>
        <tr class="crm-contact">
            <td data-title="<?php esc_attr_e('ID', 'autopuzzle'); ?>">#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'autopuzzle'); ?>"><?php echo esc_html($customer_name); ?></td>
            <td data-title="<?php esc_attr_e('Mobile', 'autopuzzle'); ?>"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($customer_mobile) : esc_html($customer_mobile); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'autopuzzle'); ?>">
                <?php echo esc_html(get_the_title($product_id)); ?>
            </td>
            <td class="inquiry-status-cell-cash" data-title="<?php esc_attr_e('Status', 'autopuzzle'); ?>">
                <?php
                // Determine latest/current status to display
                // Priority: expert_status > inquiry_status
                if ($expert_status_info) {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html($expert_status_info['label']) . '</span>';
                } else {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html($status_label) . '</span>';
                }
                ?>
            </td>
            <?php if ($is_admin) : ?>
                <td data-title="<?php esc_attr_e('Assigned', 'autopuzzle'); ?>">
                    <?php if (!empty($expert_name)) : ?>
                        <span class="assigned-expert-name"><?php echo esc_html($expert_name); ?></span>
                    <?php else : ?>
                        <button class="btn btn-sm btn-info-light assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash" title="<?php esc_attr_e('Assign Expert', 'autopuzzle'); ?>">
                            <i class="la la-user-plus me-1"></i><?php esc_html_e('Assign', 'autopuzzle'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="<?php esc_attr_e('Date', 'autopuzzle'); ?>"><?php echo esc_html($formatted_date); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'autopuzzle'); ?>">
                <div class="btn-list">
                    <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View Details', 'autopuzzle'); ?>">
                        <i class="la la-eye"></i>
                    </a>
                    <?php if ($is_admin || $is_assigned_expert) : 
                        if (!empty($customer_mobile)): ?>
                            <button class="btn btn-sm btn-success-light btn-icon send-sms-report-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-phone="<?php echo esc_attr($customer_mobile); ?>"
                                    data-customer-name="<?php echo esc_attr($customer_name); ?>"
                                    data-inquiry-type="cash"
                                    title="<?php esc_attr_e('Send SMS', 'autopuzzle'); ?>">
                                <i class="la la-sms"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-info-light btn-icon view-sms-history-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-inquiry-type="cash" 
                                title="<?php esc_attr_e('SMS History', 'autopuzzle'); ?>">
                            <i class="la la-history"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Get expert status information (label and color) from settings.
     *
     * @param string $expert_status The expert status key.
     * @return array|null Array with 'label' and 'color' keys, or null if not found.
     */
    public static function get_expert_status_info($expert_status) {
        if (empty($expert_status)) {
            return null;
        }
        
        $raw = Autopuzzle_Options_Helper::get_option('expert_statuses', '');
        $lines = array_filter(array_map('trim', explode("\n", (string)$raw)));
        
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3) {
                $key = trim($parts[0]);
                $label = trim($parts[1]);
                $color = trim($parts[2]);
                
                if ($key === $expert_status) {
                    return [
                        'label' => $label,
                        'color' => $color
                    ];
                }
            }
        }
        
        return null;
    }
}