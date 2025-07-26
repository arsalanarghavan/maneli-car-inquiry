<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Shortcode_Handler {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('car_inquiry_form', [$this, 'render_inquiry_form']);
        add_shortcode('loan_calculator', [$this, 'render_loan_calculator']);
        add_shortcode('maneli_expert_new_inquiry_form', [$this, 'render_expert_new_inquiry_form']);
        add_shortcode('maneli_expert_inquiry_list', [$this, 'render_expert_inquiry_list']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], '6.2.1');
        
        if (is_product()) {
            wp_enqueue_script('maneli-calculator-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/calculator.js', [], '6.2.1', true);
            if (is_user_logged_in()) {
                wp_localize_script('maneli-calculator-js', 'maneli_ajax_object', [
                    'ajax_url'         => admin_url('admin-ajax.php'),
                    'inquiry_page_url' => home_url('/dashboard/?endp=inf_menu_1'),
                    'nonce'            => wp_create_nonce('maneli_ajax_nonce')
                ]);
            }
        }

        if (is_page()) {
            global $post;
            if ($post && has_shortcode($post->post_content, 'maneli_expert_new_inquiry_form')) {
                wp_enqueue_script('maneli-expert-panel-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js', ['jquery'], '6.2.1', true);
            }
        }
    }

    public function render_inquiry_form() {
        if (!is_user_logged_in()) { 
            $login_url = home_url('/login/?redirect_to=' . urlencode(home_url('/dashboard/?endp=inf_menu_1')));
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای ثبت و پیگیری استعلام، لطفاً ابتدا <a href="' . esc_url($login_url) . '">وارد شوید</a>.</p></div>'; 
        }
        
        $user_id = get_current_user_id();
        
        if (isset($_GET['inquiry_id_view']) && (current_user_can('maneli_expert') || current_user_can('manage_options'))) {
            $inquiry_id_to_view = intval($_GET['inquiry_id_view']);
            $inquiry_post = get_post($inquiry_id_to_view);
            $created_by_expert_id = (int)get_post_meta($inquiry_id_to_view, 'created_by_expert_id', true);

            if ($inquiry_post && ($user_id === $created_by_expert_id || current_user_can('manage_options'))) {
                return $this->render_customer_report_html($inquiry_id_to_view);
            } else {
                 return '<div class="maneli-inquiry-wrapper error-box"><p>شما اجازه دسترسی به این گزارش را ندارید.</p></div>';
            }
        }
        
        $latest_inquiry = get_posts(['author' => $user_id, 'post_type' => 'inquiry', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => 'publish']);
        $inquiry_step_meta = get_user_meta($user_id, 'maneli_inquiry_step', true);
        
        ob_start();
        echo '<div class="maneli-inquiry-wrapper">';

        $current_step_number = 1;
        if ($latest_inquiry) {
            $status = get_post_meta($latest_inquiry[0]->ID, 'inquiry_status', true);
            if (in_array($status, ['user_confirmed', 'rejected'])) { $current_step_number = 5; } 
            else { $current_step_number = 4; }
        } else {
            if ($inquiry_step_meta === 'form_pending') { $current_step_number = 2; }
            elseif ($inquiry_step_meta === 'payment_pending') { $current_step_number = 3; }
        }
        $this->render_progress_tracker($current_step_number);
        
        if ($latest_inquiry) {
            $inquiry_post = $latest_inquiry[0];
            $status = get_post_meta($inquiry_post->ID, 'inquiry_status', true);
            switch ($status) {
                case 'user_confirmed':
                case 'rejected':
                    echo $this->render_customer_report_html($inquiry_post->ID);
                    break;
                case 'failed':
                    $this->render_failed_inquiry($inquiry_post);
                    break;
                case 'more_docs':
                default: // 'pending'
                    $this->render_step_final_wait_message($status);
                    break;
            }
        } else {
            switch ($inquiry_step_meta) {
                case 'form_pending': $this->render_step2_identity_form($user_id); break;
                case 'payment_pending': $this->render_step_payment($user_id); break;
                default: $this->render_step1_car_selection(); break;
            }
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function render_expert_new_inquiry_form() {
        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای دسترسی به این بخش، لطفاً وارد شوید.</p></div>';
        }
        if (!current_user_can('manage_options') && !current_user_can('read')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما اجازه دسترسی به این بخش را ندارید.</p></div>';
        }
        $products = wc_get_products(['limit' => -1, 'status' => 'publish']);
        ob_start();
        ?>
        <div class="maneli-expert-panel">
            <h2>ثبت استعلام جدید برای مشتری</h2>
            <p>از این فرم برای ثبت نام مشتری جدید و ایجاد اولین استعلام برای او استفاده کنید.</p>
            <form id="expert-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_expert_create_inquiry">
                <?php wp_nonce_field('maneli_expert_create_nonce'); ?>
                <div class="expert-form-section">
                    <h3>۱. انتخاب خودرو و شرایط</h3>
                    <div class="form-group"><label for="product_id_expert">انتخاب خودرو</label><select name="product_id" id="product_id_expert" required><option value="">-- یک خودرو انتخاب کنید --</option><?php foreach ($products as $product) : ?><option value="<?php echo esc_attr($product->get_id()); ?>" data-price="<?php echo esc_attr($product->get_price()); ?>"><?php echo esc_html($product->get_name()); ?></option><?php endforeach; ?></select></div>
                    <div id="loan-calculator-wrapper" style="display:none;"></div>
                </div>
                <div class="expert-form-section">
                    <h3>۲. اطلاعات هویتی</h3>
                    <div class="issuer-choice-wrapper" style="max-width: 100%; margin: 20px 0;"><h4>اطلاعات صادر کننده چک</h4><div class="form-group-radio"><label><input type="radio" name="issuer_type" value="self" checked> خریدار و صادرکننده چک یک نفر هستند.</label></div><div class="form-group-radio"><label><input type="radio" name="issuer_type" value="other"> صادرکننده چک شخص دیگری است.</label></div></div>
                    <div id="buyer-form-wrapper"><p class="form-section-title">فرم اطلاعات خریدار</p><div class="form-grid"><div class="form-row"><div class="form-group"><label>نام:</label><input type="text" name="first_name" required></div><div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name" required></div></div><div class="form-row"><div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" required></div><div class="form-group"><label>تاریخ تولد:</label><input type="text" name="birth_date" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" required></div></div><div class="form-row"><div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه (نام کاربری):</label><input type="text" name="mobile_number" placeholder="مثال: 09123456789" required></div></div></div></div>
                    <div id="issuer-form-wrapper" style="display: none;"><p class="form-section-title">فرم اطلاعات صادر کننده چک</p><div class="form-grid"><div class="form-row"><div class="form-group"><label>نام صادرکننده چک:</label><input type="text" name="issuer_first_name"></div><div class="form-group"><label>نام خانوادگی صادرکننده چک:</label><input type="text" name="issuer_last_name"></div></div><div class="form-row"><div class="form-group"><label>نام پدر صادرکننده چک:</label><input type="text" name="issuer_father_name"></div><div class="form-group"><label>تاریخ تولد صادرکننده چک:</label><input type="text" name="issuer_birth_date" placeholder="مثال: ۱۳۶۰/۰۱/۰۱"></div></div><div class="form-row"><div class="form-group"><label>کد ملی صادرکننده چک:</label><input type="text" name="issuer_national_code" placeholder="کد ملی ۱۰ رقمی" pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه صادرکننده چک:</label><input type="text" name="issuer_mobile_number" placeholder="مثال: 09129876543"></div></div></div></div>
                </div>
                <button type="submit" class="button button-primary">ثبت استعلام برای مشتری</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_expert_inquiry_list() {
        if (!is_user_logged_in() || (!current_user_can('manage_options') && !current_user_can('maneli_expert'))) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما اجازه دسترسی به این بخش را ندارید.</p></div>';
        }
        $expert_id = get_current_user_id();
        $args = ['post_type' => 'inquiry','posts_per_page' => -1,'meta_query' => [['key' => 'created_by_expert_id','value' => $expert_id,'compare' => '=']]];
        $my_inquiries = new WP_Query($args);
        ob_start();
        ?>
        <div class="maneli-expert-panel">
            <h2>استعلام‌های من</h2>
            <table class="wp-list-table widefat striped maneli-table">
                <thead><tr><th>مشتری</th><th>خودرو</th><th>وضعیت</th><th>تاریخ ثبت</th><th>عملیات</th></tr></thead>
                <tbody>
                    <?php if ($my_inquiries->have_posts()): ?>
                        <?php while ($my_inquiries->have_posts()): $my_inquiries->the_post(); 
                            $post_id = get_the_ID();
                            $customer_id = get_post_field('post_author', $post_id);
                            $customer = get_userdata($customer_id);
                            $car_id = get_post_meta($post_id, 'product_id', true);
                            $status = get_post_meta($post_id, 'inquiry_status', true);
                            $cpt_handler = new Maneli_CPT_Handler();
                            $report_url = add_query_arg(['inquiry_id_view' => $post_id], home_url('/dashboard/?endp=inf_menu_1'));
                        ?>
                        <tr>
                            <td><?php echo esc_html($customer->display_name); ?></td>
                            <td><?php echo get_the_title($car_id); ?></td>
                            <td><?php echo $cpt_handler->get_status_label($status); ?></td>
                            <td><?php echo get_the_date('Y/m/d', $post_id); ?></td>
                            <td><a href="<?php echo esc_url($report_url); ?>" class="button">مشاهده گزارش</a></td>
                        </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else: ?>
                        <tr><td colspan="5">هیچ استعلامی توسط شما ثبت نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function render_loan_calculator() {
        if (!function_exists('is_product') || !is_product() || !function_exists('WC')) return '';
        global $product;
        if (!$product instanceof WC_Product) return '';
        $price = (int)$product->get_price();
        $min_down_payment = (int)get_post_meta($product->get_id(), 'min_downpayment', true);
        $max_down_payment = (int)($price * 0.8);
        ob_start();
        ?>
        <form class="loan-calculator-form" method="post">
            <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
            <?php wp_nonce_field('maneli_ajax_nonce'); ?>
            <div id="loan-calculator" data-price="<?php echo esc_attr($price); ?>" data-min-down="<?php echo esc_attr($min_down_payment); ?>" data-max-down="<?php echo esc_attr($max_down_payment); ?>">
                <h2 class="loan-title">تعیین بودجه و محاسبه اقساط</h2>
                <div class="loan-section"><div class="loan-row"><label class="loan-label">مقدار پیش‌پرداخت:</label><input type="text" id="downPaymentInput" step="1000000"></div><input type="range" id="downPaymentSlider" step="1000000"><div class="loan-note"><span>حداقل پیش‌پرداخت:</span><span><span id="minDownDisplay"></span> تومان</span></div></div>
                <div class="loan-section"><h4 class="loan-subtitle">شرایط مورد نیاز</h4><ul class="loan-requirements"><li>۱. شناسنامه - کارت ملی</li><li>۲. دسته چک</li><li>۳. پرینت سه ماه آخر حساب (صاحب چک)</li><li>۴. فیش حقوق یا جواز کسب (متقاضی و صاحب چک)</li></ul></div>
                <div class="loan-section"><label class="loan-label">مدت زمان باز پرداخت:</label><div class="loan-buttons"><button type="button" class="term-btn active" data-months="12">۱۲ ماهه</button><button type="button" class="term-btn" data-months="18">۱۸ ماهه</button><button type="button" class="term-btn" data-months="24">۲۴ ماهه</button><button type="button" class="term-btn" data-months="36">۳۶ ماهه</button></div></div>
                <div class="loan-section result-section"><strong>مبلغ تقریبی هر قسط:</strong><span id="installmentAmount">0</span><span> تومان</span></div>
                <div class="loan-section loan-action-wrapper">
                    <?php if (is_user_logged_in()): ?>
                        <button type="button" class="loan-action-btn">استعلام سنجی بانکی جهت خرید خودرو</button>
                    <?php else:
                        $login_url = home_url('/login/?redirect_to=' . urlencode(get_permalink()));
                        ?>
                        <a href="<?php echo esc_url($login_url); ?>" class="loan-action-btn">برای استعلام ابتدا وارد شوید</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_step1_car_selection() {
        echo "<div class='maneli-inquiry-form'><h3>مرحله ۱: انتخاب خودرو</h3><p>برای شروع فرآیند استعلام، لطفاً ابتدا از صفحه یکی از محصولات، خودروی مورد نظر خود را انتخاب کرده و روی دکمه «استعلام سنجی بانکی» کلیک کنید.</p></div>";
    }

    private function render_step2_identity_form($user_id) {
        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $car_name = get_the_title($car_id);
        $user_info = get_userdata($user_id);
        $down_payment = get_user_meta($user_id, 'maneli_inquiry_down_payment', true);
        $term_months = get_user_meta($user_id, 'maneli_inquiry_term_months', true);
        $total_price = get_user_meta($user_id, 'maneli_inquiry_total_price', true);
        $installment_amount = get_user_meta($user_id, 'maneli_inquiry_installment', true);
        ?>
        <div class="inquiry-car-image">
            <?php if ($car_id && has_post_thumbnail($car_id)) { echo get_the_post_thumbnail($car_id, 'medium'); } ?>
        </div>
        <div class="inquiry-summary-box">
            <h4>خلاصه درخواست شما</h4>
            <table class="summary-table">
                <tr><td><strong>خودروی انتخابی:</strong></td><td><?php echo esc_html($car_name); ?></td></tr>
                <tr><td><strong>مبلغ حدودی کل خودرو:</strong></td><td><?php echo esc_html(number_format_i18n((int)$total_price)); ?> <span>تومان</span></td></tr>
                <tr><td><strong>مبلغ پیش پرداخت:</strong></td><td><?php echo esc_html(number_format_i18n((int)$down_payment)); ?> <span>تومان</span></td></tr>
                <tr><td><strong>تعداد اقساط:</strong></td><td><?php echo esc_html($term_months); ?> <span>ماهه</span></td></tr>
                <tr><td><strong>مبلغ هر قسط (تقریبی):</strong></td><td><?php echo esc_html(number_format_i18n((int)$installment_amount)); ?> <span>تومان</span></td></tr>
            </table>
        </div>
        <form id="identity-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="maneli_submit_identity">
            <?php wp_nonce_field('maneli_submit_identity_nonce'); ?>
            <div class="issuer-choice-wrapper">
                <h4>اطلاعات صادر کننده چک</h4>
                <div class="form-group-radio"><label><input type="radio" name="issuer_type" value="self"> خریدار و صادرکننده چک خودم هستم.</label></div>
                <div class="form-group-radio"><label><input type="radio" name="issuer_type" value="other"> صادرکننده چک شخص دیگری است.</label></div>
            </div>
            <div id="buyer-form-wrapper" style="display: none;">
                <p class="form-section-title">فرم اطلاعات خریدار</p>
                <div class="form-grid">
                    <div class="form-row"><div class="form-group"><label>نام:</label><input type="text" name="first_name" value="<?php echo esc_attr($user_info->first_name); ?>" required></div><div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name" value="<?php echo esc_attr($user_info->last_name); ?>" required></div></div>
                    <div class="form-row"><div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" value="<?php echo esc_attr(get_user_meta($user_id, 'father_name', true)); ?>" required></div><div class="form-group"><label>تاریخ تولد:</label><input type="text" name="birth_date" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" required></div></div>
                    <div class="form-row"><div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" value="<?php echo esc_attr(get_user_meta($user_id, 'national_code', true)); ?>" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه:</label><input type="text" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user_id, 'mobile_number', true)); ?>" placeholder="مثال: 09123456789" required></div></div>
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
            <button type="submit" id="submit-identity-btn" style="display:none;">ادامه و رفتن به مرحله پرداخت</button>
        </form>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="issuer_type"]');
            if (!radios.length) return;
            const buyerForm = document.getElementById('buyer-form-wrapper');
            const issuerForm = document.getElementById('issuer-form-wrapper');
            const submitBtn = document.getElementById('submit-identity-btn');
            const issuerInputs = issuerForm.querySelectorAll('input');
            function toggleForms() {
                const checkedRadio = document.querySelector('input[name="issuer_type"]:checked');
                if (!checkedRadio) {
                    buyerForm.style.display = 'none';
                    issuerForm.style.display = 'none';
                    submitBtn.style.display = 'none';
                    return;
                }
                const selectedValue = checkedRadio.value;
                submitBtn.style.display = 'block';
                if (selectedValue === 'self') {
                    buyerForm.style.display = 'block';
                    issuerForm.style.display = 'none';
                    issuerInputs.forEach(input => input.required = false);
                } else {
                    buyerForm.style.display = 'block';
                    issuerForm.style.display = 'block';
                    issuerInputs.forEach(input => input.required = true);
                }
            }
            radios.forEach(radio => radio.addEventListener('change', toggleForms));
        });
        </script>
        <?php
    }
    
    private function render_step_final_wait_message($status = 'pending') {
        $message = 'درخواست استعلام شما با موفقیت ثبت شد. نتیجه آن پس از بررسی توسط کارشناسان (معمولاً ظرف ۲۴ ساعت کاری) به شما اطلاع داده خواهد شد.';
        if ($status === 'more_docs') {
            $message = 'کارشناسان ما برای تکمیل فرآیند به مدارک بیشتری نیاز دارند. لطفاً منتظر تماس همکاران ما باشید.';
        }
        ?>
        <h3>مرحله ۴: در انتظار بررسی</h3>
        <div class="status-box status-pending">
            <p>از شکیبایی شما سپاسگزاریم.</p>
            <p><?php echo $message; ?></p>
        </div>
        <?php
    }
    
    public function render_customer_report_html($inquiry_id) {
        $status = get_post_meta($inquiry_id, 'inquiry_status', true);
        $post_meta = get_post_meta($inquiry_id);
        $car_id = $post_meta['product_id'][0] ?? 0;
        $car_name = get_the_title($car_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
        
        ob_start();
        ?>
        <h3>مرحله ۵: نتیجه نهایی</h3>
        <div class="customer-report">
            <?php if ($status === 'user_confirmed'): ?>
                <div class="status-box status-final">
                    <p><strong>نتیجه نهایی: درخواست شما تایید شد.</strong></p>
                    <p>درخواست شما توسط کارشناسان ما تایید و به واحد فروش ارجاع داده شد. به زودی یکی از همکاران ما برای هماهنگی‌های نهایی با شما تماس خواهد گرفت.</p>
                </div>
            <?php elseif ($status === 'rejected'): 
                $reason = get_post_meta($inquiry_id, 'rejection_reason', true);
            ?>
                <div class="status-box status-rejected">
                    <p><strong>نتیجه نهایی: درخواست شما رد شد.</strong></p>
                    <?php if (!empty($reason)): ?>
                        <p><strong>دلیل:</strong> <?php echo esc_html($reason); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="report-section">
                <h4>خلاصه درخواست</h4>
                <table class="summary-table">
                    <tr><td><strong>خودروی انتخابی:</strong></td><td><?php echo esc_html($car_name); ?></td></tr>
                    <tr><td><strong>مبلغ پیش پرداخت:</strong></td><td><?php echo esc_html(number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0))); ?> <span>تومان</span></td></tr>
                    <tr><td><strong>تعداد اقساط:</strong></td><td><?php echo esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0); ?> <span>ماهه</span></td></tr>
                </table>
            </div>

            <div class="report-section">
                <h4>نتیجه اعتبارسنجی</h4>
                <div class="maneli-status-bar">
                    <?php
                    $colors = [
                        1 => ['name' => 'سفید', 'class' => 'white'],
                        2 => ['name' => 'زرد', 'class' => 'yellow'],
                        3 => ['name' => 'نارنجی', 'class' => 'orange'],
                        4 => ['name' => 'قهوه‌ای', 'class' => 'brown'],
                        5 => ['name' => 'قرمز', 'class' => 'red']
                    ];
                    foreach ($colors as $code => $color) {
                        $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
                        echo "<div class='bar-segment segment-{$color['class']} {$active_class}'><span>" . esc_html($color['name']) . "</span></div>";
                    }
                    ?>
                </div>
                <table class="summary-table" style="margin-top:20px;">
                    <?php
                    $cheque_color_map = [
                        '1' => ['text' => 'سفید', 'desc' => 'فاقد هرگونه سابقه چک برگشتی.'],
                        '2' => ['text' => 'زرد', 'desc' => 'یک فقره چک برگشتی.'],
                        '3' => ['text' => 'نارنجی', 'desc' => 'دو الی چهار فقره چک برگشتی.'],
                        '4' => ['text' => 'قهوه‌ای', 'desc' => 'پنج تا ده فقره چک برگشتی.'],
                        '5' => ['text' => 'قرمز', 'desc' => 'بیش از ده فقره چک برگشتی.'],
                         0  => ['text' => 'نامشخص', 'desc' => 'اطلاعاتی از فینوتک دریافت نشد.']
                    ];
                    $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
                    ?>
                    <tr>
                        <td><strong>وضعیت چک صیادی:</strong></td>
                        <td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>توضیح وضعیت:</strong></td>
                        <td><?php echo esc_html($color_info['desc']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function render_failed_inquiry($inquiry) {
        ?>
        <h3>استعلام ناموفق</h3>
        <div class="status-box status-failed">
            <p>متاسفانه در تلاش برای دریافت استعلام شما از سیستم بانکی، خطایی رخ داد. این مشکل می‌تواند به دلیل اطلاعات هویتی نادرست یا مشکلات موقت سیستم باشد.</p>
        </div>
        <form class="inquiry-action-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="maneli_retry_inquiry">
            <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry->ID); ?>">
            <?php wp_nonce_field('maneli_retry_inquiry_nonce'); ?>
            <p>می‌توانید اطلاعات خود را ویرایش کرده و مجدداً تلاش کنید.</p>
            <button type="submit">تلاش مجدد و اصلاح اطلاعات</button>
        </form>
        <?php
    }
}