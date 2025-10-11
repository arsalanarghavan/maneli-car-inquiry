<?php
/**
 * Adds and manages custom fields on the user profile page in the WordPress admin area.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (Removed inline script for Datepicker initialization)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_User_Profile {

    /**
     * An array of custom fields to be added to the user profile.
     * @var array
     */
    private $custom_fields;

    public function __construct() {
        $this->define_custom_fields();
        
        // Hooks to add and save the custom fields
        add_action('show_user_profile', [$this, 'render_custom_user_fields']);
        add_action('edit_user_profile', [$this, 'render_custom_user_fields']);
        add_action('personal_options_update', [$this, 'save_custom_user_fields']);
        add_action('edit_user_profile_update', [$this, 'save_custom_user_fields']);

        // Hook to enqueue scripts for the profile page
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Defines the structure of all custom fields to be added.
     * This makes adding/removing fields easier in the future.
     */
    private function define_custom_fields() {
        $this->custom_fields = [
            'inquiry_info' => [
                'title' => esc_html__('Inquiry Supplementary Information', 'maneli-car-inquiry'),
                'fields' => [
                    'national_code' => ['label' => esc_html__('National Code', 'maneli-car-inquiry'), 'type' => 'text'],
                    'father_name'   => ['label' => esc_html__('Father\'s Name', 'maneli-car-inquiry'), 'type' => 'text'],
                    // Note: 'birth_date' uses the class 'maneli-datepicker' which is targeted by the centralized JS file.
                    'birth_date'    => ['label' => esc_html__('Date of Birth', 'maneli-car-inquiry'), 'type' => 'text', 'class' => 'maneli-datepicker'],
                    'mobile_number' => ['label' => esc_html__('Mobile Number', 'maneli-car-inquiry'), 'type' => 'tel', 'desc' => esc_html__('Used for SMS notifications and as the username.', 'maneli-car-inquiry')],
                    'phone_number'  => ['label' => esc_html__('Phone Number', 'maneli-car-inquiry'), 'type' => 'tel'],
                    'occupation'    => ['label' => esc_html__('Occupation', 'maneli-car-inquiry'), 'type' => 'text'],
                    'income_level'  => ['label' => esc_html__('Income Level (Toman)', 'maneli-car-inquiry'), 'type' => 'number'],
                    'address'       => ['label' => esc_html__('Address', 'maneli-car-inquiry'), 'type' => 'textarea'],
                    'residency_status' => [
                        'label'   => esc_html__('Residency Status', 'maneli-car-inquiry'),
                        'type'    => 'select',
                        'options' => [
                            ''       => esc_html__('-- Select --', 'maneli-car-inquiry'),
                            'owner'  => esc_html__('Owner', 'maneli-car-inquiry'),
                            'tenant' => esc_html__('Tenant', 'maneli-car-inquiry'),
                        ]
                    ],
                    'workplace_status' => [
                        'label'   => esc_html__('Workplace Status', 'maneli-car-inquiry'),
                        'type'    => 'select',
                        'options' => [
                            ''          => esc_html__('-- Select --', 'maneli-car-inquiry'),
                            'permanent' => esc_html__('Permanent', 'maneli-car-inquiry'),
                            'contract'  => esc_html__('Contract', 'maneli-car-inquiry'),
                            'freelance' => esc_html__('Freelance', 'maneli-car-inquiry'),
                        ]
                    ],
                ]
            ],
            'bank_info' => [
                'title' => esc_html__('Bank Information', 'maneli-car-inquiry'),
                'fields' => [
                    'bank_name'      => ['label' => esc_html__('Bank Name', 'maneli-car-inquiry'), 'type' => 'text'],
                    'account_number' => ['label' => esc_html__('Account Number', 'maneli-car-inquiry'), 'type' => 'text'],
                    'branch_code'    => ['label' => esc_html__('Branch Code', 'maneli-car-inquiry'), 'type' => 'text'],
                    'branch_name'    => ['label' => esc_html__('Branch Name', 'maneli-car-inquiry'), 'type' => 'text'],
                ]
            ]
        ];
    }


    /**
     * Enqueues datepicker assets specifically on user profile pages.
     * @param string $hook The hook suffix for the current admin page.
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'profile.php' && $hook !== 'user-edit.php') {
            return;
        }

        wp_enqueue_style('maneli-datepicker-theme', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/maneli-datepicker-theme.css', [], '1.0.0');
        wp_enqueue_script('maneli-jalali-datepicker', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/vendor/kamadatepicker.min.js', [], '2.1.0', true);
        
        // FIX: Removed the wp_add_inline_script block.
        // The initialization logic is now assumed to be in the centralized JS file.
        wp_enqueue_script(
            'maneli-profile-datepicker-init',
            MANELI_INQUIRY_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js',
            ['maneli-jalali-datepicker'],
            '1.0.0',
            true
        );
    }

    /**
     * Renders the custom fields on the user profile page.
     *
     * @param WP_User $user The user object.
     */
    public function render_custom_user_fields($user) {
        foreach ($this->custom_fields as $section_id => $section_data) {
            ?>
            <h3><?php echo esc_html($section_data['title']); ?></h3>
            <table class="form-table">
                <?php foreach ($section_data['fields'] as $field_key => $field_args) : ?>
                    <tr>
                        <th><label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_args['label']); ?></label></th>
                        <td>
                            <?php
                            $value = get_user_meta($user->ID, $field_key, true);
                            $this->render_field_html($field_key, $field_args, $value);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php
        }
    }

    /**
     * Generates the HTML for a single field based on its type.
     *
     * @param string $key   The meta key for the field.
     * @param array  $args  The field's arguments (type, options, etc.).
     * @param mixed  $value The current value of the field.
     */
    private function render_field_html($key, $args, $value) {
        $type = $args['type'] ?? 'text';
        $class = 'regular-text ' . ($args['class'] ?? '');

        switch ($type) {
            case 'textarea':
                echo '<textarea name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" rows="3" class="' . esc_attr($class) . '">' . esc_textarea($value) . '</textarea>';
                break;
            case 'select':
                echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($key) . '">';
                foreach ($args['options'] as $option_value => $option_label) {
                    echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                }
                echo '</select>';
                break;
            default:
                echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="' . esc_attr($class) . '" />';
                break;
        }

        if (!empty($args['desc'])) {
            echo '<p class="description">' . wp_kses_post($args['desc']) . '</p>';
        }
    }

    /**
     * Saves the custom user fields when the profile is updated.
     *
     * @param int $user_id The ID of the user being updated.
     */
    public function save_custom_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        foreach ($this->custom_fields as $section) {
            foreach ($section['fields'] as $field_key => $field_args) {
                if (isset($_POST[$field_key])) {
                    $value = $_POST[$field_key];
                    $sanitized_value = ($field_args['type'] === 'textarea')
                        ? sanitize_textarea_field($value)
                        : sanitize_text_field($value);
                    
                    update_user_meta($user_id, $field_key, $sanitized_value);
                }
            }
        }
    }
}