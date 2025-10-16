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
            return maneli_gregorian_to_jalali(
                date('Y', $timestamp), 
                date('m', $timestamp), 
                date('d', $timestamp), 
                $format
            );
        }
        return date($format, strtotime($gregorian_date_time));
    }

    /**
     * رندر نوار وضعیت رنگی چک صیادی و توضیحات مرتبط.
     * این تابع در گزارش‌های فرانت‌اند استفاده می‌شود.
     *
     * @param int $cheque_color_code کد رنگ (1=سفید، 5=قرمز، 0=نامشخص).
     * @return string خروجی HTML.
     */
    public static function render_cheque_status_info($cheque_color_code) {
        $colors = [
            1 => ['name' => esc_html__('White', 'maneli-car-inquiry'), 'class' => 'white'], 
            2 => ['name' => esc_html__('Yellow', 'maneli-car-inquiry'), 'class' => 'yellow'],
            3 => ['name' => esc_html__('Orange', 'maneli-car-inquiry'), 'class' => 'orange'], 
            4 => ['name' => esc_html__('Brown', 'maneli-car-inquiry'), 'class' => 'brown'],
            5 => ['name' => esc_html__('Red', 'maneli-car-inquiry'), 'class' => 'red']
        ];
        
        $cheque_color_map = [
            '1' => ['text' => esc_html__('White', 'maneli-car-inquiry'), 'desc' => esc_html__('No history of bounced cheques.', 'maneli-car-inquiry')],
            '2' => ['text' => esc_html__('Yellow', 'maneli-car-inquiry'), 'desc' => esc_html__('One bounced cheque or a maximum of 50 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '3' => ['text' => esc_html__('Orange', 'maneli-car-inquiry'), 'desc' => esc_html__('Two to four bounced cheques or a maximum of 200 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '4' => ['text' => esc_html__('Brown', 'maneli-car-inquiry'), 'desc' => esc_html__('Five to ten bounced cheques or a maximum of 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
            '5' => ['text' => esc_html__('Red', 'maneli-car-inquiry'), 'desc' => esc_html__('More than ten bounced cheques or more than 500 million Rials in returned commitments.', 'maneli-car-inquiry')],
             0  => ['text' => esc_html__('Undetermined', 'maneli-car-inquiry'), 'desc' => esc_html__('Information was not received from Finotex or the inquiry was unsuccessful.', 'maneli-car-inquiry')]
        ];

        $output = '<div class="maneli-status-bar">';
        foreach ($colors as $code => $color) {
            $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
            $output .= "<div class='bar-segment segment-{$color['class']} {$active_class}'><span>" . esc_html($color['name']) . "</span></div>";
        }
        $output .= '</div>';
        
        $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];

        $output .= '<table class="summary-table" style="margin-top: 20px;">
                        <tr>
                            <td><strong>' . esc_html__('Sayad Cheque Status:', 'maneli-car-inquiry') . '</strong></td>
                            <td><strong class="cheque-color-' . esc_attr($cheque_color_code) . '">' . esc_html($color_info['text']) . '</strong></td>
                        </tr>
                        <tr>
                            <td><strong>' . esc_html__('Status Explanation:', 'maneli-car-inquiry') . '</strong></td>
                            <td>' . esc_html($color_info['desc']) . '</td>
                        </tr>
                    </table>';
        
        return $output;
    }

    /**
     * رندر یک ردیف برای جدول ویرایش محصولات (شورت‌کد فرانت‌اند).
     *
     * @param WC_Product $product شیء محصول.
     */
    public static function render_product_editor_row($product) {
        if (!($product instanceof WC_Product)) return;
        $product_id = $product->get_id();
        $installment_price = get_post_meta($product_id, 'installment_price', true);
        $min_downpayment = get_post_meta($product_id, 'min_downpayment', true);
        $car_colors = get_post_meta($product_id, '_maneli_car_colors', true);
        $car_status = get_post_meta($product_id, '_maneli_car_status', true);
        $statuses = [
            'special_sale' => esc_html__('Special Sale (Active)', 'maneli-car-inquiry'),
            'unavailable'  => esc_html__('Unavailable (Show in site)', 'maneli-car-inquiry'),
            'disabled'     => esc_html__('Disabled (Hide from site)', 'maneli-car-inquiry'),
        ];
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('Car Name', 'maneli-car-inquiry'); ?>">
                <strong><a href="<?php echo esc_url(get_permalink($product_id)); ?>" target="_blank"><?php echo esc_html($product->get_name()); ?></a></strong>
            </td>
            <td data-title="<?php esc_attr_e('Cash Price (Toman)', 'maneli-car-inquiry'); ?>">
                <input type="text" class="manli-data-input manli-price-input" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="regular_price" value="<?php echo esc_attr($product->get_regular_price()); ?>" placeholder="<?php esc_attr_e('Cash Price', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Installment Price (Toman)', 'maneli-car-inquiry'); ?>">
                <input type="text" class="manli-data-input manli-price-input" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="installment_price" value="<?php echo esc_attr($installment_price); ?>" placeholder="<?php esc_attr_e('Installment Price', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Min. Down Payment (Toman)', 'maneli-car-inquiry'); ?>">
                <input type="text" class="manli-data-input manli-price-input" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="min_downpayment" value="<?php echo esc_attr($min_downpayment); ?>" placeholder="<?php esc_attr_e('Down Payment Amount', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Available Colors', 'maneli-car-inquiry'); ?>">
                 <input type="text" class="manli-data-input" style="width:100%;" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="car_colors" value="<?php echo esc_attr($car_colors); ?>" placeholder="<?php esc_attr_e('e.g., White, Black, Silver', 'maneli-car-inquiry'); ?>">
            </td>
            <td data-title="<?php esc_attr_e('Sales Status', 'maneli-car-inquiry'); ?>">
                <select class="manli-data-input" data-product-id="<?php echo esc_attr($product_id); ?>" data-field-type="car_status">
                    <?php foreach ($statuses as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($car_status, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="spinner"></span>
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
            <td data-title="<?php esc_attr_e('Role', 'maneli-car-inquiry'); ?>"><?php echo $role_display; ?></td>
            <td class="woocommerce-orders-table__cell-order-actions">
                <a href="<?php echo $edit_url; ?>" class="button view"><?php esc_html_e('Edit', 'maneli-car-inquiry'); ?></a>
                <button class="button delete-user-btn" data-user-id="<?php echo esc_attr($user->ID); ?>" style="background-color: var(--theme-red); border-color: var(--theme-red); margin-top: 5px;">
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
    public static function render_inquiry_row($inquiry_id, $base_url) {
        $inquiry_post      = get_post($inquiry_id);
        if (!$inquiry_post) return;
        // از آنجایی که در حالت فرانت‌اند تنها هستیم، از توابع عمومی وردپرس استفاده می‌کنیم.
        $product_id        = get_post_meta( $inquiry_id, 'product_id', true );
        $inquiry_status    = get_post_meta( $inquiry_id, 'inquiry_status', true );
        $expert_status     = get_post_meta( $inquiry_id, 'expert_status', true );
        $customer          = get_userdata( $inquiry_post->post_author );
        $expert_name       = get_post_meta( $inquiry_id, 'assigned_expert_name', true );
        $report_url        = add_query_arg('inquiry_id', $inquiry_id, $base_url);
        // از کلاس CPT Handler برای گرفتن لیبل وضعیت استفاده می‌کنیم (باید بارگذاری شده باشد).
        $status_label      = Maneli_CPT_Handler::get_status_label($inquiry_status); 
        $is_admin          = current_user_can( 'manage_maneli_inquiries' );
        
        // Get expert status info
        $expert_status_info = self::get_expert_status_info($expert_status);
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer->display_name ?? '—'); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td class="inquiry-status-cell-installment" data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                <span class="status-indicator status-<?php echo esc_attr($inquiry_status); ?>"><?php echo esc_html($status_label); ?></span>
                <?php if ($expert_status_info): ?>
                    <br><span class="expert-status-badge" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-top: 4px; display: inline-block;"><?php echo esc_html($expert_status_info['label']); ?></span>
                <?php endif; ?>
            </td>
            <?php if ($is_admin) : ?>
                <td data-title="<?php esc_attr_e('Assigned', 'maneli-car-inquiry'); ?>">
                    <?php if (!empty($expert_name)) : ?>
                        <?php echo esc_html($expert_name); ?>
                    <?php else : ?>
                        <button class="button assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="installment"><?php esc_html_e('Assign', 'maneli-car-inquiry'); ?></button>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo self::maneli_gregorian_to_jalali($inquiry_post->post_date, 'Y/m/d'); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                <a href="<?php echo esc_url($report_url); ?>" class="button view"><?php esc_html_e('View Details', 'maneli-car-inquiry'); ?></a>
                <?php if ($is_admin) : ?>
                    <button class="button delete-installment-list-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" style="background-color: var(--theme-red); border-color: var(--theme-red); margin-top: 5px;"><?php esc_html_e('Delete', 'maneli-car-inquiry'); ?></button>
                <?php endif; ?>
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
        
        $product_id     = get_post_meta( $inquiry_id, 'product_id', true );
        $inquiry_status = get_post_meta( $inquiry_id, 'cash_inquiry_status', true );
        $expert_status  = get_post_meta( $inquiry_id, 'expert_status', true );
        $customer_name  = get_post_meta( $inquiry_id, 'cash_first_name', true ) . ' ' . get_post_meta( $inquiry_id, 'cash_last_name', true );
        $customer_mobile= get_post_meta( $inquiry_id, 'mobile_number', true );
        $expert_name    = get_post_meta( $inquiry_id, 'assigned_expert_name', true );
        $report_url     = add_query_arg('cash_inquiry_id', $inquiry_id, $base_url);
        // از کلاس CPT Handler برای گرفتن لیبل وضعیت استفاده می‌کنیم.
        $status_label   = Maneli_CPT_Handler::get_cash_inquiry_status_label($inquiry_status); 
        $is_admin       = current_user_can( 'manage_maneli_inquiries' );

        // Get expert status info
        $expert_status_info = self::get_expert_status_info($expert_status);

        $set_downpayment_button = ($inquiry_status === 'pending' || $inquiry_status === 'approved') ? 
            '<a href="#" class="set-down-payment-btn" data-inquiry-id="' . esc_attr($inquiry_id) . '" style="display: block; font-size: 11px; margin-top: 5px;">' . esc_html__('Set Down Payment', 'maneli-car-inquiry') . '</a>' : '';
        ?>
        <tr>
            <td data-title="<?php esc_attr_e('ID', 'maneli-car-inquiry'); ?>">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="<?php esc_attr_e('Customer', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer_name); ?></td>
            <td data-title="<?php esc_attr_e('Mobile', 'maneli-car-inquiry'); ?>"><?php echo esc_html($customer_mobile); ?></td>
            <td data-title="<?php esc_attr_e('Car', 'maneli-car-inquiry'); ?>"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td class="inquiry-status-cell-cash" data-title="<?php esc_attr_e('Status', 'maneli-car-inquiry'); ?>">
                <span class="status-indicator status-<?php echo esc_attr($inquiry_status); ?>"><?php echo esc_html($status_label); ?></span>
                <?php if ($expert_status_info): ?>
                    <br><span class="expert-status-badge" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; margin-top: 4px; display: inline-block;"><?php echo esc_html($expert_status_info['label']); ?></span>
                <?php endif; ?>
                <?php if ($is_admin) echo $set_downpayment_button; ?>
            </td>
            <td data-title="<?php esc_attr_e('Assigned', 'maneli-car-inquiry'); ?>">
                <?php if (!empty($expert_name)) : ?>
                    <?php echo esc_html($expert_name); ?>
                <?php else : ?>
                    <button class="button assign-expert-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash"><?php esc_html_e('Assign', 'maneli-car-inquiry'); ?></button>
                <?php endif; ?>
            </td>
            <td data-title="<?php esc_attr_e('Date', 'maneli-car-inquiry'); ?>"><?php echo self::maneli_gregorian_to_jalali($cash_post->post_date, 'Y/m/d'); ?></td>
            <td data-title="<?php esc_attr_e('Actions', 'maneli-car-inquiry'); ?>">
                <a href="<?php echo esc_url($report_url); ?>" class="button view"><?php esc_html_e('View Details', 'maneli-car-inquiry'); ?></a>
                <?php if ($is_admin) : ?>
                    <button class="button delete-cash-list-btn" data-inquiry-id="<?php echo esc_attr($inquiry_id); ?>" data-inquiry-type="cash" style="background-color: var(--theme-red); border-color: var(--theme-red); margin-top: 5px;"><?php esc_html_e('Delete', 'maneli-car-inquiry'); ?></button>
                <?php endif; ?>
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