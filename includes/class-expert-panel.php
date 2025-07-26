<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Expert_Panel {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_expert_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_panel_assets']);
    }

    public function add_expert_menu_pages() {
        // Add the main menu page
        add_menu_page(
            'پنل کارشناس',           // Page Title
            'پنل کارشناس',           // Menu Title
            'read',                   // Capability (base capability for our expert role)
            'maneli-expert-panel',    // Menu Slug
            [$this, 'render_new_inquiry_page'], // Function for the first sub-page
            'dashicons-businessperson',
            25
        );

        // Add the "New Inquiry" sub-menu page
        add_submenu_page(
            'maneli-expert-panel',    // Parent Slug
            'ثبت استعلام جدید',       // Page Title
            'ثبت استعلام جدید',       // Menu Title
            'read',                   // Capability
            'maneli-expert-panel',    // Menu Slug (same as parent to make it the default)
            [$this, 'render_new_inquiry_page']
        );

        // Add the "My Inquiries" sub-menu page
        add_submenu_page(
            'maneli-expert-panel',      // Parent Slug
            'استعلام‌های من',          // Page Title
            'استعلام‌های من',          // Menu Title
            'read',                     // Capability
            'maneli-expert-my-inquiries',// New, unique slug
            [$this, 'render_my_inquiries_page']
        );
    }
    
    public function enqueue_panel_assets($hook) {
        // Load assets on both of our new pages
        if ($hook !== 'toplevel_page_maneli-expert-panel' && $hook !== 'پنل-کارشناس_page_maneli-expert-my-inquiries') {
            return;
        }
        
        $new_version = '6.2.0';
        wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], $new_version);
        wp_enqueue_script('maneli-expert-panel-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js', ['jquery'], $new_version, true);
    }

    public function render_new_inquiry_page() {
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);
        ?>
        <div class="wrap maneli-expert-panel">
            <h1>ثبت استعلام جدید برای مشتری</h1>
            <p>از این فرم برای ثبت نام مشتری جدید و ایجاد اولین استعلام برای او استفاده کنید.</p>
            
            <form id="expert-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_expert_create_inquiry">
                <?php wp_nonce_field('maneli_expert_create_nonce'); ?>

                <div class="expert-form-section">
                    <h3>۱. انتخاب خودرو و شرایط</h3>
                    <div class="form-group">
                        <label for="product_id_expert">انتخاب خودرو</label>
                        <select name="product_id" id="product_id_expert" required>
                            <option value="">-- یک خودرو انتخاب کنید --</option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo esc_attr($product->get_id()); ?>" data-price="<?php echo esc_attr($product->get_price()); ?>"><?php echo esc_html($product->get_name()); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="loan-calculator-wrapper" style="display:none;"></div>
                </div>

                <div class="expert-form-section">
                    <h3>۲. اطلاعات هویتی</h3>
                    <div class="issuer-choice-wrapper" style="max-width: 100%; margin: 20px 0;">
                        <h4>اطلاعات صادر کننده چک</h4>
                        <div class="form-group-radio"><label><input type="radio" name="issuer_type" value="self" checked> خریدار و صادرکننده چک یک نفر هستند.</label></div>
                        <div class="form-group-radio"><label><input type="radio" name="issuer_type" value="other"> صادرکننده چک شخص دیگری است.</label></div>
                    </div>

                    <div id="buyer-form-wrapper">
                         <p class="form-section-title">فرم اطلاعات خریدار</p>
                         <div class="form-grid">
                            <div class="form-row"><div class="form-group"><label>نام:</label><input type="text" name="first_name" required></div><div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name" required></div></div>
                            <div class="form-row"><div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" required></div><div class="form-group"><label>تاریخ تولد:</label><input type="text" name="birth_date" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" required></div></div>
                            <div class="form-row"><div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه (نام کاربری):</label><input type="text" name="mobile_number" placeholder="مثال: 09123456789" required></div></div>
                         </div>
                    </div>

                    <div id="issuer-form-wrapper" style="display: none;">
                        <p class="form-section-title">فرم اطلاعات صادر کننده چک</p>
                        <div class="form-grid">
                           <div class="form-row"><div class="form-group"><label>نام صادرکننده چک:</label><input type="text" name="issuer_first_name"></div><div class="form-group"><label>نام خانوادگی صادرکننده چک:</label><input type="text" name="issuer_last_name"></div></div>
                           <div class="form-row"><div class="form-group"><label>نام پدر صادرکننده چک:</label><input type="text" name="issuer_father_name"></div><div class="form-group"><label>تاریخ تولد صادرکننده چک:</label><input type="text" name="issuer_birth_date" placeholder="مثال: ۱۳۶۰/۰۱/۰۱"></div></div>
                           <div class="form-row"><div class="form-group"><label>کد ملی صادرکننده چک:</label><input type="text" name="issuer_national_code" placeholder="کد ملی ۱۰ رقمی" pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه صادرکننده چک:</label><input type="text" name="issuer_mobile_number" placeholder="مثال: 09129876543"></div></div>
                        </div>
                    </div>
                </div>

                <?php submit_button('ثبت استعلام برای مشتری'); ?>
            </form>
        </div>
        <?php
    }

    public function render_my_inquiries_page() {
        $expert_id = get_current_user_id();
        $args = [
            'post_type' => 'inquiry',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'assigned_expert_id',
                    'value' => $expert_id,
                    'compare' => '='
                ],
                [
                    'key' => 'created_by_expert_id',
                    'value' => $expert_id,
                    'compare' => '='
                ]
            ]
        ];
        $my_inquiries = new WP_Query($args);
        ?>
        <div class="wrap">
            <h1>استعلام‌های من</h1>
            <?php if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1'): ?>
                <div class="notice notice-success is-dismissible"><p>استعلام جدید با موفقیت برای مشتری ثبت شد.</p></div>
            <?php endif; ?>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>مشتری</th>
                        <th>خودرو</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($my_inquiries->have_posts()): ?>
                        <?php while ($my_inquiries->have_posts()): $my_inquiries->the_post(); 
                            $post_id = get_the_ID();
                            $customer_id = get_post_field('post_author', $post_id);
                            $customer = get_userdata($customer_id);
                            $car_id = get_post_meta($post_id, 'product_id', true);
                            $status = get_post_meta($post_id, 'inquiry_status', true);
                            $cpt_handler = new Maneli_CPT_Handler();
                            $report_url = admin_url('edit.php?post_type=inquiry&page=maneli-credit-report&inquiry_id=' . $post_id);
                        ?>
                        <tr>
                            <td><?php echo esc_html($customer->display_name); ?></td>
                            <td><?php echo get_the_title($car_id); ?></td>
                            <td><?php echo $cpt_handler->get_status_label($status); ?></td>
                            <td><?php echo get_the_date('Y/m/d'); ?></td>
                            <td><a href="<?php echo esc_url($report_url); ?>" class="button">مشاهده گزارش مدیر</a></td>
                        </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else: ?>
                        <tr><td colspan="5">هیچ استعلامی برای شما ثبت نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}