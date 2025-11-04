<?php
/**
 * Creates and manages the plugin's settings, providing core encryption/decryption utilities
 * and handling settings updates exclusively from the frontend shortcode.
 *
 * All backend WordPress Admin UI elements for settings have been removed.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.1.0 (Frontend-Only Settings Management)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Settings_Page {

    /**
     * The name of the option in the wp_options table where all settings are stored.
     * @var string
     */
    private $options_name = 'maneli_inquiry_all_options';

    public function __construct() {
        // تنها هوک لازم برای ذخیره تنظیمات از طریق فرم فرانت‌اند (شورت‌کد) نگهداری شده است.
        add_action('admin_post_maneli_save_frontend_settings', [$this, 'handle_frontend_settings_save']);
    }

    /**
     * Retrieves a unique, site-specific key for encryption, ensuring it's 32 bytes long.
     * @return string The encryption key.
     */
    private function get_encryption_key() {
        // Use a unique, secure key from wp-config.php
        $key = defined('AUTH_KEY') ? AUTH_KEY : NONCE_KEY;
        // Generate a 32-byte key from the security constant using SHA-256 for openssl_encrypt
        return hash('sha256', $key, true); 
    }

    /**
     * Encrypts data using AES-256-CBC.
     * @param string $data The data to encrypt.
     * @return string The Base64 encoded encrypted data with IV.
     */
    public function encrypt_data($data) {
        if (empty($data)) {
            return '';
        }
        $key = $this->get_encryption_key();
        $cipher = 'aes-256-cbc';
        $iv_length = openssl_cipher_iv_length($cipher);
        
        // Use a cryptographically secure IV
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        
        if ($encrypted === false) {
             return ''; // Encryption failed
        }

        // Return IV and encrypted data combined with a separator, then Base64 encode
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypts data using AES-256-CBC.
     * @param string $encrypted_data The encrypted data (Base64 encoded).
     * @return string The decrypted data or empty string on failure.
     */
    public function decrypt_data($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        $key = $this->get_encryption_key();
        $cipher = 'aes-256-cbc';
        
        // Decode and separate IV and encrypted data
        $parts = explode('::', base64_decode($encrypted_data), 2);
        
        if (count($parts) !== 2) {
            return ''; // Invalid format or decryption failed
        }
        $encrypted = $parts[0];
        $iv = $parts[1];
        
        // Basic check for IV length
        if (strlen($iv) !== openssl_cipher_iv_length($cipher)) {
            return '';
        }

        // Decrypt
        $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
        
        return $decrypted === false ? '' : $decrypted;
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
        $is_sensitive = in_array($name, ['finotex_username', 'finotex_password', 'sadad_key'], true);

        if ($is_sensitive && !empty($value)) {
            $value = $this->decrypt_data($value);
            // اگر رمزگشایی ناموفق باشد، فیلد خالی نمایش داده می‌شود تا کاربر مجدداً وارد کند.
        }

        switch ($type) {
            case 'textarea':
                echo "<textarea name='" . esc_attr($field_name) . "' id='" . esc_attr($this->options_name . '_' . $name) . "' rows='3' class='form-control'>" . esc_textarea($value) . "</textarea>";
                break;
            case 'switch':
                echo '<label class="maneli-switch">';
                echo "<input type='checkbox' name='" . esc_attr($field_name) . "' value='1' " . checked('1', $value, false) . '>';
                echo '<span class="maneli-slider round"></span></label>';
                break;
            case 'multiselect':
                // Handle multi-select field for excluded days
                if ($name === 'meetings_excluded_days') {
                    $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                    $day_labels = [
                        'saturday' => esc_html__('Saturday', 'maneli-car-inquiry'),
                        'sunday' => esc_html__('Sunday', 'maneli-car-inquiry'),
                        'monday' => esc_html__('Monday', 'maneli-car-inquiry'),
                        'tuesday' => esc_html__('Tuesday', 'maneli-car-inquiry'),
                        'wednesday' => esc_html__('Wednesday', 'maneli-car-inquiry'),
                        'thursday' => esc_html__('Thursday', 'maneli-car-inquiry'),
                        'friday' => esc_html__('Friday', 'maneli-car-inquiry'),
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
        $sensitive_keys = ['finotex_username', 'finotex_password', 'sadad_key'];
        
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
        check_admin_referer('maneli_save_frontend_settings_nonce');
        // اطمینان از دسترسی کاربر به مدیریت پلاگین
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry'));
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
                'title' => esc_html__('Finance & Calculator', 'maneli-car-inquiry'),
                'icon' => 'fas fa-hand-holding-usd',
                'sections' => [
                    'maneli_loan_interest_section' => [
                        'title' => esc_html__('Installment Loan Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'loan_interest_rate', 
                                'label' => esc_html__('Monthly Interest Rate (Decimal)', 'maneli-car-inquiry'), 
                                'type' => 'text', // Use text to allow decimals better, will sanitize as text
                                'default' => '0.035', 
                                'desc' => esc_html__('Enter the monthly interest rate as a decimal (e.g., 0.035 for 3.5%). Used in all installment calculations.', 'maneli-car-inquiry')
                            ],
                        ]
                    ],
                    'maneli_price_display_section' => [
                        'title' => esc_html__('Price Display Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'hide_prices_for_customers', 
                                'label' => esc_html__('Hide Prices from Customers', 'maneli-car-inquiry'), 
                                'type' => 'switch', 
                                'default' => '0',
                                'desc' => esc_html__('If enabled, all product prices will be hidden from customers on the website. Note: Unavailable products will still show "ناموجود" text.', 'maneli-car-inquiry')
                            ],
                        ]
                    ],
                    'maneli_visitor_statistics_section' => [
                        'title' => esc_html__('Visitor Statistics', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'enable_visitor_statistics', 
                                'label' => esc_html__('Enable Visitor Statistics Tracking', 'maneli-car-inquiry'), 
                                'type' => 'switch', 
                                'default' => '1',
                                'desc' => esc_html__('If enabled, the plugin will track visitor statistics including visits, browsers, devices, countries, and more. This data will be displayed in the Visitor Statistics dashboard.', 'maneli-car-inquiry')
                            ],
                        ]
                    ]
                ]
            ],
            // EXISTING TABS
            'gateways' => [
                'title' => esc_html__('Payment Gateway', 'maneli-car-inquiry'),
                'icon' => 'fas fa-money-check-alt',
                'sections' => [
                    'maneli_payment_general_section' => [
                        'title' => esc_html__('General Payment Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'payment_enabled', 'label' => esc_html__('Enable Payment Gateway', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('If enabled, the inquiry fee payment step will be shown to users.', 'maneli-car-inquiry')],
                            ['name' => 'inquiry_fee', 'label' => esc_html__('Inquiry Fee (Toman)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Enter 0 for free inquiries.', 'maneli-car-inquiry')],
                            ['name' => 'zero_fee_message', 'label' => esc_html__('Message for Free Inquiry', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('The inquiry fee is waived for you. Please click the button below to continue.', 'maneli-car-inquiry')],
                        ]
                    ],
                     'maneli_discount_section' => [
                        'title' => esc_html__('Discount Code Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'discount_code', 'label' => esc_html__('Discount Code', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Enter a code for 100% off the inquiry fee.', 'maneli-car-inquiry')],
                            ['name' => 'discount_code_text', 'label' => esc_html__('Discount Code Success Message', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('100% discount applied successfully.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sadad_section' => [
                        'title' => esc_html__('Sadad (Melli Bank) Settings', 'maneli-car-inquiry'),
                        'fields' => [
                             ['name' => 'sadad_enabled', 'label' => esc_html__('Enable Sadad', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                             ['name' => 'sadad_terminal_id', 'label' => esc_html__('Terminal ID', 'maneli-car-inquiry'), 'type' => 'text'],
                             ['name' => 'sadad_merchant_id', 'label' => esc_html__('Merchant ID', 'maneli-car-inquiry'), 'type' => 'text'],
                             // Encrypted Field
                             ['name' => 'sadad_key', 'label' => esc_html__('Sadad Encryption Key', 'maneli-car-inquiry'), 'type' => 'password', 'desc' => esc_html__('The encryption key (TDes key) provided by Sadad. Stored securely encrypted.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_zarinpal_section' => [
                        'title' => esc_html__('Zarinpal Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'zarinpal_enabled', 'label' => esc_html__('Enable Zarinpal', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'zarinpal_merchant_code', 'label' => esc_html__('Merchant ID', 'maneli-car-inquiry'), 'type' => 'text'],
                        ]
                    ],
                ]
            ],
            'authentication' => [
                'title' => esc_html__('Authentication', 'maneli-car-inquiry'),
                'icon' => 'fas fa-shield-alt',
                'sections' => [
                    'maneli_dashboard_security_section' => [
                        'title' => esc_html__('Dashboard Security', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure dashboard access credentials.', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'dashboard_password',
                                'label' => esc_html__('Dashboard Password', 'maneli-car-inquiry'),
                                'type' => 'password',
                                'desc' => esc_html__('Password for dashboard login. This field is required - no default password is provided for security reasons.', 'maneli-car-inquiry')
                            ],
                        ]
                    ],
                    'maneli_otp_settings_section' => [
                        'title' => esc_html__('OTP Authentication Settings', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure One-Time Password (OTP) settings for login authentication.', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'otp_enabled',
                                'label' => esc_html__('Enable OTP Login', 'maneli-car-inquiry'),
                                'type' => 'switch',
                                'default' => '1',
                                'desc' => esc_html__('Allow users to login using OTP sent via SMS.', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'otp_pattern_code',
                                'label' => esc_html__('OTP Pattern Code (Body ID)', 'maneli-car-inquiry'),
                                'type' => 'number',
                                'desc' => esc_html__('Pattern code for OTP SMS. Variable: {0} = OTP Code (4-digit)', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'otp_expiry_minutes',
                                'label' => esc_html__('OTP Expiry Time (minutes)', 'maneli-car-inquiry'),
                                'type' => 'number',
                                'default' => '5',
                                'desc' => esc_html__('How long the OTP code remains valid (in minutes).', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'otp_resend_delay',
                                'label' => esc_html__('Resend Delay (seconds)', 'maneli-car-inquiry'),
                                'type' => 'number',
                                'default' => '60',
                                'desc' => esc_html__('Minimum time between OTP resend requests (in seconds).', 'maneli-car-inquiry')
                            ],
                        ]
                    ],
                ]
            ],
            'sms' => [
                'title' => esc_html__('SMS', 'maneli-car-inquiry'),
                'icon' => 'fas fa-mobile-alt',
                'sections' => [
                    'maneli_sms_api_section' => [
                        'title' => esc_html__('MeliPayamak Panel Information', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_username', 'label' => esc_html__('Username', 'maneli-car-inquiry'), 'type' => 'text'],
                            ['name' => 'sms_password', 'label' => esc_html__('Password', 'maneli-car-inquiry'), 'type' => 'password'],
                        ]
                    ],
                    'maneli_sms_patterns_section' => [
                        'title' => esc_html__('SMS Pattern Codes (Body ID)', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Enter only the approved Pattern Code (Body ID) from your SMS panel. Variable order must match the descriptions.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'admin_notification_mobile', 'label' => esc_html__('Admin Mobile Number', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Mobile number to receive new request notifications.', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_new_inquiry', 'label' => esc_html__('Pattern: "New Request for Admin"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_meeting_reminder', 'label' => esc_html__('Pattern: "Meeting Reminder"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Pattern code for SMS meeting reminders. Variables: 1. Customer Name 2. Meeting Date 3. Meeting Time 4. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_installment_patterns_customer_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Installment Inquiry (Customer)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_pattern_installment_new_customer', 'label' => esc_html__('Pattern: "New" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_pending_customer', 'label' => esc_html__('Pattern: "Pending Review" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_user_confirmed_customer', 'label' => esc_html__('Pattern: "Approved and Referred" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_more_docs_customer', 'label' => esc_html__('Pattern: "More Documents Required" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_rejected_customer', 'label' => esc_html__('Pattern: "Rejected" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_failed_customer', 'label' => esc_html__('Pattern: "Inquiry Failed" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_referred_customer', 'label' => esc_html__('Pattern: "Referred to Expert" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_in_progress_customer', 'label' => esc_html__('Pattern: "In Progress" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_follow_up_scheduled_customer', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_meeting_scheduled_customer', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_awaiting_documents_customer', 'label' => esc_html__('Pattern: "Awaiting Documents" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_approved_customer', 'label' => esc_html__('Pattern: "Approved" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_completed_customer', 'label' => esc_html__('Pattern: "Completed" (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_installment_patterns_expert_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Installment Inquiry (Expert)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_pattern_installment_new_expert', 'label' => esc_html__('Pattern: "New" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_referred_expert', 'label' => esc_html__('Pattern: "Referred to Expert" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_in_progress_expert', 'label' => esc_html__('Pattern: "In Progress" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_follow_up_scheduled_expert', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_meeting_scheduled_expert', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_awaiting_documents_expert', 'label' => esc_html__('Pattern: "Awaiting Documents" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_approved_expert', 'label' => esc_html__('Pattern: "Approved" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_rejected_expert', 'label' => esc_html__('Pattern: "Rejected" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_completed_expert', 'label' => esc_html__('Pattern: "Completed" (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_installment_patterns_admin_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Installment Inquiry (Admin)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_pattern_installment_new_admin', 'label' => esc_html__('Pattern: "New" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_pending_admin', 'label' => esc_html__('Pattern: "Pending Review" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_user_confirmed_admin', 'label' => esc_html__('Pattern: "Approved and Referred" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_referred_admin', 'label' => esc_html__('Pattern: "Referred to Expert" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_approved_admin', 'label' => esc_html__('Pattern: "Approved" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_rejected_admin', 'label' => esc_html__('Pattern: "Rejected" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_failed_admin', 'label' => esc_html__('Pattern: "Inquiry Failed" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_installment_completed_admin', 'label' => esc_html__('Pattern: "Completed" (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_cash_patterns_customer_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Inquiry (Customer)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_pattern_cash_new_customer', 'label' => esc_html__('Pattern: "New" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_referred_customer', 'label' => esc_html__('Pattern: "Referred to Expert" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_in_progress_customer', 'label' => esc_html__('Pattern: "In Progress" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_follow_up_scheduled_customer', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_meeting_scheduled_customer', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_awaiting_downpayment_customer', 'label' => esc_html__('Pattern: "Awaiting Down Payment" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_downpayment_received_customer', 'label' => esc_html__('Pattern: "Down Payment Received" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Down Payment Amount', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_awaiting_documents_customer', 'label' => esc_html__('Pattern: "Awaiting Documents" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_approved_customer', 'label' => esc_html__('Pattern: "Approved" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Down Payment Amount', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_rejected_customer', 'label' => esc_html__('Pattern: "Rejected" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_completed_customer', 'label' => esc_html__('Pattern: "Completed" (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_cash_patterns_expert_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Inquiry (Expert)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_pattern_cash_new_expert', 'label' => esc_html__('Pattern: "New" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_referred_expert', 'label' => esc_html__('Pattern: "Referred to Expert" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_in_progress_expert', 'label' => esc_html__('Pattern: "In Progress" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_follow_up_scheduled_expert', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_meeting_scheduled_expert', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_awaiting_downpayment_expert', 'label' => esc_html__('Pattern: "Awaiting Down Payment" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_downpayment_received_expert', 'label' => esc_html__('Pattern: "Down Payment Received" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_awaiting_documents_expert', 'label' => esc_html__('Pattern: "Awaiting Documents" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_approved_expert', 'label' => esc_html__('Pattern: "Approved" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_rejected_expert', 'label' => esc_html__('Pattern: "Rejected" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_completed_expert', 'label' => esc_html__('Pattern: "Completed" (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_cash_patterns_admin_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Inquiry (Admin)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_pattern_cash_new_admin', 'label' => esc_html__('Pattern: "New" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_referred_admin', 'label' => esc_html__('Pattern: "Referred to Expert" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_in_progress_admin', 'label' => esc_html__('Pattern: "In Progress" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_follow_up_scheduled_admin', 'label' => esc_html__('Pattern: "Follow Up Scheduled" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_meeting_scheduled_admin', 'label' => esc_html__('Pattern: "Meeting Scheduled" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_awaiting_downpayment_admin', 'label' => esc_html__('Pattern: "Awaiting Down Payment" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_downpayment_received_admin', 'label' => esc_html__('Pattern: "Down Payment Received" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_awaiting_documents_admin', 'label' => esc_html__('Pattern: "Awaiting Documents" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_approved_admin', 'label' => esc_html__('Pattern: "Approved" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_rejected_admin', 'label' => esc_html__('Pattern: "Rejected" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_cash_completed_admin', 'label' => esc_html__('Pattern: "Completed" (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_scheduled_section' => [
                        'title' => esc_html__('Scheduled SMS', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'scheduled_sms_enabled', 'label' => esc_html__('Enable Scheduled SMS', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('Allow scheduling SMS messages for future delivery.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_sms_bulk_section' => [
                        'title' => esc_html__('Bulk SMS', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'bulk_sms_limit', 'label' => esc_html__('Maximum Recipients per Bulk Send', 'maneli-car-inquiry'), 'type' => 'number', 'default' => '100', 'desc' => esc_html__('Maximum number of recipients allowed in a single bulk send operation.', 'maneli-car-inquiry')],
                            ['name' => 'bulk_sms_recipient_filter', 'label' => esc_html__('Recipient Filter Options', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('Allow filtering recipients by user role (customers, experts, admins).', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'telegram' => [
                'title' => esc_html__('Telegram', 'maneli-car-inquiry'),
                'icon' => 'fab fa-telegram',
                'sections' => [
                    'maneli_telegram_api_section' => [
                        'title' => esc_html__('Telegram Bot Configuration', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_bot_token', 'label' => esc_html__('Bot Token', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Get your bot token from @BotFather on Telegram.', 'maneli-car-inquiry')],
                            ['name' => 'telegram_chat_ids', 'label' => esc_html__('Default Chat IDs', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Comma-separated chat IDs. These will be used for bulk notifications and meeting reminders.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_settings_section' => [
                        'title' => esc_html__('Telegram Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_enabled', 'label' => esc_html__('Enable Telegram Notifications', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                        ]
                    ],
                    'maneli_telegram_messages_section' => [
                        'title' => esc_html__('Telegram Message Templates', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure message templates for Telegram notifications. Use variables: {customer_name}, {car_name}, {date}, {time}, {reason}, {expert_name}, {customer_mobile}, {amount}.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_new_inquiry', 'label' => esc_html__('Message: New Inquiry for Admin', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('New inquiry received from {customer_name} for {car_name}', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_meeting_reminder', 'label' => esc_html__('Message: Meeting Reminder', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Reminder: You have a meeting scheduled for {date} at {time}. Car: {car_name}', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {date}, {time}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_installment_messages_customer_section' => [
                        'title' => esc_html__('Telegram Messages - Installment Inquiry (Customer)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_installment_new_customer', 'label' => esc_html__('Message: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_pending_customer', 'label' => esc_html__('Message: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_user_confirmed_customer', 'label' => esc_html__('Message: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_more_docs_customer', 'label' => esc_html__('Message: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_rejected_customer', 'label' => esc_html__('Message: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {reason}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_failed_customer', 'label' => esc_html__('Message: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_in_progress_customer', 'label' => esc_html__('Message: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_approved_customer', 'label' => esc_html__('Message: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_completed_customer', 'label' => esc_html__('Message: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_installment_messages_expert_section' => [
                        'title' => esc_html__('Telegram Messages - Installment Inquiry (Expert)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_installment_new_expert', 'label' => esc_html__('Message: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('New installment inquiry has been created. Customer: {customer_name}, Car: {car_name}', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {expert_name}, a new inquiry has been referred to you. Customer: {customer_name} ({customer_mobile}), Car: {car_name}', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {expert_name}, {customer_name}, {customer_mobile}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_in_progress_expert', 'label' => esc_html__('Message: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) is now in progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Follow-up scheduled for {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Meeting scheduled for {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Awaiting documents from {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_approved_expert', 'label' => esc_html__('Message: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) has been approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_rejected_expert', 'label' => esc_html__('Message: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) has been rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_completed_expert', 'label' => esc_html__('Message: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Inquiry for {customer_name} ({car_name}) has been completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_installment_messages_admin_section' => [
                        'title' => esc_html__('Telegram Messages - Installment Inquiry (Admin)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_installment_new_admin', 'label' => esc_html__('Message: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('New installment inquiry: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_pending_admin', 'label' => esc_html__('Message: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry pending review: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_user_confirmed_admin', 'label' => esc_html__('Message: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry approved and referred: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry referred to expert: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_approved_admin', 'label' => esc_html__('Message: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry approved: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_rejected_admin', 'label' => esc_html__('Message: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry rejected: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_failed_admin', 'label' => esc_html__('Message: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry failed: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_installment_completed_admin', 'label' => esc_html__('Message: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Installment inquiry completed: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_cash_messages_customer_section' => [
                        'title' => esc_html__('Telegram Messages - Cash Inquiry (Customer)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_cash_new_customer', 'label' => esc_html__('Message: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_in_progress_customer', 'label' => esc_html__('Message: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_awaiting_downpayment_customer', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_downpayment_received_customer', 'label' => esc_html__('Message: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_approved_customer', 'label' => esc_html__('Message: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved. Down payment: {amount} Toman', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_rejected_customer', 'label' => esc_html__('Message: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected. Reason: {reason}', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {reason}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_completed_customer', 'label' => esc_html__('Message: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_cash_messages_expert_section' => [
                        'title' => esc_html__('Telegram Messages - Cash Inquiry (Expert)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_cash_new_expert', 'label' => esc_html__('Message: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('New cash inquiry: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {expert_name}, a new cash inquiry has been referred to you. Customer: {customer_name} ({customer_mobile}), Car: {car_name}', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {expert_name}, {customer_name}, {customer_mobile}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_in_progress_expert', 'label' => esc_html__('Message: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) is now in progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Follow-up scheduled for {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Meeting scheduled for {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_awaiting_downpayment_expert', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Awaiting down payment from {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_downpayment_received_expert', 'label' => esc_html__('Message: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Down payment received from {customer_name} ({car_name}): {amount} Toman', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Awaiting documents from {customer_name} ({car_name}).', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_approved_expert', 'label' => esc_html__('Message: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) has been approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_rejected_expert', 'label' => esc_html__('Message: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) has been rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_completed_expert', 'label' => esc_html__('Message: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry for {customer_name} ({car_name}) has been completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_telegram_cash_messages_admin_section' => [
                        'title' => esc_html__('Telegram Messages - Cash Inquiry (Admin)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'telegram_message_cash_new_admin', 'label' => esc_html__('Message: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('New cash inquiry: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry referred to expert: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_in_progress_admin', 'label' => esc_html__('Message: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry in progress: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_follow_up_scheduled_admin', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Follow-up scheduled: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_meeting_scheduled_admin', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Meeting scheduled: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_awaiting_downpayment_admin', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Awaiting down payment: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_downpayment_received_admin', 'label' => esc_html__('Message: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Down payment received: {customer_name} ({car_name}), Amount: {amount} Toman', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}, {amount}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_awaiting_documents_admin', 'label' => esc_html__('Message: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Awaiting documents: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_approved_admin', 'label' => esc_html__('Message: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry approved: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_rejected_admin', 'label' => esc_html__('Message: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry rejected: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'telegram_message_cash_completed_admin', 'label' => esc_html__('Message: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Cash inquiry completed: {customer_name} ({car_name})', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                ]
            ],
            'email' => [
                'title' => esc_html__('Email', 'maneli-car-inquiry'),
                'icon' => 'fas fa-envelope',
                'sections' => [
                    'maneli_email_smtp_section' => [
                        'title' => esc_html__('SMTP Configuration', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_use_smtp', 'label' => esc_html__('Use SMTP', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0', 'desc' => esc_html__('If disabled, WordPress default wp_mail will be used.', 'maneli-car-inquiry')],
                            ['name' => 'email_smtp_host', 'label' => esc_html__('SMTP Host', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('e.g., smtp.gmail.com', 'maneli-car-inquiry')],
                            ['name' => 'email_smtp_port', 'label' => esc_html__('SMTP Port', 'maneli-car-inquiry'), 'type' => 'number', 'default' => '587', 'desc' => esc_html__('Common ports: 587 (TLS), 465 (SSL)', 'maneli-car-inquiry')],
                            ['name' => 'email_smtp_username', 'label' => esc_html__('SMTP Username', 'maneli-car-inquiry'), 'type' => 'text'],
                            ['name' => 'email_smtp_password', 'label' => esc_html__('SMTP Password', 'maneli-car-inquiry'), 'type' => 'password'],
                            ['name' => 'email_smtp_encryption', 'label' => esc_html__('Encryption', 'maneli-car-inquiry'), 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL'], 'default' => 'tls'],
                        ]
                    ],
                    'maneli_email_from_section' => [
                        'title' => esc_html__('Email From Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_from_email', 'label' => esc_html__('From Email', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Default sender email address.', 'maneli-car-inquiry')],
                            ['name' => 'email_from_name', 'label' => esc_html__('From Name', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Default sender name.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_settings_section' => [
                        'title' => esc_html__('Email Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_enabled', 'label' => esc_html__('Enable Email Notifications', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                        ]
                    ],
                    'maneli_email_messages_section' => [
                        'title' => esc_html__('Email Message Templates', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure email message templates. Use variables: {customer_name}, {car_name}, {date}, {time}, {reason}, {expert_name}, {customer_mobile}, {amount}.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_new_inquiry', 'label' => esc_html__('Subject: New Inquiry for Admin', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('New Inquiry Received', 'maneli-car-inquiry')],
                            ['name' => 'email_message_new_inquiry', 'label' => esc_html__('Message: New Inquiry for Admin', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('A new inquiry has been received from {customer_name} for {car_name}.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_meeting_reminder', 'label' => esc_html__('Subject: Meeting Reminder', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Meeting Reminder', 'maneli-car-inquiry')],
                            ['name' => 'email_message_meeting_reminder', 'label' => esc_html__('Message: Meeting Reminder', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, this is a reminder that you have a meeting scheduled for {date} at {time} regarding {car_name}.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {date}, {time}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_installment_messages_customer_section' => [
                        'title' => esc_html__('Email Messages - Installment Inquiry (Customer)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_installment_new_customer', 'label' => esc_html__('Subject: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - New', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_new_customer', 'label' => esc_html__('Message: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_pending_customer', 'label' => esc_html__('Subject: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Pending Review', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_pending_customer', 'label' => esc_html__('Message: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_user_confirmed_customer', 'label' => esc_html__('Subject: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved and Referred', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_user_confirmed_customer', 'label' => esc_html__('Message: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_more_docs_customer', 'label' => esc_html__('Subject: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - More Documents Required', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_more_docs_customer', 'label' => esc_html__('Message: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_rejected_customer', 'label' => esc_html__('Subject: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Rejected', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_rejected_customer', 'label' => esc_html__('Message: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_failed_customer', 'label' => esc_html__('Subject: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Inquiry Failed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_failed_customer', 'label' => esc_html__('Message: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_referred_customer', 'label' => esc_html__('Subject: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Referred to Expert', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_in_progress_customer', 'label' => esc_html__('Subject: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - In Progress', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_in_progress_customer', 'label' => esc_html__('Message: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_follow_up_scheduled_customer', 'label' => esc_html__('Subject: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Follow Up Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_meeting_scheduled_customer', 'label' => esc_html__('Subject: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Meeting Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_awaiting_documents_customer', 'label' => esc_html__('Subject: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Awaiting Documents', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_approved_customer', 'label' => esc_html__('Subject: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_approved_customer', 'label' => esc_html__('Message: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_completed_customer', 'label' => esc_html__('Subject: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Completed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_completed_customer', 'label' => esc_html__('Message: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_installment_messages_expert_section' => [
                        'title' => esc_html__('Email Messages - Installment Inquiry (Expert)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_installment_new_expert', 'label' => esc_html__('Subject: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - New', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_new_expert', 'label' => esc_html__('Message: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_pending_expert', 'label' => esc_html__('Subject: Pending Review (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Pending Review', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_pending_expert', 'label' => esc_html__('Message: Pending Review (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_user_confirmed_expert', 'label' => esc_html__('Subject: Approved and Referred (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved and Referred', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_user_confirmed_expert', 'label' => esc_html__('Message: Approved and Referred (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_more_docs_expert', 'label' => esc_html__('Subject: More Documents Required (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - More Documents Required', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_more_docs_expert', 'label' => esc_html__('Message: More Documents Required (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_rejected_expert', 'label' => esc_html__('Subject: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Rejected', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_rejected_expert', 'label' => esc_html__('Message: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_failed_expert', 'label' => esc_html__('Subject: Inquiry Failed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Inquiry Failed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_failed_expert', 'label' => esc_html__('Message: Inquiry Failed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_referred_expert', 'label' => esc_html__('Subject: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Referred to Expert', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_in_progress_expert', 'label' => esc_html__('Subject: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - In Progress', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_in_progress_expert', 'label' => esc_html__('Message: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_follow_up_scheduled_expert', 'label' => esc_html__('Subject: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Follow Up Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_meeting_scheduled_expert', 'label' => esc_html__('Subject: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Meeting Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_awaiting_documents_expert', 'label' => esc_html__('Subject: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Awaiting Documents', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_approved_expert', 'label' => esc_html__('Subject: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_approved_expert', 'label' => esc_html__('Message: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_completed_expert', 'label' => esc_html__('Subject: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Completed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_completed_expert', 'label' => esc_html__('Message: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_installment_messages_admin_section' => [
                        'title' => esc_html__('Email Messages - Installment Inquiry (Admin)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_installment_new_admin', 'label' => esc_html__('Subject: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - New', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_new_admin', 'label' => esc_html__('Message: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_pending_admin', 'label' => esc_html__('Subject: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Pending Review', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_pending_admin', 'label' => esc_html__('Message: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Pending Review.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_user_confirmed_admin', 'label' => esc_html__('Subject: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved and Referred', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_user_confirmed_admin', 'label' => esc_html__('Message: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved and Referred.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_more_docs_admin', 'label' => esc_html__('Subject: More Documents Required (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - More Documents Required', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_more_docs_admin', 'label' => esc_html__('Message: More Documents Required (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: More Documents Required.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_rejected_admin', 'label' => esc_html__('Subject: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Rejected', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_rejected_admin', 'label' => esc_html__('Message: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_failed_admin', 'label' => esc_html__('Subject: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Inquiry Failed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_failed_admin', 'label' => esc_html__('Message: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Inquiry Failed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_referred_admin', 'label' => esc_html__('Subject: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Referred to Expert', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_in_progress_admin', 'label' => esc_html__('Subject: In Progress (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - In Progress', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_in_progress_admin', 'label' => esc_html__('Message: In Progress (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_follow_up_scheduled_admin', 'label' => esc_html__('Subject: Follow Up Scheduled (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Follow Up Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_follow_up_scheduled_admin', 'label' => esc_html__('Message: Follow Up Scheduled (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_meeting_scheduled_admin', 'label' => esc_html__('Subject: Meeting Scheduled (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Meeting Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_meeting_scheduled_admin', 'label' => esc_html__('Message: Meeting Scheduled (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_awaiting_documents_admin', 'label' => esc_html__('Subject: Awaiting Documents (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Awaiting Documents', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_awaiting_documents_admin', 'label' => esc_html__('Message: Awaiting Documents (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_approved_admin', 'label' => esc_html__('Subject: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Approved', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_approved_admin', 'label' => esc_html__('Message: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_installment_completed_admin', 'label' => esc_html__('Subject: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Installment Inquiry - Completed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_installment_completed_admin', 'label' => esc_html__('Message: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your installment inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_cash_messages_customer_section' => [
                        'title' => esc_html__('Email Messages - Cash Inquiry (Customer)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_cash_new_customer', 'label' => esc_html__('Subject: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - New', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_new_customer', 'label' => esc_html__('Message: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_referred_customer', 'label' => esc_html__('Subject: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Referred to Expert', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_referred_customer', 'label' => esc_html__('Message: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_in_progress_customer', 'label' => esc_html__('Subject: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - In Progress', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_in_progress_customer', 'label' => esc_html__('Message: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_follow_up_scheduled_customer', 'label' => esc_html__('Subject: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Follow Up Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_follow_up_scheduled_customer', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_meeting_scheduled_customer', 'label' => esc_html__('Subject: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Meeting Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_meeting_scheduled_customer', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_awaiting_downpayment_customer', 'label' => esc_html__('Subject: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Down Payment', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_awaiting_downpayment_customer', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_downpayment_received_customer', 'label' => esc_html__('Subject: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Down Payment Received', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_downpayment_received_customer', 'label' => esc_html__('Message: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_awaiting_documents_customer', 'label' => esc_html__('Subject: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Documents', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_awaiting_documents_customer', 'label' => esc_html__('Message: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_approved_customer', 'label' => esc_html__('Subject: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Approved', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_approved_customer', 'label' => esc_html__('Message: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_rejected_customer', 'label' => esc_html__('Subject: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Rejected', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_rejected_customer', 'label' => esc_html__('Message: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_completed_customer', 'label' => esc_html__('Subject: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Completed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_completed_customer', 'label' => esc_html__('Message: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_cash_messages_expert_section' => [
                        'title' => esc_html__('Email Messages - Cash Inquiry (Expert)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_cash_new_expert', 'label' => esc_html__('Subject: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - New', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_new_expert', 'label' => esc_html__('Message: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_referred_expert', 'label' => esc_html__('Subject: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Referred to Expert', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_referred_expert', 'label' => esc_html__('Message: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_in_progress_expert', 'label' => esc_html__('Subject: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - In Progress', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_in_progress_expert', 'label' => esc_html__('Message: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_follow_up_scheduled_expert', 'label' => esc_html__('Subject: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Follow Up Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_follow_up_scheduled_expert', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_meeting_scheduled_expert', 'label' => esc_html__('Subject: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Meeting Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_meeting_scheduled_expert', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_awaiting_downpayment_expert', 'label' => esc_html__('Subject: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Down Payment', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_awaiting_downpayment_expert', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_downpayment_received_expert', 'label' => esc_html__('Subject: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Down Payment Received', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_downpayment_received_expert', 'label' => esc_html__('Message: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_awaiting_documents_expert', 'label' => esc_html__('Subject: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Documents', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_awaiting_documents_expert', 'label' => esc_html__('Message: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_approved_expert', 'label' => esc_html__('Subject: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Approved', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_approved_expert', 'label' => esc_html__('Message: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_rejected_expert', 'label' => esc_html__('Subject: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Rejected', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_rejected_expert', 'label' => esc_html__('Message: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_completed_expert', 'label' => esc_html__('Subject: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Completed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_completed_expert', 'label' => esc_html__('Message: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_email_cash_messages_admin_section' => [
                        'title' => esc_html__('Email Messages - Cash Inquiry (Admin)', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'email_subject_cash_new_admin', 'label' => esc_html__('Subject: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - New', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_new_admin', 'label' => esc_html__('Message: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: New.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_referred_admin', 'label' => esc_html__('Subject: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Referred to Expert', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_referred_admin', 'label' => esc_html__('Message: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Referred to Expert.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_in_progress_admin', 'label' => esc_html__('Subject: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - In Progress', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_in_progress_admin', 'label' => esc_html__('Message: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: In Progress.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_follow_up_scheduled_admin', 'label' => esc_html__('Subject: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Follow Up Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_follow_up_scheduled_admin', 'label' => esc_html__('Message: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Follow Up Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_meeting_scheduled_admin', 'label' => esc_html__('Subject: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Meeting Scheduled', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_meeting_scheduled_admin', 'label' => esc_html__('Message: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Meeting Scheduled.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_awaiting_downpayment_admin', 'label' => esc_html__('Subject: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Down Payment', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_awaiting_downpayment_admin', 'label' => esc_html__('Message: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Down Payment.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_downpayment_received_admin', 'label' => esc_html__('Subject: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Down Payment Received', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_downpayment_received_admin', 'label' => esc_html__('Message: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Down Payment Received.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_awaiting_documents_admin', 'label' => esc_html__('Subject: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Awaiting Documents', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_awaiting_documents_admin', 'label' => esc_html__('Message: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Awaiting Documents.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_approved_admin', 'label' => esc_html__('Subject: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Approved', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_approved_admin', 'label' => esc_html__('Message: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Approved.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_rejected_admin', 'label' => esc_html__('Subject: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Rejected', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_rejected_admin', 'label' => esc_html__('Message: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Rejected.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                            ['name' => 'email_subject_cash_completed_admin', 'label' => esc_html__('Subject: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('Update on Your Cash Inquiry - Completed', 'maneli-car-inquiry')],
                            ['name' => 'email_message_cash_completed_admin', 'label' => esc_html__('Message: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('Dear {customer_name}, your cash inquiry for {car_name} status is now: Completed.', 'maneli-car-inquiry'), 'desc' => esc_html__('Variables: {customer_name}, {car_name}', 'maneli-car-inquiry')],
                        ]
                    ],
                ]
            ],
            'cash_inquiry' => [
                'title' => esc_html__('Cash Request', 'maneli-car-inquiry'),
                'icon' => 'fas fa-money-bill-wave',
                'sections' => [
                    'maneli_cash_inquiry_reasons_section' => [
                        'title' => esc_html__('Cash Request Process Settings', 'maneli-car-inquiry'),
                        'fields' => [
                             ['name' => 'cash_inquiry_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a list to the admin when rejecting a request.', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'installment' => [
                'title' => esc_html__('Installment Request', 'maneli-car-inquiry'),
                'icon' => 'fas fa-car',
                'sections' => [
                    'maneli_front_messages_section' => [
                        'title' => esc_html__('Frontend Messages', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'msg_price_disclaimer',
                                'label' => esc_html__('Price Disclaimer', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => esc_html__('Due to severe market fluctuations, car prices are approximate and may change until final approval.', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'msg_waiting_review',
                                'label' => esc_html__('Waiting Review Message', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => esc_html__('Your request has been submitted. The result will be announced within the next 24 hours.', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'msg_after_approval',
                                'label' => esc_html__('Post-Approval Message', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => esc_html__('Your documents have been approved. Our team will contact you shortly for next steps.', 'maneli-car-inquiry')
                            ],
                        ]
                    ],
                    'maneli_installment_rejection_reasons_section' => [
                        'title' => esc_html__('Installment Rejection Reasons', 'maneli-car-inquiry'),
                        'fields' => [
                             ['name' => 'installment_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a list to the admin when rejecting an installment request.', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'experts' => [
                'title' => esc_html__('Experts', 'maneli-car-inquiry'),
                'icon' => 'fas fa-users',
                'sections' => [
                    'maneli_experts_list_section' => [
                        'title' => esc_html__('Expert Management', 'maneli-car-inquiry'),
                        'desc' => wp_kses_post(__('The system automatically identifies all users with the <strong>"Maneli Expert"</strong> role. Requests are assigned to them in a round-robin fashion.<br>To add a new expert, simply create a new user from the <strong>Users > Add New</strong> menu with the "Maneli Expert" role and enter their mobile number in their profile.', 'maneli-car-inquiry')),
                        'fields' => [] // This section is for display only
                    ]
                ]
            ],
            'finotex' => [
                'title' => esc_html__('Finotex', 'maneli-car-inquiry'),
                'icon' => 'fas fa-university',
                'sections' => [
                    'maneli_finotex_credentials_section' => [
                        'title' => esc_html__('Finnotech API Credentials', 'maneli-car-inquiry'),
                        'desc' => esc_html__('These credentials are used for all Finnotech API services including Credit Risk, Credit Score, Collaterals, and Cheque Color inquiries.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'finotex_username', 'label' => esc_html__('Finnotech Client ID', 'maneli-car-inquiry'), 'type' => 'password', 'desc' => esc_html__('Client ID provided by Finnotech. Stored securely encrypted.', 'maneli-car-inquiry')],
                            ['name' => 'finotex_password', 'label' => esc_html__('Finnotech API Key', 'maneli-car-inquiry'), 'type' => 'password', 'desc' => esc_html__('API Key (Access Token) provided by Finnotech. Stored securely encrypted.', 'maneli-car-inquiry')],
                            ['name' => 'finotex_enabled', 'label' => esc_html__('Enable Legacy Finotex API', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('Enable the legacy Finotex cheque color inquiry API (deprecated, use Finnotech API Services instead).', 'maneli-car-inquiry'), 'default' => '0'],
                        ]
                    ],
                    'maneli_finnotech_apis_section' => [
                        'title' => esc_html__('Finnotech API Services', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Enable or disable individual Finnotech API services. When disabled, the data will be hidden from reports but preserved in the database.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'finnotech_credit_risk_enabled', 'label' => esc_html__('Enable Credit Risk Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('Enable banking risk inquiry for individuals (ریسک بانکی شخص).', 'maneli-car-inquiry')],
                            ['name' => 'finnotech_credit_score_enabled', 'label' => esc_html__('Enable Credit Score Report', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('Enable credit score decrease reasons inquiry (دلایل کاهش امتیاز اعتباری).', 'maneli-car-inquiry')],
                            ['name' => 'finnotech_collaterals_enabled', 'label' => esc_html__('Enable Collaterals Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('Enable contracts summary inquiry (وام‌ها/تسهیلات).', 'maneli-car-inquiry')],
                            ['name' => 'finnotech_cheque_color_enabled', 'label' => esc_html__('Enable Cheque Color Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('Enable Sadad cheque status inquiry (وضعیت چک‌های صیادی).', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'meetings' => [
                'title' => esc_html__('Meetings & Calendar', 'maneli-car-inquiry'),
                'icon'  => 'fas fa-calendar-alt',
                'sections' => [
                    'maneli_meetings_general' => [
                        'title' => esc_html__('General Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'meetings_enabled', 'label' => esc_html__('Enable Meetings', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'meetings_start_hour', 'label' => esc_html__('Workday Start (HH:MM)', 'maneli-car-inquiry'), 'type' => 'text', 'default' => '10:00'],
                            ['name' => 'meetings_end_hour',   'label' => esc_html__('Workday End (HH:MM)', 'maneli-car-inquiry'),   'type' => 'text', 'default' => '20:00'],
                            ['name' => 'meetings_slot_minutes','label' => esc_html__('Slot Duration (minutes)', 'maneli-car-inquiry'), 'type' => 'number', 'default' => '30'],
                            ['name' => 'meetings_excluded_days', 'label' => esc_html__('Excluded Days', 'maneli-car-inquiry'), 'type' => 'multiselect', 'default' => [], 'desc' => esc_html__('Select days when meetings cannot be scheduled', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'documents' => [
                'title' => esc_html__('Documents', 'maneli-car-inquiry'),
                'icon' => 'fas fa-file-alt',
                'sections' => [
                    'maneli_customer_documents_section' => [
                        'title' => esc_html__('Customer Documents', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'customer_required_documents', 'label' => esc_html__('Required Documents List', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one document name per line. These documents will be shown to customers in their profile to upload.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_document_rejection_reasons_section' => [
                        'title' => esc_html__('Document Rejection Reasons', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'document_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a dropdown list when rejecting a document.', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'availability' => [
                'title' => esc_html__('Availability', 'maneli-car-inquiry'),
                'icon' => 'fas fa-box-open',
                'sections' => [
                    'maneli_availability_messages_section' => [
                        'title' => esc_html__('Unavailable Product Messages', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure messages shown to customers when products are unavailable for different purchase methods.', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'unavailable_installment_message',
                                'label' => esc_html__('Installment Unavailable Message', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => esc_html__('This product is currently unavailable for installment purchase.', 'maneli-car-inquiry'),
                                'desc' => esc_html__('Message shown when installment price is not available (0 or empty).', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'unavailable_cash_message',
                                'label' => esc_html__('Cash Unavailable Message', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => esc_html__('This product is currently unavailable for cash purchase.', 'maneli-car-inquiry'),
                                'desc' => esc_html__('Message shown when cash price is not available (0 or empty).', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'unavailable_product_message',
                                'label' => esc_html__('Product Unavailable Message (Both)', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => esc_html__('This product is currently unavailable for purchase.', 'maneli-car-inquiry'),
                                'desc' => esc_html__('Message shown when product status is set to "Unavailable" or both prices are unavailable.', 'maneli-car-inquiry')
                            ],
                        ]
                    ]
                ]
            ],
            'notifications' => [
                'title' => esc_html__('Notifications', 'maneli-car-inquiry'),
                'icon' => 'fas fa-bell',
                'sections' => [
                    'maneli_global_channel_toggle_section' => [
                        'title' => esc_html__('Global Channel Settings', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Enable or disable notification channels globally. When disabled, the channel will be disabled for all notification types below.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'notifications_sms_global_enabled', 'label' => esc_html__('Enable SMS Notifications (Global)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('When disabled, all SMS notifications will be turned off.', 'maneli-car-inquiry')],
                            ['name' => 'notifications_telegram_global_enabled', 'label' => esc_html__('Enable Telegram Notifications (Global)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0', 'desc' => esc_html__('When disabled, all Telegram notifications will be turned off.', 'maneli-car-inquiry')],
                            ['name' => 'notifications_email_global_enabled', 'label' => esc_html__('Enable Email Notifications (Global)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0', 'desc' => esc_html__('When disabled, all Email notifications will be turned off.', 'maneli-car-inquiry')],
                            ['name' => 'notifications_inapp_global_enabled', 'label' => esc_html__('Enable In-App Notifications (Global)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1', 'desc' => esc_html__('When disabled, all in-app notifications will be turned off.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_meeting_reminders_section' => [
                        'title' => esc_html__('Meeting Reminders', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure automatic reminders sent before scheduled meetings.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'meeting_reminder_hours', 'label' => esc_html__('Remind Hours Before Meeting', 'maneli-car-inquiry'), 'type' => 'text', 'default' => '2', 'desc' => esc_html__('Comma-separated hours (e.g., 2,6,24). Reminders will be sent these hours before the meeting.', 'maneli-car-inquiry')],
                            ['name' => 'meeting_reminder_days', 'label' => esc_html__('Remind Days Before Meeting', 'maneli-car-inquiry'), 'type' => 'text', 'default' => '1', 'desc' => esc_html__('Comma-separated days (e.g., 1,3). Reminders will be sent these days before the meeting.', 'maneli-car-inquiry')],
                            ['name' => 'meeting_reminder_sms_enabled', 'label' => esc_html__('Enable SMS Reminders', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'meeting_reminder_telegram_enabled', 'label' => esc_html__('Enable Telegram Reminders', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'meeting_reminder_email_enabled', 'label' => esc_html__('Enable Email Reminders', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'meeting_reminder_notification_enabled', 'label' => esc_html__('Enable In-App Notification Reminders', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_new_inquiry_notifications_section' => [
                        'title' => esc_html__('New Inquiry Notifications', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure notifications sent when a new inquiry is submitted.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'new_inquiry_sms_enabled', 'label' => esc_html__('Enable SMS: New Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'new_inquiry_telegram_enabled', 'label' => esc_html__('Enable Telegram: New Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'new_inquiry_email_enabled', 'label' => esc_html__('Enable Email: New Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'new_inquiry_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_installment_status_notifications_section' => [
                        'title' => esc_html__('Installment Inquiry Status Notifications - Customer', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure which notifications to send to customers for different installment inquiry statuses.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'installment_new_customer_sms_enabled', 'label' => esc_html__('Enable SMS: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_new_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_customer_email_enabled', 'label' => esc_html__('Enable Email: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_customer_email_enabled', 'label' => esc_html__('Enable Email: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Pending Review (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_customer_email_enabled', 'label' => esc_html__('Enable Email: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved and Referred (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_more_docs_customer_sms_enabled', 'label' => esc_html__('Enable SMS: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_more_docs_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_more_docs_customer_email_enabled', 'label' => esc_html__('Enable Email: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_more_docs_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: More Documents Required (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_customer_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_customer_email_enabled', 'label' => esc_html__('Enable Email: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Inquiry Failed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_customer_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_customer_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_customer_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_customer_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_customer_email_enabled', 'label' => esc_html__('Enable Email: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_customer_email_enabled', 'label' => esc_html__('Enable Email: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Installment - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_installment_status_notifications_expert_section' => [
                        'title' => esc_html__('Installment Inquiry Status Notifications - Expert', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure which notifications to send to experts for different installment inquiry statuses.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'installment_new_expert_sms_enabled', 'label' => esc_html__('Enable SMS: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_new_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_expert_email_enabled', 'label' => esc_html__('Enable Email: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_expert_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_expert_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_in_progress_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_expert_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_in_progress_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_follow_up_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_follow_up_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_meeting_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_meeting_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_awaiting_documents_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_expert_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_awaiting_documents_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_expert_email_enabled', 'label' => esc_html__('Enable Email: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_expert_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_expert_email_enabled', 'label' => esc_html__('Enable Email: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Installment - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_installment_status_notifications_admin_section' => [
                        'title' => esc_html__('Installment Inquiry Status Notifications - Admin', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure which notifications to send to admins for different installment inquiry statuses.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'installment_new_admin_sms_enabled', 'label' => esc_html__('Enable SMS: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_new_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_admin_email_enabled', 'label' => esc_html__('Enable Email: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_new_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_pending_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_admin_email_enabled', 'label' => esc_html__('Enable Email: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_pending_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Pending Review (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_user_confirmed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_admin_email_enabled', 'label' => esc_html__('Enable Email: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_user_confirmed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved and Referred (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_referred_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_admin_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_referred_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_approved_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_admin_email_enabled', 'label' => esc_html__('Enable Email: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_approved_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_rejected_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_admin_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_rejected_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_failed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_admin_email_enabled', 'label' => esc_html__('Enable Email: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_failed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Inquiry Failed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'installment_completed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_admin_email_enabled', 'label' => esc_html__('Enable Email: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'installment_completed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Installment - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_cash_status_notifications_customer_section' => [
                        'title' => esc_html__('Cash Inquiry Status Notifications - Customer', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure which notifications to send to customers for different cash inquiry statuses.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'cash_new_customer_sms_enabled', 'label' => esc_html__('Enable SMS: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_new_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_customer_email_enabled', 'label' => esc_html__('Enable Email: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_customer_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_customer_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_customer_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_customer_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_customer_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Down Payment (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_customer_email_enabled', 'label' => esc_html__('Enable Email: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Down Payment Received (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_customer_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_customer_email_enabled', 'label' => esc_html__('Enable Email: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_customer_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_customer_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_customer_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_customer_email_enabled', 'label' => esc_html__('Enable Email: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_customer_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Cash - Customer)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_cash_status_notifications_expert_section' => [
                        'title' => esc_html__('Cash Inquiry Status Notifications - Expert', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure which notifications to send to experts for different cash inquiry statuses.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'cash_new_expert_sms_enabled', 'label' => esc_html__('Enable SMS: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_new_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_expert_email_enabled', 'label' => esc_html__('Enable Email: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_expert_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_expert_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_expert_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_expert_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_expert_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Down Payment (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_expert_email_enabled', 'label' => esc_html__('Enable Email: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Down Payment Received (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_expert_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_expert_email_enabled', 'label' => esc_html__('Enable Email: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_expert_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_expert_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_expert_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_expert_email_enabled', 'label' => esc_html__('Enable Email: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_expert_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Cash - Expert)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                    'maneli_cash_status_notifications_admin_section' => [
                        'title' => esc_html__('Cash Inquiry Status Notifications - Admin', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Configure which notifications to send to admins for different cash inquiry statuses.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'cash_new_admin_sms_enabled', 'label' => esc_html__('Enable SMS: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_new_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_admin_email_enabled', 'label' => esc_html__('Enable Email: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_new_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: New (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_referred_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_admin_email_enabled', 'label' => esc_html__('Enable Email: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_referred_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Referred to Expert (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_admin_sms_enabled', 'label' => esc_html__('Enable SMS: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_in_progress_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_admin_email_enabled', 'label' => esc_html__('Enable Email: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_in_progress_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: In Progress (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_follow_up_scheduled_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_admin_email_enabled', 'label' => esc_html__('Enable Email: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_follow_up_scheduled_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Follow Up Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_meeting_scheduled_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_admin_email_enabled', 'label' => esc_html__('Enable Email: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_meeting_scheduled_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Meeting Scheduled (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_downpayment_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_admin_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_downpayment_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Down Payment (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_downpayment_received_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_admin_email_enabled', 'label' => esc_html__('Enable Email: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_downpayment_received_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Down Payment Received (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_awaiting_documents_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_admin_email_enabled', 'label' => esc_html__('Enable Email: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_awaiting_documents_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Awaiting Documents (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_approved_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_admin_email_enabled', 'label' => esc_html__('Enable Email: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_approved_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Approved (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_rejected_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_admin_email_enabled', 'label' => esc_html__('Enable Email: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_rejected_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Rejected (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_admin_sms_enabled', 'label' => esc_html__('Enable SMS: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                            ['name' => 'cash_completed_admin_telegram_enabled', 'label' => esc_html__('Enable Telegram: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_admin_email_enabled', 'label' => esc_html__('Enable Email: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '0'],
                            ['name' => 'cash_completed_admin_notification_enabled', 'label' => esc_html__('Enable In-App Notification: Completed (Cash - Admin)', 'maneli-car-inquiry'), 'type' => 'switch', 'default' => '1'],
                        ]
                    ],
                ]
            ],
        ];
    }
}