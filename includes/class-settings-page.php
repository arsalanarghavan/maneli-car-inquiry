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
                        'title' => esc_html__('SMS Pattern Codes (Body ID) - General & Installment', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Enter only the approved Pattern Code (Body ID) from your SMS panel. Variable order must match the descriptions.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'admin_notification_mobile', 'label' => esc_html__('Admin Mobile Number', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Mobile number to receive new request notifications.', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_new_inquiry', 'label' => esc_html__('Pattern: "New Request for Admin"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_pending', 'label' => esc_html__('Pattern: "Pending Review" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_approved', 'label' => esc_html__('Pattern: "Approved" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_rejected', 'label' => esc_html__('Pattern: "Rejected" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_expert_referral', 'label' => esc_html__('Pattern: "Referral to Expert" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_cash_inquiry_sms_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Request', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'cash_inquiry_approved_pattern', 'label' => esc_html__('Pattern: "Cash Request Approved"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Sent to customer after down payment is set. Variables: 1. Customer Name 2. Car Name 3. Down Payment Amount', 'maneli-car-inquiry')],
                            ['name' => 'cash_inquiry_rejected_pattern', 'label' => esc_html__('Pattern: "Cash Request Rejected"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Sent to customer after rejection. Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'maneli-car-inquiry')],
                            ['name' => 'cash_inquiry_expert_referral_pattern', 'label' => esc_html__('Pattern: "Cash Request Referral to Expert"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Sent to expert. Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'maneli-car-inquiry')],
                        ]
                    ]
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
                    'maneli_expert_decision_section' => [
                        'title' => esc_html__('Expert Decision Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            [
                                'name' => 'expert_statuses',
                                'label' => esc_html__('Statuses (one per line: key|label|#color)', 'maneli-car-inquiry'),
                                'type' => 'textarea',
                                'default' => "unknown|" . esc_html__('Unknown', 'maneli-car-inquiry') . "|#9CA3AF\nprogress|" . esc_html__('Follow-up in Progress', 'maneli-car-inquiry') . "|#3B82F6\napproved|" . esc_html__('Approved', 'maneli-car-inquiry') . "|#10B981\nrejected|" . esc_html__('Rejected', 'maneli-car-inquiry') . "|#EF4444",
                                'desc' => esc_html__('Example: unknown|Unknown|#999999', 'maneli-car-inquiry')
                            ],
                            [
                                'name' => 'expert_default_status',
                                'label' => esc_html__('Default Expert Status Key', 'maneli-car-inquiry'),
                                'type' => 'text',
                                'default' => 'unknown'
                            ],
                        ]
                    ],
                    'maneli_installment_rejection_reasons_section' => [
                        'title' => esc_html__('Installment Rejection Reasons', 'maneli-car-inquiry'),
                        'fields' => [
                             ['name' => 'installment_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a list to the admin when rejecting an installment request.', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_installment_required_documents_section' => [
                        'title' => esc_html__('Required Documents', 'maneli-car-inquiry'),
                        'fields' => [
                             ['name' => 'installment_required_documents', 'label' => esc_html__('Document List', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one document name per line. These will be shown to the manager when requesting additional documents from customers.', 'maneli-car-inquiry')],
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
                    'maneli_finotex_cheque_section' => [
                        'title' => esc_html__('Cheque Color Inquiry Service', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'finotex_enabled', 'label' => esc_html__('Enable Finotex Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('Enable the cheque color inquiry via the Finotex service.', 'maneli-car-inquiry')],
                            // Encrypted Fields
                            ['name' => 'finotex_username', 'label' => esc_html__('Finotex Client ID', 'maneli-car-inquiry'), 'type' => 'password', 'desc' => esc_html__('Client ID provided by Finotex. Stored securely encrypted.', 'maneli-car-inquiry')],
                            ['name' => 'finotex_password', 'label' => esc_html__('Finotex API Key', 'maneli-car-inquiry'), 'type' => 'password', 'desc' => esc_html__('API Key (Access Token) provided by Finotex. Stored securely encrypted.', 'maneli-car-inquiry')],
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
                    ]
                ]
            ],
        ];
    }
}