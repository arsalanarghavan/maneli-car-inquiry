<?php
/**
 * Helper class for rendering various HTML elements and data in Maneli Car Inquiry plugin.
 *
 * This class centralizes all rendering, formatting, and loan calculation logic, 
 * ensuring consistency and fixing the missing function dependencies.
 *
 * @package Maneli_Car_Inquiry/Includes/Helpers
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Comprehensive Frontend Helper)
 */

if (!defined('ABSPATH')) {
    exit;
}

// توجه: فضای نام (Namespace) حذف شد تا با سایر کلاس‌های پلاگین که از فضای نام استفاده نمی‌کنند، سازگار باشد.
// توابع maneli_get_template_part و maneli_gregorian_to_jalali از طریق includes/functions.php در دسترس هستند.
// کلاس‌های Maneli_CPT_Handler، WP_User، WC_Product و غیره باید قبل از استفاده بارگذاری شده باشند.

class Maneli_Render_Helpers {

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

        $options = get_option('maneli_inquiry_all_options', []);
        // نرخ سود ماهانه را از تنظیمات می‌خواند
        $monthly_rate = floatval($options['loan_interest_rate'] ?? 0.035); 
        
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
        // Use maneli_number_format_persian for proper Persian formatting with Persian comma
        if (function_exists('maneli_number_format_persian')) {
            return maneli_number_format_persian((int)$amount);
        }
        return number_format_i18n((int)$amount);
    }
    
    /**
     * Converts Gregorian date to Jalali date. (Wrapper for global function)
     *
     * @param string $gregorian_date_time The date string.
     * @param string $format The output format.
     * @return string The formatted Jalali date.
     */
    public static function maneli_gregorian_to_jalali($gregorian_date_time, $format = 'Y/m/d H:i') {
        if (function_exists('maneli_gregorian_to_jalali')) {
            $timestamp = strtotime($gregorian_date_time);
            
            // تبدیل تاریخ به شمسی
            $jalali_date = maneli_gregorian_to_jalali(
                date('Y', $timestamp), 
                date('m', $timestamp), 
                date('d', $timestamp), 
                'Y/m/d'
            );
            
            // اگر فرمت شامل ساعت باشد، آن را اضافه کن
            if (strpos($format, 'H:i') !== false || strpos($format, 'H:i:s') !== false) {
                $time = date('H:i', $timestamp);
                return $jalali_date . ' ' . $time;
            }
            
            return $jalali_date;
        }
        return date($format, strtotime($gregorian_date_time));
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
                'self' => esc_html__('Freelance', 'maneli-car-inquiry'),
                'employee' => esc_html__('Employee', 'maneli-car-inquiry'),
            ],
            'residency_status' => [
                'owner' => esc_html__('Owner', 'maneli-car-inquiry'),
                'tenant' => esc_html__('Tenant', 'maneli-car-inquiry'),
            ],
            'workplace_status' => [
                'owned' => esc_html__('Owned', 'maneli-car-inquiry'),
                'rented' => esc_html__('Rented', 'maneli-car-inquiry'),
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
            1 => ['name' => esc_html__('White', 'maneli-car-inquiry'), 'class' => 'white'],
            2 => ['name' => esc_html__('Yellow', 'maneli-car-inquiry'), 'class' => 'yellow'],
            3 => ['name' => esc_html__('Orange', 'maneli-car-inquiry'), 'class' => 'orange'],
            4 => ['name' => esc_html__('Brown', 'maneli-car-inquiry'), 'class' => 'brown'],
            5 => ['name' => esc_html__('Red', 'maneli-car-inquiry'), 'class' => 'red'],
            0 => ['name' => esc_html__('Undetermined', 'maneli-car-inquiry'), 'class' => 'undetermined'],
        ];

        $cheque_color_map = [
            '1' => ['text' => esc_html__('White', 'maneli-car-inquiry'), 'desc' => esc_html__('No history of bounced cheques.', 'maneli-car-inquiry')],
            '2' => ['text' => esc_html__('Yellow', 'maneli-car-inquiry'), 'desc' => esc_html__('One bounced cheque or a maximum of 50 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '3' => ['text' => esc_html__('Orange', 'maneli-car-inquiry'), 'desc' => esc_html__('Two to four bounced cheques or a maximum of 200 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '4' => ['text' => esc_html__('Brown', 'maneli-car-inquiry'), 'desc' => esc_html__('Five to ten bounced cheques or a maximum of 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '5' => ['text' => esc_html__('Red', 'maneli-car-inquiry'), 'desc' => esc_html__('More than ten bounced cheques or more than 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '0' => ['text' => esc_html__('Undetermined', 'maneli-car-inquiry'), 'desc' => esc_html__('Information was not received from Finotex or the inquiry was unsuccessful.', 'maneli-car-inquiry')],
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
                .maneli-cheque-status-card {border:1px solid rgba(15,23,42,0.08);border-radius:18px;padding:22px;background:var(--bs-card-bg,#fff);}
                .maneli-cheque-status-main {display:flex;align-items:center;gap:1.25rem;padding:20px;border-radius:16px;position:relative;overflow:hidden;color:#0f172a;}
                .maneli-cheque-status-main .status-icon {width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:30px;background:rgba(255,255,255,0.35);box-shadow:0 10px 25px rgba(15,23,42,0.08);}
                .maneli-cheque-status-main .status-body {flex:1;min-width:0;}
                .maneli-cheque-status-main .status-title {margin-bottom:.4rem;font-size:1rem;font-weight:600;}
                .maneli-cheque-status-main .status-chip {display:inline-flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:999px;font-weight:600;background:rgba(255,255,255,0.35);backdrop-filter:blur(4px);box-shadow:0 5px 18px rgba(15,23,42,0.12);}
                .maneli-cheque-status-main .status-desc {margin-top:.75rem;color:rgba(15,23,42,0.75);font-size:.9rem;line-height:1.65;}
                .maneli-cheque-status-legend {display:flex;flex-wrap:wrap;gap:.6rem;margin-top:1.5rem;}
                .maneli-cheque-chip {display:inline-flex;align-items:center;gap:.45rem;padding:.42rem .95rem;border-radius:999px;font-size:.85rem;font-weight:500;border:1px solid transparent;transition:all .2s ease;}
                .maneli-cheque-chip .chip-dot {width:10px;height:10px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px rgba(15,23,42,0.06);}
                .maneli-cheque-chip.active {transform:translateY(-2px);box-shadow:0 10px 22px rgba(15,23,42,0.14);border-color:currentColor;background:#fff;}
                .maneli-cheque-chip.chip-white {color:#475569;background:linear-gradient(135deg,#f8fafc,#fefefe);}
                .maneli-cheque-chip.chip-yellow {color:#b7791f;background:linear-gradient(135deg,#fff7d6,#fde68a);}
                .maneli-cheque-chip.chip-orange {color:#c2410c;background:linear-gradient(135deg,#ffe0c2,#fed7aa);}
                .maneli-cheque-chip.chip-brown {color:#92400e;background:linear-gradient(135deg,#f5d7b8,#f3c98b);}
                .maneli-cheque-chip.chip-red {color:#b91c1c;background:linear-gradient(135deg,#fecaca,#f87171);}
                .maneli-cheque-chip.chip-undetermined {color:#475569;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);}
                .maneli-cheque-status-main.status-color-white {background:linear-gradient(135deg,#f8fafc,#ffffff);color:#1f2937;}
                .maneli-cheque-status-main.status-color-yellow {background:linear-gradient(135deg,#fffbeb,#fef08a);color:#854d0e;}
                .maneli-cheque-status-main.status-color-orange {background:linear-gradient(135deg,#fff7ed,#fed7aa);color:#9a3412;}
                .maneli-cheque-status-main.status-color-brown {background:linear-gradient(135deg,#f9eadf,#f3c999);color:#78350f;}
                .maneli-cheque-status-main.status-color-red {background:linear-gradient(135deg,#fee2e2,#fca5a5);color:#7f1d1d;}
                .maneli-cheque-status-main.status-color-undetermined {background:linear-gradient(135deg,#eef2f7,#cbd5f5);color:#1f2937;}
                @media (max-width: 576px){.maneli-cheque-status-main{flex-direction:column;text-align:center;}.maneli-cheque-status-main .status-icon{margin-bottom:.75rem;}}
            </style>';
        }

        $output .= '<div class="maneli-cheque-status-card">';
        $output .= '<div class="maneli-cheque-status-main ' . esc_attr($status_class) . '">';
        $output .= '<div class="status-icon"><i class="la la-shield-alt"></i></div>';
        $output .= '<div class="status-body">';
        $output .= '<div class="status-title">' . esc_html__('Sadad Cheque Status Inquiry', 'maneli-car-inquiry') . '</div>';
        $output .= '<div class="status-chip"><span class="chip-dot"></span><span>' . esc_html($active_info['text']) . '</span></div>';
        $output .= '<p class="status-desc">' . esc_html($active_info['desc']) . '</p>';
        $output .= '</div>';
        $output .= '</div>';

        $output .= '<div class="maneli-cheque-status-legend">';
        foreach ([1, 2, 3, 4, 5] as $code) {
            $info = $palette[$code];
            $output .= '<div class="maneli-cheque-chip chip-' . esc_attr($info['class']) . ' ' . ($cheque_color_code === (string)$code ? 'active' : '') . '"><span class="chip-dot"></span><span>' . esc_html($info['name']) . '</span></div>';
        }
        $output .= '<div class="maneli-cheque-chip chip-undetermined ' . ($cheque_color_code === '0' ? 'active' : '') . '"><span class="chip-dot"></span><span>' . esc_html($palette[0]['name']) . '</span></div>';
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
        
        // Helper function to convert numbers to Persian
        if (!function_exists('persian_numbers')) {
            function persian_numbers($str) {
                // Use maneli_number_format_persian if it exists for better formatting
                if (function_exists('maneli_number_format_persian') && is_numeric($str)) {
                    return maneli_number_format_persian($str);
                }
                $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
                return str_replace($english, $persian, $str);
            }
        }
        
        $product_id = $product->get_id();
        $installment_price = get_post_meta($product_id, 'installment_price', true);
        $min_downpayment = get_post_meta($product_id, 'min_downpayment', true);
        $car_colors = get_post_meta($product_id, '_maneli_car_colors', true);
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        $manufacture_year = get_post_meta($product_id, '_maneli_car_year', true);
        $manufacture_year_raw = is_scalar($manufacture_year) ? trim((string) $manufacture_year) : '';
        $manufacture_year_clean = '';
        if ($manufacture_year_raw !== '') {
            $manufacture_year_clean = preg_replace('/[^\p{N}]/u', '', $manufacture_year_raw);
        }
        if ($manufacture_year_clean !== '') {
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $manufacture_year_display = str_replace($english_digits, $persian_digits, $manufacture_year_clean);
        } else {
            $manufacture_year_display = '';
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
            'special_sale' => ['label' => esc_html__('Special Sale', 'maneli-car-inquiry'), 'class' => 'primary-transparent'],
            'unavailable'  => ['label' => esc_html__('Unavailable', 'maneli-car-inquiry'), 'class' => 'warning-transparent'],
            'disabled'     => ['label' => esc_html__('Disabled', 'maneli-car-inquiry'), 'class' => 'danger-transparent'],
        ];
        $current_status = $statuses[$car_status] ?? $statuses['special_sale'];
        
        // Format prices for display (Persian numbers with thousand separators)
        $regular_price = $product->get_regular_price();
        $regular_price_formatted = $regular_price ? persian_numbers(number_format($regular_price, 0, '.', ',')) : '';
        $installment_price_formatted = $installment_price ? persian_numbers(number_format($installment_price, 0, '.', ',')) : '';
        $min_downpayment_formatted = $min_downpayment ? persian_numbers(number_format($min_downpayment, 0, '.', ',')) : '';
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
                                <?php echo persian_numbers(esc_html($product->get_name())); ?>
                            </a>
                            <?php if ($manufacture_year_display !== '') : ?>
                                <span class="badge maneli-year-badge ms-2"><?php echo esc_html($manufacture_year_display); ?></span>
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
                           data-raw-value="<?php echo esc_attr($regular_price); ?>"
                           value="<?php echo esc_attr($regular_price_formatted); ?>" 
                           placeholder="<?php echo esc_attr__('Cash Price', 'maneli-car-inquiry'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon maneli-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input manli-price-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="installment_price" 
                           data-raw-value="<?php echo esc_attr($installment_price); ?>"
                           value="<?php echo esc_attr($installment_price_formatted); ?>" 
                           placeholder="<?php echo esc_attr__('Installment Price', 'maneli-car-inquiry'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon maneli-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input manli-price-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="min_downpayment" 
                           data-raw-value="<?php echo esc_attr($min_downpayment); ?>"
                           value="<?php echo esc_attr($min_downpayment_formatted); ?>" 
                           placeholder="<?php echo esc_attr__('Minimum Down Payment', 'maneli-car-inquiry'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon maneli-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <input type="text" class="manli-data-input form-control form-control-sm flex-grow-1" 
                           data-product-id="<?php echo esc_attr($product_id); ?>" 
                           data-field-type="car_colors" 
                           value="<?php echo esc_attr($car_colors); ?>" 
                           placeholder="<?php echo esc_attr__('Colors (e.g., White, Black, Silver)', 'maneli-car-inquiry'); ?>">
                    <span class="spinner"></span>
                    <span class="save-status-icon maneli-save-status-icon"></span>
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
                    <span class="save-status-icon maneli-save-status-icon"></span>
                </div>
            </td>
            <td>
                <div class="hstack gap-2 fs-15">
                    <a href="<?php echo esc_url(home_url('/dashboard/add-product?edit_product=' . $product_id)); ?>" 
                       class="btn btn-icon btn-sm btn-primary-light" 
                       title="<?php esc_attr_e('Edit', 'maneli-car-inquiry'); ?>">
                        <i class="ri-edit-line"></i>
                    </a>
                    <a href="<?php echo esc_url(get_permalink($product_id)); ?>" 
                       class="btn btn-icon btn-sm btn-info-light" 
                       target="_blank" 
                       title="<?php esc_attr_e('View', 'maneli-car-inquiry'); ?>">
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
            <td data-title="<?php esc_attr_e('Display Name', 'maneli-car-inquiry'); ?>">
                <strong><?php echo esc_html($user->display_name); ?></strong>
            </td>
            <td data-title="<?php esc_attr_e('Username', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->user_login); ?></td>
            <td data-title="<?php esc_attr_e('Email', 'maneli-car-inquiry'); ?>"><?php echo esc_html($user->user_email); ?></td>
            <td data-title="<?php esc_attr_e('Role', 'maneli-car-inquiry'); ?>"><?php echo esc_html($role_display); ?></td>
            <td class="woocommerce-orders-table__cell-order-actions">
                <a href="<?php echo esc_url($edit_url); ?>" class="button view"><?php esc_html_e('Edit', 'maneli-car-inquiry'); ?></a>
                <button class="button delete-user-btn maneli-btn-delete" data-user-id="<?php echo esc_attr($user->ID); ?>">
                    <?php esc_html_e('Delete', 'maneli-car-inquiry'); ?>
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
        $status_label      = Maneli_CPT_Handler::get_status_label($inquiry_status); 
        $is_admin          = current_user_can( 'manage_maneli_inquiries' );
        
        // Check if current user is assigned expert
        $is_assigned_expert = false;
        if (!$is_admin) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
            $is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
        }
        
        // Get expert status info and tracking status
        $expert_status_info = self::get_expert_status_info($expert_status);
        $tracking_status = get_post_meta($inquiry_id, 'tracking_status', true);
        $follow_up_date = get_post_meta($inquiry_id, 'follow_up_date', true);
        
        // Check Finnotech API data availability
        $options = get_option('maneli_inquiry_all_options', []);
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
            $finnotech_indicators[] = '<i class="la la-exclamation-circle text-danger" title="' . esc_attr__('Credit Risk Available', 'maneli-car-inquiry') . '"></i>';
        }
        if ($credit_score_enabled && !empty($credit_score_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-chart-line text-warning" title="' . esc_attr__('Credit Score Available', 'maneli-car-inquiry') . '"></i>';
        }
        if ($collaterals_enabled && !empty($collaterals_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-file-contract text-info" title="' . esc_attr__('Contracts Available', 'maneli-car-inquiry') . '"></i>';
        }
        if ($cheque_color_enabled && !empty($cheque_color_data)) {
            $has_finnotech_data = true;
            $finnotech_indicators[] = '<i class="la la-shield-alt text-primary" title="' . esc_attr__('Cheque Status Available', 'maneli-car-inquiry') . '"></i>';
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
        $formatted_date = self::maneli_gregorian_to_jalali($inquiry_post->post_date, 'Y/m/d');
        $formatted_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($formatted_date) : $formatted_date;
        ?>
        <tr class="crm-contact">
            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer->display_name ?? '—'); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>">
                <div class="d-flex align-items-center">
                    <?php echo esc_html(get_the_title($product_id)); ?>
                    <?php if ($has_finnotech_data && $is_admin): ?>
                        <span class="ms-2" style="font-size: 14px;" title="<?php esc_attr_e('Credit Information Available', 'maneli-car-inquiry'); ?>">
                            <?php echo implode(' ', $finnotech_indicators); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </td>
            <?php if ($show_followup_date): ?>
                <td data-title="<?php esc_attr_e('Follow-up Date', 'maneli-car-inquiry'); ?>">
                    <strong class="maneli-text-danger"><?php echo esc_html($follow_up_date ? (function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($follow_up_date) : $follow_up_date) : '—'); ?></strong>
                </td>
            <?php endif; ?>
            <td class="inquiry-status-cell-installment" data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                <?php
                // Determine latest/current status to display
                // Priority: tracking_status > expert_status > inquiry_status
                if ($tracking_status) {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html(Maneli_CPT_Handler::get_tracking_status_label($tracking_status)) . '</span>';
                } elseif ($expert_status_info) {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html($expert_status_info['label']) . '</span>';
                } else {
                    echo '<span class="badge ' . esc_attr($status_badge_class) . '">' . esc_html($status_label) . '</span>';
                }
                ?>
            </td>
            <?php if ($is_admin) : ?>
                <td data-title="<?php esc_attr_e('Assigned', 'maneli-car-inquiry'); ?>">
                    <?php if (!empty($expert_name)) : ?>
                        <span class="assigned-expert-name"><?php echo esc_html($expert_name); ?></span>
                    <?php else : ?>
                        <button class="btn btn-sm btn-info-light assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment" title="<?php esc_attr_e('Assign Expert', 'maneli-car-inquiry'); ?>">
                            <i class="la la-user-plus me-1"></i><?php esc_html_e('تخصیص', 'maneli-car-inquiry'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html($formatted_date); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                <div class="btn-list">
                    <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View Details', 'maneli-car-inquiry'); ?>">
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
                                    title="<?php esc_attr_e('Send SMS', 'maneli-car-inquiry'); ?>">
                                <i class="la la-sms"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-info-light btn-icon view-sms-history-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-inquiry-type="installment" 
                                title="<?php esc_attr_e('SMS History', 'maneli-car-inquiry'); ?>">
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
        $status_label   = Maneli_CPT_Handler::get_cash_inquiry_status_label($inquiry_status); 
        $is_admin       = current_user_can( 'manage_maneli_inquiries' );
        
        // Check if current user is assigned expert
        $is_assigned_expert = false;
        if (!$is_admin) {
            require_once MANELI_INQUIRY_PLUGIN_PATH . 'includes/helpers/class-maneli-permission-helpers.php';
            $is_assigned_expert = Maneli_Permission_Helpers::is_assigned_expert($inquiry_id, get_current_user_id());
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
        $formatted_date = self::maneli_gregorian_to_jalali($cash_post->post_date, 'Y/m/d');
        $formatted_date = function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($formatted_date) : $formatted_date;
        ?>
        <tr class="crm-contact">
            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($inquiry_id) : esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer_name); ?></td>
            <td data-title="<?php esc_attr_e('Mobile', 'maneli-car-inquiry'); ?>"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator($customer_mobile) : esc_html($customer_mobile); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>">
                <?php echo esc_html(get_the_title($product_id)); ?>
            </td>
            <td class="inquiry-status-cell-cash" data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
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
                <td data-title="<?php esc_attr_e('Assigned', 'maneli-car-inquiry'); ?>">
                    <?php if (!empty($expert_name)) : ?>
                        <span class="assigned-expert-name"><?php echo esc_html($expert_name); ?></span>
                    <?php else : ?>
                        <button class="btn btn-sm btn-info-light assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash" title="<?php esc_attr_e('Assign Expert', 'maneli-car-inquiry'); ?>">
                            <i class="la la-user-plus me-1"></i><?php esc_html_e('تخصیص', 'maneli-car-inquiry'); ?>
                        </button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo esc_html($formatted_date); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                <div class="btn-list">
                    <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light btn-icon" title="<?php esc_attr_e('View Details', 'maneli-car-inquiry'); ?>">
                        <i class="la la-eye"></i>
                    </a>
                    <?php if ($is_admin || $is_assigned_expert) : 
                        if (!empty($customer_mobile)): ?>
                            <button class="btn btn-sm btn-success-light btn-icon send-sms-report-btn" 
                                    data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                    data-phone="<?php echo esc_attr($customer_mobile); ?>"
                                    data-customer-name="<?php echo esc_attr($customer_name); ?>"
                                    data-inquiry-type="cash"
                                    title="<?php esc_attr_e('Send SMS', 'maneli-car-inquiry'); ?>">
                                <i class="la la-sms"></i>
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-info-light btn-icon view-sms-history-btn" 
                                data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" 
                                data-inquiry-type="cash" 
                                title="<?php esc_attr_e('SMS History', 'maneli-car-inquiry'); ?>">
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
        
        $options = get_option('maneli_inquiry_all_options', []);
        $raw = $options['expert_statuses'] ?? '';
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