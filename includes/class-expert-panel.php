<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_panel_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_panel_assets']);
        add_action('wp_ajax_maneli_search_cars', [$this, 'handle_car_search_ajax']);
    }

    public function add_panel_page() {
        add_menu_page(
            'ثبت استعلام جدید',
            'پنل کارشناс',
            'maneli_expert', // Capability required to see this menu
            'maneli-expert-panel',
            [$this, 'render_panel_page'],
            'dashicons-id-alt',
            30
        );
    }

    public function enqueue_panel_assets($hook) {
        if ($hook !== 'toplevel_page_maneli-expert-panel') {
            return;
        }
        
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);

        wp_enqueue_script(
            'maneli-expert-panel-js',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js',
            ['jquery', 'select2'],
            '1.1.0',
            true
        );
        
        wp_localize_script('maneli-expert-panel-js', 'maneli_expert_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('maneli_expert_nonce')
        ]);

        wp_enqueue_style('maneli-admin-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/admin-styles.css');
    }

    public function render_panel_page() {
        if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1') {
            echo '<div class="updated notice"><p>استعلام جدید با موفقیت برای مشتری ثبت شد.</p></div>';
        }
        ?>
        <div class="wrap maneli-report-wrap">
            <h1>ثبت استعلام جدید برای مشتری</h1>
            <p>در این صفحه می‌توانید با جستجوی خودرو و وارد کردن اطلاعات مشتری، یک درخواست استعلام جدید ثبت کنید.</p>
            
            <form id="expert-inquiry-form" class="report-box" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_expert_create_inquiry">
                <?php wp_nonce_field('maneli_expert_create_nonce'); ?>

                <h2>۱. انتخاب خودرو و محاسبه اقساط</h2>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="product_id_expert"><strong>جستجوی خودرو</strong></label>
                    <select id="product_id_expert" name="product_id" style="width: 100%;" required>
                        <option></option>
                    </select>
                </div>
                
                <div id="loan-calculator-wrapper"></div>

                <div id="expert-form-details" style="display: none;">
                     <div class="issuer-choice-wrapper" style="background: transparent; border: none; padding: 0; margin: 20px 0;">
                        <h2 style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">۲. اطلاعات هویتی</h2>
                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <label><input type="radio" name="issuer_type" value="self" checked> خریدار و صادرکننده چک یکی هستند.</label>
                            <label><input type="radio" name="issuer_type" value="other"> صادرکننده چک شخص دیگری است.</label>
                        </div>
                    </div>
                
                    <div id="buyer-form-wrapper">
                        <h3>فرم اطلاعات خریدار</h3>
                        <table class="form-table">
                            <tr><th><label>نام</label></th><td><input type="text" name="first_name" class="regular-text" required></td><th><label>نام خانوادگی</label></th><td><input type="text" name="last_name" class="regular-text" required></td></tr>
                            <tr><th><label>نام پدر</label></th><td><input type="text" name="father_name" class="regular-text" required></td><th><label>تاریخ تولد</label></th><td><input type="text" name="birth_date" class="regular-text" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" required></td></tr>
                            <tr><th><label>کد ملی</label></th><td><input type="text" name="national_code" class="regular-text" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></td><th><label>تلفن همراه (نام کاربری)</label></th><td><input type="text" name="mobile_number" class="regular-text" placeholder="مثال: 09123456789" required></td></tr>
                        </table>
                    </div>
                    
                    <div id="issuer-form-wrapper" style="display: none;">
                        <h3 style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;">فرم اطلاعات صادر کننده چک</h3>
                        <table class="form-table">
                            <tr><th><label>نام صادرکننده</label></th><td><input type="text" name="issuer_first_name" class="regular-text"></td><th><label>نام خانوادگی صادرکننده</label></th><td><input type="text" name="issuer_last_name" class="regular-text"></td></tr>
                            <tr><th><label>نام پدر صادرکننده</label></th><td><input type="text" name="issuer_father_name" class="regular-text"></td><th><label>تاریخ تولد صادرکننده</label></th><td><input type="text" name="issuer_birth_date" class="regular-text" placeholder="مثال: ۱۳۶۰/۰۱/۰۱"></td></tr>
                            <tr><th><label>کد ملی صادرکننده</label></th><td><input type="text" name="issuer_national_code" class="regular-text" placeholder="کد ملی ۱۰ رقمی" pattern="\d{10}"></td><th><label>تلفن همراه صادرکننده</label></th><td><input type="text" name="issuer_mobile_number" class="regular-text" placeholder="مثال: 09129876543"></td></tr>
                        </table>
                    </div>
                    
                    <div class="form-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <button type="submit" class="button button-primary button-large">ثبت استعلام و ایجاد کاربر</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    public function handle_car_search_ajax() {
        check_ajax_referer('maneli_expert_nonce', 'nonce');
        if (!current_user_can('maneli_expert')) {
            wp_send_json_error(['message' => 'دسترسی غیر مجاز.']);
        }

        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $products = wc_get_products([ 'limit' => 20, 'status' => 'publish', 's' => $search_term ]);
        
        $results = [];
        if (!empty($products)) {
            foreach ($products as $product) {
                $results[] = [
                    'id' => $product->get_id(),
                    'text' => $product->get_name(),
                    'price' => $product->get_price(),
                    'min_downpayment' => get_post_meta($product->get_id(), 'min_downpayment', true) ?: 0
                ];
            }
        }
        
        wp_send_json(['results' => $results]);
    }
}