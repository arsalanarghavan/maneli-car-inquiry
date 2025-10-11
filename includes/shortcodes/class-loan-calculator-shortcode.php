<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Loan_Calculator_Shortcode {

    public function __construct() {
        add_shortcode('loan_calculator', [$this, 'render_loan_calculator']);
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
                        
                        <?php if (is_user_logged_in()): 
                            $current_user = wp_get_current_user();
                            $first_name = $current_user->first_name;
                            $last_name = $current_user->last_name;
                            $mobile = get_user_meta($current_user->ID, 'mobile_number', true);
                        ?>
                        
                        <div class="loan-section">
                            <div class="form-grid">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>نام:</label>
                                        <input type="text" name="cash_first_name" value="<?php echo esc_attr($first_name); ?>" readonly required>
                                    </div>
                                    <div class="form-group">
                                        <label>نام خانوادگی:</label>
                                        <input type="text" name="cash_last_name" value="<?php echo esc_attr($last_name); ?>" readonly required>
                                    </div>
                                </div>
                                <div class="form-row">
                                     <div class="form-group">
                                        <label>شماره موبایل:</label>
                                        <input type="text" name="cash_mobile_number" value="<?php echo esc_attr($mobile); ?>" readonly required>
                                    </div>
                                    <div class="form-group">
                                        <label>رنگ خودرو:</label>
                                        <?php if (!empty($car_colors)): ?>
                                            <select name="cash_car_color" required>
                                                 <option value="">-- انتخاب کنید --</option>
                                                <?php foreach ($car_colors as $color): ?>
                                                    <option value="<?php echo esc_attr($color); ?>"><?php echo esc_html($color); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <input type="text" name="cash_car_color" placeholder="رنگ مورد نظر را وارد کنید" required>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: center; font-size: 14px; color: #777; padding: 10px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; margin-bottom: 20px;">
                            قیمت درج‌شده حدودی است و به دلیل نوسانات بازار، قیمت قطعی پس از واریز پیش‌پرداخت مشخص و نهایی می‌شود.
                        </div>
                        <div class="loan-section result-section">
                            <strong>قیمت تقریبی:</strong>
                            <span id="cashPriceAmount"><?php echo number_format_i18n($cash_price); ?></span>
                            <span> تومان</span>
                        </div>
                        <div class="loan-section loan-action-wrapper">
                            <button type="submit" class="loan-action-btn">ثبت درخواست استعلام قیمت</button>
                        </div>
                        <?php else: // User is not logged in ?>
                            <div class="loan-section" style="text-align: center;">
                                <p>برای ثبت درخواست خرید نقدی، لطفاً ابتدا وارد حساب کاربری خود شوید.</p>
                                <a href="<?php echo esc_url(home_url('/login/?redirect_to=' . urlencode(get_permalink()))); ?>" class="loan-action-btn">ورود و ثبت درخواست</a>
                            </div>
                        <?php endif; ?>
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
}