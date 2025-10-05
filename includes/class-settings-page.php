<?php

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Settings_Page {

    private $options_name = 'maneli_inquiry_all_options';

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
                <?php
                $all_settings = $this->get_all_settings_public();
                foreach ($all_settings as $tab_key => $tab_data) {
                    $class = ($active_tab == $tab_key) ? 'nav-tab-active' : '';
                    echo '<a href="?page=maneli-inquiry-settings&tab=' . esc_attr($tab_key) . '" class="nav-tab ' . $class . '">' . esc_html($tab_data['title']) . '</a>';
                }
                ?>
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

        foreach (array_keys($this->get_all_settings_public()) as $tab) {
            $this->register_tab_settings($tab);
        }
    }

    private function register_tab_settings($tab) {
        $settings = $this->get_all_settings_public();
        if (!isset($settings[$tab]['sections'])) return;

        foreach ($settings[$tab]['sections'] as $section_id => $section) {
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
        $sanitized_input = [];
        foreach ($input as $key => $value) {
             if (is_array($value)) {
                $sanitized_input[$key] = $value;
            } elseif (in_array($key, ['sadad_key', 'finotex_api_key', 'zero_fee_message', 'unavailable_product_message'])) {
                $sanitized_input[$key] = sanitize_text_field($value);
            } else {
                $sanitized_input[$key] = sanitize_text_field($value);
            }
        }
        $merged_options = array_merge($old_options, $sanitized_input);
        
        $all_settings = $this->get_all_settings_public();
        $all_checkboxes = [];
        foreach ($all_settings as $tab_data) {
            if (isset($tab_data['sections'])) {
                foreach ($tab_data['sections'] as $section) {
                    if (isset($section['fields'])) {
                        foreach ($section['fields'] as $field) {
                            if (isset($field['type']) && $field['type'] === 'switch') {
                                $all_checkboxes[] = $field['name'];
                            }
                        }
                    }
                }
            }
        }

        foreach($all_checkboxes as $cb) {
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
        $value = $options[$name] ?? ($args['default'] ?? '');
        $field_name = "{$this->options_name}[{$name}]";
        
        switch ($type) {
            case 'textarea':
                echo "<textarea name='{$field_name}' rows='3' class='large-text'>" . esc_textarea($value) . "</textarea>";
                break;
            case 'text':
                 echo "<input type='text' name='{$field_name}' value='" . esc_attr($value) . "' class='regular-text'>";
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
    
    public function manually_render_settings_tab_public($tab) {
        $all_settings = $this->get_all_settings_public();
        if (!isset($all_settings[$tab]['sections'])) return;
    
        if ($tab === 'experts') {
            $section = $all_settings['experts']['sections']['maneli_experts_list_section'];
            echo "<h3 class='maneli-settings-section-title'>" . esc_html($section['title']) . "</h3>";
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

        foreach ($all_settings[$tab]['sections'] as $section_id => $section) {
            echo "<h3 class='maneli-settings-section-title'>" . esc_html($section['title']) . "</h3>";
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

    public function get_all_settings_public() {
        return [
            'gateways' => [
                'title' => 'درگاه پرداخت',
                'icon' => 'fas fa-money-bill-wave',
                'sections' => [
                    'maneli_payment_general_section' => [
                        'title' => 'تنظیمات عمومی پرداخت', 'desc' => '',
                        'fields' => [
                            ['name' => 'payment_enabled', 'label' => 'فعال‌سازی درگاه پرداخت', 'type' => 'switch', 'desc' => 'با فعال‌سازی این گزینه، مرحله پرداخت هزینه استعلام برای کاربران نمایش داده می‌شود.'],
                            ['name' => 'inquiry_fee', 'label' => 'هزینه استعلام (تومان)', 'type' => 'number', 'desc' => 'مبلغ را به تومان وارد کنید. برای رایگان بودن، عدد 0 را وارد کنید.'],
                            ['name' => 'zero_fee_message', 'label' => 'پیام در صورت رایگان بودن استعلام', 'type' => 'textarea', 'desc' => 'این پیام زمانی نمایش داده می‌شود که هزینه استعلام 0 باشد.', 'default' => 'هزینه استعلام برای شما رایگان در نظر گرفته شده است. لطفاً برای ادامه روی دکمه زیر کلیک کنید.'],
                        ]
                    ],
                     'maneli_discount_section' => [
                        'title' => 'تنظیمات کد تخفیف', 'desc' => '',
                        'fields' => [
                            ['name' => 'discount_code', 'label' => 'کد تخفیف', 'type' => 'text', 'desc' => 'یک کد تخفیف برای ۱۰۰٪ تخفیف در هزینه استعلام وارد کنید.'],
                            ['name' => 'discount_code_text', 'label' => 'متن پیام کد تخفیف', 'type' => 'text', 'desc' => 'این پیام پس از اعمال کد تخفیف موفق به کاربر نمایش داده می‌شود.', 'default' => 'تخفیف ۱۰۰٪ با موفقیت اعمال شد.'],
                        ]
                    ]
                ]
            ],
            'sms' => [
                'title' => 'پیامک',
                'icon' => 'fas fa-mobile-alt',
                'sections' => [
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
                            ['name' => 'sms_pattern_pending', 'label' => 'پترن «در انتظار بررسی»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                            ['name' => 'sms_pattern_approved', 'label' => 'پترن «تایید شده»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                            ['name' => 'sms_pattern_rejected', 'label' => 'پترن «رد شده»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو 3. دلیل رد'],
                            ['name' => 'admin_notification_mobile', 'label' => 'شماره موبایل مدیر', 'type' => 'text', 'desc' => 'شماره موبایل برای دریافت پیام ثبت استعلام جدید.'],
                            ['name' => 'sms_pattern_new_inquiry', 'label' => 'پترن «استعلام جدید»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو'],
                            ['name' => 'sms_pattern_expert_referral', 'label' => 'پترن «ارجاع به کارشناس»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام کارشناس 2. نام مشتری 3. موبایل مشتری 4. نام خودرو'],
                        ]
                    ],
                    'maneli_cash_inquiry_sms_section' => [
                        'title' => 'کدهای پترن پیامک‌ درخواست نقدی',
                        'desc' => 'در این بخش کدهای پترن مربوط به فرآیند خرید نقدی را مدیریت کنید.',
                        'fields' => [
                            ['name' => 'cash_inquiry_approved_pattern', 'label' => 'پترن «تایید درخواست نقدی»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو 3. مبلغ پیش‌پرداخت'],
                            ['name' => 'cash_inquiry_rejected_pattern', 'label' => 'پترن «رد درخواست نقدی»', 'type' => 'number', 'desc' => 'متغیرها: 1. نام مشتری 2. نام خودرو 3. دلیل رد'],
                        ]
                    ]
                ]
            ],
            'experts' => [
                'title' => 'کارشناسان',
                'icon' => 'fas fa-users',
                'sections' => [
                    'maneli_experts_list_section' => [
                        'title' => 'مدیریت کارشناسان',
                        'desc' => 'سیستم به صورت خودکار تمام کاربرانی که نقش کاربری آن‌ها <strong>«کارشناس مانلی»</strong> باشد را به عنوان کارشناس فروش شناسایی می‌کند.<br>استعلام‌ها به صورت گردشی (Round-robin) و به ترتیب به این کارشناسان ارجاع داده خواهد شد.<br>برای افزودن کارشناس جدید، کافیست از منوی <strong>کاربران > افزودن کاربر</strong>، یک کاربر جدید با نقش «کارشناس مانلی» بسازید و شماره موبایل او را در پروفایلش (فیلد "شماره موبایل") وارد کنید.',
                        'fields' => []
                    ]
                ]
            ],
            'finotex' => [
                'title' => 'فینوتک',
                'icon' => 'fas fa-university',
                'sections' => [
                    'maneli_finotex_cheque_section' => [
                        'title' => 'سرویس استعلام رنگ چک', 'desc' => '',
                        'fields' => [
                            ['name' => 'finotex_enabled', 'label' => 'فعال‌سازی استعلام فینوتک', 'type' => 'switch', 'desc' => 'در صورت فعال بودن، در زمان ثبت درخواست، استعلام بانکی از فینوتک انجام می‌شود.'],
                            ['name' => 'finotex_client_id', 'label' => 'شناسه کلاینت (Client ID)', 'type' => 'text'],
                            ['name' => 'finotex_api_key', 'label' => 'توکن دسترسی (Access Token)', 'type' => 'textarea'],
                        ]
                    ]
                ]
            ],
            'display' => [
                'title' => 'تنظیمات نمایش',
                'icon' => 'fas fa-paint-brush',
                'sections' => [
                    'maneli_display_main_section' => [
                        'title' => 'تنظیمات عمومی نمایش',
                        'desc' => 'در این بخش می‌توانید نحوه نمایش بخش‌های مختلف در سایت را برای کاربران کنترل کنید.',
                        'fields' => [
                            ['name' => 'hide_prices_for_customers', 'label' => 'مخفی کردن قیمت برای مشتریان', 'type' => 'switch', 'desc' => 'با فعال کردن این گزینه، قیمت‌ها در تمام بخش‌های فروشگاه برای کاربرانی که مدیر نیستند، مخفی می‌شود.'],
                            ['name' => 'enable_grouped_attributes', 'label' => 'فعال‌سازی نمایش گروهی ویژگی‌ها', 'type' => 'switch', 'desc' => 'با فعال کردن این گزینه، جدول ویژگی‌ها بر اساس گروه (مثال: فنی - ابعاد) دسته‌بندی و نمایش داده می‌شود.'],
                            ['name' => 'unavailable_product_message', 'label' => 'پیام محصول ناموجود', 'type' => 'text', 'desc' => 'این پیام روی فرم محاسبه اقساط برای محصولاتی که وضعیت "ناموجود" دارند، نمایش داده می‌شود.', 'default' => 'در حال حاضر امکان خرید این خودرو میسر نمی‌باشد.'],
                        ]
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