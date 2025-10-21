<!-- Start::row -->
<?php
/**
 * New Installment Inquiry Page (Customer)
 * Customer multi-step installment inquiry form
 * Permission: Only customers
 * Uses the existing 5-step inquiry form from shortcode
 */

// Permission check - Only customers can create new inquiries
// Admins and experts should create inquiries directly from inquiry list
$is_admin = current_user_can('manage_maneli_inquiries');
$is_expert = in_array('maneli_expert', wp_get_current_user()->roles, true);

if ($is_admin || $is_expert) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-info alert-dismissible fade show">
                <i class="la la-info-circle me-2"></i>
                برای ثبت استعلام اقساطی، لطفاً از صفحه <a href="<?php echo home_url('/dashboard/new-installment-inquiry'); ?>" class="alert-link">ثبت استعلام اقساطی جدید</a> اقدام نمایید.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Include the multi-step inquiry form shortcode
// This uses the existing [car_inquiry_form] logic which has 5 steps:
// 1. Car Selection (Calculator)
// 2. Identity Form
// 3. Confirm Car
// 4. Payment
// 5. Final Report
if (class_exists('Maneli_Inquiry_Form_Shortcode')) {
    $inquiry_form = new Maneli_Inquiry_Form_Shortcode();
    echo $inquiry_form->render_shortcode();
} else {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger">
                <i class="la la-exclamation-triangle me-2"></i>
                خطا در بارگذاری فرم استعلام. لطفاً با پشتیبانی تماس بگیرید.
            </div>
        </div>
    </div>
    <?php
}
?>
<!-- End::row -->
