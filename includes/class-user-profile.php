<?php
/**
 * Adds and manages custom fields on the user profile page in the WordPress admin area.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (Removed inline script for Datepicker initialization)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_User_Profile {

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
                'title' => esc_html__('Inquiry Supplementary Information', 'autopuzzle'),
                'fields' => [
                    'national_code' => ['label' => esc_html__('National Code', 'autopuzzle'), 'type' => 'text'],
                    'father_name'   => ['label' => esc_html__('Father\'s Name', 'autopuzzle'), 'type' => 'text'],
                    // Note: 'birth_date' uses the class 'autopuzzle-datepicker' which is targeted by the centralized JS file.
                    'birth_date'    => ['label' => esc_html__('Date of Birth', 'autopuzzle'), 'type' => 'text', 'class' => 'autopuzzle-datepicker'],
                    'mobile_number' => ['label' => esc_html__('Mobile Number', 'autopuzzle'), 'type' => 'tel', 'desc' => esc_html__('Used for SMS notifications and as the username.', 'autopuzzle')],
                    'phone_number'  => ['label' => esc_html__('Phone Number', 'autopuzzle'), 'type' => 'tel'],
                    'occupation'    => ['label' => esc_html__('Occupation', 'autopuzzle'), 'type' => 'text'],
                    'income_level'  => ['label' => esc_html__('Income Level (Toman)', 'autopuzzle'), 'type' => 'number'],
                    'address'       => ['label' => esc_html__('Address', 'autopuzzle'), 'type' => 'textarea'],
                    'residency_status' => [
                        'label'   => esc_html__('Residency Status', 'autopuzzle'),
                        'type'    => 'select',
                        'options' => [
                            ''       => esc_html__('-- Select --', 'autopuzzle'),
                            'owner'  => esc_html__('Owner', 'autopuzzle'),
                            'tenant' => esc_html__('Tenant', 'autopuzzle'),
                        ]
                    ],
                    'workplace_status' => [
                        'label'   => esc_html__('Workplace Status', 'autopuzzle'),
                        'type'    => 'select',
                        'options' => [
                            ''          => esc_html__('-- Select --', 'autopuzzle'),
                            'permanent' => esc_html__('Permanent', 'autopuzzle'),
                            'contract'  => esc_html__('Contract', 'autopuzzle'),
                            'freelance' => esc_html__('Freelance', 'autopuzzle'),
                        ]
                    ],
                ]
            ],
            'bank_info' => [
                'title' => esc_html__('Bank Information', 'autopuzzle'),
                'fields' => [
                    'bank_name'      => ['label' => esc_html__('Bank Name', 'autopuzzle'), 'type' => 'text'],
                    'account_number' => ['label' => esc_html__('Account Number', 'autopuzzle'), 'type' => 'text'],
                    'branch_code'    => ['label' => esc_html__('Branch Code', 'autopuzzle'), 'type' => 'text'],
                    'branch_name'    => ['label' => esc_html__('Branch Name', 'autopuzzle'), 'type' => 'text'],
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

        $datepicker_loaded = false;
        if (function_exists('autopuzzle_enqueue_persian_datepicker')) {
            $datepicker_loaded = autopuzzle_enqueue_persian_datepicker();
        }
        
        // FIX: Removed the wp_add_inline_script block.
        // The initialization logic is now assumed to be in the centralized JS file.
        $profile_deps = [];
        if ($datepicker_loaded) {
            $profile_deps[] = 'autopuzzle-persian-datepicker';
        }

        wp_enqueue_script(
            'autopuzzle-profile-datepicker-init',
            AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/inquiry-form.js',
            $profile_deps,
            filemtime(AUTOPUZZLE_PLUGIN_PATH . 'assets/js/frontend/inquiry-form.js'),
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