<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Maneli_User_Profile Class
 * Adds custom identity fields to the user profile page in the admin area.
 */
class Maneli_User_Profile {

    private $user_fields = [
        'father_name'   => 'نام پدر',
        'birth_date'    => 'تاریخ تولد (مثال: 1365/04/15)',
        'mobile_number' => 'شماره موبایل',
        'national_code' => 'کد ملی',
    ];

    public function __construct() {
        add_action('show_user_profile', [$this, 'render_fields']);
        add_action('edit_user_profile', [$this, 'render_fields']);
        add_action('personal_options_update', [$this, 'save_fields']);
        add_action('edit_user_profile_update', [$this, 'save_fields']);
    }

    public function render_fields($user) {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        ?>
        <h2>اطلاعات تکمیلی استعلام</h2>
        <table class="form-table">
            <?php foreach ($this->user_fields as $key => $label) : ?>
                <tr>
                    <th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr(get_user_meta($user->ID, $key, true)); ?>" class="regular-text" />
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function save_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        foreach ($this->user_fields as $key => $label) {
            if (isset($_POST[$key])) {
                update_user_meta($user_id, $key, sanitize_text_field($_POST[$key]));
            }
        }
    }
}