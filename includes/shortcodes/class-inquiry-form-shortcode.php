<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Inquiry_Form_Shortcode {

    private $datepicker_loaded = false;

    public function __construct() {
        add_shortcode('car_inquiry_form', [$this, 'render_inquiry_form']);
    }

    private function load_datepicker_assets() {
        if ($this->datepicker_loaded) {
            return;
        }

        wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
        wp_enqueue_script(
            'maneli-jalali-datepicker',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js',
            [], '2.1.0', true
        );
        
        $init_script = "
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof kamadatepicker !== 'undefined') {
                    // Standard inquiry form
                    var buyer_birth_date_elem = document.getElementById('buyer_birth_date');
                    if(buyer_birth_date_elem) kamadatepicker('buyer_birth_date', { bidi: true, placeholder: 'مثال: ۱۳۶۵/۰۴/۱۵', format: 'YYYY/MM/DD' });

                    var issuer_birth_date_elem = document.getElementById('issuer_birth_date');
                    if(issuer_birth_date_elem) kamadatepicker('issuer_birth_date', { bidi: true, placeholder: 'مثال: ۱۳۶۰/۰۱/۰۱', format: 'YYYY/MM/DD' });
                    
                    // Expert panel form
                    var expert_buyer_birth_date_elem = document.getElementById('expert_buyer_birth_date');
                    if(expert_buyer_birth_date_elem) kamadatepicker('expert_buyer_birth_date', { bidi: true, placeholder: 'مثال: ۱۳۶۵/۰۴/۱۵', format: 'YYYY/MM/DD' });

                    var expert_issuer_birth_date_elem = document.getElementById('expert_issuer_birth_date');
                    if(expert_issuer_birth_date_elem) kamadatepicker('expert_issuer_birth_date', { bidi: true, placeholder: 'مثال: ۱۳۶۰/۰۱/۰۱', format: 'YYYY/MM/DD' });
                }
            });
        ";
        wp_add_inline_script('maneli-jalali-datepicker', $init_script);
        $this->datepicker_loaded = true;
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
                case 'form_pending': 
                    echo $this->render_step2_identity_form($user_id); 
                    break;
                case 'payment_pending': 
                    echo $this->render_step_payment($user_id); 
                    break;
                default: 
                    echo $this->render_step1_car_selection(); 
                    break;
            }
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    private function render_progress_tracker($active_step) {
        $steps = [1 => 'انتخاب خودرو', 2 => 'تکمیل اطلاعات', 3 => 'پرداخت هزینه', 4 => 'در انتظار بررسی', 5 => 'نتیجه نهایی'];
        $options = get_option('maneli_inquiry_all_options', []);
        $payment_enabled = isset($options['payment_enabled']) && $options['payment_enabled'] == '1';
        if (!$payment_enabled) {
            unset($steps[3]);
        }
        ?>
        <div class="progress-tracker">
            <?php foreach ($steps as $number => $title) : ?>
                <?php
                $step_class = '';
                if ($number < $active_step) { $step_class = 'completed'; } 
                elseif ($number == $active_step) { $step_class = 'active'; }
                ?>
                <div class="step <?php echo $step_class; ?>">
                    <div class="circle"><?php echo number_format_i18n(array_search($number, array_keys($steps)) + 1); ?></div>
                    <div class="label"><span class="step-title">مرحله <?php echo number_format_i18n(array_search($number, array_keys($steps)) + 1); ?></span><span class="step-name"><?php echo esc_html($title); ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function render_step_payment($user_id) {
        $options = get_option('maneli_inquiry_all_options', []);
        $amount = (int)($options['inquiry_fee'] ?? 0);
        $payment_enabled = isset($options['payment_enabled']) && $options['payment_enabled'] == '1';

        if (!$payment_enabled) { return ''; }

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
        $this->load_datepicker_assets();
        $car_id = get_user_meta($user_id, 'maneli_selected_car_id', true);
        $car_name = get_the_title($car_id);
        $user_info = get_userdata($user_id);
        $down_payment = get_user_meta($user_id, 'maneli_inquiry_down_payment', true);
        $term_months = get_user_meta($user_id, 'maneli_inquiry_term_months', true);
        $total_price = get_user_meta($user_id, 'maneli_inquiry_total_price', true);
        $installment_amount = get_user_meta($user_id, 'maneli_inquiry_installment', true);
        
        $loan_amount = (int)$total_price - (int)$down_payment;
        $product = $car_id ? wc_get_product($car_id) : null;
        $car_model = '';
        if ($product) {
            $car_model = $product->get_attribute('pa_model'); 
        }

        ob_start();
        ?>
        <div class="inquiry-summary-flex-container">
            <div class="inquiry-summary-box">
                <h4>خلاصه درخواست شما</h4>
                <table class="summary-table">
                    <tr><td><strong>خودروی انتخابی:</strong></td><td><?php echo esc_html($car_name); ?></td></tr>
                    <tr><td><strong>مدل خودرو:</strong></td><td><?php echo esc_html($car_model); ?></td></tr>
                    <tr><td><strong>قیمت کل خودرو:</strong></td><td><?php echo esc_html(number_format_i18n((int)$total_price)); ?> <span>تومان</span></td></tr>
                    <tr><td><strong>مبلغ پیش پرداخت:</strong></td><td><?php echo esc_html(number_format_i18n((int)$down_payment)); ?> <span>تومان</span></td></tr>
                    <tr><td><strong>میزان وام:</strong></td><td><?php echo esc_html(number_format_i18n($loan_amount)); ?> <span>تومان</span></td></tr>
                    <tr><td><strong>تعداد اقساط:</strong></td><td><?php echo esc_html($term_months); ?> <span>ماهه</span></td></tr>
                    <tr><td><strong>مبلغ هر قسط (تقریبی):</strong></td><td><?php echo esc_html(number_format_i18n((int)$installment_amount)); ?> <span>تومان</span></td></tr>
                </table>
            </div>
            <div class="inquiry-car-image">
                <?php if ($car_id && has_post_thumbnail($car_id)) { echo get_the_post_thumbnail($car_id, 'medium'); } ?>
            </div>
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
                <p class="form-section-title">مشخصات مراجعه کننده</p>
                <div class="form-grid">
                     <div class="form-row">
                        <div class="form-group"><label>نام:</label><input type="text" name="first_name" value="<?php echo esc_attr($user_info->first_name); ?>" required></div>
                        <div class="form-group"><label>نام خانوادگی:</label><input type="text" name="last_name" value="<?php echo esc_attr($user_info->last_name); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" value="<?php echo esc_attr(get_user_meta($user_id, 'father_name', true)); ?>" required></div>
                        <div class="form-group"><label>تاریخ تولد:</label><input type="text" id="buyer_birth_date" name="birth_date" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" value="<?php echo esc_attr(get_user_meta($user_id, 'national_code', true)); ?>" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div>
                        <div class="form-group"><label>شماره همراه:</label><input type="text" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user_id, 'mobile_number', true)); ?>" placeholder="مثال: 09123456789" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>شغل:</label><input type="text" name="occupation" value="<?php echo esc_attr(get_user_meta($user_id, 'occupation', true)); ?>"></div>
                        <div class="form-group"><label>میزان درآمد (تومان):</label><input type="text" name="income_level" value="<?php echo esc_attr(get_user_meta($user_id, 'income_level', true)); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>شماره تماس ثابت:</label><input type="text" name="phone_number" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>"></div>
                        <div class="form-group">
                            <label>وضعیت محل سکونت:</label>
                            <select name="residency_status">
                                <option value="">-- انتخاب کنید --</option>
                                <option value="owner" <?php selected(get_user_meta($user_id, 'residency_status', true), 'owner'); ?>>مالک</option>
                                <option value="tenant" <?php selected(get_user_meta($user_id, 'residency_status', true), 'tenant'); ?>>مستاجر</option>
                            </select>
                        </div>
                    </div>
                     <div class="form-row">
                        <div class="form-group">
                            <label>وضعیت محل کار:</label>
                            <select name="workplace_status">
                                <option value="">-- انتخاب کنید --</option>
                                <option value="permanent" <?php selected(get_user_meta($user_id, 'workplace_status', true), 'permanent'); ?>>رسمی</option>
                                <option value="contract" <?php selected(get_user_meta($user_id, 'workplace_status', true), 'contract'); ?>>قراردادی</option>
                                 <option value="freelance" <?php selected(get_user_meta($user_id, 'workplace_status', true), 'freelance'); ?>>آزاد</option>
                            </select>
                        </div>
                        <div class="form-group"><label>آدرس منزل:</label><textarea name="address" rows="3"><?php echo esc_textarea(get_user_meta($user_id, 'address', true)); ?></textarea></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label>نام بانک:</label><input type="text" name="bank_name" value="<?php echo esc_attr(get_user_meta($user_id, 'bank_name', true)); ?>"></div>
                        <div class="form-group"><label>شماره حساب:</label><input type="text" name="account_number" value="<?php echo esc_attr(get_user_meta($user_id, 'account_number', true)); ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>کد شعبه:</label><input type="text" name="branch_code" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_code', true)); ?>"></div>
                        <div class="form-group"><label>نام شعبه:</label><input type="text" name="branch_name" value="<?php echo esc_attr(get_user_meta($user_id, 'branch_name', true)); ?>"></div>
                    </div>
                </div>
            </div>

            <div id="issuer-form-wrapper" style="display: none;">
                <p class="form-section-title">فرم اطلاعات صادر کننده چک</p>
                <div class="form-grid">
                    <div class="form-row">
                        <div class="form-group"><label>نام صادرکننده چک:</label><input type="text" name="issuer_full_name"></div>
                        <div class="form-group"><label>کد ملی صادرکننده:</label><input type="text" name="issuer_national_code" placeholder="کد ملی ۱۰ رقمی" pattern="\d{10}"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>نام بانک:</label><input type="text" name="issuer_bank_name"></div>
                        <div class="form-group"><label>شماره حساب:</label><input type="text" name="issuer_account_number"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>کد شعبه:</label><input type="text" name="issuer_branch_code"></div>
                        <div class="form-group"><label>نام شعبه:</label><input type="text" name="issuer_branch_name"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>وضعیت سکونت صادرکننده:</label>
                            <select name="issuer_residency_status">
                               <option value="">-- انتخاب کنید --</option>
                                <option value="owner">مالک</option>
                                <option value="tenant">مستاجر</option>
                            </select>
                        </div>
                         <div class="form-group">
                            <label>وضعیت محل کار صادرکننده:</label>
                            <select name="issuer_workplace_status">
                                <option value="">-- انتخاب کنید --</option>
                                <option value="permanent">رسمی</option>
                                <option value="contract">قراردادی</option>
                                <option value="freelance">آزاد</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>نام پدر صادرکننده:</label><input type="text" name="issuer_father_name"></div>
                        <div class="form-group"><label>شغل صادرکننده:</label><input type="text" name="issuer_occupation"></div>
                    </div>
                     <div class="form-row">
                        <div class="form-group"><label>آدرس صادرکننده:</label><textarea name="issuer_address" rows="3"></textarea></div>
                        <div class="form-group"><label>شماره تماس صادرکننده:</label><input type="text" name="issuer_phone_number"></div>
                    </div>
                </div>
            </div>
            
            <button type="submit" id="submit-identity-btn">ادامه و رفتن به مرحله بعد</button>
        </form>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="issuer_type"]');
            if (!radios.length) return;
            const buyerForm = document.getElementById('buyer-form-wrapper');
            const issuerForm = document.getElementById('issuer-form-wrapper');

            function toggleForms() {
                const checkedRadio = document.querySelector('input[name="issuer_type"]:checked');
                if (!checkedRadio) {
                    buyerForm.style.display = 'none';
                    issuerForm.style.display = 'none';
                    return;
                }
                const selectedValue = checkedRadio.value;
                if (selectedValue === 'self') {
                    buyerForm.style.display = 'block';
                    issuerForm.style.display = 'none';
                } else {
                    buyerForm.style.display = 'block';
                    issuerForm.style.display = 'block';
                }
            }
            radios.forEach(radio => radio.addEventListener('change', toggleForms));
            // Call toggleForms on load in case a radio is pre-checked (though it's not in the HTML)
            toggleForms();
        });
        </script>
        <?php
        return ob_get_clean();
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

    private function render_customer_report_html($inquiry_id) {
        // This function remains the same as it's part of the form workflow
        // To keep it clean, the full code is here. In a more advanced refactor,
        // this could also become a template part.
        
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
                 <?php if (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED')): ?>
                     <table class="summary-table" style="margin-top:20px;">
                        <tr>
                            <td><strong>وضعیت چک صیادی:</strong></td>
                            <td>نامشخص</td>
                        </tr>
                        <tr>
                            <td><strong>توضیح وضعیت:</strong></td>
                            <td>استعلام بانکی انجام نشده است.</td>
                        </tr>
                    </table>
                <?php else: ?>
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
                <?php endif; ?>
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

    private function render_maneli_expert_new_inquiry_form() {
        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای استفاده از این فرم را ندارید.</p></div>';
        }

        $this->load_datepicker_assets();
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);
        wp_enqueue_script('maneli-expert-panel-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/expert-panel.js', ['jquery', 'select2'], '2.5.0', true);
        wp_localize_script('maneli-expert-panel-js', 'maneli_expert_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('maneli_expert_car_search_nonce')
        ]);
        
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
                    <select id="product_id_expert" name="product_id" style="width: 100%;" required>
                        <option value="">یک خودرو را جستجو کنید</option>
                    </select>
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
                            <div class="form-row"><div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" required></div><div class="form-group"><label>تاریخ تولد:</label><input type="text" id="expert_buyer_birth_date" name="birth_date" required></div></div>
                            <div class="form-row"><div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه (نام کاربری):</label><input type="text" name="mobile_number" placeholder="مثال: 09123456789" required></div></div>
                        </div>
                    </div>
                    
                    <div id="issuer-form-wrapper" style="display: none;">
                        <p class="form-section-title">فرم اطلاعات صادر کننده چک</p>
                        <div class="form-grid">
                            <div class="form-row"><div class="form-group"><label>نام صادرکننده:</label><input type="text" name="issuer_first_name"></div><div class="form-group"><label>نام خانوادگی صادرکننده:</label><input type="text" name="issuer_last_name"></div></div>
                            <div class="form-row"><div class="form-group"><label>نام پدر صادرکننده:</label><input type="text" name="issuer_father_name"></div><div class="form-group"><label>تاریخ تولد صادرکننده:</label><input type="text" id="expert_issuer_birth_date" name="issuer_birth_date"></div></div>
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
}