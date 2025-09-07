<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Settings_Page {

    private $options_name = 'maneli_inquiry_all_options';
    private $settings_registered = false;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_maneli_save_frontend_settings', [$this, 'handle_frontend_settings_save']);
    }

    public function add_plugin_menu() {
        add_options_page(
            'تنظیمات استعلام خودرو',
            'تنظیمات استعلام خودرو',
            'manage_options',
            'maneli-inquiry-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'gateways';
        ?>
        <div class="wrap">
            <h1>تنظیمات پلاگین استعلام خودرو</h1>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=maneli-inquiry-settings&tab=gateways" class="nav-tab <?php echo $active_tab == 'gateways' ? 'nav-tab-active' : ''; ?>">درگاه‌های پرداخت</a>
                <a href="?page=maneli-inquiry-settings&tab=sms" class="nav-tab <?php echo $active_tab == 'sms' ? 'nav-tab-active' : ''; ?>">تنظیمات پیامک</a>
                <a href="?page=maneli-inquiry-settings&tab=experts" class="nav-tab <?php echo $active_tab == 'experts' ? 'nav-tab-active' : ''; ?>">مدیریت کارشناسان</a>
                <a href="?page=maneli-inquiry-settings&tab=finotex" class="nav-tab <?php echo $active_tab == 'finotex' ? 'nav-tab-active' : ''; ?>">تنظیمات فینوتک</a>
            </h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('maneli_inquiry_settings_group');
                if ($active_tab == 'finotex') {
                    do_settings_sections('maneli-finotex-settings-section');
                } elseif ($active_tab == 'gateways') {
                    do_settings_sections('maneli-payment-settings-section');
                } elseif ($active_tab == 'experts') {
                    do_settings_sections('maneli-experts-settings-section');
                } else {
                    do_settings_sections('maneli-sms-settings-section');
                }
                submit_button('ذخیره تنظیمات');
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting(
            'maneli_inquiry_settings_group',
            $this->options_name,
            [$this, 'sanitize_and_merge_options']
        );
        $this->register_all_settings_sections();
    }

    public function register_all_settings_sections() {
        if ($this->settings_registered) {
            return;
        }

        // Finotex Section
        add_settings_section('maneli_finotex_cheque_section', 'سرویس استعلام رنگ چک', null, 'maneli-finotex-settings-section');
        add_settings_field('finotex_enabled', 'فعال‌سازی استعلام فینوتک', [$this, 'render_field'], 'maneli-finotex-settings-section', 'maneli_finotex_cheque_section', ['name' => 'finotex_enabled', 'type' => 'checkbox', 'desc' => 'در صورت فعال بودن، در زمان ثبت درخواست، استعلام بانکی از فینوتک انجام می‌شود.']);
        add_settings_field('finotex_client_id', 'شناسه کلاینت (Client ID)', [$this, 'render_field'], 'maneli-finotex-settings-section', 'maneli_finotex_cheque_section', ['name' => 'finotex_client_id']);
        add_settings_field('finotex_api_key', 'توکن دسترسی (Access Token)', [$this, 'render_field'], 'maneli-finotex-settings-section', 'maneli_finotex_cheque_section', ['name' => 'finotex_api_key', 'type' => 'textarea']);
        
        // Payment Settings Section (Gateways)
        add_settings_section('maneli_payment_general_section', 'تنظیمات عمومی پرداخت', null, 'maneli-payment-settings-section');
        add_settings_field('inquiry_fee', 'هزینه استعلام (تومان)', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_payment_general_section', ['name' => 'inquiry_fee', 'type' => 'number', 'desc' => 'مبلغ را به تومان وارد کنید. برای رایگان بودن، عدد 0 را وارد کنید.']);
        add_settings_field('active_gateway', 'درگاه پرداخت فعال', [$this, 'render_gateway_choice_field'], 'maneli-payment-settings-section', 'maneli_payment_general_section');
        add_settings_field('zero_fee_message', 'پیام در صورت رایگان بودن استعلام', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_payment_general_section', ['name' => 'zero_fee_message', 'type' => 'textarea', 'desc' => 'این پیام زمانی نمایش داده می‌شود که هزینه استعلام 0 باشد.']);
        
        // ZarinPal Section
        add_settings_section('maneli_zarinpal_section', 'تنظیمات زرین‌پال', null, 'maneli-payment-settings-section');
        add_settings_field('zarinpal_merchant_code', 'مرچنت کد زرین‌پال', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_zarinpal_section', ['name' => 'zarinpal_merchant_code']);
        
        // Sadad Section
        add_settings_section('maneli_sadad_section', 'تنظیمات درگاه پرداخت سداد (بانک ملی)', null, 'maneli-payment-settings-section');
        add_settings_field('sadad_merchant_id', 'شناسه مرچنت (Merchant ID)', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_sadad_section', ['name' => 'sadad_merchant_id']);
        add_settings_field('sadad_terminal_id', 'شناسه ترمینال (Terminal ID)', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_sadad_section', ['name' => 'sadad_terminal_id']);
        add_settings_field('sadad_key', 'کلید ترمینال (Terminal Key)', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_sadad_section', ['name' => 'sadad_key', 'type' => 'textarea', 'desc' => 'این کلید به صورت base64 توسط بانک ارائه می‌شود.']);

        // Discount Code Section
        add_settings_section('maneli_discount_section', 'تنظیمات کد تخفیف', null, 'maneli-payment-settings-section');
        add_settings_field('discount_code', 'کد تخفیف', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_discount_section', ['name' => 'discount_code', 'desc' => 'یک کد تخفیف برای ۱۰۰٪ تخفیف در هزینه استعلام وارد کنید.']);
        add_settings_field('discount_code_text', 'متن پیام کد تخفیف', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_discount_section', ['name' => 'discount_code_text', 'type' => 'text', 'desc' => 'این پیام پس از اعمال کد تخفیف موفق به کاربر نمایش داده می‌شود.']);

        // SMS Section
        add_settings_section('maneli_sms_api_section', 'اطلاعات پنل ملی پیامک', null, 'maneli-sms-settings-section');
        add_settings_field('sms_username', 'نام کاربری', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_api_section', ['name' => 'sms_username']);
        add_settings_field('sms_password', 'رمز عبور', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_api_section', ['name' => 'sms_password', 'type' => 'password']);

        add_settings_section('maneli_sms_patterns_section', 'کدهای پترن پیامک (Body ID)', [$this, 'render_sms_patterns_description'], 'maneli-sms-settings-section');
        add_settings_field('sms_pattern_pending', 'پترن «در انتظار بررسی» (به مشتری)', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'sms_pattern_pending', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو']);
        add_settings_field('sms_pattern_approved', 'پترن «تایید شده» (به مشتری)', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'sms_pattern_approved', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو']);
        add_settings_field('sms_pattern_rejected', 'پترن «رد شده» (به مشتری)', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'sms_pattern_rejected', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو 3. دلیل رد']);
        add_settings_field('sms_pattern_more_docs', 'پترن «نیازمند مدارک» (به مشتری)', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'sms_pattern_more_docs', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو']);
        add_settings_field('admin_notification_mobile', 'شماره موبایل مدیر', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'admin_notification_mobile', 'desc' => 'شماره موبایل برای دریافت پیام ثبت استعلام جدید.']);
        add_settings_field('sms_pattern_new_inquiry', 'پترن «استعلام جدید» (به مدیر)', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'sms_pattern_new_inquiry', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو']);
        add_settings_field('sms_pattern_expert_referral', 'پترن «ارجاع به کارشناس»', [$this, 'render_field'], 'maneli-sms-settings-section', 'maneli_sms_patterns_section', ['name' => 'sms_pattern_expert_referral', 'type' => 'number', 'desc' => 'متغیرها: 1. نام کارشناس 2. نام مشتری 3. موبایل مشتری 4. نام خودرو']);

        // Experts Section
        add_settings_section('maneli_experts_list_section', 'مدیریت چرخشی کارشناسان', [$this, 'render_experts_description'], 'maneli-experts-settings-section');

        $this->settings_registered = true;
    }
    
    public function sanitize_and_merge_options($input) {
        $old_options = get_option($this->options_name, []);

        if (!isset($input['finotex_enabled'])) {
            $input['finotex_enabled'] = '0';
        }

        $merged_options = array_merge($old_options, $input);
        $sanitized_options = [];
        foreach ($merged_options as $key => $value) {
            if (is_array($value)) {
                $sanitized_options[$key] = $value;
            } elseif (is_string($value)) {
                 if ($key === 'sadad_key' || $key === 'finotex_api_key' || $key === 'zero_fee_message') {
                     $sanitized_options[$key] = sanitize_textarea_field($value);
                 } else {
                     $sanitized_options[$key] = sanitize_text_field($value);
                 }
            } else {
                $sanitized_options[$key] = $value;
            }
        }
        return $sanitized_options;
    }

    public function render_field($args) {
        $options = get_option($this->options_name, []);
        $name = $args['name'];
        $type = $args['type'] ?? 'text';
        $desc = $args['desc'] ?? '';
        $value = isset($options[$name]) ? $options[$name] : '';
        $field_name = "{$this->options_name}[{$name}]";
        
        switch ($type) {
            case 'textarea':
                echo "<textarea name='{$field_name}' rows='3' class='large-text' dir='ltr'>" . esc_textarea($value) . "</textarea>";
                break;
            case 'checkbox':
                $checked = checked('1', $value, false);
                echo "<label><input type='checkbox' name='{$field_name}' value='1' {$checked}></label>";
                break;
            default:
                echo "<input type='{$type}' name='{$field_name}' value='" . esc_attr($value) . "' class='regular-text' dir='ltr'>";
                break;
        }

        if ($desc) {
            echo "<p class='description'>" . esc_html($desc) . "</p>";
        }
    }

    public function render_gateway_choice_field() {
        $options = get_option($this->options_name, []);
        $active_gateway = $options['active_gateway'] ?? 'zarinpal';
        ?>
        <label style="margin-right: 20px;">
            <input type="radio" name="<?php echo $this->options_name; ?>[active_gateway]" value="zarinpal" <?php checked($active_gateway, 'zarinpal'); ?>>
            زرین‌پال
        </label>
        <label>
            <input type="radio" name="<?php echo $this->options_name; ?>[active_gateway]" value="sadad" <?php checked($active_gateway, 'sadad'); ?>>
            پرداخت سداد (بانک ملی)
        </label>
        <?php
    }

    public function render_sms_patterns_description() {
        echo '<p>در این بخش، به جای متن کامل پیامک، فقط **کد پترن (Body ID)** که در پنل ملی پیامک شما تایید شده است را وارد کنید.</p><p>ترتیب متغیرها باید دقیقاً مطابق توضیحات هر فیلد باشد.</p>';
    }

    public function render_experts_description() {
        echo '<p>سیستم به صورت خودکار تمام کاربرانی که نقش کاربری آن‌ها <strong>«کارشناس مانلی»</strong> باشد را به عنوان کارشناس فروش شناسایی می‌کند.</p>';
        echo '<p>استعلام‌ها به صورت گردشی (Round-robin) و به ترتیب به این کارشناسان ارجاع داده خواهد شد.</p>';
        echo '<p>برای افزودن کارشناس جدید، کافیست از منوی <strong>کاربران > افزودن کاربر</strong>، یک کاربر جدید با نقش «کارشناس مانلی» بسازید و شماره موبایل او را در پروفایلش (فیلد "شماره موبایل") وارد کنید.</p>';
    }

    public function render_frontend_settings_form($tab) {
        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper maneli-frontend-settings">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_save_frontend_settings">
                <?php wp_nonce_field('maneli_save_frontend_settings_nonce'); ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url(wp_unslash($_SERVER['REQUEST_URI'])); ?>">

                <?php
                // CRITICAL FIX: Manually render the form fields for the specified tab.
                $this->manually_render_settings_tab($tab);
                ?>
                 <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="ذخیره تغییرات">
                </p>
            </form>
        </div>
        <style>
            .maneli-frontend-settings .form-table { width: 100%; }
            .maneli-frontend-settings .form-table th { width: 200px; padding: 15px 10px; text-align: right; }
            .maneli-frontend-settings .form-table td { padding: 10px; }
            .maneli-frontend-settings input[type="text"], .maneli-frontend-settings input[type="number"], .maneli-frontend-settings input[type="password"], .maneli-frontend-settings textarea { width: 100%; max-width: 400px; border-radius: 4px; border: 1px solid #ccc; padding: 8px; }
            .maneli-frontend-settings .description { font-size: 13px; color: #666; }
            .maneli-frontend-settings .button-primary { font-size: 16px !important; padding: 8px 20px !important; height: auto !important; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    private function manually_render_settings_tab($tab) {
        $all_settings = $this->get_all_settings();

        if (!isset($all_settings[$tab])) {
            echo '<div class="error-box"><p>بخش تنظیمات مورد نظر یافت نشد.</p></div>';
            return;
        }

        $sections = $all_settings[$tab];
        foreach ($sections as $section_id => $section) {
            echo "<h3>" . esc_html($section['title']) . "</h3>";
            if (!empty($section['desc'])) {
                echo '<p>' . esc_html($section['desc']) . '</p>';
            }
            echo '<table class="form-table">';
            foreach ($section['fields'] as $field) {
                echo '<tr>';
                echo '<th scope="row"><label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . '</label></th>';
                echo '<td>';
                $this->render_field($field);
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }

    private function get_all_settings() {
        return [
            'finotex' => [
                'main' => [
                    'title' => 'سرویس استعلام رنگ چک',
                    'fields' => [
                        ['name' => 'finotex_enabled', 'label' => 'فعال‌سازی استعلام فینوتک', 'type' => 'checkbox', 'desc' => 'در صورت فعال بودن، در زمان ثبت درخواست، استعلام بانکی از فینوتک انجام می‌شود.'],
                        ['name' => 'finotex_client_id', 'label' => 'شناسه کلاینت (Client ID)', 'type' => 'text'],
                        ['name' => 'finotex_api_key', 'label' => 'توکن دسترسی (Access Token)', 'type' => 'textarea'],
                    ]
                ]
            ],
            'gateways' => [
                'general' => [
                    'title' => 'تنظیمات عمومی پرداخت',
                    'fields' => [
                        ['name' => 'inquiry_fee', 'label' => 'هزینه استعلام (تومان)', 'type' => 'number', 'desc' => 'مبلغ را به تومان وارد کنید. برای رایگان بودن، عدد 0 را وارد کنید.'],
                        ['name' => 'active_gateway', 'label' => 'درگاه پرداخت فعال', 'type' => 'radio', 'options' => ['zarinpal' => 'زرین‌پال', 'sadad' => 'پرداخت سداد (بانک ملی)']],
                        ['name' => 'zero_fee_message', 'label' => 'پیام در صورت رایگان بودن استعلام', 'type' => 'textarea', 'desc' => 'این پیام زمانی نمایش داده می‌شود که هزینه استعلام 0 باشد.'],
                    ]
                ],
                'zarinpal' => [
                    'title' => 'تنظیمات زرین‌پال',
                    'fields' => [
                        ['name' => 'zarinpal_merchant_code', 'label' => 'مرچنت کد زرین‌پال', 'type' => 'text'],
                    ]
                ],
                // ... other gateways and sections here ...
            ],
            'sms' => [
                'api' => [
                    'title' => 'اطلاعات پنل ملی پیامک',
                    'fields' => [
                        ['name' => 'sms_username', 'label' => 'نام کاربری', 'type' => 'text'],
                        ['name' => 'sms_password', 'label' => 'رمز عبور', 'type' => 'password'],
                    ]
                ],
                'patterns' => [
                    'title' => 'کدهای پترن پیامک (Body ID)',
                    'desc' => 'در این بخش، فقط کد پترن (Body ID) را وارد کنید.',
                    'fields' => [
                        ['name' => 'sms_pattern_pending', 'label' => 'پترن «در انتظار بررسی»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                        ['name' => 'sms_pattern_approved', 'label' => 'پترن «تایید شده»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                        // ... more sms fields
                    ]
                ]
            ],
            'experts' => [
                'main' => [
                    'title' => 'مدیریت چرخشی کارشناسان',
                    'desc' => 'سیستم به صورت خودکار تمام کاربرانی که نقش کاربری آن‌ها «کارشناس مانلی» باشد را به عنوان کارشناس فروش شناسایی می‌کند و استعلام‌ها به صورت گردشی به آن‌ها ارجاع داده خواهد شد.',
                    'fields' => [] // No fields, just description
                ]
            ]
        ];
    }
    
    public function handle_frontend_settings_save() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'maneli_save_frontend_settings_nonce')) {
            wp_die('خطای امنیتی!');
        }

        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die('شما اجازه‌ی انجام این کار را ندارید.');
        }
        
        $options = isset($_POST[$this->options_name]) ? (array) $_POST[$this->options_name] : [];
        $sanitized_options = $this->sanitize_and_merge_options($options);
        
        update_option($this->options_name, $sanitized_options);
        
        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();
        wp_redirect(add_query_arg('settings-updated', 'true', $redirect_url));
        exit;
    }
}