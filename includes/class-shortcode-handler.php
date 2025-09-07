<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Shortcode_Handler {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Main Shortcodes
        add_shortcode('car_inquiry_form', [$this, 'render_inquiry_form']);
        add_shortcode('loan_calculator', [$this, 'render_loan_calculator']);
        add_shortcode('maneli_inquiry_list', [$this, 'render_inquiry_list']);
        add_shortcode('maneli_frontend_credit_report', [$this, 'render_frontend_credit_report']);

        // Admin Panel Shortcodes
        add_shortcode('maneli_user_list', [$this, 'render_user_list_shortcode']);
        add_shortcode('maneli_settings', [$this, 'render_settings_shortcode']);

        // Backward compatibility for old shortcode
        add_shortcode('maneli_expert_inquiry_list', [$this, 'render_inquiry_list']);
    }

    public function enqueue_assets() {
        if (!is_admin()) {
            wp_enqueue_style('maneli-frontend-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/frontend.css', [], '7.2.1');
            
            // Enqueue scripts for the product page calculator
            if (is_product()) {
                wp_enqueue_script('maneli-calculator-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/calculator.js', ['jquery'], '7.2.0', true);
                if (is_user_logged_in()) {
                    wp_localize_script('maneli-calculator-js', 'maneli_ajax_object', [
                        'ajax_url'         => admin_url('admin-ajax.php'),
                        'inquiry_page_url' => home_url('/dashboard/?endp=inf_menu_1'),
                        'nonce'            => wp_create_nonce('maneli_ajax_nonce')
                    ]);
                }
            }
            
            // Enqueue scripts for the expert/admin new inquiry form
            if (is_singular()) { 
                global $post;
                if ($post && has_shortcode($post->post_content, 'car_inquiry_form')) {
                     if (current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries')) {
                        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
                        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
                        wp_enqueue_script('maneli-expert-panel-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js', ['jquery', 'select2'], '2.3.5', true);
                        wp_localize_script('maneli-expert-panel-js', 'maneli_expert_ajax', [
                            'ajax_url' => admin_url('admin-ajax.php'),
                            'nonce'    => wp_create_nonce('maneli_expert_nonce')
                        ]);
                     }
                }
            }
        }
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
        <div class="maneli-calculator-container">
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
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_inquiry_form() {
        if (!is_user_logged_in()) { 
            $login_url = home_url('/login/');
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای ثبت و پیگیری استعلام، لطفاً ابتدا <a href="' . esc_url($login_url) . '">وارد شوید</a>.</p></div>'; 
        }

        if (current_user_can('manage_maneli_inquiries') || current_user_can('maneli_expert')) {
            return $this->render_maneli_expert_new_inquiry_form();
        }
        
        $user_id = get_current_user_id();
        $latest_inquiry = get_posts(['author' => $user_id, 'post_type' => 'inquiry', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'post_status' => ['publish', 'private']]);
        $inquiry_step_meta = get_user_meta($user_id, 'maneli_inquiry_step', true);
        
        ob_start();
        echo '<div class="maneli-inquiry-wrapper">';

        $current_step_number = 1;
        if ($latest_inquiry) {
            $status = get_post_meta($latest_inquiry[0]->ID, 'inquiry_status', true);
            if (in_array($status, ['user_confirmed', 'rejected'])) { $current_step_number = 5; } 
            else { $current_step_number = 4; }
        } else {
            if (isset($_GET['payment_status'])) {
                $this->display_payment_message(sanitize_text_field($_GET['payment_status']));
            }
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
                default:
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
    
    private function render_progress_tracker($active_step) {
        $steps = [1 => 'انتخاب خودرو', 2 => 'تکمیل اطلاعات', 3 => 'پرداخت هزینه', 4 => 'در انتظار بررسی', 5 => 'نتیجه نهایی'];
        ?>
        <div class="progress-tracker">
            <?php foreach ($steps as $number => $title) : ?>
                <?php
                $step_class = '';
                if ($number < $active_step) { $step_class = 'completed'; } 
                elseif ($number == $active_step) { $step_class = 'active'; }
                ?>
                <div class="step <?php echo $step_class; ?>">
                    <div class="circle"><?php echo number_format_i18n($number); ?></div>
                    <div class="label"><span class="step-title">مرحله <?php echo number_format_i18n($number); ?></span><span class="step-name"><?php echo esc_html($title); ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_step_payment($user_id) {
        $options = get_option('maneli_inquiry_all_options', []);
        $amount = (int)($options['inquiry_fee'] ?? 0);
        $discount_code_exists = !empty($options['discount_code']);
        $discount_text = $options['discount_code_text'] ?? 'تخفیف ۱۰۰٪ با موفقیت اعمال شد.';
        $zero_fee_message = $options['zero_fee_message'] ?? 'هزینه استعلام برای شما رایگان در نظر گرفته شده است. لطفاً برای ادامه روی دکمه زیر کلیک کنید.';
        if (get_user_meta($user_id, 'maneli_discount_applied', true)) {
            echo '<div class="status-box status-approved" style="margin-bottom:20px;"><p>' . esc_html($discount_text) . '</p></div>';
            delete_user_meta($user_id, 'maneli_discount_applied');
        }
        ?>
        <h3>مرحله ۳: پرداخت هزینه استعلام</h3>
        <div class="payment-box">
            <?php if ($amount > 0): ?>
                <p>برای ثبت نهایی درخواست و ارسال آن برای کارشناسان، لطفاً هزینه استعلام به مبلغ <strong><?php echo number_format_i18n($amount); ?> تومان</strong> را پرداخت نمایید.</p>
            <?php else: ?>
                <p><?php echo esc_html($zero_fee_message); ?></p>
            <?php endif; ?>
            <form id="payment-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="maneli_start_payment">
                <?php wp_nonce_field('maneli_payment_nonce'); ?>

                <?php if ($amount > 0 && $discount_code_exists): ?>
                <p class="discount-toggle" style="cursor: pointer;"><a id="show-discount-form">کد تخفیف دارید؟</a></p>
                <div class="form-group" id="discount-form-wrapper" style="display:none;">
                    <label for="discount_code_input">کد تخفیف را وارد کنید:</label>
                    <input type="text" name="discount_code_input" placeholder="کد تخفیف">
                </div>
                <?php endif; ?>

                <button type="submit" class="button button-primary"><?php echo ($amount > 0) ? 'پرداخت و ثبت نهایی' : 'ثبت نهایی درخواست'; ?></button>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showDiscountLink = document.getElementById('show-discount-form');
            if (showDiscountLink) {
                showDiscountLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    const discountWrapper = document.getElementById('discount-form-wrapper');
                    discountWrapper.style.display = 'block';
                    this.parentElement.style.display = 'none';
                });
            }
        });
        </script>
        <?php
    }
    
    private function display_payment_message($status) {
        $reason = isset($_GET['reason']) ? sanitize_text_field(urldecode($_GET['reason'])) : '';
        switch($status) {
            case 'success': 
                echo '<div class="status-box status-approved" style="margin-bottom:20px;"><p>پرداخت شما با موفقیت انجام شد. درخواست شما برای کارشناسان ارسال گردید.</p></div>'; 
                break;
            case 'failed': 
                $message = '<p>متاسفانه تراکنش شما ناموفق بود. در صورت کسر وجه، مبلغ تا ۷۲ ساعت آینده به حساب شما باز خواهد گشت.</p>';
                if (!empty($reason)) {
                    $message .= '<p><strong>دلیل:</strong> ' . esc_html($reason) . '</p>';
                }
                echo '<div class="status-box status-failed" style="margin-bottom:20px;">' . $message . '</div>'; 
                break;
            case 'cancelled': 
                echo '<div class="status-box status-pending" style="margin-bottom:20px;"><p>شما پرداخت را لغو کردید. درخواست شما هنوز نهایی نشده است.</p></div>'; 
                break;
        }
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
                        1 => ['name' => 'سفید', 'class' => 'white'], 2 => ['name' => 'زرد', 'class' => 'yellow'],
                        3 => ['name' => 'نارنجی', 'class' => 'orange'], 4 => ['name' => 'قهوه‌ای', 'class' => 'brown'],
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
                        '1' => ['text' => 'سفید', 'desc' => 'فاقد هرگونه سابقه چک برگشتی.'],'2' => ['text' => 'زرد', 'desc' => 'یک فقره چک برگشتی یا حداکثر مبلغ 50 میلیون ریال تعهد برگشتی.'],'3' => ['text' => 'نارنجی', 'desc' => 'دو الی چهار فقره چک برگشتی یا حداکثر مبلغ 200 میلیون ریال تعهد برگشتی.'],'4' => ['text' => 'قهوه‌ای', 'desc' => 'پنج تا ده فقره چک برگشتی یا حداکثر مبلغ 500 میلیون ریال تعهد برگشتی.'],'5' => ['text' => 'قرمز', 'desc' => 'بیش از ده فقره چک برگشتی یا بیش از مبلغ 500 میلیون ریال تعهد برگشتی.'], 0  => ['text' => 'نامشخص', 'desc' => 'اطلاعاتی از فینوتک دریافت نشد.']
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

    public function render_maneli_expert_new_inquiry_form() {
        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای استفاده از این فرم را ندارید.</p></div>';
        }
        
        ob_start();

        if (isset($_GET['inquiry_created']) && $_GET['inquiry_created'] == '1') {
            echo '<div class="status-box status-approved"><p>استعلام جدید با موفقیت برای مشتری ثبت شد.</p></div>';
        }
        ?>
        <div class="maneli-inquiry-wrapper">
            <form id="expert-inquiry-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_expert_create_inquiry">
                <?php wp_nonce_field('maneli_expert_create_nonce'); ?>

                <h3>۱. انتخاب خودرو و شرایط</h3>
                <div class="form-group">
                    <label for="product_id_expert"><strong>جستجوی خودرو</strong></label>
                    <select id="product_id_expert" name="product_id" style="width: 100%;" required></select>
                </div>
                
                <?php if (current_user_can('manage_maneli_inquiries')):
                    $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                    if (!empty($experts)):
                ?>
                    <div class="form-group" style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">
                        <label for="assigned_expert_id"><strong>انتخاب کارشناس مسئول</strong></label>
                        <select id="assigned_expert_id" name="assigned_expert_id" style="width: 100%;">
                            <option value="auto">-- انتساب خودکار (گردشی) --</option>
                            <?php foreach ($experts as $expert) : ?>
                                <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">در صورت عدم انتخاب، سیستم به صورت خودکار یک کارشناس را انتخاب می‌کند.</p>
                    </div>
                <?php 
                    endif;
                endif; ?>

                <div id="loan-calculator-wrapper"></div>

                <div id="expert-form-details" style="display: none; margin-top: 20px;">
                    <h3 style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">۲. اطلاعات هویتی</h3>
                     <div class="issuer-choice-wrapper" style="background: transparent; border: none; padding: 0; margin: 10px 0;">
                        <div class="form-group-radio" style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <label><input type="radio" name="issuer_type" value="self" checked> خریدار و صادرکننده چک یکی هستند.</label>
                            <label><input type="radio" name="issuer_type" value="other"> صادرکننده چک شخص دیگری است.</label>
                        </div>
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
                            <div class="form-row"><div class="form-group"><label>نام صادرکننده:</label><input type="text" name="issuer_first_name"></div><div class="form-group"><label>نام خانوادگی صادرکننده:</label><input type="text" name="issuer_last_name"></div></div>
                            <div class="form-row"><div class="form-group"><label>نام پدر صادرکننده:</label><input type="text" name="issuer_father_name"></div><div class="form-group"><label>تاریخ تولد صادرکننده:</label><input type="text" name="issuer_birth_date" placeholder="مثال: ۱۳۶۰/۰۱/۰۱"></div></div>
                            <div class="form-row"><div class="form-group"><label>کد ملی صادرکننده:</label><input type="text" name="issuer_national_code" placeholder="کد ملی ۱۰ رقمی" pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه صادرکننده:</label><input type="text" name="issuer_mobile_number" placeholder="مثال: 09129876543"></div></div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="loan-action-btn">ثبت استعلام و ایجاد کاربر</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_inquiry_list() {
        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای مشاهده لیست استعلام‌ها، لطفاً ابتدا وارد شوید.</p></div>';
        }
    
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $args = [
            'post_type'      => 'inquiry',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
    
        $is_admin_view = current_user_can('manage_maneli_inquiries');
    
        if ($is_admin_view) {
            // Admin can see all, no changes to args.
        } 
        elseif (in_array('maneli_expert', $current_user->roles)) {
            $args['meta_query'] = [
                'relation' => 'OR',
                ['key' => 'assigned_expert_id', 'value' => $user_id],
                ['key' => 'created_by_expert_id', 'value' => $user_id]
            ];
        } 
        else { // Customers
            $args['author'] = $user_id;
        }
    
        $inquiries = get_posts($args);
    
        ob_start();
        echo '<div class="maneli-inquiry-wrapper">';
    
        if (empty($inquiries)) {
            echo '<div class="status-box status-pending"><p>تاکنون هیچ استعلامی برای شما ثبت نشده است.</p></div>';
        } else {
            $report_page_url = home_url('/dashboard/?endp=inf_menu_4');
    
            echo '<h3>لیست استعلام‌های شما</h3>';
            echo '<table class="shop_table shop_table_responsive my_account_orders">';
            echo '<thead><tr>';
            echo '<th><span class="nobr">شناسه</span></th>';
            if ($is_admin_view) {
                echo '<th><span class="nobr">مشتری</span></th>';
                echo '<th><span class="nobr">ثبت توسط</span></th>';
            }
            echo '<th><span class="nobr">خودرو</span></th>';
            echo '<th><span class="nobr">وضعیت</span></th>';
            echo '<th><span class="nobr">تاریخ ثبت</span></th>';
            echo '<th></th>';
            echo '</tr></thead>';
            echo '<tbody>';
    
            foreach ($inquiries as $inquiry) {
                $inquiry_id = $inquiry->ID;
                $product_id = get_post_meta($inquiry_id, 'product_id', true);
                $status = get_post_meta($inquiry_id, 'inquiry_status', true);
                $report_url = add_query_arg('inquiry_id', $inquiry_id, $report_page_url);
                
                echo '<tr>';
                echo '<td data-title="شناسه">#' . esc_html($inquiry_id) . '</td>';
    
                if ($is_admin_view) {
                    $customer = get_userdata($inquiry->post_author);
                    echo '<td data-title="مشتری">' . esc_html($customer->display_name) . '</td>';
                    $creator_name = get_post_meta($inquiry_id, 'assigned_expert_name', true) ?: 'مشتری';
                    echo '<td data-title="ثبت توسط">' . esc_html($creator_name) . '</td>';
                }
    
                echo '<td data-title="خودرو">' . esc_html(get_the_title($product_id)) . '</td>';
                echo '<td data-title="وضعیت">' . esc_html(Maneli_CPT_Handler::get_status_label($status)) . '</td>';
                echo '<td data-title="تاریخ">' . get_the_date('Y/m/d', $inquiry_id) . '</td>';
                echo '<td class="woocommerce-orders-table__cell-order-actions"><a href="' . esc_url($report_url) . '" class="button view">مشاهده جزئیات</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
        echo '</div>';
    
        return ob_get_clean();
    }
    
    public function render_frontend_credit_report() {
        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این محتوا را ندارید.</p></div>';
        }
        if (!isset($_GET['inquiry_id'])) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شناسه استعلام مشخص نشده است.</p></div>';
        }
        
        $inquiry_id = intval($_GET['inquiry_id']);
        $inquiry = get_post($inquiry_id);
        
        if (!$inquiry || $inquiry->post_type !== 'inquiry') {
            return '<div class="maneli-inquiry-wrapper error-box"><p>استعلام یافت نشد.</p></div>';
        }

        $current_user = wp_get_current_user();
        $inquiry_author_id = (int)$inquiry->post_author;
        $assigned_expert_id = (int)get_post_meta($inquiry_id, 'assigned_expert_id', true);
        
        $can_view = false;
        if (current_user_can('manage_maneli_inquiries')) {
            $can_view = true;
        } elseif (in_array('maneli_expert', $current_user->roles) && $assigned_expert_id === $current_user->ID) {
            $can_view = true;
        } elseif ($inquiry_author_id === $current_user->ID) {
            return $this->render_customer_report_html($inquiry_id);
        }

        if (!$can_view) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما اجازه مشاهده این گزارش را ندارید.</p></div>';
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
        $product_id = $post_meta['product_id'][0] ?? 0;

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper frontend-expert-report">
            <h2 class="report-main-title">گزارش کامل اعتبار <small>(برای استعلام #<?php echo esc_html($inquiry_id); ?>)</small></h2>
            
            <div class="report-box">
                <h3 class="report-box-title">خودروی درخواستی</h3>
                <div class="report-car-image">
                    <?php
                    if ($product_id && has_post_thumbnail($product_id)) {
                        echo get_the_post_thumbnail($product_id, 'medium');
                    } else {
                        echo '<div class="no-image">تصویری برای این محصول ثبت نشده است.</div>';
                    }
                    ?>
                </div>
                <table class="summary-table">
                    <tbody>
                        <tr><th>خودروی انتخابی</th><td><?php echo get_the_title($product_id); ?></td></tr>
                        <tr><th>قیمت کل</th><td><?php printf('%s <span>تومان</span>', number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0))); ?></td></tr>
                        <tr><th>مقدار پیش پرداخت</th><td><?php printf('%s <span>تومان</span>', number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0))); ?></td></tr>
                        <tr><th>تعداد اقساط</th><td><?php printf('%s <span>ماهه</span>', esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0)); ?></td></tr>
                        <tr><th>مبلغ هر قسط</th><td><?php printf('%s <span>تومان</span>', number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0))); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="report-box">
                <h3 class="report-box-title">اطلاعات فردی</h3>
                <div class="form-grid">
                    <?php
                    $buyer_fields = ['first_name' => 'نام (خریدار)','last_name' => 'نام خانوادگی (خریدار)','national_code' => 'کد ملی (خریدار)','father_name' => 'نام پدر (خریدار)','birth_date' => 'تاریخ تولد (خریدار)','mobile_number' => 'شماره موبایل (خریدار)'];
                    $buyer_field_pairs = array_chunk($buyer_fields, 2, true);
                    foreach ($buyer_field_pairs as $pair) {
                        echo '<div class="form-row">';
                        foreach($pair as $key => $label) {
                            $value = $post_meta[$key][0] ?? '';
                            echo '<div class="form-group"><label>' . esc_html($label) . '</label><div class="detail-value-box">' . esc_html($value) . '</div></div>';
                        }
                        if (count($pair) < 2) { echo '<div class="form-group"></div>'; }
                        echo '</div>';
                    }
                    ?>
                </div>
                <?php
                $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
                if ($issuer_type === 'other'):
                    $issuer_fields = ['issuer_first_name' => 'نام (صادر کننده)','issuer_last_name' => 'نام خانوادگی (صادر کننده)','issuer_national_code' => 'کد ملی (صادر کننده)','issuer_father_name' => 'نام پدر (صادر کننده)','issuer_birth_date' => 'تاریخ تولد (صادر کننده)','issuer_mobile_number' => 'شماره موبایل (صادر کننده)'];
                    $issuer_field_pairs = array_chunk($issuer_fields, 2, true);
                ?>
                    <h4 class="report-section-divider">اطلاعات صادر کننده چک</h4>
                    <div class="form-grid">
                    <?php
                    foreach ($issuer_field_pairs as $pair) {
                        echo '<div class="form-row">';
                        foreach($pair as $key => $label) {
                            $value = $post_meta[$key][0] ?? '';
                            echo '<div class="form-group"><label>' . esc_html($label) . '</label><div class="detail-value-box">' . esc_html($value) . '</div></div>';
                        }
                        if (count($pair) < 2) { echo '<div class="form-group"></div>'; }
                        echo '</div>';
                    }
                    ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="report-box">
                <h3 class="report-box-title">نتیجه استعلام وضعیت چک (صیادی)</h3>
                <div class="maneli-status-bar">
                    <?php
                    $colors = [ 1 => ['name' => 'سفید', 'class' => 'white'], 2 => ['name' => 'زرد', 'class' => 'yellow'], 3 => ['name' => 'نارنجی', 'class' => 'orange'], 4 => ['name' => 'قهوه‌ای', 'class' => 'brown'], 5 => ['name' => 'قرمز', 'class' => 'red'] ];
                    foreach ($colors as $code => $color) {
                        $active_class = ((string)$code === (string)$cheque_color_code) ? 'active' : '';
                        echo "<div class='bar-segment segment-{$color['class']} {$active_class}'><span>" . esc_html($color['name']) . "</span></div>";
                    }
                    ?>
                </div>
                 <table class="summary-table right-aligned-table" style="margin-top: 20px;">
                    <tbody>
                        <?php
                        $cheque_color_map = [
                            '1' => ['text' => 'سفید', 'desc' => 'فاقد هرگونه سابقه چک برگشتی.'],'2' => ['text' => 'زرد', 'desc' => 'یک فقره چک برگشتی یا حداکثر مبلغ 50 میلیون ریال تعهد برگشتی.'],'3' => ['text' => 'نارنجی', 'desc' => 'دو الی چهار فقره چک برگشتی یا حداکثر مبلغ 200 میلیون ریال تعهد برگشتی.'],'4' => ['text' => 'قهوه‌ای', 'desc' => 'پنج تا ده فقره چک برگشتی یا حداکثر مبلغ 500 میلیون ریال تعهد برگشتی.'],'5' => ['text' => 'قرمز', 'desc' => 'بیش از ده فقره چک برگشتی یا بیش از مبلغ 500 میلیون ریال تعهد برگشتی.'], 0  => ['text' => 'نامشخص', 'desc' => 'اطلاعاتی از فینوتک دریافت نشد یا استعلام ناموفق بود.']];
                        $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
                        ?>
                        <tr><th>وضعیت اعتباری</th><td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td></tr>
                        <tr><th>توضیح</th><td><?php echo esc_html($color_info['desc']); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="report-back-button-wrapper">
                <a href="<?php echo esc_url(home_url('/dashboard/?endp=inf_menu_3')); ?>" class="loan-action-btn">بازگشت به لیست استعلام‌ها</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_settings_shortcode($atts) {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        $atts = shortcode_atts(['tab' => 'gateways'], $atts, 'maneli_settings');
        $tab = sanitize_key($atts['tab']);
        
        if(isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            echo '<div class="status-box status-approved"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        }

        $settings_page = new Maneli_Settings_Page();
        return $settings_page->render_frontend_settings_form($tab);
    }

    public function render_user_list_shortcode() {
        if (!current_user_can('manage_maneli_inquiries')) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }

        if (isset($_GET['edit_user'])) {
            $user_id_to_edit = intval($_GET['edit_user']);
            return $this->render_user_edit_form($user_id_to_edit);
        }

        if (isset($_GET['user-updated']) && $_GET['user-updated'] == 'true') {
            echo '<div class="status-box status-approved"><p>اطلاعات کاربر با موفقیت به‌روزرسانی شد.</p></div>';
        }

        $all_users = get_users(['orderby' => 'display_name']);
        
        $current_url = remove_query_arg(['edit_user', 'user-updated'], $_SERVER['REQUEST_URI']);

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper">
            <h3>لیست کامل کاربران</h3>
            <table class="shop_table shop_table_responsive">
                <thead>
                    <tr>
                        <th>نام نمایشی</th>
                        <th>نام کاربری</th>
                        <th>ایمیل</th>
                        <th>نقش</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): 
                        $role_names = array_map(
                            function($role) {
                                global $wp_roles;
                                return $wp_roles->roles[$role]['name'] ?? $role;
                            },
                            $user->roles
                        );
                        $edit_link = add_query_arg('edit_user', $user->ID, $current_url);
                    ?>
                    <tr>
                        <td data-title="نام نمایشی"><?php echo esc_html($user->display_name); ?></td>
                        <td data-title="نام کاربری"><?php echo esc_html($user->user_login); ?></td>
                        <td data-title="ایمیل"><?php echo esc_html($user->user_email); ?></td>
                        <td data-title="نقش"><?php echo esc_html(implode(', ', $role_names)); ?></td>
                        <td data-title="عملیات">
                            <a href="<?php echo esc_url($edit_link); ?>" class="button view">ویرایش</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_user_edit_form($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>کاربر مورد نظر یافت نشد.</p></div>';
        }
        
        $back_link = remove_query_arg('edit_user', $_SERVER['REQUEST_URI']);

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper">
            <h3>ویرایش کاربر: <?php echo esc_html($user->display_name); ?></h3>
            <form id="admin-edit-user-form" class="maneli-inquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="maneli_admin_update_user">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
                <?php wp_nonce_field('maneli_admin_update_user', 'maneli_update_user_nonce'); ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url($back_link); ?>">

                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label>نام:</label><input type="text" name="first_name" value="<?php echo esc_attr($user->first_name); ?>"></div>
                        <div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name" value="<?php echo esc_attr($user->last_name); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>ایمیل:</label><input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>"></div>
                        <div class="form-group"><label>تلفن همراه:</label><input type="text" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_number', true)); ?>"></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'father_name', true)); ?>"></div>
                        <div class="form-group"><label>تاریخ تولد:</label><input type="text" name="birth_date" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>" placeholder="مثال: ۱۳۶۵/۰۴/۱۵"></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'national_code', true)); ?>" placeholder="کد ملی ۱۰ رقمی"></div>
                        <div class="form-group"></div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="loan-action-btn">ذخیره تغییرات</button>
                    <a href="<?php echo esc_url($back_link); ?>" style="margin-right: 15px;">انصراف</a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}