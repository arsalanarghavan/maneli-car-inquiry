<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Credit_Report_Page {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_report_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_report_page() {
        add_submenu_page(
            null,
            'گزارش کامل اعتبار',
            'گزارش کامل اعتبار',
            'manage_options',
            'maneli-credit-report',
            [$this, 'render_report_page']
        );
    }
    
    public function enqueue_admin_assets($hook) {
        $current_screen = get_current_screen();
        if ($current_screen && $current_screen->id === 'inquiry_page_maneli-credit-report') {
            wp_enqueue_script('maneli-admin-report-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/admin-report.js', ['jquery'], '6.0.0', true);
            wp_enqueue_style('maneli-admin-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/admin-styles.css', [], '6.0.0');
        }
    }

    public function render_report_page() {
        if (!isset($_GET['inquiry_id'])) wp_die('شناسه استعلام مشخص نشده است.');
        $inquiry_id = intval($_GET['inquiry_id']);
        $inquiry = get_post($inquiry_id);
        if (!$inquiry || $inquiry->post_type !== 'inquiry') wp_die('استعلام یافت نشد.');

        $post_meta = get_post_meta($inquiry_id);
        $finotex_data = get_post_meta($inquiry_id, '_finotex_response_data', true);
        $cheque_color_code = $finotex_data['result']['chequeColor'] ?? 0;
        $product_id = $post_meta['product_id'][0] ?? 0;
        ?>
        <div class="wrap maneli-report-wrap">
            <h1>گزارش کامل اعتبار <small>(برای استعلام #<?php echo esc_html($inquiry_id); ?>)</small></h1>
            
            <div class="report-box">
                <h2>خودروی درخواستی</h2>
                <div class="report-flex-container">
                    <div class="report-details-table">
                        <table class="form-table">
                            <tbody>
                                <tr><th scope="row">خودروی انتخابی</th><td><?php echo get_the_title($product_id); ?></td></tr>
                                <tr><th scope="row">قیمت کل</th><td><?php echo number_format_i18n((int)($post_meta['maneli_inquiry_total_price'][0] ?? 0)); ?> تومان</td></tr>
                                <tr><th scope="row">مقدار پیش پرداخت</th><td><?php echo number_format_i18n((int)($post_meta['maneli_inquiry_down_payment'][0] ?? 0)); ?> تومان</td></tr>
                                <tr><th scope="row">تعداد اقساط</th><td><?php echo esc_html($post_meta['maneli_inquiry_term_months'][0] ?? 0); ?> ماهه</td></tr>
                                <tr><th scope="row">مبلغ هر قسط</th><td><?php echo number_format_i18n((int)($post_meta['maneli_inquiry_installment'][0] ?? 0)); ?> تومان</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="report-image-container">
                        <?php
                        if ($product_id && has_post_thumbnail($product_id)) {
                            echo get_the_post_thumbnail($product_id, 'medium');
                        } else {
                            echo '<div class="no-image">تصویری برای این محصول ثبت نشده است.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="report-box">
                <h2>اطلاعات فردی</h2>
                <?php
                $buyer_fields = ['first_name' => 'نام (خریدار)','last_name' => 'نام خانوادگی (خریدار)','national_code' => 'کد ملی (خریدار)','father_name' => 'نام پدر (خریدار)','birth_date' => 'تاریخ تولد (خریدار)','mobile_number' => 'شماره موبایل (خریدار)'];
                $field_pairs = array_chunk($buyer_fields, 2, true);
                echo '<table class="form-table"><tbody>';
                foreach ($field_pairs as $pair) {
                    echo '<tr>'; $i = 0;
                    foreach($pair as $key => $label) {
                        $value = $post_meta[$key][0] ?? '';
                        echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th><td style="width: 35%;">' . esc_html($value) . '</td>'; $i++;
                    }
                    if ($i == 1) { echo '<th></th><td></td>'; } echo '</tr>';
                }
                echo '</tbody></table>';

                $issuer_type = $post_meta['issuer_type'][0] ?? 'self';
                if ($issuer_type === 'other') {
                    echo '<h3 style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;">اطلاعات صادر کننده چک</h3>';
                    $issuer_fields = ['issuer_first_name' => 'نام','issuer_last_name' => 'نام خانوادگی','issuer_national_code' => 'کد ملی','issuer_father_name'   => 'نام پدر','issuer_birth_date'    => 'تاریخ تولد','issuer_mobile_number' => 'شماره موبایل'];
                    $issuer_pairs = array_chunk($issuer_fields, 2, true);
                    echo '<table class="form-table"><tbody>';
                    foreach ($issuer_pairs as $pair) {
                         echo '<tr>'; $i = 0;
                         foreach($pair as $key => $label) {
                             $value = $post_meta[$key][0] ?? '';
                             echo '<th scope="row" style="width: 15%;"><label>' . esc_html($label) . '</label></th><td style="width: 35%;">' . esc_html($value) . '</td>'; $i++;
                         }
                         if ($i == 1) { echo '<th></th><td></td>'; } echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>

            <div class="report-box">
                <h2>نتیجه استعلام وضعیت چک (صیادی)</h2>
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
                        echo "<div class='bar-segment segment-{$color['class']} {$active_class}'><span>{$color['name']}</span></div>";
                    }
                    ?>
                </div>
                 <table class="widefat fixed" style="margin-top: 20px;">
                    <thead><tr><th style="width: 20%;">وضعیت اعتباری</th><th>توضیح</th></tr></thead>
                    <tbody>
                        <?php
                        $cheque_color_map = [
                            '1' => ['text' => 'سفید', 'desc' => 'فاقد هرگونه سابقه چک برگشتی.'],'2' => ['text' => 'زرد', 'desc' => 'یک فقره چک برگشتی یا حداکثر ۵۰ م ریال تعهد.'],'3' => ['text' => 'نارنجی', 'desc' => 'دو الی چهار فقره چک برگشتی یا حداکثر ۲۰۰ م ریال تعهد.'],'4' => ['text' => 'قهوه‌ای', 'desc' => 'پنج تا ده فقره چک برگشتی یا حداکثر ۵۰۰ م ریال تعهد.'],'5' => ['text' => 'قرمز', 'desc' => 'بیش از ده فقره چک برگشتی یا بیش از ۵۰۰ م ریال تعهد.'], 0  => ['text' => 'نامشخص', 'desc' => 'اطلاعاتی از فینوتک دریافت نشد یا استعلام ناموفق بود.']];
                        $color_info = $cheque_color_map[$cheque_color_code] ?? $cheque_color_map[0];
                        ?>
                        <tr><td><strong class="cheque-color-<?php echo esc_attr($cheque_color_code); ?>"><?php echo esc_html($color_info['text']); ?></strong></td><td><?php echo esc_html($color_info['desc']); ?></td></tr>
                    </tbody>
                </table>
            </div>
            
            <div class="report-box report-actions">
                <h2>تصمیم نهایی</h2>
                <p>پس از بررسی اطلاعات بالا، وضعیت نهایی این درخواست را مشخص کنید.</p>
                <form id="admin-action-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="maneli_admin_update_status">
                    <input type="hidden" name="inquiry_id" value="<?php echo esc_attr($inquiry_id); ?>">
                    <input type="hidden" id="final-status-input" name="new_status" value="">
                    <input type="hidden" id="rejection-reason-input" name="rejection_reason" value="">
                    <?php wp_nonce_field('maneli_admin_update_status_nonce'); ?>
                    <button type="button" id="approve-btn" class="button button-primary button-large">تایید و ارجاع به کارشناس</button>
                    <button type="button" id="reject-btn" class="button button-secondary button-large" style="margin-right: 10px;">رد نهایی درخواست</button>
                </form>
            </div>
        </div>
        
        <div id="rejection-modal" class="maneli-modal" style="display:none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span><h3>دلیل رد درخواست</h3>
                <p>لطفاً دلیل رد این درخواست را مشخص کنید. این دلیل به کاربر نمایش داده خواهد شد.</p>
                <div class="form-group"><label for="rejection-reason-select">انتخاب دلیل:</label><select id="rejection-reason-select" style="width: 100%;"><option value="">-- یک دلیل انتخاب کنید --</option><option value="متاسفانه در حال حاضر امکان خرید با این مبلغ پیش‌پرداخت وجود ندارد.">مبلغ پیش‌پرداخت کافی نیست.</option><option value="متاسفانه سابقه اعتباری شما برای خرید این خودرو مورد تایید قرار نگرفت.">سابقه اعتباری مورد تایید نیست.</option><option value="مدارک ارسالی شما ناقص یا نامعتبر است. لطفاً با پشتیبانی تماس بگیرید.">مدارک ناقص یا نامعتبر.</option><option value="custom">دلیل دیگر (در کادر زیر بنویسید)</option></select></div>
                <div class="form-group" id="custom-reason-wrapper" style="display:none;"><label for="rejection-reason-custom">متن سفارشی:</label><textarea id="rejection-reason-custom" rows="3" style="width: 100%;"></textarea></div>
                <button type="button" id="confirm-rejection-btn" class="button button-primary">ثبت دلیل و رد کردن درخواست</button>
            </div>
        </div>
        <?php
    }
}