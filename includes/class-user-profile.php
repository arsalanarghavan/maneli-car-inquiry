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
            // 1. Enqueue our new custom theme
            wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css');
            wp_enqueue_script('jquery-ui-datepicker');
            
            // 2. Enqueue our local Jalali converter script
            wp_enqueue_script(
                'maneli-jalali-datepicker',
                MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/jquery-ui-datepicker-fa.js',
                ['jquery-ui-datepicker'],
                '1.0.0',
                true
            );
        }
    }

    public function add_custom_user_fields($user) {
        // Create and add the initialization script
        $init_script = '
            jQuery(document).ready(function($) {
                 var toPersianDigits = function(str) {
                    var persianDigits = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
                    return String(str).replace(/[0-9]/g, function(w) {
                        return persianDigits[+w];
                    });
                };
                
                $("input.maneli-date-picker").datepicker({
                    isJalali: true, // <-- FIX WAS ADDED HERE
                    dateFormat: "yy/mm/dd",
                    changeMonth: true,
                    changeYear: true,
                     onSelect: function(dateText, inst) {
                        // This is to ensure the input value is also in Persian digits
                        $(this).val(toPersianDigits(dateText));
                     },
                     onChangeMonthYear: function(year, month, inst) {
                        setTimeout(function() {
                            var pYear = toPersianDigits(inst.selectedYear > 0 ? inst.selectedYear : year);
                            $(".ui-datepicker-year").val(pYear);
                            $(".ui-datepicker-year option").each(function() {
                                $(this).text(toPersianDigits($(this).text()));
                            });
                             $(".ui-datepicker-month option").each(function() {
                                $(this).text(toPersianDigits($(this).text()));
                            });
                        }, 0);
                    },
                     beforeShow: function(input, inst) {
                         setTimeout(function() {
                             var pYear = toPersianDigits(inst.selectedYear > 0 ? inst.selectedYear : $(input).val().split("/")[0]);
                             $(".ui-datepicker-year").val(pYear);
                             $(".ui-datepicker-year option").each(function() {
                                $(this).text(toPersianDigits($(this).text()));
                            });
                             $(".ui-datepicker-month option").each(function() {
                                $(this).text(toPersianDigits($(this).text()));
                            });
                         },0);
                     }
                });
            });
        ';
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
                    <input type="text" name="birth_date" id="birth_date" value="<?php echo esc_attr(get_user_meta($user->ID, 'birth_date', true)); ?>" class="regular-text maneli-date-picker" placeholder="مثال: ۱۳۶۵/۰۴/۱۵" autocomplete="off" />
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