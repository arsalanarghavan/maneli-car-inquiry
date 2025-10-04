<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Inquiry_Shortcodes {

    private $datepicker_loaded = false;

    public function __construct() {
        add_shortcode('car_inquiry_form', [$this, 'render_inquiry_form']);
        add_shortcode('loan_calculator', [$this, 'render_loan_calculator']);
        add_shortcode('maneli_inquiry_list', [$this, 'render_inquiry_list']);
        add_shortcode('maneli_cash_inquiry_list', [$this, 'render_cash_inquiry_list']);
        add_shortcode('maneli_frontend_credit_report', [$this, 'render_frontend_credit_report']);

        // AJAX handler for filtering inquiries
        add_action('wp_ajax_maneli_filter_inquiries_ajax', [$this, 'handle_filter_inquiries_ajax']);
        add_action('wp_ajax_maneli_filter_cash_inquiries_ajax', [$this, 'handle_filter_cash_inquiries_ajax']);


        // Backward compatibility for old shortcode
        add_shortcode('maneli_expert_inquiry_list', [$this, 'render_inquiry_list']);
    }

    private function load_datepicker_assets() {
        if ($this->datepicker_loaded) {
            return;
        }

        wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script(
            'maneli-jalali-datepicker',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/jquery-ui-datepicker-fa.js',
            ['jquery-ui-datepicker'], '1.0.0', true
        );
        
        $init_script = '
            jQuery(document).ready(function($) {
                var toPersianDigits = function(str) {
                    var persianDigits = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
                    return String(str).replace(/[0-9]/g, function(w) { return persianDigits[+w]; });
                };
                $("input.maneli-date-picker").datepicker({
                    isJalali: true, 
                    dateFormat: "yy/mm/dd",
                    changeMonth: true,
                    changeYear: true,
                    onSelect: function(dateText, inst) { $(this).val(toPersianDigits(dateText)); },
                    onChangeMonthYear: function(year, month, inst) {
                        setTimeout(function() {
                            var pYear = toPersianDigits(inst.selectedYear > 0 ? inst.selectedYear : year);
                            $(".ui-datepicker-year").val(pYear);
                            $(".ui-datepicker-year option").each(function() { $(this).text(toPersianDigits($(this).text())); });
                            $(".ui-datepicker-month option").each(function() { $(this).text(toPersianDigits($(this).text())); });
                        }, 0);
                    },
                    beforeShow: function(input, inst) {
                         setTimeout(function() {
                             var pYear = toPersianDigits(inst.selectedYear > 0 ? inst.selectedYear : $(input).val().split("/")[0]);
                             $(".ui-datepicker-year").val(pYear);
                             $(".ui-datepicker-year option").each(function() { $(this).text(toPersianDigits($(this).text())); });
                             $(".ui-datepicker-month option").each(function() { $(this).text(toPersianDigits($(this).text())); });
                         },0);
                    }
                });
            });
        ';
        wp_add_inline_script('maneli-jalali-datepicker', $init_script);
        $this->datepicker_loaded = true;
    }

    public function render_loan_calculator() {
        if (!function_exists('is_product') || !is_product() || !function_exists('WC')) return '';
        global $product;
        if (!$product instanceof WC_Product) return '';

		if (isset($_GET['cash_inquiry_sent']) && $_GET['cash_inquiry_sent'] == 'true') {
            echo '<div class="status-box status-approved" style="margin-bottom:20px; text-align:center;"><p>درخواست شما با موفقیت ثبت شد. به زودی با شما تماس خواهیم گرفت.</p></div>';
        }
		
        $car_status = get_post_meta($product->get_id(), '_maneli_car_status', true);

        if ($car_status === 'unavailable') {
            $options = get_option('maneli_inquiry_all_options', []);
            $message = $options['unavailable_product_message'] ?? 'در حال حاضر امکان خرید این خودرو میسر نمی‌باشد.';

            ob_start();
            ?>
            <div class="maneli-calculator-container unavailable">
                <div class="unavailable-overlay">
                    <p><?php echo esc_html($message); ?></p>
                </div>
                 <div class="loan-calculator-form" style="filter: blur(5px); pointer-events: none; user-select: none;">
                    <h2 class="loan-title">تعیین بودجه و محاسبه اقساط</h2>
                    <div class="loan-section"><div class="loan-row"><label class="loan-label">مقدار پیش‌پرداخت:</label><input type="text" id="downPaymentInput" disabled></div><input type="range" id="downPaymentSlider" disabled><div class="loan-note"><span>حداقل پیش‌پرداخت:</span><span>- تومان</span></div></div>
                    <div class="loan-section"><h4 class="loan-subtitle">شرایط مورد نیاز</h4><ul class="loan-requirements"><li>۱. شناسنامه - کارت ملی</li><li>۲. دسته چک</li><li>۳. پرینت سه ماه آخر حساب (صاحب چک)</li><li>۴. فیش حقوق یا جواز کسب (متقاضی و صاحب چک)</li></ul></div>
                    <div class="loan-section"><label class="loan-label">مدت زمان باز پرداخت:</label><div class="loan-buttons"><button type="button" class="term-btn" disabled>۱۲ ماهه</button><button type="button" class="term-btn" disabled>۱۸ ماهه</button><button type="button" class="term-btn" disabled>۲۴ ماهه</button><button type="button" class="term-btn" disabled>۳۶ ماهه</button></div></div>
                    <div class="loan-section result-section"><strong>مبلغ تقریبی هر قسط:</strong><span id="installmentAmount">0</span><span> تومان</span></div>
                    <div class="loan-section loan-action-wrapper">
                         <button type="button" class="loan-action-btn" disabled>استعلام سنجی بانکی جهت خرید خودرو</button>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        $cash_price = (int)$product->get_regular_price();
        $installment_price = (int)get_post_meta($product->get_id(), 'installment_price', true);
        $car_colors_str = get_post_meta($product->get_id(), '_maneli_car_colors', true);
        $car_colors = !empty($car_colors_str) ? array_map('trim', explode(',', $car_colors_str)) : [];

        if (empty($installment_price)) {
            $installment_price = $cash_price;
        }
        $min_down_payment = (int)get_post_meta($product->get_id(), 'min_downpayment', true);
        $max_down_payment = (int)($installment_price * 0.8);
        ob_start();
        ?>
        <div class="maneli-calculator-container">
            <div class="calculator-tabs">
                <a href="#" class="tab-link active" data-tab="installment-tab">فروش اقساطی</a>
                <a href="#" class="tab-link" data-tab="cash-tab">فروش نقدی</a>
            </div>
            
            <div class="tabs-content-wrapper">
                <div id="cash-tab" class="tab-content">
                     <form class="loan-calculator-form cash-request-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="maneli_submit_cash_inquiry">
                        <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
                        <?php wp_nonce_field('maneli_cash_inquiry_nonce'); ?>

                        <h2 class="loan-title">درخواست خرید نقدی</h2>
                        
                        <div class="loan-section">
                            <div class="form-grid">
                                <div class="form-row">
                                    <div class="form-group"><label>نام:</label><input type="text" name="cash_first_name" required></div>
                                    <div class="form-group"><label>نام خانوادگی:</label><input type="text" name="cash_last_name" required></div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group"><label>شماره تماس:</label><input type="text" name="cash_mobile_number" required></div>
                                    <div class="form-group">
                                        <label>رنگ خودرو:</label>
                                        <?php if (!empty($car_colors)): ?>
                                            <select name="cash_car_color">
                                                <?php foreach ($car_colors as $color): ?>
                                                    <option value="<?php echo esc_attr($color); ?>"><?php echo esc_html($color); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="text" name="cash_car_color" placeholder="رنگ مورد نظر را وارد کنید">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="loan-section result-section">
                            <strong>قیمت تقریبی:</strong>
                            <span id="cashPriceAmount"><?php echo number_format_i18n($cash_price); ?></span>
                            <span> تومان</span>
                        </div>

                        <div class="loan-section loan-action-wrapper">
                            <button type="submit" class="loan-action-btn">ثبت درخواست استعلام قیمت</button>
                        </div>
                    </form>
                </div>

                <div id="installment-tab" class="tab-content active">
                     <form class="loan-calculator-form" method="post">
                        <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
                        <?php wp_nonce_field('maneli_ajax_nonce'); ?>
                        <div id="loan-calculator" data-price="<?php echo esc_attr($installment_price); ?>" data-min-down="<?php echo esc_attr($min_down_payment); ?>" data-max-down="<?php echo esc_attr($max_down_payment); ?>">
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
            </div>
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
        ob_start();
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
                    <div class="form-row"><div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" value="<?php echo esc_attr(get_user_meta($user_id, 'father_name', true)); ?>" required></div><div class="form-group"><label>تاریخ تولد:</label><input type="text" class="maneli-date-picker" name="birth_date" value="<?php echo esc_attr(get_user_meta($user_id, 'birth_date', true)); ?>" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" required autocomplete="off"></div></div>
                    <div class="form-row"><div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" value="<?php echo esc_attr(get_user_meta($user_id, 'national_code', true)); ?>" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه:</label><input type="text" name="mobile_number" value="<?php echo esc_attr(get_user_meta($user_id, 'mobile_number', true)); ?>" placeholder="مثال: 09123456789" required></div></div>
                </div>
            </div>
            <div id="issuer-form-wrapper" style="display: none;">
                <p class="form-section-title">فرم اطلاعات صادر کننده چک</p>
                <div class="form-grid">
                    <div class="form-row"><div class="form-group"><label>نام صادرکننده چک:</label><input type="text" name="issuer_first_name"></div><div class="form-group"><label>نام خانوادگی صادرکننده چک:</label><input type="text" name="issuer_last_name"></div></div>
                    <div class="form-row"><div class="form-group"><label>نام پدر صادرکننده چک:</label><input type="text" name="issuer_father_name"></div><div class="form-group"><label>تاریخ تولد صادرکننده چک:</label><input type="text" class="maneli-date-picker" name="issuer_birth_date" placeholder="مثال: ۱۳۶۰/۰۱/۰۱" autocomplete="off"></div></div>
                    <div class="form-row"><div class="form-group"><label>کد ملی صادرکننده چک:</label><input type="text" name="issuer_national_code" placeholder="کد ملی ۱۰ رقمی" pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه صادرکننده چک:</label><input type="text" name="issuer_mobile_number" placeholder="مثال: 09129876543"></div></div>
                </div>
            </div>
            <button type="submit" id="submit-identity-btn" style="display:none;">ادامه و رفتن به مرحله بعد</button>
        </form>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="issuer_type"]');
            if (!radios.length) return;
            const buyerForm = document.getElementById('buyer-form-wrapper');
            const issuerForm = document.getElementById('issuer-form-wrapper');
            const submitBtn = document.getElementById('submit-identity-btn');
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
                } else {
                    buyerForm.style.display = 'block';
                    issuerForm.style.display = 'block';
                }
            }
            radios.forEach(radio => radio.addEventListener('change', toggleForms));
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
                            <div class="form-row"><div class="form-group"><label>نام پدر:</label><input type="text" name="father_name" required></div><div class="form-group"><label>تاریخ تولد:</label><input type="text" class="maneli-date-picker" name="birth_date" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" required autocomplete="off"></div></div>
                            <div class="form-row"><div class="form-group"><label>کد ملی:</label><input type="text" name="national_code" placeholder="کد ملی ۱۰ رقمی" required pattern="\d{10}"></div><div class="form-group"><label>تلفن همراه (نام کاربری):</label><input type="text" name="mobile_number" placeholder="مثال: 09123456789" required></div></div>
                        </div>
                    </div>
                    
                    <div id="issuer-form-wrapper" style="display: none;">
                        <p class="form-section-title">فرم اطلاعات صادر کننده چک</p>
                        <div class="form-grid">
                            <div class="form-row"><div class="form-group"><label>نام صادرکننده:</label><input type="text" name="issuer_first_name"></div><div class="form-group"><label>نام خانوادگی صادرکننده:</label><input type="text" name="issuer_last_name"></div></div>
                            <div class="form-row"><div class="form-group"><label>نام پدر صادرکننده:</label><input type="text" name="issuer_father_name"></div><div class="form-group"><label>تاریخ تولد صادرکننده:</label><input type="text" class="maneli-date-picker" name="issuer_birth_date" placeholder="مثال: ۱۳۶۰/۰۱/۰۱" autocomplete="off"></div></div>
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
        $is_admin_or_expert = current_user_can('manage_maneli_inquiries') || in_array('maneli_expert', $current_user->roles);

        if (!$is_admin_or_expert) {
            // Original simple list for customers
            return $this->render_customer_inquiry_list($current_user->ID);
        }
        
        // Advanced list for Admins and Experts
        ob_start();
        ?>
        <div class="maneli-full-width-container">
            <div class="maneli-inquiry-wrapper">

                <?php echo Maneli_Admin_Dashboard_Widgets::render_inquiry_statistics_widgets(); ?>
                
                <h3 style="margin-top:40px;">لیست کامل استعلام‌ها</h3>
                
                <div class="user-list-filters">
                    <form id="maneli-inquiry-filter-form" onsubmit="return false;">
                        <div class="filter-row search-row">
                            <input type="search" id="inquiry-search-input" class="search-input" placeholder="جستجو بر اساس نام مشتری، نام خودرو، کد ملی یا شماره موبایل...">
                        </div>
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="status-filter">وضعیت:</label>
                                <select id="status-filter">
                                    <option value="">همه وضعیت‌ها</option>
                                    <?php foreach (Maneli_CPT_Handler::get_all_statuses() as $key => $label): ?>
                                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (current_user_can('manage_maneli_inquiries')): 
                                $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                            ?>
                             <div class="filter-group">
                                <label for="expert-filter">کارشناس مسئول:</label>
                                <select id="expert-filter" class="maneli-select2">
                                    <option value="">همه کارشناسان</option>
                                    <?php foreach ($experts as $expert) : ?>
                                        <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="maneli-table-wrapper">
                    <table class="shop_table shop_table_responsive">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <?php if (current_user_can('manage_maneli_inquiries')) echo '<th>کارشناس مسئول</th>'; ?>
                                <th>خودرو</th>
                                <th>وضعیت</th>
                                <th>تاریخ ثبت</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="maneli-inquiry-list-tbody">
                           <?php // Initial load is handled by JS ?>
                        </tbody>
                    </table>
                </div>
                <div id="inquiry-list-loader" style="display: none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
                <div class="maneli-pagination-wrapper" style="margin-top: 20px; text-align: center;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Make sure the function exists before calling
            if (typeof $.fn.select2 === 'function' && $('.maneli-select2').length) {
                $('.maneli-select2').select2({
                     placeholder: "یک کارشناس انتخاب کنید",
                     allowClear: true,
                     width: '100%'
                });
            }

            var xhr;
            var searchTimeout;

            function fetch_inquiries(page = 1) {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }

                $('#inquiry-list-loader').show();
                $('#maneli-inquiry-list-tbody').css('opacity', 0.5);
                
                var filters = {
                    action: 'maneli_filter_inquiries_ajax',
                    _ajax_nonce: '<?php echo wp_create_nonce("maneli_inquiry_filter_nonce"); ?>',
                    page: page,
                    search: $('#inquiry-search-input').val(),
                    status: $('#status-filter').val(),
                    expert: $('#expert-filter').length ? $('#expert-filter').val() : ''
                };

                xhr = $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: filters,
                    success: function(response) {
                        if (response.success) {
                            $('#maneli-inquiry-list-tbody').html(response.data.html);
                             $('.maneli-pagination-wrapper').html(response.data.pagination_html);
                        } else {
                            $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" style="text-align:center;">' + (response.data.message || 'خطایی رخ داد.') + '</td></tr>');
                             $('.maneli-pagination-wrapper').html('');
                        }
                    },
                    error: function() {
                        $('#maneli-inquiry-list-tbody').html('<tr><td colspan="7" style="text-align:center;">خطای ارتباط با سرور.</td></tr>');
                    },
                    complete: function() {
                        $('#inquiry-list-loader').hide();
                        $('#maneli-inquiry-list-tbody').css('opacity', 1);
                    }
                });
            }

            // Initial fetch
            fetch_inquiries(1);

            // Event Handlers
            $('#inquiry-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    fetch_inquiries(1);
                }, 500);
            });

            $('#status-filter, #expert-filter').on('change', function() {
                fetch_inquiries(1);
            });

            // Pagination click handler
            $('.maneli-pagination-wrapper').on('click', 'a.page-numbers', function(e) {
                e.preventDefault();
                var pageUrl = $(this).attr('href');
                let pageNum = 1;
                
                // This regex is more robust for extracting the paged number
                const regex = /paged=(\d+)/;
                const matches = pageUrl.match(regex);

                if (matches) {
                    pageNum = parseInt(matches[1]);
                } else if (!$(this).hasClass('prev') && !$(this).hasClass('next')) {
                    pageNum = parseInt($(this).text());
                } else {
                    let currentPage = parseInt($('.maneli-pagination-wrapper .page-numbers.current').text());
                    currentPage = isNaN(currentPage) ? 1 : currentPage;
                    if ($(this).hasClass('prev')) {
                        pageNum = Math.max(1, currentPage - 1);
                    } else {
                        pageNum = currentPage + 1;
                    }
                }

                fetch_inquiries(pageNum);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
	
	public function render_cash_inquiry_list() {
        if (!is_user_logged_in()) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>برای مشاهده لیست، لطفاً ابتدا وارد شوید.</p></div>';
        }
    
        $current_user = wp_get_current_user();
        if (!current_user_can('manage_maneli_inquiries') && !in_array('maneli_expert', $current_user->roles)) {
             return '<div class="maneli-inquiry-wrapper error-box"><p>شما دسترسی لازم برای مشاهده این بخش را ندارید.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="maneli-full-width-container">
            <div class="maneli-inquiry-wrapper">
                <h3>لیست درخواست‌های خرید نقدی</h3>
                <div class="user-list-filters">
                    <form id="maneli-cash-inquiry-filter-form" onsubmit="return false;">
                        <div class="filter-row search-row">
                            <input type="search" id="cash-inquiry-search-input" class="search-input" placeholder="جستجو بر اساس نام مشتری، نام خودرو یا شماره موبایل...">
                        </div>
                    </form>
                </div>

                <div class="maneli-table-wrapper">
                    <table class="shop_table shop_table_responsive">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>مشتری</th>
                                <th>موبایل</th>
                                <th>خودرو</th>
                                <th>رنگ</th>
                                <th>تاریخ ثبت</th>
                            </tr>
                        </thead>
                        <tbody id="maneli-cash-inquiry-list-tbody">
                           <?php // Initial load is handled by JS ?>
                        </tbody>
                    </table>
                </div>
                <div id="cash-inquiry-list-loader" style="display: none; text-align:center; padding: 40px;"><div class="spinner is-active" style="float:none;"></div></div>
                <div class="maneli-cash-pagination-wrapper" style="margin-top: 20px; text-align: center;"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var xhr;
            var searchTimeout;

            function fetch_cash_inquiries(page = 1) {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }

                $('#cash-inquiry-list-loader').show();
                $('#maneli-cash-inquiry-list-tbody').css('opacity', 0.5);
                
                var filters = {
                    action: 'maneli_filter_cash_inquiries_ajax',
                    _ajax_nonce: '<?php echo wp_create_nonce("maneli_cash_inquiry_filter_nonce"); ?>',
                    page: page,
                    search: $('#cash-inquiry-search-input').val()
                };

                xhr = $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: filters,
                    success: function(response) {
                        if (response.success) {
                            $('#maneli-cash-inquiry-list-tbody').html(response.data.html);
                             $('.maneli-cash-pagination-wrapper').html(response.data.pagination_html);
                        } else {
                            $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="6" style="text-align:center;">' + (response.data.message || 'خطایی رخ داد.') + '</td></tr>');
                             $('.maneli-cash-pagination-wrapper').html('');
                        }
                    },
                    error: function() {
                        $('#maneli-cash-inquiry-list-tbody').html('<tr><td colspan="6" style="text-align:center;">خطای ارتباط با سرور.</td></tr>');
                    },
                    complete: function() {
                        $('#cash-inquiry-list-loader').hide();
                        $('#maneli-cash-inquiry-list-tbody').css('opacity', 1);
                    }
                });
            }

            fetch_cash_inquiries(1);

            $('#cash-inquiry-search-input').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    fetch_cash_inquiries(1);
                }, 500);
            });

            $('.maneli-cash-pagination-wrapper').on('click', 'a.page-numbers', function(e) {
                e.preventDefault();
                var pageUrl = $(this).attr('href');
                let pageNum = 1;
                const regex = /paged=(\d+)/;
                const matches = pageUrl.match(regex);
                if (matches) {
                    pageNum = parseInt(matches[1]);
                } else if (!$(this).hasClass('prev') && !$(this).hasClass('next')) {
                    pageNum = parseInt($(this).text());
                } else {
                    let currentPage = parseInt($('.maneli-cash-pagination-wrapper .page-numbers.current').text());
                    currentPage = isNaN(currentPage) ? 1 : currentPage;
                    pageNum = $(this).hasClass('prev') ? Math.max(1, currentPage - 1) : currentPage + 1;
                }
                fetch_cash_inquiries(pageNum);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    
    private function render_customer_inquiry_list($user_id) {
        $args = [
            'post_type'      => 'inquiry',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'author'         => $user_id
        ];
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
                $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
                list($y, $m, $d) = explode('-', $gregorian_date);
                
                echo '<tr>';
                echo '<td data-title="شناسه">#' . esc_html($inquiry_id) . '</td>';
                echo '<td data-title="خودرو">' . esc_html(get_the_title($product_id)) . '</td>';
                echo '<td data-title="وضعیت">' . esc_html(Maneli_CPT_Handler::get_status_label($status)) . '</td>';
                echo '<td data-title="تاریخ">' . esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')) . '</td>';
                echo '<td class="woocommerce-orders-table__cell-order-actions"><a href="' . esc_url($report_url) . '" class="button view">مشاهده جزئیات</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        }
        echo '</div>';
    
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for filtering inquiries.
     */
    public function handle_filter_inquiries_ajax() {
        if ( !isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'maneli_inquiry_filter_nonce') ) {
            wp_send_json_error(['message' => 'خطای امنیتی. لطفاً صفحه را رفرش کنید.']);
        }

        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        $args = [
            'post_type'      => 'inquiry',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ];

        // This is the main query for filters
        $meta_query = ['relation' => 'AND'];

        if (!empty($search_query)) {
            $user_ids_from_meta = get_users([
                'fields' => 'ID',
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => 'first_name', 'value' => $search_query, 'compare' => 'LIKE'],
                    ['key' => 'last_name', 'value' => $search_query, 'compare' => 'LIKE'],
                    ['key' => 'national_code', 'value' => $search_query, 'compare' => 'LIKE'],
                    ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'],
                ]
            ]);
            $users_by_display_name = get_users(['search' => '*' . esc_attr($search_query) . '*', 'fields' => 'ID']);
            $all_user_ids = array_unique(array_merge($user_ids_from_meta, $users_by_display_name));
            
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);

            $search_related_inquiry_ids = [];
            if (!empty($all_user_ids)) {
                $user_inquiries = get_posts(['post_type' => 'inquiry', 'posts_per_page' => -1, 'author__in' => $all_user_ids, 'fields' => 'ids']);
                $search_related_inquiry_ids = array_merge($search_related_inquiry_ids, $user_inquiries);
            }
             if (!empty($product_ids)) {
                $product_inquiries = get_posts(['post_type' => 'inquiry', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => 'product_id', 'meta_value' => $product_ids, 'meta_compare' => 'IN']);
                $search_related_inquiry_ids = array_merge($search_related_inquiry_ids, $product_inquiries);
            }

            if(empty($search_related_inquiry_ids)){
                 $args['post__in'] = [0]; // Force no results
            } else {
                 $args['post__in'] = array_unique($search_related_inquiry_ids);
            }
        }
        
        if (!empty($_POST['status'])) {
            $meta_query[] = ['key' => 'inquiry_status', 'value' => sanitize_text_field($_POST['status'])];
        }

        if (current_user_can('manage_maneli_inquiries')) {
            if (!empty($_POST['expert'])) {
                $meta_query[] = ['key' => 'assigned_expert_id', 'value' => absint($_POST['expert'])];
            }
        } else { // It's an expert
            $user_id = get_current_user_id();
            $meta_query[] = [
                'relation' => 'OR',
                ['key' => 'assigned_expert_id', 'value' => $user_id],
                ['key' => 'created_by_expert_id', 'value' => $user_id]
            ];
        }
        
        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        $inquiry_query = new WP_Query($args);

        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                $this->render_inquiry_row(get_the_ID());
            }
        } else {
            $columns = current_user_can('manage_maneli_inquiries') ? 7 : 6;
            echo '<tr><td colspan="' . $columns . '" style="text-align:center;">هیچ استعلامی با این مشخصات یافت نشد.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $inquiry_query->max_num_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
            'type'  => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }
	
	public function handle_filter_cash_inquiries_ajax() {
        if ( !isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'maneli_cash_inquiry_filter_nonce') ) {
            wp_send_json_error(['message' => 'خطای امنیتی.']);
        }

        if (!is_user_logged_in() || !(current_user_can('maneli_expert') || current_user_can('manage_maneli_inquiries'))) {
            wp_send_json_error(['message' => 'شما دسترسی لازم را ندارید.']);
        }

        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $search_query = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        
        $args = [
            'post_type'      => 'cash_inquiry',
            'posts_per_page' => 20,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ];

        if (!empty($search_query)) {
            $product_ids = wc_get_products(['s' => $search_query, 'limit' => -1, 'return' => 'ids']);
            
            $meta_query = [
                'relation' => 'OR',
                ['key' => 'cash_first_name', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'cash_last_name', 'value' => $search_query, 'compare' => 'LIKE'],
                ['key' => 'mobile_number', 'value' => $search_query, 'compare' => 'LIKE'],
            ];

            if(!empty($product_ids)){
                $meta_query[] = ['key' => 'product_id', 'value' => $product_ids, 'compare' => 'IN'];
            }
            $args['meta_query'] = $meta_query;
        }

        $inquiry_query = new WP_Query($args);

        ob_start();
        if ($inquiry_query->have_posts()) {
            while ($inquiry_query->have_posts()) {
                $inquiry_query->the_post();
                $this->render_cash_inquiry_row(get_the_ID());
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center;">هیچ درخواستی یافت نشد.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $inquiry_query->max_num_pages,
            'prev_text' => '« قبلی',
            'next_text' => 'بعدی »',
            'type'  => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination_html' => $pagination_html]);
    }


    /**
     * Renders a single row in the inquiry list table.
     */
    private function render_inquiry_row($inquiry_id) {
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $status = get_post_meta($inquiry_id, 'inquiry_status', true);
        $report_page_url = home_url('/dashboard/?endp=inf_menu_4');
        $report_url = add_query_arg('inquiry_id', $inquiry_id, $report_page_url);
        $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
        list($y, $m, $d) = explode('-', $gregorian_date);
        $customer = get_userdata(get_post_field('post_author', $inquiry_id));
        ?>
        <tr>
            <td data-title="شناسه">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="مشتری"><?php echo esc_html($customer->display_name); ?></td>
            <?php if (current_user_can('manage_maneli_inquiries')): 
                $expert_name = get_post_meta($inquiry_id, 'assigned_expert_name', true);
            ?>
                <td data-title="کارشناس"><?php echo $expert_name ? esc_html($expert_name) : '—'; ?></td>
            <?php endif; ?>
            <td data-title="خودرو"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td data-title="وضعیت"><?php echo esc_html(Maneli_CPT_Handler::get_status_label($status)); ?></td>
            <td data-title="تاریخ"><?php echo esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')); ?></td>
            <td class="woocommerce-orders-table__cell-order-actions">
                <a href="<?php echo esc_url($report_url); ?>" class="button view">مشاهده جزئیات</a>
            </td>
        </tr>
        <?php
    }
	
	private function render_cash_inquiry_row($inquiry_id) {
        $product_id = get_post_meta($inquiry_id, 'product_id', true);
        $gregorian_date = get_the_date('Y-m-d', $inquiry_id);
        list($y, $m, $d) = explode('-', $gregorian_date);
        $customer_name = get_post_meta($inquiry_id, 'cash_first_name', true) . ' ' . get_post_meta($inquiry_id, 'cash_last_name', true);
        ?>
        <tr>
            <td data-title="شناسه">#<?php echo esc_html($inquiry_id); ?></td>
            <td data-title="مشتری"><?php echo esc_html($customer_name); ?></td>
            <td data-title="موبایل"><?php echo esc_html(get_post_meta($inquiry_id, 'mobile_number', true)); ?></td>
            <td data-title="خودرو"><?php echo esc_html(get_the_title($product_id)); ?></td>
            <td data-title="رنگ"><?php echo esc_html(get_post_meta($inquiry_id, 'cash_car_color', true)); ?></td>
            <td data-title="تاریخ"><?php echo esc_html(maneli_gregorian_to_jalali($y, $m, $d, 'Y/m/d')); ?></td>
        </tr>
        <?php
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
        
        $can_view_as_admin = current_user_can('manage_maneli_inquiries');
        $can_view_as_expert = in_array('maneli_expert', $current_user->roles) && $assigned_expert_id === $current_user->ID;
        $can_view_as_customer = $inquiry_author_id === $current_user->ID;

        if (!$can_view_as_admin && !$can_view_as_expert && !$can_view_as_customer) {
            return '<div class="maneli-inquiry-wrapper error-box"><p>شما اجازه مشاهده این گزارش را ندارید.</p></div>';
        }
        
        if ($can_view_as_customer && !$can_view_as_admin && !$can_view_as_expert) {
             return $this->render_customer_report_html($inquiry_id);
        }

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
        $product_id = $post_meta['product_id'][0] ?? 0;
        $status = $post_meta['inquiry_status'][0] ?? 'pending';

        $status_map = [
            'user_confirmed' => ['label' => 'تایید و ارجاع شده', 'class' => 'status-bg-approved'],
            'rejected'       => ['label' => 'رد شده', 'class' => 'status-bg-rejected'],
            'more_docs'      => ['label' => 'نیازمند مدارک', 'class' => 'status-bg-pending'],
            'pending'        => ['label' => 'در حال بررسی', 'class' => 'status-bg-pending'],
            'failed'         => ['label' => 'استعلام ناموفق', 'class' => 'status-bg-rejected'],
        ];
        $status_info = $status_map[$status] ?? ['label' => 'نامشخص', 'class' => ''];

        ob_start();
        ?>
        <div class="maneli-inquiry-wrapper frontend-expert-report">
            <h2 class="report-main-title">گزارش کامل اعتبار <small>(برای استعلام #<?php echo esc_html($inquiry_id); ?>)</small></h2>

            <div class="report-status-box <?php echo esc_attr($status_info['class']); ?>">
                <strong>وضعیت فعلی:</strong> <?php echo esc_html($status_info['label']); ?>
            </div>
            
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
                    $issuer_fields = ['issuer_first_name' => 'نام (صادر کننده)','issuer_last_name' => 'نام خانوادگی (صادر کننده)','issuer_national_code' => 'کد ملی (صادر کننده)','issuer_father_name'   => 'نام پدر (صادر کننده)','issuer_birth_date'    => 'تاریخ تولد (صادر کننده)','issuer_mobile_number' => 'شماره موبایل (صادر کننده)'];
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

                <?php 
                $finotex_skipped = (empty($finotex_data) || (isset($finotex_data['status']) && $finotex_data['status'] === 'SKIPPED'));
                if ($finotex_skipped): 
                ?>
                     <table class="summary-table right-aligned-table" style="margin-top: 20px;">
                        <tbody>
                             <tr>
                                <th>وضعیت اعتباری</th>
                                <td>نامشخص</td>
                            </tr>
                            <tr>
                                <th>توضیح</th>
                                <td>
                                    <?php if ($can_view_as_admin): ?>
                                        استعلام فینوتک در تنظیمات غیرفعال است.
                                    <?php else: ?>
                                        استعلام بانکی انجام نشده است.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php if ($can_view_as_admin): ?>
                        <div class="admin-notice" style="margin-top: 20px;">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                 <input type="hidden" name="action" value="maneli_admin_retry_finotex">
                                 <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                                 <?php wp_nonce_field('maneli_retry_finotex_nonce'); ?>
                                 <button type="submit" class="action-btn approve">انجام مجدد استعلام فینوتک</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
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
                <?php endif; ?>
            </div>

            <?php if ($can_view_as_admin): ?>
            <div class="admin-actions-box">
                <h3 class="report-box-title">تصمیم نهایی</h3>
                <p>پس از بررسی اطلاعات بالا، وضعیت نهایی این درخواست را مشخص کنید.</p>
                <form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="maneli_admin_update_status">
                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                    <input type="hidden" id="final-status-input" name="new_status" value="">
                    <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
                    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>

                    <div class="action-button-group">
                        <div class="approve-section">
                            <label for="assigned_expert_id_frontend">ارجاع به کارشناس:</label>
                             <?php
                                $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
                                if (!empty($experts)):
                            ?>
                            <select name="assigned_expert_id" id="assigned_expert_id_frontend">
                                <option value="auto">-- انتساب خودکار (گردشی) --</option>
                                <?php foreach ($experts as $expert) : ?>
                                    <option value="<?php echo esc_attr($expert->ID); ?>"><?php echo esc_html($expert->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                            <button type="button" id="approve-btn" class="action-btn approve">
                                &#10004; تایید و ارجاع
                            </button>
                        </div>
                        <button type="button" id="reject-btn" class="action-btn reject">
                            &#10006; رد نهایی درخواست
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <div class="report-back-button-wrapper">
                <a href="<?php echo esc_url(home_url('/dashboard/?endp=inf_menu_3')); ?>" class="loan-action-btn">بازگشت به لیست استعلام‌ها</a>
            </div>
        </div>
        
        <?php if ($can_view_as_admin): ?>
        <div id="rejection-modal" class="maneli-modal-frontend" style="display:none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span><h3>دلیل رد درخواست</h3>
                <p>لطفاً دلیل رد این درخواست را مشخص کنید. این دلیل به کاربر پیامک خواهد شد.</p>
                <div class="form-group"><label for="rejection-reason-select">انتخاب دلیل:</label><select id="rejection-reason-select" style="width: 100%;"><option value="">-- یک دلیل انتخاب کنید --</option><option value="متاسفانه در حال حاضر امکان خرید با این مبلغ پیش‌پرداخت وجود ندارد.">مبلغ پیش‌پرداخت کافی نیست.</option><option value="متاسفانه سابقه اعتباری شما برای خرید این خودرو مورد تایید قرار نگرفت.">سابقه اعتباری مورد تایید نیست.</option><option value="مدارک ارسالی شما ناقص یا نامعتبر است. لطفاً با پشتیبانی تماس بگیرید.">مدارک ناقص یا نامعتبر.</option><option value="custom">دلیل دیگر (در کادر زیر بنویسید)</option></select></div>
                <div class="form-group" id="custom-reason-wrapper" style="display:none;"><label for="rejection-reason-custom">متن سفارشی:</label><textarea id="rejection-reason-custom" rows="3" style="width: 100%;"></textarea></div>
                <button type="button" id="confirm-rejection-btn" class="button button-primary">ثبت دلیل و رد کردن درخواست</button>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminActionForm = document.getElementById('admin-action-form');
            if (!adminActionForm) return;

            const finalStatusInput = document.getElementById('final-status-input');
            const approveBtn = document.getElementById('approve-btn');
            
            if (approveBtn) {
                approveBtn.addEventListener('click', function() {
                    finalStatusInput.value = 'approved';
                    adminActionForm.submit();
                });
            }

            const modal = document.getElementById('rejection-modal');
            const rejectBtn = document.getElementById('reject-btn');
            const closeModalBtn = document.querySelector('.modal-close');
            const confirmRejectionBtn = document.getElementById('confirm-rejection-btn');
            const reasonSelect = document.getElementById('rejection-reason-select');
            const customReasonWrapper = document.getElementById('custom-reason-wrapper');
            const customReasonText = document.getElementById('rejection-reason-custom');
            const finalReasonInput = document.getElementById('rejection-reason-input');

            if (rejectBtn) {
                rejectBtn.addEventListener('click', function() {
                    modal.style.display = 'block';
                });
            }

            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            }
            
            window.addEventListener('click', function(e) {
                if (e.target == modal) {
                    modal.style.display = 'none';
                }
            });

            if (reasonSelect) {
                reasonSelect.addEventListener('change', function() {
                    if (this.value === 'custom') {
                        customReasonWrapper.style.display = 'block';
                    } else {
                        customReasonWrapper.style.display = 'none';
                    }
                });
            }

            if (confirmRejectionBtn) {
                confirmRejectionBtn.addEventListener('click', function() {
                    let reason = reasonSelect.value;
                    if (reason === 'custom') {
                        reason = customReasonText.value;
                    }

                    if (!reason) {
                        alert('لطفاً یک دلیل برای رد درخواست انتخاب یا وارد کنید.');
                        return;
                    }

                    finalReasonInput.value = reason;
                    finalStatusInput.value = 'rejected';
                    adminActionForm.submit();
                });
            }
        });
        </script>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
}