<?php
if (!defined('ABSPATH')) {
    exit;
}

class Maneli_User_Profile {

    public function __construct() {
        add_action('show_user_profile', [$this, 'add_custom_user_fields']);
        add_action('edit_user_profile', [$this, 'add_custom_user_fields']);
        add_action('personal_options_update', [$this, 'save_custom_user_fields']);
        add_action('edit_user_profile_update', [$this, 'save_custom_user_fields']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_datepicker_assets']);
    }

    public function enqueue_admin_datepicker_assets($hook) {
        if ($hook == 'profile.php' || $hook == 'user-edit.php') {
            // ۱. فایل CSS جدید تقویم را فراخوانی می‌کنیم
            wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
            
            // ۲. فایل JavaScript جدید و مستقل تقوim را فراخوانی می‌کنیم
            wp_enqueue_script(
                'maneli-jalali-datepicker',
                MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', // *** تغییر کرده ***
                [], // بدون وابستگی
                '2.0.0',
                true
            );
        }
    }

    public function add_custom_user_fields($user) {
        // اسکریپت راه‌اندازی تقویم جدید
        $init_script = "
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof kamadatepicker !== 'undefined') {
                    kamadatepicker('birth_date', {
                        bidi: true,
                        placeholder: 'مثال: ۱۳۶۵/۰۴/۱۵',
                        format: 'YYYY/MM/DD'
                    });
                }
            });
        ";
        wp_add_inline_script('maneli-jalali-datepicker', $init_script);
        ?>
        <h3>اطلاعات تکمیلی استعلام</h3>
        <table class="form-table">
            <tr>
                <th><label for="national_code">کد ملی</label></th>
                <td>
                    <input type="text" name="national_code" id="national_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'national_code', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="father_name">نام پدر</label></th>
                <td>
                    <input type="text" name="father_name" id="father_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'father_name', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="birth_date">تاریخ تولد</label></th>
                <td>
                    <input type="text" name="birth_date" id="birth_date" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="mobile_number">شماره موبایل</label></th>
                <td>
                    <input type="text" name="mobile_number" id="mobile_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_number', true)); ?>" class="regular-text" />
                    <p class="description">این شماره برای ارسال پیامک‌ها استفاده می‌شود.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_custom_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        if (isset($_POST['national_code'])) {
            update_user_meta($user_id, 'national_code', sanitize_text_field($_POST['national_code']));
        }
        if (isset($_POST['father_name'])) {
            update_user_meta($user_id, 'father_name', sanitize_text_field($_POST['father_name']));
        }
        if (isset($_POST['birth_date'])) {
            update_user_meta($user_id, 'birth_date', sanitize_text_field($_POST['birth_date']));
        }
        if (isset($_POST['mobile_number'])) {
            update_user_meta($user_id, 'mobile_number', sanitize_text_field($_POST['mobile_number']));
        }
    }
}