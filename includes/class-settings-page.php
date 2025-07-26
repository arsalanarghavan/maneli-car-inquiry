<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Settings_Page {

    private $active_tab;
    private $options_name = 'maneli_inquiry_all_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
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
        $this->active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'finotex';
        ?>
        <div class="wrap">
            <h1>تنظیمات پلاگین استعلام خودرو</h1>
            <?php settings_errors(); ?>
            <h2 class="nav-tab-wrapper">
                <a href="?page=maneli-inquiry-settings&tab=finotex" class="nav-tab <?php echo $this->active_tab == 'finotex' ? 'nav-tab-active' : ''; ?>">تنظیمات فینوتک</a>
                <a href="?page=maneli-inquiry-settings&tab=payment" class="nav-tab <?php echo $this->active_tab == 'payment' ? 'nav-tab-active' : ''; ?>">تنظیمات پرداخت</a>
                <a href="?page=maneli-inquiry-settings&tab=sms" class="nav-tab <?php echo $this->active_tab == 'sms' ? 'nav-tab-active' : ''; ?>">تنظیمات پیامک</a>
                <a href="?page=maneli-inquiry-settings&tab=experts" class="nav-tab <?php echo $this->active_tab == 'experts' ? 'nav-tab-active' : ''; ?>">مدیریت کارشناسان</a>
            </h2>

            <form method="post" action="options.php">
                <?php
                settings_fields('maneli_inquiry_settings_group');
                if ($this->active_tab == 'finotex') {
                    do_settings_sections('maneli-finotex-settings-section');
                } elseif ($this->active_tab == 'payment') {
                    do_settings_sections('maneli-payment-settings-section');
                } elseif ($this->active_tab == 'experts') {
                    do_settings_sections('maneli-experts-settings-section');
                } else { // sms tab
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

        // Finotex Section
        add_settings_section('maneli_finotex_cheque_section', 'سرویس استعلام رنگ چک', null, 'maneli-finotex-settings-section');
        add_settings_field('finotex_client_id', 'شناسه کلاینت (Client ID)', [$this, 'render_field'], 'maneli-finotex-settings-section', 'maneli_finotex_cheque_section', ['name' => 'finotex_client_id']);
        add_settings_field('finotex_api_key', 'توکن دسترسی (Access Token)', [$this, 'render_field'], 'maneli-finotex-settings-section', 'maneli_finotex_cheque_section', ['name' => 'finotex_api_key', 'type' => 'textarea']);

        // Payment Settings Section
        add_settings_section('maneli_payment_section', 'تنظیمات هزینه استعلام و زرین‌پال', null, 'maneli-payment-settings-section');
        add_settings_field('payment_enable', 'فعال‌سازی مرحله پرداخت', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_payment_section', ['name' => 'payment_enable', 'type' => 'checkbox', 'desc' => 'کاربر قبل از تکمیل اطلاعات، باید هزینه استعلام را پرداخت کند.']);
        add_settings_field('inquiry_fee', 'هزینه استعلام (تومان)', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_payment_section', ['name' => 'inquiry_fee', 'type' => 'number', 'desc' => 'مبلغ را به تومان وارد کنید. برای رایگان بودن، عدد 0 را وارد کنید.']);
        add_settings_field('zarinpal_merchant_code', 'مرچنت کد زرین‌پال', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_payment_section', ['name' => 'zarinpal_merchant_code']);
        add_settings_field('zero_fee_message', 'پیام در صورت رایگان بودن استعلام', [$this, 'render_field'], 'maneli-payment-settings-section', 'maneli_payment_section', ['name' => 'zero_fee_message', 'type' => 'textarea', 'desc' => 'این پیام زمانی نمایش داده می‌شود که هزینه استعلام 0 باشد یا کد تخفیف 100% اعمال شود.']);
        
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
        add_settings_section(
            'maneli_experts_list_section',
            'مدیریت چرخشی کارشناسان',
            [$this, 'render_experts_description'],
            'maneli-experts-settings-section'
        );
    }
    
    public function sanitize_and_merge_options($input) {
        $old_options = get_option($this->options_name, []);
        $new_input = $input;
        if (!isset($input['payment_enable'])) {
            $new_input['payment_enable'] = '0';
        }
        $merged_options = array_merge($old_options, $new_input);
        $sanitized_options = [];
        foreach ($merged_options as $key => $value) {
            if (is_string($value)) {
                if (strpos($key, 'template') !== false || $key === 'experts_list' || $key === 'zero_fee_message') {
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
            case 'checkbox':
                echo "<label><input type='checkbox' name='{$field_name}' value='1' " . checked(1, $value, false) . "></label>";
                break;
            case 'textarea':
                echo "<textarea name='{$field_name}' rows='5' class='large-text'>" . esc_textarea($value) . "</textarea>";
                break;
            default:
                echo "<input type='{$type}' name='{$field_name}' value='" . esc_attr($value) . "' class='regular-text' dir='ltr'>";
                break;
        }
        if ($desc) {
            echo "<p class='description'>" . esc_html($desc) . "</p>";
        }
    }

    public function render_sms_patterns_description() {
        echo '<p>در این بخش، به جای متن کامل پیامک، فقط **کد پترن (Body ID)** که در پنل ملی پیامک شما تایید شده است را وارد کنید.</p>';
    }

    public function render_experts_description() {
        echo '<p>سیستم به صورت خودکار تمام کاربرانی که نقش کاربری آن‌ها <strong>«کارشناس مانلی»</strong> باشد را به عنوان کارشناس فروش شناسایی می‌کند.</p>';
        echo '<p>استعلام‌ها به صورت گردشی (Round-robin) و به ترتیب به این کارشناسان ارجاع داده خواهد شد.</p>';
        echo '<p>برای افزودن کارشناس جدید، کافیست از منوی <strong>کاربران > افزودن کاربر</strong>، یک کاربر جدید با نقش «کارشناس مانلی» بسازید و شماره موبایل او را در پروفایلش (فیلد "شماره موبایل") وارد کنید.</p>';
    }
}