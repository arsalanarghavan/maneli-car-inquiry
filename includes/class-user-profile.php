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
            wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
            wp_enqueue_script(
                'maneli-jalali-datepicker',
                MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js',
                [], '2.0.0', true
            );
        }
    }

    public function add_custom_user_fields($user) {
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
                <td><input type="text" name="national_code" id="national_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'national_code', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="father_name">نام پدر</label></th>
                <td><input type="text" name="father_name" id="father_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'father_name', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="birth_date">تاریخ تولد</label></th>
                <td><input type="text" name="birth_date" id="birth_date" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="mobile_number">شماره موبایل</label></th>
                <td><input type="text" name="mobile_number" id="mobile_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_number', true)); ?>" class="regular-text" /><p class="description">این شماره برای ارسال پیامک‌ها و به عنوان نام کاربری استفاده می‌شود.</p></td>
            </tr>
             <tr>
                <th><label for="phone_number">شماره تماس ثابت</label></th>
                <td><input type="text" name="phone_number" id="phone_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'phone_number', true)); ?>" class="regular-text" /></td>
            </tr>
             <tr>
                <th><label for="occupation">شغل</label></th>
                <td><input type="text" name="occupation" id="occupation" value="<?php echo esc_attr(get_user_meta($user->ID, 'occupation', true)); ?>" class="regular-text" /></td>
            </tr>
             <tr>
                <th><label for="income_level">میزان درآمد (تومان)</label></th>
                <td><input type="text" name="income_level" id="income_level" value="<?php echo esc_attr(get_user_meta($user->ID, 'income_level', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="address">آدرس</label></th>
                <td><textarea name="address" id="address" rows="3" class="regular-text"><?php echo esc_textarea(get_user_meta($user->ID, 'address', true)); ?></textarea></td>
            </tr>
             <tr>
                <th><label for="residency_status">وضعیت محل سکونت</label></th>
                <td>
                    <select name="residency_status" id="residency_status">
                        <option value="">-- انتخاب کنید --</option>
                        <option value="owner" <?php selected(get_user_meta($user->ID, 'residency_status', true), 'owner'); ?>>مالک</option>
                        <option value="tenant" <?php selected(get_user_meta($user->ID, 'residency_status', true), 'tenant'); ?>>مستاجر</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="workplace_status">وضعیت محل کار</label></th>
                <td>
                     <select name="workplace_status" id="workplace_status">
                        <option value="">-- انتخاب کنید --</option>
                        <option value="permanent" <?php selected(get_user_meta($user->ID, 'workplace_status', true), 'permanent'); ?>>رسمی</option>
                        <option value="contract" <?php selected(get_user_meta($user->ID, 'workplace_status', true), 'contract'); ?>>قراردادی</option>
                        <option value="freelance" <?php selected(get_user_meta($user->ID, 'workplace_status', true), 'freelance'); ?>>آزاد</option>
                    </select>
                </td>
            </tr>
             <tr><th colspan="2"><h3>اطلاعات بانکی</h3></th></tr>
             <tr>
                <th><label for="bank_name">نام بانک</label></th>
                <td><input type="text" name="bank_name" id="bank_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'bank_name', true)); ?>" class="regular-text" /></td>
            </tr>
             <tr>
                <th><label for="account_number">شماره حساب</label></th>
                <td><input type="text" name="account_number" id="account_number" value="<?php echo esc_attr(get_user_meta($user->ID, 'account_number', true)); ?>" class="regular-text" /></td>
            </tr>
             <tr>
                <th><label for="branch_code">کد شعبه</label></th>
                <td><input type="text" name="branch_code" id="branch_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'branch_code', true)); ?>" class="regular-text" /></td>
            </tr>
             <tr>
                <th><label for="branch_name">نام شعبه</label></th>
                <td><input type="text" name="branch_name" id="branch_name" value="<?php echo esc_attr(get_user_meta($user->ID, 'branch_name', true)); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }

    public function save_custom_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $fields_to_save = [
            'national_code', 'father_name', 'birth_date', 'mobile_number', 'phone_number',
            'occupation', 'income_level', 'address', 'residency_status', 'workplace_status',
            'bank_name', 'account_number', 'branch_code', 'branch_name'
        ];

        foreach($fields_to_save as $field) {
            if (isset($_POST[$field])) {
                if ($field === 'address') {
                    update_user_meta($user_id, $field, sanitize_textarea_field($_POST[$field]));
                } else {
                    update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }
}