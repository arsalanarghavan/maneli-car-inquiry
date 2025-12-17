<?php
/**
 * Creates and manages the plugin's settings, providing core encryption/decryption utilities
 * and handling settings updates exclusively from the frontend shortcode.
 *
 * All backend WordPress Admin UI elements for settings have been removed.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Frontend-Only Settings Management)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Settings_Page {

    /**
     * The name of the option in the wp_options table where all settings are stored.
     * @var string
     */
    private $options_name = 'autopuzzle_inquiry_all_options';

    public function __construct() {
        // تنها هوک لازم برای ذخیره تنظیمات از طریق فرم فرانت‌اند (شورت‌کد) نگهداری شده است.
        add_action('admin_post_maneli_save_frontend_settings', [$this, 'handle_frontend_settings_save']);
        add_action('admin_post_autopuzzle_save_frontend_settings', [$this, 'handle_frontend_settings_save']);
    }

    /**
     * Encrypts data using AES-256-CBC.
     * Wrapper method for backward compatibility - uses Autopuzzle_Encryption_Helper.
     * 
     * @param string $data The data to encrypt.
     * @return string The Base64 encoded encrypted data with IV.
     */
    public function encrypt_data($data) {
        return Autopuzzle_Encryption_Helper::encrypt($data);
    }

    /**
     * Decrypts data using AES-256-CBC.
     * Wrapper method for backward compatibility - uses Autopuzzle_Encryption_Helper.
     * 
     * @param string $encrypted_data The encrypted data (Base64 encoded).
     * @return string The decrypted data or empty string on failure.
     */
    public function decrypt_data($encrypted_data) {
        return Autopuzzle_Encryption_Helper::decrypt($encrypted_data);
    }

    /**
     * Renders the HTML برای یک فیلد تنظیمات مشخص بر اساس نوع آن.
     * این متد توسط تمپلیت تنظیمات فرانت‌اند فراخوانی می‌شود.
     * * @param array $args آرگومان‌های فیلد.
     */
    public function render_field_html($args) {
        $options = get_option($this->options_name, []);
        $name = $args['name'];
        $type = $args['type'] ?? 'text';
        $value = $options[$name] ?? ($args['default'] ?? '');
        $field_name = "{$this->options_name}[{$name}]";
        
        // بررسی کلیدهای حساس و رمزگشایی آنها قبل از نمایش در فیلد ورودی
        $is_sensitive = in_array($name, ['finotex_username', 'finotex_password', 'sadad_key', 'recaptcha_v2_secret_key', 'recaptcha_v3_secret_key', 'hcaptcha_secret_key'], true);

        if ($is_sensitive && !empty($value)) {
            $value = $this->decrypt_data($value);
            // اگر رمزگشایی ناموفق باشد، فیلد خالی نمایش داده می‌شود تا کاربر مجدداً وارد کند.
        }

        switch ($type) {
            case 'textarea':
                echo "<textarea name='" . esc_attr($field_name) . "' id='" . esc_attr($this->options_name . '_' . $name) . "' rows='3' class='form-control'>" . esc_textarea($value) . "</textarea>";
                break;
            case 'switch':
                echo '<label class="autopuzzle-switch">';
                echo "<input type='checkbox' name='" . esc_attr($field_name) . "' value='1' " . checked('1', $value, false) . '>';
                echo '<span class="autopuzzle-slider round"></span></label>';
                break;
            case 'select':
                $options = $args['options'] ?? [];
                echo '<select name="' . esc_attr($field_name) . '" id="' . esc_attr($this->options_name . '_' . $name) . '" class="form-select">';
                foreach ($options as $opt_val => $opt_label) {
                    echo '<option value="' . esc_attr($opt_val) . '" ' . selected($value, $opt_val, false) . '>' . esc_html($opt_label) . '</option>';
                }
                echo '</select>';
                break;
            case 'multiselect':
                // Handle multi-select field for excluded days
                if ($name === 'meetings_excluded_days') {
                    $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                    $day_labels = [
                        'saturday' => esc_html__('Saturday', 'autopuzzle'),
                        'sunday' => esc_html__('Sunday', 'autopuzzle'),
                        'monday' => esc_html__('Monday', 'autopuzzle'),
                        'tuesday' => esc_html__('Tuesday', 'autopuzzle'),
                        'wednesday' => esc_html__('Wednesday', 'autopuzzle'),
                        'thursday' => esc_html__('Thursday', 'autopuzzle'),
                        'friday' => esc_html__('Friday', 'autopuzzle'),
                    ];
                    
                    // Value might be array or comma-separated string
                    $selected = is_array($value) ? $value : (empty($value) ? [] : explode(',', $value));
                    
                    echo '<div class="row g-2">';
                    foreach ($days as $day) {
                        echo '<div class="col-6 col-md-4">';
                        echo '<label class="form-check">';
                        echo '<input type="checkbox" name="' . esc_attr($field_name) . '[]" value="' . esc_attr($day) . '" class="form-check-input" ' . checked(in_array($day, $selected), true, false) . '>';
                        echo '<span class="form-check-label">' . esc_html($day_labels[$day]) . '</span>';
                        echo '</label>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    // Generic multi-select
                    $options = $args['options'] ?? [];
                    echo '<select name="' . esc_attr($field_name) . '[]" id="' . esc_attr($this->options_name . '_' . $name) . '" class="form-select" multiple>';
                    foreach ($options as $opt_val => $opt_label) {
                        $selected = is_array($value) ? in_array($opt_val, $value) : ($value == $opt_val);
                        echo '<option value="' . esc_attr($opt_val) . '" ' . selected($selected, true, false) . '>' . esc_html($opt_label) . '</option>';
                    }
                    echo '</select>';
                }
                break;
            case 'html':
                 // برای نوع 'html'، محتوا در کلید 'desc' است.
                 break;
            default: // شامل text, number, password و غیره
                $input_class = 'form-control';
                $dir = ($type === 'password' || $type === 'number' || $type === 'email') ? 'dir="ltr"' : '';
                echo "<input type='" . esc_attr($type) . "' name='" . esc_attr($field_name) . "' id='" . esc_attr($this->options_name . '_' . $name) . "' value='" . esc_attr($value) . "' class='" . esc_attr($input_class) . "' " . $dir . ">";
                break;
        }

        // نمایش توضیحات بعد از فیلد
        if (!empty($args['desc'])) {
            if ($type === 'html') {
                 echo wp_kses_post($args['desc']);
            } else {
                 echo "<p class='description text-muted fs-12 mt-1 mb-0'>" . wp_kses_post($args['desc']) . "</p>";
            }
        }
    }

    /**
     * Sanitizes and merges new options with old options, handling unchecked checkboxes and encryption.
     * این منطق برای ذخیره داده از فرم فرانت‌اند ضروری است.
     */
    public function sanitize_and_merge_options($input) {
        $old_options = get_option($this->options_name, []);
        $sanitized_input = [];
        $all_fields = $this->get_all_settings_fields();
        $sensitive_keys = ['finotex_username', 'finotex_password', 'sadad_key', 'recaptcha_v2_secret_key', 'recaptcha_v3_secret_key', 'hcaptcha_secret_key'];
        
        foreach ($all_fields as $tab) {
            if(empty($tab['sections'])) continue;
            foreach ($tab['sections'] as $section) {
                if (empty($section['fields'])) continue;
                foreach ($section['fields'] as $field) {
                    $key = $field['name'];
                    
                    // فیلدهای نمایشی را نادیده بگیر
                    if ($field['type'] === 'html') continue;
                    
                    if (isset($input[$key])) {
                        $value = $input[$key];
                        
                        // Handle dashboard password - hash it if provided
                        if ($key === 'dashboard_password' && !empty($value)) {
                            // Check if it's already hashed (old password from database)
                            $old_password = $old_options['dashboard_password'] ?? '';
                            
                            // If new password is different from old one, hash it
                            if ($value !== $old_password) {
                                $value = password_hash(sanitize_text_field($value), PASSWORD_DEFAULT);
                            } else {
                                // Keep the old hashed password
                                $value = $old_password;
                            }
                        }
                        // رمزنگاری فیلدهای حساس
                        elseif (in_array($key, $sensitive_keys, true)) {
                            if (!empty($value)) {
                                $value = $this->encrypt_data(sanitize_text_field($value));
                            } else {
                                $value = '';
                            }
                        } elseif ($field['type'] === 'number' && ($key === 'loan_interest_rate' || $key === 'inquiry_fee')) {
                            // Proper validation for numeric fields
                            if ($key === 'loan_interest_rate') {
                                $value = floatval($value);
                                if ($value < 0 || $value > 1) {
                                    $value = 0.035; // Default 3.5% monthly
                                }
                            } elseif ($key === 'inquiry_fee') {
                                $value = intval($value);
                                if ($value < 0) {
                                    $value = 0;
                                }
                            }
                        } elseif ($field['type'] === 'select') {
                            // Handle select field - validate against allowed options
                            $allowed_options = isset($field['options']) ? array_keys($field['options']) : [];
                            if (!empty($allowed_options) && !in_array($value, $allowed_options)) {
                                // Use default if invalid
                                $value = isset($field['default']) ? $field['default'] : '';
                            }
                            $value = sanitize_text_field($value);
                        } elseif ($field['type'] === 'multiselect') {
                            // Handle multiselect field - validate array values
                            if (is_array($value)) {
                                $valid_days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                                if ($key === 'meetings_excluded_days') {
                                    // Filter to only valid day values
                                    $value = array_intersect($value, $valid_days);
                                } else {
                                    // Generic multiselect - sanitize each value
                                    $value = array_map('sanitize_text_field', $value);
                                }
                            } else {
                                $value = [];
                            }
                        } else {
                            $value = ($field['type'] === 'textarea') ? sanitize_textarea_field($value) : sanitize_text_field($value);
                        }
                        
                        $sanitized_input[$key] = $value;

                    }
                    
                    // مدیریت فیلدهای Switch که در صورت Uncheck بودن در POST وجود ندارند.
                    if ($field['type'] === 'switch' && !isset($input[$key])) {
                        $sanitized_input[$key] = '0';
                    }
                    
                    // مدیریت فیلدهای multiselect که اگر چیزی انتخاب نشده در POST وجود ندارند.
                    if ($field['type'] === 'multiselect' && !isset($input[$key])) {
                        $sanitized_input[$key] = [];
                    }
                }
            }
        }
        
        // ادغام تنظیمات جدید با تنظیمات قبلی برای جلوگیری از حذف تنظیمات دیگر تب‌ها
        return array_merge($old_options, $sanitized_input);
    }
    
    /**
     * Handles saving settings from the frontend settings shortcode.
     */
    public function handle_frontend_settings_save() {
        check_admin_referer('autopuzzle_save_frontend_settings_nonce');
        // اطمینان از دسترسی کاربر به مدیریت پلاگین
        if (!current_user_can('manage_autopuzzle_inquiries')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'autopuzzle'));
        }

        // ورودی‌ها در کلید options_name در POST قرار دارند
        $options = isset($_POST[$this->options_name]) ? (array) $_POST[$this->options_name] : [];
        $sanitized_options = $this->sanitize_and_merge_options($options);
        
        // ذخیره تنظیمات
        update_option($this->options_name, $sanitized_options);
        
        // ریدایرکت به صفحه تنظیمات فرانت‌اند با پیام موفقیت
        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();
        wp_redirect(add_query_arg('settings-updated', 'true', $redirect_url));
        exit;
    }

    /**
     * Public method to get the settings fields structure for use in the shortcode class.
     *
     * @return array ساختار کامل تنظیمات.
     */
    public function get_all_settings_public() {
        return $this->get_all_settings_fields();
    }

    /**
     * Public method to get the options name for use in templates.
     *
     * @return string The options name.
     */
    public function get_options_name() {
        return $this->options_name;
    }
    
    /**
     * Defines the entire structure of the plugin's settings, organized by tabs and sections.
     */
    private function get_all_settings_fields() {
        return [
            // NEW TAB
            'finance' => [
                'title' => esc_html__('Finance & Calculator', 'autopuzzle'),
                'icon' => 'fas fa-hand-holding-usd',
                'sections' => [
                    'autopuzzle_loan_interest_section' => [
                        'title' => esc_html__('Installment Loan Settings', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'loan_interest_rate', 
                                'label' => esc_html__('Monthly Interest Rate (Decimal)', 'autopuzzle'), 
                                'type' => 'text', // Use text to allow decimals better, will sanitize as text
                                'default' => '0.035', 
                                'desc' => esc_html__('Enter the monthly interest rate as a decimal (e.g., 0.035 for 3.5%). Used in all installment calculations.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_price_display_section' => [
                        'title' => esc_html__('Price Display Settings', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'hide_prices_for_customers', 
                                'label' => esc_html__('Hide Prices from Customers', 'autopuzzle'), 
                                'type' => 'switch', 
                                'default' => '0',
                                'desc' => esc_html__('If enabled, all product prices will be hidden from customers on the website. Note: Unavailable products will still show "ناموجود" text.', 'autopuzzle')
                            ],
                        ]
                    ]
                ]
            ],
            // Visitor Statistics Tab
            'visitor_statistics' => [
                'title' => esc_html__('Visitor Statistics', 'autopuzzle'),
                'icon' => 'fas fa-chart-line',
                'sections' => [
                    'autopuzzle_visitor_statistics_section' => [
                        'title' => esc_html__('Visitor Statistics Settings', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'enable_visitor_statistics', 
                                'label' => esc_html__('Enable Visitor Statistics Tracking', 'autopuzzle'), 
                                'type' => 'switch', 
                                'default' => '1',
                                'desc' => esc_html__('If enabled, the plugin will track visitor statistics including visits, browsers, devices, countries, and more. This data will be displayed in the Visitor Statistics dashboard.', 'autopuzzle')
                            ],
                        ]
                    ]
                ]
            ],
            // Logs Tab
            'logs' => [
                'title' => esc_html__('Logs', 'autopuzzle'),
                'icon' => 'fas fa-file-alt',
                'sections' => [
                    'autopuzzle_logs_general_section' => [
                        'title' => esc_html__('General Log Settings', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'enable_logging_system',
                                'label' => esc_html__('Enable System Logging', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Enable logging system to track errors, debug messages, and system events.', 'autopuzzle')
                            ],
                            [
                                'name' => 'enable_user_logging',
                                'label' => esc_html__('Enable User Activity Logging', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Enable logging of user actions including button clicks, form submissions, and AJAX calls.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_logs_system_section' => [
                        'title' => esc_html__('System Log Types', 'autopuzzle'),
                        'desc' => esc_html__('Control which types of system logs are recorded.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'log_system_errors',
                                'label' => esc_html__('Log Errors', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Log PHP errors and exceptions.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_system_debug',
                                'label' => esc_html__('Log Debug Messages', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '0',
                                'desc' => esc_html__('Log debug messages (only when WP_DEBUG is enabled).', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_console_messages',
                                'label' => esc_html__('Log Console Messages', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Log JavaScript console.log, console.error, and console.warn messages.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_button_errors',
                                'label' => esc_html__('Log Button Errors', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Log errors related to button clicks and interactions.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_logs_user_section' => [
                        'title' => esc_html__('User Activity Log Types', 'autopuzzle'),
                        'desc' => esc_html__('Control which types of user activities are logged.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'log_button_clicks',
                                'label' => esc_html__('Log Button Clicks', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Log all button clicks by staff and managers.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_form_submissions',
                                'label' => esc_html__('Log Form Submissions', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Log form submissions.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_ajax_calls',
                                'label' => esc_html__('Log AJAX Calls', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Log AJAX requests made by staff and managers.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_page_views',
                                'label' => esc_html__('Log Page Views', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '0',
                                'desc' => esc_html__('Log page views in dashboard (may generate many logs).', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_logs_retention_section' => [
                        'title' => esc_html__('Log Retention & Cleanup', 'autopuzzle'),
                        'desc' => esc_html__('Configure automatic deletion of old logs to manage database size.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'enable_auto_log_cleanup',
                                'label' => esc_html__('Enable Automatic Log Cleanup', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Automatically delete logs older than the retention period.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_retention_days',
                                'label' => esc_html__('Log Retention Period (Days)', 'autopuzzle'),
                                'type' => 'number',
                                'default' => '90',
                                'desc' => esc_html__('Logs older than this number of days will be automatically deleted. Minimum: 7 days, Recommended: 30-90 days.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_cleanup_frequency',
                                'label' => esc_html__('Cleanup Frequency', 'autopuzzle'),
                                'type' => 'select',
                                'default' => 'daily',
                                'options' => [
                                    'hourly' => esc_html__('Hourly', 'autopuzzle'),
                                    'daily' => esc_html__('Daily', 'autopuzzle'),
                                    'weekly' => esc_html__('Weekly', 'autopuzzle'),
                                ],
                                'desc' => esc_html__('How often to run the automatic cleanup process.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_logs_limits_section' => [
                        'title' => esc_html__('Log Limits & Performance', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'max_logs_per_page',
                                'label' => esc_html__('Maximum Logs Per Page', 'autopuzzle'),
                                'type' => 'number',
                                'default' => '50',
                                'desc' => esc_html__('Maximum number of logs to display per page in the logs dashboard. Recommended: 50-100.', 'autopuzzle')
                            ],
                            [
                                'name' => 'log_max_file_size',
                                'label' => esc_html__('Maximum Log Message Size (Characters)', 'autopuzzle'),
                                'type' => 'number',
                                'default' => '5000',
                                'desc' => esc_html__('Maximum length of log messages. Longer messages will be truncated. Set to 0 for unlimited.', 'autopuzzle')
                            ],
                        ]
                    ],
                ]
            ],
            // EXISTING TABS
            'gateways' => [
                'title' => esc_html__('Payment Gateway', 'autopuzzle'),
                'icon' => 'fas fa-money-check-alt',
                'sections' => [
                    'autopuzzle_payment_general_section' => [
                        'title' => esc_html__('General Payment Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'payment_enabled', 'label' => esc_html__('Enable Payment Gateway', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('If enabled, the inquiry fee payment step will be shown to users.', 'autopuzzle')],
                            ['name' => 'inquiry_fee', 'label' => esc_html__('Inquiry Fee (Toman)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Enter 0 for free inquiries.', 'autopuzzle')],
                            ['name' => 'zero_fee_message', 'label' => esc_html__('Message for Free Inquiry', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('The inquiry fee is waived for you. Please click the button below to continue.', 'autopuzzle')],
                        ]
                    ],
                     'autopuzzle_discount_section' => [
                        'title' => esc_html__('Discount Code Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'discount_code', 'label' => esc_html__('Discount Code', 'autopuzzle'), 'type' => 'text', 'desc' => esc_html__('Enter a code for 100% off the inquiry fee.', 'autopuzzle')],
                            ['name' => 'discount_code_text', 'label' => esc_html__('Discount Code Success Message', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('100% discount applied successfully.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sadad_section' => [
                        'title' => esc_html__('Sadad (Melli Bank) Settings', 'autopuzzle'),
                        'fields' => [
                             ['name' => 'sadad_enabled', 'label' => esc_html__('Enable Sadad', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                             ['name' => 'sadad_terminal_id', 'label' => esc_html__('Terminal ID', 'autopuzzle'), 'type' => 'text'],
                             ['name' => 'sadad_merchant_id', 'label' => esc_html__('Merchant ID', 'autopuzzle'), 'type' => 'text'],
                             // Encrypted Field
                             ['name' => 'sadad_key', 'label' => esc_html__('Sadad Encryption Key', 'autopuzzle'), 'type' => 'password', 'desc' => esc_html__('The encryption key (TDes key) provided by Sadad. Stored securely encrypted.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_zarinpal_section' => [
                        'title' => esc_html__('Zarinpal Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'zarinpal_enabled', 'label' => esc_html__('Enable Zarinpal', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'zarinpal_merchant_code', 'label' => esc_html__('Merchant ID', 'autopuzzle'), 'type' => 'text'],
                        ]
                    ],
                ]
            ],
            'authentication' => [
                'title' => esc_html__('Authentication', 'autopuzzle'),
                'icon' => 'fas fa-shield-alt',
                'sections' => [
                    'autopuzzle_dashboard_security_section' => [
                        'title' => esc_html__('Dashboard Security', 'autopuzzle'),
                        'desc' => esc_html__('Configure dashboard access credentials.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'dashboard_password',
                                'label' => esc_html__('Dashboard Password', 'autopuzzle'),
                                'type' => 'password',
                                'desc' => esc_html__('Password for dashboard login. This field is required - no default password is provided for security reasons.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_otp_settings_section' => [
                        'title' => esc_html__('OTP Authentication Settings', 'autopuzzle'),
                        'desc' => esc_html__('Configure One-Time Password (OTP) settings for login authentication.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'otp_enabled',
                                'label' => esc_html__('Enable OTP Login', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Allow users to login using OTP sent via SMS.', 'autopuzzle')
                            ],
                            [
                                'name' => 'otp_pattern_code',
                                'label' => esc_html__('OTP Pattern Code (Body ID)', 'autopuzzle'),
                                'type' => 'number',
                                'desc' => esc_html__('Pattern code for OTP SMS. Variable: {0} = OTP Code (4-digit)', 'autopuzzle')
                            ],
                            [
                                'name' => 'otp_expiry_minutes',
                                'label' => esc_html__('OTP Expiry Time (minutes)', 'autopuzzle'),
                                'type' => 'number',
                                'default' => '5',
                                'desc' => esc_html__('How long the OTP code remains valid (in minutes).', 'autopuzzle')
                            ],
                            [
                                'name' => 'otp_resend_delay',
                                'label' => esc_html__('Resend Delay (seconds)', 'autopuzzle'),
                                'type' => 'number',
                                'default' => '60',
                                'desc' => esc_html__('Minimum time between OTP resend requests (in seconds).', 'autopuzzle')
                            ],
                        ]
                    ],
                ]
            ],
            'sms' => [
                'title' => esc_html__('SMS', 'autopuzzle'),
                'icon' => 'fas fa-mobile-alt',
                'sections' => [
                    'autopuzzle_sms_api_section' => [
                        'title' => esc_html__('MeliPayamak Panel Information', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_username', 'label' => esc_html__('Username', 'autopuzzle'), 'type' => 'text'],
                            ['name' => 'sms_password', 'label' => esc_html__('Password', 'autopuzzle'), 'type' => 'password'],
                        ]
                    ],
                    'autopuzzle_sms_patterns_section' => [
                        'title' => esc_html__('SMS Pattern Codes (Body ID)', 'autopuzzle'),
                        'desc' => esc_html__('Enter only the approved Pattern Code (Body ID) from your SMS panel. Variable order must match the descriptions.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'admin_notification_mobile', 'label' => esc_html__('Admin Mobile Number', 'autopuzzle'), 'type' => 'text', 'desc' => esc_html__('Mobile number to receive new request notifications.', 'autopuzzle')],
                            ['name' => 'sms_pattern_new_inquiry', 'label' => esc_html__('Pattern: "New Request for Admin"', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_meeting_reminder', 'label' => esc_html__('Pattern: "Meeting Reminder"', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Pattern code for SMS meeting reminders. Variables: 1. Customer Name 2. Meeting Date 3. Meeting Time 4. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_installment_patterns_customer_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Installment Inquiry (Customer)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_pattern_installment_new_customer', 'label' => esc_html__('Pattern: "New" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_pending_customer', 'label' => esc_html__('Pattern: "Pending Review" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_user_confirmed_customer', 'label' => esc_html__('Pattern: "Approved and Referred" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_more_docs_customer', 'label' => esc_html__('Pattern: "More Documents Required" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_rejected_customer', 'label' => esc_html__('Pattern: "Rejected" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_failed_customer', 'label' => esc_html__('Pattern: "Inquiry Failed" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_referred_customer', 'label' => esc_html__('Pattern: "Referred to Expert" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_in_progress_customer', 'label' => esc_html__('Pattern: "In Progress" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_follow_up_scheduled_customer', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_meeting_scheduled_customer', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_awaiting_documents_customer', 'label' => esc_html__('Pattern: "Awaiting Documents" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_approved_customer', 'label' => esc_html__('Pattern: "Approved" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_completed_customer', 'label' => esc_html__('Pattern: "Completed" (Installment - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_installment_patterns_expert_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Installment Inquiry (Expert)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_pattern_installment_new_expert', 'label' => esc_html__('Pattern: "New" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_referred_expert', 'label' => esc_html__('Pattern: "Referred to Expert" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_in_progress_expert', 'label' => esc_html__('Pattern: "In Progress" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_follow_up_scheduled_expert', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_meeting_scheduled_expert', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_awaiting_documents_expert', 'label' => esc_html__('Pattern: "Awaiting Documents" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_approved_expert', 'label' => esc_html__('Pattern: "Approved" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_rejected_expert', 'label' => esc_html__('Pattern: "Rejected" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_completed_expert', 'label' => esc_html__('Pattern: "Completed" (Installment - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_installment_patterns_admin_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Installment Inquiry (Admin)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_pattern_installment_new_admin', 'label' => esc_html__('Pattern: "New" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_pending_admin', 'label' => esc_html__('Pattern: "Pending Review" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_user_confirmed_admin', 'label' => esc_html__('Pattern: "Approved and Referred" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_referred_admin', 'label' => esc_html__('Pattern: "Referred to Expert" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_approved_admin', 'label' => esc_html__('Pattern: "Approved" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_rejected_admin', 'label' => esc_html__('Pattern: "Rejected" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_failed_admin', 'label' => esc_html__('Pattern: "Inquiry Failed" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_installment_completed_admin', 'label' => esc_html__('Pattern: "Completed" (Installment - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_cash_patterns_customer_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Inquiry (Customer)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_pattern_cash_new_customer', 'label' => esc_html__('Pattern: "New" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_referred_customer', 'label' => esc_html__('Pattern: "Referred to Expert" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_in_progress_customer', 'label' => esc_html__('Pattern: "In Progress" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_follow_up_scheduled_customer', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_meeting_scheduled_customer', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_awaiting_downpayment_customer', 'label' => esc_html__('Pattern: "Awaiting Down Payment" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_downpayment_received_customer', 'label' => esc_html__('Pattern: "Down Payment Received" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Down Payment Amount', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_awaiting_documents_customer', 'label' => esc_html__('Pattern: "Awaiting Documents" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_approved_customer', 'label' => esc_html__('Pattern: "Approved" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Down Payment Amount', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_rejected_customer', 'label' => esc_html__('Pattern: "Rejected" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_completed_customer', 'label' => esc_html__('Pattern: "Completed" (Cash - Customer)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_cash_patterns_expert_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Inquiry (Expert)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_pattern_cash_new_expert', 'label' => esc_html__('Pattern: "New" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_referred_expert', 'label' => esc_html__('Pattern: "Referred to Expert" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_in_progress_expert', 'label' => esc_html__('Pattern: "In Progress" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_follow_up_scheduled_expert', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_meeting_scheduled_expert', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_awaiting_downpayment_expert', 'label' => esc_html__('Pattern: "Awaiting Down Payment" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_downpayment_received_expert', 'label' => esc_html__('Pattern: "Down Payment Received" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_awaiting_documents_expert', 'label' => esc_html__('Pattern: "Awaiting Documents" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_approved_expert', 'label' => esc_html__('Pattern: "Approved" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_rejected_expert', 'label' => esc_html__('Pattern: "Rejected" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_completed_expert', 'label' => esc_html__('Pattern: "Completed" (Cash - Expert)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_cash_patterns_admin_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Inquiry (Admin)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'sms_pattern_cash_new_admin', 'label' => esc_html__('Pattern: "New" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_referred_admin', 'label' => esc_html__('Pattern: "Referred to Expert" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_in_progress_admin', 'label' => esc_html__('Pattern: "In Progress" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_follow_up_scheduled_admin', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_meeting_scheduled_admin', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_awaiting_downpayment_admin', 'label' => esc_html__('Pattern: "Awaiting Down Payment" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_downpayment_received_admin', 'label' => esc_html__('Pattern: "Down Payment Received" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_awaiting_documents_admin', 'label' => esc_html__('Pattern: "Awaiting Documents" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_approved_admin', 'label' => esc_html__('Pattern: "Approved" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_rejected_admin', 'label' => esc_html__('Pattern: "Rejected" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                            ['name' => 'sms_pattern_cash_completed_admin', 'label' => esc_html__('Pattern: "Completed" (Cash - Admin)', 'autopuzzle'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_scheduled_section' => [
                        'title' => esc_html__('Scheduled SMS', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'scheduled_sms_enabled', 'label' => esc_html__('Enable Scheduled SMS', 'autopuzzle'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('Allow scheduling SMS messages for future delivery.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_sms_bulk_section' => [
                        'title' => esc_html__('Bulk SMS', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'bulk_sms_limit', 'label' => esc_html__('Maximum Recipients per Bulk Send', 'autopuzzle'), 'type' => 'number', 'default' => '100', 'desc' => esc_html__('Maximum number of recipients allowed in a single bulk send operation.', 'autopuzzle')],
                            ['name' => 'bulk_sms_recipient_filter', 'label' => esc_html__('Recipient Filter Options', 'autopuzzle'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('Allow filtering recipients by user role (customers, experts, admins).', 'autopuzzle')],
                        ]
                    ]
                ]
            ],
            'telegram' => [
                'title' => esc_html__('Telegram', 'autopuzzle'),
                'icon' => 'fab fa-telegram',
                'sections' => [
                    'autopuzzle_telegram_api_section' => [
                        'title' => esc_html__('Telegram Bot Configuration', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_bot_token', 'label' => esc_html__('Bot Token', 'autopuzzle'), 'type' => 'text', 'desc' => esc_html__('Get your bot token from @BotFather on Telegram.', 'autopuzzle')],
                            ['name' => 'telegram_chat_ids', 'label' => esc_html__('Default Chat IDs', 'autopuzzle'), 'type' => 'textarea', 'desc' => esc_html__('Comma-separated chat IDs. These will be used for bulk notifications and meeting reminders.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_settings_section' => [
                        'title' => esc_html__('Telegram Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_enabled', 'label' => esc_html__('Enable Telegram Notifications', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                        ]
                    ],
                    'autopuzzle_telegram_messages_section' => [
                        'title' => esc_html__('Telegram Message Templates', 'autopuzzle'),
                        'desc' => esc_html__('Configure message templates for Telegram notifications. Use variables: {customer_name}, {car_name}, {date}, {time}, {reason}, {expert_name}, {customer_mobile}, {amount}.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_new_inquiry', 'label' => esc_html__('Message: New Inquiry for Admin', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('New inquiry received from {customer_name} for {car_name}', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_meeting_reminder', 'label' => esc_html__('Message: Meeting Reminder', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Reminder: You have a meeting scheduled for {date} at {time}. Car: {car_name}', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {date}, {time}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_installment_messages_customer_section' => [
                        'title' => esc_html__('Telegram Messages - Installment Inquiry (Customer)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_installment_new_customer', 'label' => esc_html__('Message: New (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_pending_customer', 'label' => esc_html__('Message: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_user_confirmed_customer', 'label' => esc_html__('Message: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_more_docs_customer', 'label' => esc_html__('Message: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_rejected_customer', 'label' => esc_html__('Message: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {reason}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_failed_customer', 'label' => esc_html__('Message: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_in_progress_customer', 'label' => esc_html__('Message: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_approved_customer', 'label' => esc_html__('Message: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_completed_customer', 'label' => esc_html__('Message: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_installment_messages_expert_section' => [
                        'title' => esc_html__('Telegram Messages - Installment Inquiry (Expert)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_installment_new_expert', 'label' => esc_html__('Message: New (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('New installment inquiry has been created. Customer: {customer_name}, Car: {car_name}', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {expert_name}, a new inquiry has been referred to you. Customer: {customer_name} ({customer_mobile}), Car: {car_name}', 'autopuzzle'), 'desc' => esc_html__('Variables: {expert_name}, {customer_name}, {customer_mobile}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_in_progress_expert', 'label' => esc_html__('Message: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) is now in progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Follow-up scheduled for {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Meeting scheduled for {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Awaiting documents from {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_approved_expert', 'label' => esc_html__('Message: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) has been approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_rejected_expert', 'label' => esc_html__('Message: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) has been rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_completed_expert', 'label' => esc_html__('Message: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) has been completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_installment_messages_admin_section' => [
                        'title' => esc_html__('Telegram Messages - Installment Inquiry (Admin)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_installment_new_admin', 'label' => esc_html__('Message: New (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('New installment inquiry: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_pending_admin', 'label' => esc_html__('Message: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry pending review: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_user_confirmed_admin', 'label' => esc_html__('Message: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry approved and referred: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry referred to expert: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_approved_admin', 'label' => esc_html__('Message: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry approved: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_rejected_admin', 'label' => esc_html__('Message: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry rejected: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_failed_admin', 'label' => esc_html__('Message: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry failed: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_installment_completed_admin', 'label' => esc_html__('Message: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry completed: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_cash_messages_customer_section' => [
                        'title' => esc_html__('Telegram Messages - Cash Inquiry (Customer)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_cash_new_customer', 'label' => esc_html__('Message: New (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_in_progress_customer', 'label' => esc_html__('Message: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_awaiting_downpayment_customer', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_downpayment_received_customer', 'label' => esc_html__('Message: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_approved_customer', 'label' => esc_html__('Message: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved. Down payment: {amount} Toman', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_rejected_customer', 'label' => esc_html__('Message: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected. Reason: {reason}', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {reason}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_completed_customer', 'label' => esc_html__('Message: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_cash_messages_expert_section' => [
                        'title' => esc_html__('Telegram Messages - Cash Inquiry (Expert)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_cash_new_expert', 'label' => esc_html__('Message: New (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('New cash inquiry: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {expert_name}, a new cash inquiry has been referred to you. Customer: {customer_name} ({customer_mobile}), Car: {car_name}', 'autopuzzle'), 'desc' => esc_html__('Variables: {expert_name}, {customer_name}, {customer_mobile}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_in_progress_expert', 'label' => esc_html__('Message: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) is now in progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Follow-up scheduled for {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Meeting scheduled for {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_awaiting_downpayment_expert', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Awaiting down payment from {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_downpayment_received_expert', 'label' => esc_html__('Message: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Down payment received from {customer_name} ({car_name}): {amount} Toman', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Awaiting documents from {customer_name} ({car_name}).', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_approved_expert', 'label' => esc_html__('Message: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) has been approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_rejected_expert', 'label' => esc_html__('Message: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) has been rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_completed_expert', 'label' => esc_html__('Message: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) has been completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_telegram_cash_messages_admin_section' => [
                        'title' => esc_html__('Telegram Messages - Cash Inquiry (Admin)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'telegram_message_cash_new_admin', 'label' => esc_html__('Message: New (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('New cash inquiry: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry referred to expert: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_in_progress_admin', 'label' => esc_html__('Message: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry in progress: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_follow_up_scheduled_admin', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Follow-up scheduled: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_meeting_scheduled_admin', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Meeting scheduled: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_awaiting_downpayment_admin', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Awaiting down payment: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_downpayment_received_admin', 'label' => esc_html__('Message: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Down payment received: {customer_name} ({car_name}), Amount: {amount} Toman', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_awaiting_documents_admin', 'label' => esc_html__('Message: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Awaiting documents: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_approved_admin', 'label' => esc_html__('Message: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry approved: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_rejected_admin', 'label' => esc_html__('Message: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry rejected: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'telegram_message_cash_completed_admin', 'label' => esc_html__('Message: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry completed: {customer_name} ({car_name})', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                ]
            ],
            'email' => [
                'title' => esc_html__('Email', 'autopuzzle'),
                'icon' => 'fas fa-envelope',
                'sections' => [
                    'autopuzzle_email_smtp_section' => [
                        'title' => esc_html__('SMTP Configuration', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_use_smtp', 'label' => esc_html__('Use SMTP', 'autopuzzle'), 'type' => 'switch', 'default' => '0', 'desc' => esc_html__('If disabled, WordPress default wp_mail will be used.', 'autopuzzle')],
                            ['name' => 'email_smtp_host', 'label' => esc_html__('SMTP Host', 'autopuzzle'), 'type' => 'text', 'desc' => esc_html__('e.g., smtp.gmail.com', 'autopuzzle')],
                            ['name' => 'email_smtp_port', 'label' => esc_html__('SMTP Port', 'autopuzzle'), 'type' => 'number', 'default' => '587', 'desc' => esc_html__('Common ports: 587 (TLS), 465 (SSL)', 'autopuzzle')],
                            ['name' => 'email_smtp_username', 'label' => esc_html__('SMTP Username', 'autopuzzle'), 'type' => 'text'],
                            ['name' => 'email_smtp_password', 'label' => esc_html__('SMTP Password', 'autopuzzle'), 'type' => 'password'],
                            ['name' => 'email_smtp_encryption', 'label' => esc_html__('Encryption', 'autopuzzle'), 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL'], 'default' => 'tls'],
                        ]
                    ],
                    'autopuzzle_email_from_section' => [
                        'title' => esc_html__('Email From Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_from_email', 'label' => esc_html__('From Email', 'autopuzzle'), 'type' => 'text', 'desc' => esc_html__('Default sender email address.', 'autopuzzle')],
                            ['name' => 'email_from_name', 'label' => esc_html__('From Name', 'autopuzzle'), 'type' => 'text', 'desc' => esc_html__('Default sender name.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_settings_section' => [
                        'title' => esc_html__('Email Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_enabled', 'label' => esc_html__('Enable Email Notifications', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                        ]
                    ],
                    'autopuzzle_email_messages_section' => [
                        'title' => esc_html__('Email Message Templates', 'autopuzzle'),
                        'desc' => esc_html__('Configure email message templates. Use variables: {customer_name}, {car_name}, {date}, {time}, {reason}, {expert_name}, {customer_mobile}, {amount}.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_new_inquiry', 'label' => esc_html__('Subject: New Inquiry for Admin', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('New Inquiry Received', 'autopuzzle')],
                            ['name' => 'email_message_new_inquiry', 'label' => esc_html__('Message: New Inquiry for Admin', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('A new inquiry has been received from {customer_name} for {car_name}.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_meeting_reminder', 'label' => esc_html__('Subject: Meeting Reminder', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Meeting Reminder', 'autopuzzle')],
                            ['name' => 'email_message_meeting_reminder', 'label' => esc_html__('Message: Meeting Reminder', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, this is a reminder that you have a meeting scheduled for {date} at {time} regarding {car_name}.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {date}, {time}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_installment_messages_customer_section' => [
                        'title' => esc_html__('Email Messages - Installment Inquiry (Customer)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_installment_new_customer', 'label' => esc_html__('Subject: New (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - New', 'autopuzzle')],
                            ['name' => 'email_message_installment_new_customer', 'label' => esc_html__('Message: New (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_pending_customer', 'label' => esc_html__('Subject: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Pending Review', 'autopuzzle')],
                            ['name' => 'email_message_installment_pending_customer', 'label' => esc_html__('Message: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_user_confirmed_customer', 'label' => esc_html__('Subject: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved and Referred', 'autopuzzle')],
                            ['name' => 'email_message_installment_user_confirmed_customer', 'label' => esc_html__('Message: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_more_docs_customer', 'label' => esc_html__('Subject: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - More Documents Required', 'autopuzzle')],
                            ['name' => 'email_message_installment_more_docs_customer', 'label' => esc_html__('Message: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_rejected_customer', 'label' => esc_html__('Subject: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Rejected', 'autopuzzle')],
                            ['name' => 'email_message_installment_rejected_customer', 'label' => esc_html__('Message: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_failed_customer', 'label' => esc_html__('Subject: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Inquiry Failed', 'autopuzzle')],
                            ['name' => 'email_message_installment_failed_customer', 'label' => esc_html__('Message: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_referred_customer', 'label' => esc_html__('Subject: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Referred to Expert', 'autopuzzle')],
                            ['name' => 'email_message_installment_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_in_progress_customer', 'label' => esc_html__('Subject: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - In Progress', 'autopuzzle')],
                            ['name' => 'email_message_installment_in_progress_customer', 'label' => esc_html__('Message: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_follow_up_scheduled_customer', 'label' => esc_html__('Subject: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Follow Up Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_installment_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_meeting_scheduled_customer', 'label' => esc_html__('Subject: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Meeting Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_installment_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_awaiting_documents_customer', 'label' => esc_html__('Subject: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Awaiting Documents', 'autopuzzle')],
                            ['name' => 'email_message_installment_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_approved_customer', 'label' => esc_html__('Subject: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved', 'autopuzzle')],
                            ['name' => 'email_message_installment_approved_customer', 'label' => esc_html__('Message: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_completed_customer', 'label' => esc_html__('Subject: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Completed', 'autopuzzle')],
                            ['name' => 'email_message_installment_completed_customer', 'label' => esc_html__('Message: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_installment_messages_expert_section' => [
                        'title' => esc_html__('Email Messages - Installment Inquiry (Expert)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_installment_new_expert', 'label' => esc_html__('Subject: New (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - New', 'autopuzzle')],
                            ['name' => 'email_message_installment_new_expert', 'label' => esc_html__('Message: New (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_pending_expert', 'label' => esc_html__('Subject: Pending Review (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Pending Review', 'autopuzzle')],
                            ['name' => 'email_message_installment_pending_expert', 'label' => esc_html__('Message: Pending Review (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_user_confirmed_expert', 'label' => esc_html__('Subject: Approved and Referred (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved and Referred', 'autopuzzle')],
                            ['name' => 'email_message_installment_user_confirmed_expert', 'label' => esc_html__('Message: Approved and Referred (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_more_docs_expert', 'label' => esc_html__('Subject: More Documents Required (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - More Documents Required', 'autopuzzle')],
                            ['name' => 'email_message_installment_more_docs_expert', 'label' => esc_html__('Message: More Documents Required (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_rejected_expert', 'label' => esc_html__('Subject: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Rejected', 'autopuzzle')],
                            ['name' => 'email_message_installment_rejected_expert', 'label' => esc_html__('Message: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_failed_expert', 'label' => esc_html__('Subject: Inquiry Failed (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Inquiry Failed', 'autopuzzle')],
                            ['name' => 'email_message_installment_failed_expert', 'label' => esc_html__('Message: Inquiry Failed (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_referred_expert', 'label' => esc_html__('Subject: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Referred to Expert', 'autopuzzle')],
                            ['name' => 'email_message_installment_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_in_progress_expert', 'label' => esc_html__('Subject: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - In Progress', 'autopuzzle')],
                            ['name' => 'email_message_installment_in_progress_expert', 'label' => esc_html__('Message: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_follow_up_scheduled_expert', 'label' => esc_html__('Subject: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Follow Up Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_installment_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_meeting_scheduled_expert', 'label' => esc_html__('Subject: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Meeting Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_installment_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_awaiting_documents_expert', 'label' => esc_html__('Subject: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Awaiting Documents', 'autopuzzle')],
                            ['name' => 'email_message_installment_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_approved_expert', 'label' => esc_html__('Subject: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved', 'autopuzzle')],
                            ['name' => 'email_message_installment_approved_expert', 'label' => esc_html__('Message: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_completed_expert', 'label' => esc_html__('Subject: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Completed', 'autopuzzle')],
                            ['name' => 'email_message_installment_completed_expert', 'label' => esc_html__('Message: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_installment_messages_admin_section' => [
                        'title' => esc_html__('Email Messages - Installment Inquiry (Admin)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_installment_new_admin', 'label' => esc_html__('Subject: New (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - New', 'autopuzzle')],
                            ['name' => 'email_message_installment_new_admin', 'label' => esc_html__('Message: New (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_pending_admin', 'label' => esc_html__('Subject: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Pending Review', 'autopuzzle')],
                            ['name' => 'email_message_installment_pending_admin', 'label' => esc_html__('Message: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_user_confirmed_admin', 'label' => esc_html__('Subject: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved and Referred', 'autopuzzle')],
                            ['name' => 'email_message_installment_user_confirmed_admin', 'label' => esc_html__('Message: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_more_docs_admin', 'label' => esc_html__('Subject: More Documents Required (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - More Documents Required', 'autopuzzle')],
                            ['name' => 'email_message_installment_more_docs_admin', 'label' => esc_html__('Message: More Documents Required (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_rejected_admin', 'label' => esc_html__('Subject: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Rejected', 'autopuzzle')],
                            ['name' => 'email_message_installment_rejected_admin', 'label' => esc_html__('Message: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_failed_admin', 'label' => esc_html__('Subject: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Inquiry Failed', 'autopuzzle')],
                            ['name' => 'email_message_installment_failed_admin', 'label' => esc_html__('Message: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_referred_admin', 'label' => esc_html__('Subject: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Referred to Expert', 'autopuzzle')],
                            ['name' => 'email_message_installment_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_in_progress_admin', 'label' => esc_html__('Subject: In Progress (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - In Progress', 'autopuzzle')],
                            ['name' => 'email_message_installment_in_progress_admin', 'label' => esc_html__('Message: In Progress (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_follow_up_scheduled_admin', 'label' => esc_html__('Subject: Follow Up Scheduled (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Follow Up Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_installment_follow_up_scheduled_admin', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_meeting_scheduled_admin', 'label' => esc_html__('Subject: Meeting Scheduled (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Meeting Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_installment_meeting_scheduled_admin', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_awaiting_documents_admin', 'label' => esc_html__('Subject: Awaiting Documents (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Awaiting Documents', 'autopuzzle')],
                            ['name' => 'email_message_installment_awaiting_documents_admin', 'label' => esc_html__('Message: Awaiting Documents (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_approved_admin', 'label' => esc_html__('Subject: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved', 'autopuzzle')],
                            ['name' => 'email_message_installment_approved_admin', 'label' => esc_html__('Message: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_installment_completed_admin', 'label' => esc_html__('Subject: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Completed', 'autopuzzle')],
                            ['name' => 'email_message_installment_completed_admin', 'label' => esc_html__('Message: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_cash_messages_customer_section' => [
                        'title' => esc_html__('Email Messages - Cash Inquiry (Customer)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_cash_new_customer', 'label' => esc_html__('Subject: New (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - New', 'autopuzzle')],
                            ['name' => 'email_message_cash_new_customer', 'label' => esc_html__('Message: New (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_referred_customer', 'label' => esc_html__('Subject: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Referred to Expert', 'autopuzzle')],
                            ['name' => 'email_message_cash_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_in_progress_customer', 'label' => esc_html__('Subject: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - In Progress', 'autopuzzle')],
                            ['name' => 'email_message_cash_in_progress_customer', 'label' => esc_html__('Message: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_follow_up_scheduled_customer', 'label' => esc_html__('Subject: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Follow Up Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_cash_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_meeting_scheduled_customer', 'label' => esc_html__('Subject: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Meeting Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_cash_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_awaiting_downpayment_customer', 'label' => esc_html__('Subject: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Down Payment', 'autopuzzle')],
                            ['name' => 'email_message_cash_awaiting_downpayment_customer', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_downpayment_received_customer', 'label' => esc_html__('Subject: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Down Payment Received', 'autopuzzle')],
                            ['name' => 'email_message_cash_downpayment_received_customer', 'label' => esc_html__('Message: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_awaiting_documents_customer', 'label' => esc_html__('Subject: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Documents', 'autopuzzle')],
                            ['name' => 'email_message_cash_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_approved_customer', 'label' => esc_html__('Subject: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Approved', 'autopuzzle')],
                            ['name' => 'email_message_cash_approved_customer', 'label' => esc_html__('Message: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_rejected_customer', 'label' => esc_html__('Subject: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Rejected', 'autopuzzle')],
                            ['name' => 'email_message_cash_rejected_customer', 'label' => esc_html__('Message: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_completed_customer', 'label' => esc_html__('Subject: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Completed', 'autopuzzle')],
                            ['name' => 'email_message_cash_completed_customer', 'label' => esc_html__('Message: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_cash_messages_expert_section' => [
                        'title' => esc_html__('Email Messages - Cash Inquiry (Expert)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_cash_new_expert', 'label' => esc_html__('Subject: New (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - New', 'autopuzzle')],
                            ['name' => 'email_message_cash_new_expert', 'label' => esc_html__('Message: New (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_referred_expert', 'label' => esc_html__('Subject: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Referred to Expert', 'autopuzzle')],
                            ['name' => 'email_message_cash_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_in_progress_expert', 'label' => esc_html__('Subject: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - In Progress', 'autopuzzle')],
                            ['name' => 'email_message_cash_in_progress_expert', 'label' => esc_html__('Message: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_follow_up_scheduled_expert', 'label' => esc_html__('Subject: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Follow Up Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_cash_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_meeting_scheduled_expert', 'label' => esc_html__('Subject: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Meeting Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_cash_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_awaiting_downpayment_expert', 'label' => esc_html__('Subject: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Down Payment', 'autopuzzle')],
                            ['name' => 'email_message_cash_awaiting_downpayment_expert', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_downpayment_received_expert', 'label' => esc_html__('Subject: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Down Payment Received', 'autopuzzle')],
                            ['name' => 'email_message_cash_downpayment_received_expert', 'label' => esc_html__('Message: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_awaiting_documents_expert', 'label' => esc_html__('Subject: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Documents', 'autopuzzle')],
                            ['name' => 'email_message_cash_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_approved_expert', 'label' => esc_html__('Subject: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Approved', 'autopuzzle')],
                            ['name' => 'email_message_cash_approved_expert', 'label' => esc_html__('Message: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_rejected_expert', 'label' => esc_html__('Subject: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Rejected', 'autopuzzle')],
                            ['name' => 'email_message_cash_rejected_expert', 'label' => esc_html__('Message: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_completed_expert', 'label' => esc_html__('Subject: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Completed', 'autopuzzle')],
                            ['name' => 'email_message_cash_completed_expert', 'label' => esc_html__('Message: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_email_cash_messages_admin_section' => [
                        'title' => esc_html__('Email Messages - Cash Inquiry (Admin)', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'email_subject_cash_new_admin', 'label' => esc_html__('Subject: New (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - New', 'autopuzzle')],
                            ['name' => 'email_message_cash_new_admin', 'label' => esc_html__('Message: New (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_referred_admin', 'label' => esc_html__('Subject: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Referred to Expert', 'autopuzzle')],
                            ['name' => 'email_message_cash_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_in_progress_admin', 'label' => esc_html__('Subject: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - In Progress', 'autopuzzle')],
                            ['name' => 'email_message_cash_in_progress_admin', 'label' => esc_html__('Message: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_follow_up_scheduled_admin', 'label' => esc_html__('Subject: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Follow Up Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_cash_follow_up_scheduled_admin', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_meeting_scheduled_admin', 'label' => esc_html__('Subject: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Meeting Scheduled', 'autopuzzle')],
                            ['name' => 'email_message_cash_meeting_scheduled_admin', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_awaiting_downpayment_admin', 'label' => esc_html__('Subject: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Down Payment', 'autopuzzle')],
                            ['name' => 'email_message_cash_awaiting_downpayment_admin', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_downpayment_received_admin', 'label' => esc_html__('Subject: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Down Payment Received', 'autopuzzle')],
                            ['name' => 'email_message_cash_downpayment_received_admin', 'label' => esc_html__('Message: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_awaiting_documents_admin', 'label' => esc_html__('Subject: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Documents', 'autopuzzle')],
                            ['name' => 'email_message_cash_awaiting_documents_admin', 'label' => esc_html__('Message: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_approved_admin', 'label' => esc_html__('Subject: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Approved', 'autopuzzle')],
                            ['name' => 'email_message_cash_approved_admin', 'label' => esc_html__('Message: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_rejected_admin', 'label' => esc_html__('Subject: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Rejected', 'autopuzzle')],
                            ['name' => 'email_message_cash_rejected_admin', 'label' => esc_html__('Message: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                            ['name' => 'email_subject_cash_completed_admin', 'label' => esc_html__('Subject: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Completed', 'autopuzzle')],
                            ['name' => 'email_message_cash_completed_admin', 'label' => esc_html__('Message: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'autopuzzle'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'autopuzzle')],
                        ]
                    ],
                ]
            ],
            'cash_inquiry' => [
                'title' => esc_html__('Cash Request', 'autopuzzle'),
                'icon' => 'fas fa-money-bill-wave',
                'sections' => [
                    'autopuzzle_cash_inquiry_reasons_section' => [
                        'title' => esc_html__('Cash Request Process Settings', 'autopuzzle'),
                        'fields' => [
                             ['name' => 'cash_inquiry_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'autopuzzle'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a list to the admin when rejecting a request.', 'autopuzzle')],
                        ]
                    ]
                ]
            ],
            'installment' => [
                'title' => esc_html__('Installment Request', 'autopuzzle'),
                'icon' => 'fas fa-car',
                'sections' => [
                    'autopuzzle_front_messages_section' => [
                        'title' => esc_html__('Frontend Messages', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'msg_price_disclaimer',
                                'label' => esc_html__('Price Disclaimer', 'autopuzzle'),
                                'type' => 'textarea',
                                'default' => esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final approval.', 'autopuzzle')
                            ],
                            [
                                'name' => 'msg_waiting_review',
                                'label' => esc_html__('Waiting Review Message', 'autopuzzle'),
                                'type' => 'textarea',
                                'default' => esc_html__('Your request has been submitted. The result will be announced within the next 24 hours.', 'autopuzzle')
                            ],
                            [
                                'name' => 'msg_after_approval',
                                'label' => esc_html__('Post-Approval Message', 'autopuzzle'),
                                'type' => 'textarea',
                                'default' => esc_html__('Your documents have been approved. Our team will contact you shortly for next steps.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_installment_rejection_reasons_section' => [
                        'title' => esc_html__('Installment Rejection Reasons', 'autopuzzle'),
                        'fields' => [
                             ['name' => 'installment_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'autopuzzle'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a list to the admin when rejecting an installment request.', 'autopuzzle')],
                        ]
                    ]
                ]
            ],
            'experts' => [
                'title' => esc_html__('Experts', 'autopuzzle'),
                'icon' => 'fas fa-users',
                'sections' => [
                    'autopuzzle_experts_list_section' => [
                        'title' => esc_html__('Expert Management', 'autopuzzle'),
                        'desc' => wp_kses_post(__('The system automatically identifies all users with the <strong>"AutoPuzzle Expert"</strong> role. Requests are assigned to them in a round-robin fashion.<br>To add a new expert, simply create a new user from the <strong>Users > Add New</strong> menu with the "AutoPuzzle Expert" role and enter their mobile number in their profile.', 'autopuzzle')),
                        'fields' => [] // This section is for display only
                    ]
                ]
            ],
            'finotex' => [
                'title' => esc_html__('Finotex', 'autopuzzle'),
                'icon' => 'fas fa-university',
                'sections' => [
                    'autopuzzle_finotex_credentials_section' => [
                        'title' => esc_html__('Finnotech API Credentials', 'autopuzzle'),
                        'desc' => esc_html__('These credentials are used for all Finnotech API services including Credit Risk, Credit Score, Collaterals, and Cheque Color inquiries.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'finotex_username', 'label' => esc_html__('Finnotech Client ID', 'autopuzzle'), 'type' => 'password', 'desc' => esc_html__('Client ID provided by Finnotech. Stored securely encrypted.', 'autopuzzle')],
                            ['name' => 'finotex_password', 'label' => esc_html__('Finnotech API Key', 'autopuzzle'), 'type' => 'password', 'desc' => esc_html__('API Key (Access Token) provided by Finnotech. Stored securely encrypted.', 'autopuzzle')],
                            ['name' => 'finotex_enabled', 'label' => esc_html__('Enable Legacy Finotex API', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('Enable the legacy Finotex cheque color inquiry API (deprecated, use Finnotech API Services instead).', 'autopuzzle'), 'default' => '0'],
                        ]
                    ],
                    'autopuzzle_finnotech_apis_section' => [
                        'title' => esc_html__('Finnotech API Services', 'autopuzzle'),
                        'desc' => esc_html__('Enable or disable individual Finnotech API services. When disabled, the data will be hidden from reports but preserved in the database.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'finnotech_credit_risk_enabled', 'label' => esc_html__('Enable Credit Risk Inquiry', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('Enable banking risk inquiry for individuals (ریسک بانکی شخص).', 'autopuzzle')],
                            ['name' => 'finnotech_credit_score_enabled', 'label' => esc_html__('Enable Credit Score Report', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('Enable credit score decrease reasons inquiry (دلایل کاهش امتیاز اعتباری).', 'autopuzzle')],
                            ['name' => 'finnotech_collaterals_enabled', 'label' => esc_html__('Enable Collaterals Inquiry', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('Enable contracts summary inquiry (وام‌ها/تسهیلات).', 'autopuzzle')],
                            ['name' => 'finnotech_cheque_color_enabled', 'label' => esc_html__('Enable Cheque Color Inquiry', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('Enable Sadad cheque status inquiry (وضعیت چک‌های صیادی).', 'autopuzzle')],
                            ['name' => 'finnotech_show_inquiry_structures', 'label' => esc_html__('Show Finnotech Inquiry Structures in Reports', 'autopuzzle'), 'type' => 'switch', 'desc' => esc_html__('Display Finnotech inquiry sections (even when empty) in manager and customer reports.', 'autopuzzle'), 'default' => '1'],
                        ]
                    ]
                ]
            ],
            'meetings' => [
                'title' => esc_html__('Meetings & Calendar', 'autopuzzle'),
                'icon'  => 'fas fa-calendar-alt',
                'sections' => [
                    'autopuzzle_meetings_general' => [
                        'title' => esc_html__('General Settings', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'meetings_enabled', 'label' => esc_html__('Enable Meetings', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'meetings_start_hour', 'label' => esc_html__('Workday Start (HH:MM)', 'autopuzzle'), 'type' => 'text', 'default' => '10:00'],
                            ['name' => 'meetings_end_hour',   'label' => esc_html__('Workday End (HH:MM)', 'autopuzzle'),   'type' => 'text', 'default' => '20:00'],
                            ['name' => 'meetings_slot_minutes','label' => esc_html__('Slot Duration (minutes)', 'autopuzzle'), 'type' => 'number', 'default' => '30'],
                            ['name' => 'meetings_excluded_days', 'label' => esc_html__('Excluded Days', 'autopuzzle'), 'type' => 'multiselect', 'default' => [], 'desc' => esc_html__('Select days when meetings cannot be scheduled', 'autopuzzle')],
                        ]
                    ]
                ]
            ],
            'documents' => [
                'title' => esc_html__('Documents', 'autopuzzle'),
                'icon' => 'fas fa-file-alt',
                'sections' => [
                    'autopuzzle_customer_documents_section' => [
                        'title' => esc_html__('Customer Documents', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'customer_required_documents', 'label' => esc_html__('Required Documents List', 'autopuzzle'), 'type' => 'textarea', 'desc' => esc_html__('Enter one document name per line. These documents will be shown to customers in their profile to upload.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_document_rejection_reasons_section' => [
                        'title' => esc_html__('Document Rejection Reasons', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'document_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'autopuzzle'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a dropdown list when rejecting a document.', 'autopuzzle')],
                        ]
                    ]
                ]
            ],
            'availability' => [
                'title' => esc_html__('Availability', 'autopuzzle'),
                'icon' => 'fas fa-box-open',
                'sections' => [
                    'autopuzzle_availability_messages_section' => [
                        'title' => esc_html__('Unavailable Product Messages', 'autopuzzle'),
                        'desc' => esc_html__('Configure messages shown to customers when products are unavailable for different purchase methods.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'unavailable_installment_message',
                                'label' => esc_html__('Installment Unavailable Message', 'autopuzzle'),
                                'type' => 'textarea',
                                'default' => esc_html__('This product is currently unavailable for installment purchase.', 'autopuzzle'),
                                'desc' => esc_html__('Message shown when installment price is not available (0 or empty).', 'autopuzzle')
                            ],
                            [
                                'name' => 'unavailable_cash_message',
                                'label' => esc_html__('Cash Unavailable Message', 'autopuzzle'),
                                'type' => 'textarea',
                                'default' => esc_html__('This product is currently unavailable for cash purchase.', 'autopuzzle'),
                                'desc' => esc_html__('Message shown when cash price is not available (0 or empty).', 'autopuzzle')
                            ],
                            [
                                'name' => 'unavailable_product_message',
                                'label' => esc_html__('Product Unavailable Message (Both)', 'autopuzzle'),
                                'type' => 'textarea',
                                'default' => esc_html__('This product is currently unavailable for purchase.', 'autopuzzle'),
                                'desc' => esc_html__('Message shown when product status is set to "Unavailable" or both prices are unavailable.', 'autopuzzle')
                            ],
                        ]
                    ]
                ]
            ],
            'notifications' => [
                'title' => esc_html__('Notifications', 'autopuzzle'),
                'icon' => 'fas fa-bell',
                'sections' => [
                    'autopuzzle_global_channel_toggle_section' => [
                        'title' => esc_html__('Global Channel Settings', 'autopuzzle'),
                        'desc' => esc_html__('Enable or disable notification channels globally. When disabled, the channel will be disabled for all notification types below.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'notifications_sms_global_enabled', 'label' => esc_html__('Enable SMS Notifications (Global)', 'autopuzzle'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('When disabled, all SMS notifications will be turned off.', 'autopuzzle')],
                            ['name' => 'notifications_telegram_global_enabled', 'label' => esc_html__('Enable Telegram Notifications (Global)', 'autopuzzle'), 'type' => 'switch', 'default' => '0', 'desc' => esc_html__('When disabled, all Telegram notifications will be turned off.', 'autopuzzle')],
                            ['name' => 'notifications_email_global_enabled', 'label' => esc_html__('Enable Email Notifications (Global)', 'autopuzzle'), 'type' => 'switch', 'default' => '0', 'desc' => esc_html__('When disabled, all Email notifications will be turned off.', 'autopuzzle')],
                            ['name' => 'notifications_inapp_global_enabled', 'label' => esc_html__('Enable In-App Notifications (Global)', 'autopuzzle'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('When disabled, all in-app notifications will be turned off.', 'autopuzzle')],
                        ]
                    ],
                    'autopuzzle_meeting_reminders_section' => [
                        'title' => esc_html__('Meeting Reminders', 'autopuzzle'),
                        'desc' => esc_html__('Configure automatic reminders sent before scheduled meetings.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'meeting_reminder_hours', 'label' => esc_html__('Remind Hours Before Meeting', 'autopuzzle'), 'type' => 'text', 'default' => '2', 'desc' => esc_html__('Comma-separated hours (e.g., 2,6,24). Reminders will be sent these hours before the meeting.', 'autopuzzle')],
                            ['name' => 'meeting_reminder_days', 'label' => esc_html__('Remind Days Before Meeting', 'autopuzzle'), 'type' => 'text', 'default' => '1', 'desc' => esc_html__('Comma-separated days (e.g., 1,3). Reminders will be sent these days before the meeting.', 'autopuzzle')],
                            ['name' => 'meeting_reminder_sms_enabled', 'label' => esc_html__('Enable SMS Reminders', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'meeting_reminder_telegram_enabled', 'label' => esc_html__('Enable Telegram Reminders', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'meeting_reminder_email_enabled', 'label' => esc_html__('Enable Email Reminders', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'meeting_reminder_notification_enabled', 'label' => esc_html__('Enable In-App Notification Reminders', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_new_inquiry_notifications_section' => [
                        'title' => esc_html__('New Inquiry Notifications', 'autopuzzle'),
                        'desc' => esc_html__('Configure notifications sent when a new inquiry is submitted.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'new_inquiry_sms_enabled', 'label' => esc_html__('Enable SMS: New Inquiry', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'new_inquiry_telegram_enabled', 'label' => esc_html__('Enable Telegram: New Inquiry', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'new_inquiry_email_enabled', 'label' => esc_html__('Enable Email: New Inquiry', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'new_inquiry_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New Inquiry', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_installment_status_notifications_section' => [
                        'title' => esc_html__('Installment Inquiry Status Notifications - Customer', 'autopuzzle'),
                        'desc' => esc_html__('Configure which notifications to send to customers for different installment inquiry statuses.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'installment_new_customer_sms_enabled', 'label' => esc_html__('Enable SMS: New (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_new_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_customer_email_enabled', 'label' => esc_html__('Enable Email: New (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_customer_email_enabled', 'label' => esc_html__('Enable Email: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Pending Review (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_customer_email_enabled', 'label' => esc_html__('Enable Email: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved and Referred (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_more_docs_customer_sms_enabled', 'label' => esc_html__('Enable SMS: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_more_docs_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_more_docs_customer_email_enabled', 'label' => esc_html__('Enable Email: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_more_docs_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: More Documents Required (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_customer_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_customer_email_enabled', 'label' => esc_html__('Enable Email: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Inquiry Failed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_customer_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_customer_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_customer_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_customer_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_customer_email_enabled', 'label' => esc_html__('Enable Email: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_customer_email_enabled', 'label' => esc_html__('Enable Email: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Installment - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_installment_status_notifications_expert_section' => [
                        'title' => esc_html__('Installment Inquiry Status Notifications - Expert', 'autopuzzle'),
                        'desc' => esc_html__('Configure which notifications to send to experts for different installment inquiry statuses.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'installment_new_expert_sms_enabled', 'label' => esc_html__('Enable SMS: New (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_new_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_expert_email_enabled', 'label' => esc_html__('Enable Email: New (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_expert_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_expert_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_expert_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_expert_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_expert_email_enabled', 'label' => esc_html__('Enable Email: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_expert_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_expert_email_enabled', 'label' => esc_html__('Enable Email: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Installment - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_installment_status_notifications_admin_section' => [
                        'title' => esc_html__('Installment Inquiry Status Notifications - Admin', 'autopuzzle'),
                        'desc' => esc_html__('Configure which notifications to send to admins for different installment inquiry statuses.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'installment_new_admin_sms_enabled', 'label' => esc_html__('Enable SMS: New (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_new_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_admin_email_enabled', 'label' => esc_html__('Enable Email: New (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_admin_email_enabled', 'label' => esc_html__('Enable Email: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Pending Review (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_admin_email_enabled', 'label' => esc_html__('Enable Email: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved and Referred (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_admin_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_admin_email_enabled', 'label' => esc_html__('Enable Email: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_admin_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_admin_email_enabled', 'label' => esc_html__('Enable Email: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Inquiry Failed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_admin_email_enabled', 'label' => esc_html__('Enable Email: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Installment - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_cash_status_notifications_customer_section' => [
                        'title' => esc_html__('Cash Inquiry Status Notifications - Customer', 'autopuzzle'),
                        'desc' => esc_html__('Configure which notifications to send to customers for different cash inquiry statuses.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'cash_new_customer_sms_enabled', 'label' => esc_html__('Enable SMS: New (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_new_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_customer_email_enabled', 'label' => esc_html__('Enable Email: New (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_customer_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_customer_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_customer_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_customer_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Down Payment (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_customer_email_enabled', 'label' => esc_html__('Enable Email: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Down Payment Received (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_customer_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_customer_email_enabled', 'label' => esc_html__('Enable Email: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_customer_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_customer_email_enabled', 'label' => esc_html__('Enable Email: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Cash - Customer)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_cash_status_notifications_expert_section' => [
                        'title' => esc_html__('Cash Inquiry Status Notifications - Expert', 'autopuzzle'),
                        'desc' => esc_html__('Configure which notifications to send to experts for different cash inquiry statuses.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'cash_new_expert_sms_enabled', 'label' => esc_html__('Enable SMS: New (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_new_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_expert_email_enabled', 'label' => esc_html__('Enable Email: New (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_expert_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_expert_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_expert_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_expert_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Down Payment (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_expert_email_enabled', 'label' => esc_html__('Enable Email: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Down Payment Received (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_expert_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_expert_email_enabled', 'label' => esc_html__('Enable Email: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_expert_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_expert_email_enabled', 'label' => esc_html__('Enable Email: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Cash - Expert)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'autopuzzle_cash_status_notifications_admin_section' => [
                        'title' => esc_html__('Cash Inquiry Status Notifications - Admin', 'autopuzzle'),
                        'desc' => esc_html__('Configure which notifications to send to admins for different cash inquiry statuses.', 'autopuzzle'),
                        'fields' => [
                            ['name' => 'cash_new_admin_sms_enabled', 'label' => esc_html__('Enable SMS: New (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_new_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_admin_email_enabled', 'label' => esc_html__('Enable Email: New (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_admin_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_admin_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_admin_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_admin_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_admin_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_admin_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Down Payment (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_admin_email_enabled', 'label' => esc_html__('Enable Email: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Down Payment Received (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_admin_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_admin_email_enabled', 'label' => esc_html__('Enable Email: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_admin_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_admin_email_enabled', 'label' => esc_html__('Enable Email: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Cash - Admin)', 'autopuzzle'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                ]
            ],
            // CAPTCHA/Security Tab
            'captcha' => [
                'title' => esc_html__('CAPTCHA & Security', 'autopuzzle'),
                'icon' => 'fas fa-shield-alt',
                'sections' => [
                    'autopuzzle_captcha_general_section' => [
                        'title' => esc_html__('CAPTCHA Settings', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'captcha_enabled',
                                'label' => esc_html__('Enable CAPTCHA', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '0',
                                'desc' => esc_html__('Enable CAPTCHA protection for public forms (inquiry forms, login).', 'autopuzzle')
                            ],
                            [
                                'name' => 'captcha_type',
                                'label' => esc_html__('CAPTCHA Type', 'autopuzzle'),
                                'type' => 'select',
                                'options' => [
                                    'recaptcha_v2' => esc_html__('Google reCAPTCHA v2', 'autopuzzle'),
                                    'recaptcha_v3' => esc_html__('Google reCAPTCHA v3', 'autopuzzle'),
                                    'hcaptcha' => esc_html__('hCaptcha', 'autopuzzle')
                                ],
                                'default' => 'recaptcha_v3',
                                'desc' => esc_html__('Select the CAPTCHA service to use. reCAPTCHA v3 works invisibly, v2 shows a checkbox challenge, hCaptcha is privacy-focused.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_recaptcha_v2_section' => [
                        'title' => esc_html__('Google reCAPTCHA v2 Configuration', 'autopuzzle'),
                        'desc' => esc_html__('Get your keys from https://www.google.com/recaptcha/admin', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'recaptcha_v2_site_key',
                                'label' => esc_html__('Site Key (reCAPTCHA v2)', 'autopuzzle'),
                                'type' => 'text',
                                'desc' => esc_html__('Your reCAPTCHA v2 site key.', 'autopuzzle')
                            ],
                            [
                                'name' => 'recaptcha_v2_secret_key',
                                'label' => esc_html__('Secret Key (reCAPTCHA v2)', 'autopuzzle'),
                                'type' => 'text',
                                'desc' => esc_html__('Your reCAPTCHA v2 secret key (will be encrypted).', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_recaptcha_v3_section' => [
                        'title' => esc_html__('Google reCAPTCHA v3 Configuration', 'autopuzzle'),
                        'desc' => esc_html__('Get your keys from https://www.google.com/recaptcha/admin', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'recaptcha_v3_site_key',
                                'label' => esc_html__('Site Key (reCAPTCHA v3)', 'autopuzzle'),
                                'type' => 'text',
                                'desc' => esc_html__('Your reCAPTCHA v3 site key.', 'autopuzzle')
                            ],
                            [
                                'name' => 'recaptcha_v3_secret_key',
                                'label' => esc_html__('Secret Key (reCAPTCHA v3)', 'autopuzzle'),
                                'type' => 'text',
                                'desc' => esc_html__('Your reCAPTCHA v3 secret key (will be encrypted).', 'autopuzzle')
                            ],
                            [
                                'name' => 'recaptcha_v3_score_threshold',
                                'label' => esc_html__('Score Threshold (reCAPTCHA v3)', 'autopuzzle'),
                                'type' => 'number',
                                'default' => '0.5',
                                'desc' => esc_html__('Minimum score required (0.0 to 1.0). Higher values are more strict. Recommended: 0.5', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_hcaptcha_section' => [
                        'title' => esc_html__('hCaptcha Configuration', 'autopuzzle'),
                        'desc' => esc_html__('Get your keys from https://www.hcaptcha.com/', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'hcaptcha_site_key',
                                'label' => esc_html__('Site Key (hCaptcha)', 'autopuzzle'),
                                'type' => 'text',
                                'desc' => esc_html__('Your hCaptcha site key.', 'autopuzzle')
                            ],
                            [
                                'name' => 'hcaptcha_secret_key',
                                'label' => esc_html__('Secret Key (hCaptcha)', 'autopuzzle'),
                                'type' => 'text',
                                'desc' => esc_html__('Your hCaptcha secret key (will be encrypted).', 'autopuzzle')
                            ],
                        ]
                    ],
                ]
            ],
            // Advanced Tab
            'advanced' => [
                'title' => esc_html__('Advanced', 'autopuzzle'),
                'icon' => 'fas fa-cogs',
                'sections' => [
                    'autopuzzle_demo_mode_section' => [
                        'title' => esc_html__('Demo Mode', 'autopuzzle'),
                        'desc' => esc_html__('Enable demo mode to generate sample data for testing and demonstration purposes.', 'autopuzzle'),
                        'fields' => [
                            [
                                'name' => 'demo_mode_enabled',
                                'label' => esc_html__('Enable Demo Mode', 'autopuzzle'),
                                'type' => 'switch',
                                'default' => '0',
                                'desc' => esc_html__('When enabled, you can import sample data including customers, experts, cars, and inquiries.', 'autopuzzle')
                            ],
                        ]
                    ],
                    'autopuzzle_demo_import_section' => [
                        'title' => esc_html__('Import Demo Content', 'autopuzzle'),
                        'desc' => esc_html__('Click the button below to import sample data. This will create 30 sample customers, 30 experts, 30 cars, and 30 inquiries of each type (cash and installment).', 'autopuzzle'),
                        'fields' => [] // Custom rendering will be done in template
                    ],
                ]
            ],
        ];
    }
}