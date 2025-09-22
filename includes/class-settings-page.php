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
                <a href="?page=maneli-inquiry-settings&tab=display" class="nav-tab <?php echo $active_tab == 'display' ? 'nav-tab-active' : ''; ?>">تنظیمات نمایش</a>
            </h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('maneli_inquiry_settings_group');
                do_settings_sections('maneli-' . $active_tab . '-settings-section');
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

        foreach (array_keys($this->get_all_settings()) as $tab) {
            $this->register_tab_settings($tab);
        }
    }

    private function register_tab_settings($tab) {
        $settings = $this->get_all_settings();
        if (!isset($settings[$tab])) return;

        foreach ($settings[$tab] as $section_id => $section) {
            add_settings_section(
                $section_id,
                $section['title'],
                function() use ($section) {
                    if (!empty($section['desc'])) {
                        echo '<p>' . wp_kses_post($section['desc']) . '</p>';
                    }
                },
                'maneli-' . $tab . '-settings-section'
            );

            if (empty($section['fields'])) continue;

            foreach ($section['fields'] as $field) {
                add_settings_field(
                    $field['name'],
                    $field['label'],
                    [$this, 'render_field'],
                    'maneli-' . $tab . '-settings-section',
                    $section_id,
                    $field
                );
            }
        }
    }
    
    public function sanitize_and_merge_options($input) {
        $old_options = get_option($this->options_name, []);

        // Sanitize new input first
        $sanitized_input = [];
        foreach ($input as $key => $value) {
             if (is_array($value)) {
                $sanitized_input[$key] = $value;
            } elseif (in_array($key, ['sadad_key', 'finotex_api_key', 'zero_fee_message'])) {
                $sanitized_input[$key] = sanitize_textarea_field($value);
            } else {
                $sanitized_input[$key] = sanitize_text_field($value);
            }
        }

        // Merge sanitized new input over old options
        $merged_options = array_merge($old_options, $sanitized_input);

        // Handle checkboxes that might be unchecked
        $all_settings = $this->get_all_settings();
        $active_tab_checkboxes = [];

        $active_tab_key = 'gateways'; // Default tab
        if (isset($_POST['_wp_http_referer'])) {
            parse_str(parse_url(wp_unslash($_POST['_wp_http_referer']), PHP_URL_QUERY), $query_params);
            if (!empty($query_params['tab'])) {
                $active_tab_key = $query_params['tab'];
            }
        }
        
        if (isset($all_settings[$active_tab_key])) {
            foreach ($all_settings[$active_tab_key] as $section) {
                if(isset($section['fields'])) {
                    foreach($section['fields'] as $field) {
                        if(isset($field['type']) && $field['type'] === 'switch') {
                           $active_tab_checkboxes[] = $field['name'];
                        }
                    }
                }
            }
        }
        
        foreach($active_tab_checkboxes as $cb) {
            if (!isset($input[$cb])) {
               $merged_options[$cb] = '0';
            }
        }
        
        return $merged_options;
    }

    public function render_field($args) {
        $options = get_option($this->options_name, []);
        $name = $args['name'];
        $type = $args['type'] ?? 'text';
        $desc = $args['desc'] ?? '';
        $value = $options[$name] ?? '';
        $field_name = "{$this->options_name}[{$name}]";
        
        switch ($type) {
            case 'textarea':
                echo "<textarea name='{$field_name}' rows='3' class='large-text' dir='ltr'>" . esc_textarea($value) . "</textarea>";
                break;
            case 'switch':
                echo '<label class="maneli-switch">';
                echo "<input type='checkbox' name='{$field_name}' value='1' " . checked('1', $value, false) . '>';
                echo '<span class="maneli-slider round"></span></label>';
                break;
            default:
                echo "<input type='{$type}' name='{$field_name}' value='" . esc_attr($value) . "' class='regular-text' dir='ltr'>";
                break;
        }

        if ($desc) {
            echo "<p class='description'>" . wp_kses_post($desc) . "</p>";
        }
    }

    public function render_frontend_settings_form($tab) {
        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper maneli-frontend-settings">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_save_frontend_settings">
                <?php wp_nonce_field('maneli_save_frontend_settings_nonce'); ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url(wp_unslash($_SERVER['REQUEST_URI'])); ?>">

                <?php $this->manually_render_settings_tab($tab); ?>
                
                 <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="ذخیره تغییرات">
                </p>
            </form>
        </div>
        <style>
            .maneli-frontend-settings .form-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
            .maneli-frontend-settings .form-table th, .maneli-frontend-settings .form-table td { padding: 15px 10px; text-align: right; border-bottom: 1px solid #eee; }
            .maneli-frontend-settings .form-table th { width: 220px; font-weight: bold; }
            .maneli-frontend-settings input[type="text"], .maneli-frontend-settings input[type="number"], .maneli-frontend-settings input[type="password"], .maneli-frontend-settings textarea { width: 100%; max-width: 450px; border-radius: 4px; border: 1px solid #ccc; padding: 8px; }
            .maneli-frontend-settings .description { font-size: 13px; color: #666; }
            .maneli-frontend-settings .button-primary { font-size: 16px !important; padding: 8px 20px !important; height: auto !important; }
            .maneli-frontend-settings h3 { margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
            .maneli-frontend-settings .expert-list-table { width: 100%; }
            .maneli-frontend-settings .user-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
            .maneli-frontend-settings .user-list-header a.button { font-size: 14px !important; padding: 4px 15px !important; height: auto !important; }
            .maneli-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
            .maneli-switch input { opacity: 0; width: 0; height: 0; }
            .maneli-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
            .maneli-slider.round { border-radius: 28px; }
            .maneli-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .maneli-slider { background-color: #2D89BE; }
            input:checked + .maneli-slider:before { transform: translateX(22px); }
        </style>
        <?php
        return ob_get_clean();
    }
    
    private function manually_render_settings_tab($tab) {
        $all_settings = $this->get_all_settings();

        if ($tab === 'experts') {
            $section = $all_settings['experts']['maneli_experts_list_section'];
            echo "<h3>" . esc_html($section['title']) . "</h3>";
            echo '<p>' . wp_kses_post($section['desc']) . '</p>';
            $expert_users = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name']);
            if (!empty($expert_users)) {
                echo '<table class="shop_table shop_table_responsive expert-list-table">';
                echo '<thead><tr><th>نام کارشناس</th><th>ایمیل</th><th>شماره موبایل</th></tr></thead>';
                echo '<tbody>';
                foreach ($expert_users as $expert) {
                    echo '<tr>';
                    echo '<td data-title="نام">' . esc_html($expert->display_name) . '</td>';
                    echo '<td data-title="ایمیل">' . esc_html($expert->user_email) . '</td>';
                    echo '<td data-title="موبایل">' . esc_html(get_user_meta($expert->ID, 'mobile_number', true) ?: ' ثبت نشده') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>در حال حاضر هیچ کارشناسی ثبت نشده است.</p>';
            }
            return;
        }

        if (!isset($all_settings[$tab])) {
            echo '<div class="error-box"><p>بخش تنظیمات مورد نظر یافت نشد.</p></div>';
            return;
        }

        foreach ($all_settings[$tab] as $section_id => $section) {
            echo "<h3>" . esc_html($section['title']) . "</h3>";
            if (!empty($section['desc'])) echo '<p>' . wp_kses_post($section['desc']) . '</p>';
            
            if (empty($section['fields'])) continue;

            echo '<table class="form-table">';
            foreach ($section['fields'] as $field) {
                echo '<tr>';
                echo '<th scope="row"><label for="' . esc_attr($this->options_name . '_' . $field['name']) . '">' . esc_html($field['label']) . '</label></th>';
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
            'gateways' => [
                'maneli_payment_general_section' => [
                    'title' => 'تنظیمات عمومی پرداخت', 'desc' => '',
                    'fields' => [
                        ['name' => 'payment_enabled', 'label' => 'فعال‌سازی درگاه پرداخت', 'type' => 'switch', 'desc' => 'با فعال‌سازی این گزینه، مرحله پرداخت هزینه استعلام برای کاربران نمایش داده می‌شود.'],
                        ['name' => 'inquiry_fee', 'label' => 'هزینه استعلام (تومان)', 'type' => 'number', 'desc' => 'مبلغ را به تومان وارد کنید. برای رایگان بودن، عدد 0 را وارد کنید.'],
                        ['name' => 'zero_fee_message', 'label' => 'پیام در صورت رایگان بودن استعلام', 'type' => 'textarea', 'desc' => 'این پیام زمانی نمایش داده می‌شود که هزینه استعلام 0 باشد.'],
                    ]
                ],
                 'maneli_discount_section' => [
                    'title' => 'تنظیمات کد تخفیف', 'desc' => '',
                    'fields' => [
                        ['name' => 'discount_code', 'label' => 'کد تخفیف', 'type' => 'text', 'desc' => 'یک کد تخفیف برای ۱۰۰٪ تخفیف در هزینه استعلام وارد کنید.'],
                        ['name' => 'discount_code_text', 'label' => 'متن پیام کد تخفیف', 'type' => 'text', 'desc' => 'این پیام پس از اعمال کد تخفیف موفق به کاربر نمایش داده می‌شود.'],
                    ]
                ],
                'maneli_zarinpal_section' => [
                    'title' => 'تنظیمات زرین‌پال', 'desc' => '',
                    'fields' => [
                         ['name' => 'zarinpal_enabled', 'label' => 'فعال‌سازی درگاه زرین‌پال', 'type' => 'switch'],
                         ['name' => 'zarinpal_merchant_code', 'label' => 'مرچنت کد زرین‌پال', 'type' => 'text'],
                    ]
                ],
                'maneli_sadad_section' => [
                    'title' => 'تنظیمات درگاه پرداخت سداد (بانک ملی)', 'desc' => '',
                    'fields' => [
                        ['name' => 'sadad_enabled', 'label' => 'فعال‌سازی درگاه سداد', 'type' => 'switch'],
                        ['name' => 'sadad_merchant_id', 'label' => 'شناسه مرچنت (Merchant ID)', 'type' => 'text'],
                        ['name' => 'sadad_terminal_id', 'label' => 'شناسه ترمینال (Terminal ID)', 'type' => 'text'],
                        ['name' => 'sadad_key', 'label' => 'کلید ترمینال (Terminal Key)', 'type' => 'textarea', 'desc' => 'این کلید به صورت base64 توسط بانک ارائه می‌شود.'],
                    ]
                ]
            ],
            'sms' => [
                'maneli_sms_api_section' => [
                    'title' => 'اطلاعات پنل ملی پیامک', 'desc' => '',
                    'fields' => [
                        ['name' => 'sms_username', 'label' => 'نام کاربری', 'type' => 'text'],
                        ['name' => 'sms_password', 'label' => 'رمز عبور', 'type' => 'password'],
                    ]
                ],
                'maneli_sms_patterns_section' => [
                    'title' => 'کدهای پترن پیامک (Body ID)',
                    'desc' => 'در این بخش، به جای متن کامل پیامک، فقط <strong>کد پترن (Body ID)</strong> که در پنل ملی پیامک شما تایید شده است را وارد کنید.<br>ترتیب متغیرها باید دقیقاً مطابق توضیحات هر فیلد باشد.',
                    'fields' => [
                        ['name' => 'sms_pattern_pending', 'label' => 'پترن «در انتظار بررسی» (به مشتری)', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                        ['name' => 'sms_pattern_approved', 'label' => 'پترن «تایید شده» (به مشتری)', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                        ['name' => 'sms_pattern_rejected', 'label' => 'پترن «رد شده» (به مشتری)', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو 3. دلیل رد'],
                        ['name' => 'sms_pattern_more_docs', 'label' => 'پترن «نیازمند مدارک» (به مشتری)', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                        ['name' => 'admin_notification_mobile', 'label' => 'شماره موبایل مدیر', 'type' => 'text', 'desc' => 'شماره موبایل برای دریافت پیام ثبت استعلام جدید.'],
                        ['name' => 'sms_pattern_new_inquiry', 'label' => 'پترن «استعلام جدید» (به مدیر)', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                        ['name' => 'sms_pattern_expert_referral', 'label' => 'پترن «ارجاع به کارشناس»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام کارشناس 2. نام مشتری 3. موبایل مشتری 4. نام خودرو'],
                    ]
                ]
            ],
            'experts' => [
                'maneli_experts_list_section' => [
                    'title' => 'مدیریت کارشناسان',
                    'desc' => 'سیستم به صورت خودکار تمام کاربرانی که نقش کاربری آن‌ها <strong>«کارشناس مانلی»</strong> باشد را به عنوان کارشناس فروش شناسایی می‌کند.<br>استعلام‌ها به صورت گردشی (Round-robin) و به ترتیب به این کارشناسان ارجاع داده خواهد شد.<br>برای افزودن کارشناس جدید، کافیست از منوی <strong>کاربران > افزودن کاربر</strong>، یک کاربر جدید با نقش «کارشناس مانلی» بسازید و شماره موبایل او را در پروفایلش (فیلد "شماره موبایل") وارد کنید.',
                    'fields' => []
                ]
            ],
            'finotex' => [
                'maneli_finotex_cheque_section' => [
                    'title' => 'سرویس استعلام رنگ چک', 'desc' => '',
                    'fields' => [
                        ['name' => 'finotex_enabled', 'label' => 'فعال‌سازی استعلام فینوتک', 'type' => 'switch', 'desc' => 'در صورت فعال بودن، در زمان ثبت درخواست، استعلام بانکی از فینوتک انجام می‌شود.'],
                        ['name' => 'finotex_client_id', 'label' => 'شناسه کلاینت (Client ID)', 'type' => 'text'],
                        ['name' => 'finotex_api_key', 'label' => 'توکن دسترسی (Access Token)', 'type' => 'textarea'],
                    ]
                ]
            ],
            'display' => [
                'maneli_display_price_section' => [
                    'title' => 'تنظیمات نمایش قیمت',
                    'desc' => 'در این بخش می‌توانید نحوه نمایش قیمت‌ها را در سایت برای کاربران عادی (مشتریان) کنترل کنید.',
                    'fields' => [
                        ['name' => 'hide_prices_for_customers', 'label' => 'مخفی کردن قیمت برای مشتریان', 'type' => 'switch', 'desc' => 'با فعال کردن این گزینه، قیمت‌ها در تمام بخش‌های فروشگاه برای کاربرانی که مدیر نیستند، مخفی می‌شود.'],
                    ]
                ],
                'maneli_display_attributes_section' => [
                    'title' => 'تنظیمات نمایش ویژگی‌ها',
                    'desc' => 'قالب‌بندی نمایش جدول ویژگی‌های محصول را کنترل کنید.',
                    'fields' => [
                        ['name' => 'enable_grouped_attributes', 'label' => 'فعال‌سازی نمایش گروهی ویژگی‌ها', 'type' => 'switch', 'desc' => 'با فعال کردن این گزینه، جدول ویژگی‌ها بر اساس گروه (مثال: فنی - ابعاد) دسته‌بندی و نمایش داده می‌شود.'],
                    ]
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