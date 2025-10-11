<?php
/**
 * Creates and manages the plugin's settings page in the WordPress admin area.
 * This class defines all settings tabs, sections, and fields.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Settings_Page {

    /**
     * The name of the option in the wp_options table where all settings are stored.
     * @var string
     */
    private $options_name = 'maneli_inquiry_all_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_plugin_admin_menu']);
        add_action('admin_init', [$this, 'register_and_build_fields']);
        add_action('admin_post_maneli_save_frontend_settings', [$this, 'handle_frontend_settings_save']);
    }

    /**
     * Adds the settings page to the WordPress admin menu under the "Settings" section.
     */
    public function add_plugin_admin_menu() {
        add_options_page(
            esc_html__('Car Inquiry Settings', 'maneli-car-inquiry'),
            esc_html__('Car Inquiry Settings', 'maneli-car-inquiry'),
            'manage_options',
            'maneli-inquiry-settings',
            [$this, 'render_settings_page_html']
        );
    }

    /**
     * Renders the main HTML structure for the backend settings page.
     */
    public function render_settings_page_html() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'gateways';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors(); ?>
            
            <nav class="nav-tab-wrapper">
                <?php
                $all_settings = $this->get_all_settings_fields();
                foreach ($all_settings as $tab_key => $tab_data) {
                    $class = ($active_tab === $tab_key) ? 'nav-tab-active' : '';
                    $url = '?page=maneli-inquiry-settings&tab=' . esc_attr($tab_key);
                    echo '<a href="' . esc_url($url) . '" class="nav-tab ' . esc_attr($class) . '">' . esc_html($tab_data['title']) . '</a>';
                }
                ?>
            </nav>

            <form method="post" action="options.php">
                <?php
                settings_fields('maneli_inquiry_settings_group');
                do_settings_sections('maneli-' . $active_tab . '-settings-section');
                submit_button(esc_html__('Save Settings', 'maneli-car-inquiry'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registers the main setting group and dynamically builds sections and fields for each tab.
     */
    public function register_and_build_fields() {
        register_setting(
            'maneli_inquiry_settings_group',
            $this->options_name,
            [$this, 'sanitize_and_merge_options']
        );

        foreach ($this->get_all_settings_fields() as $tab_key => $tab_data) {
            if (empty($tab_data['sections'])) continue;

            foreach ($tab_data['sections'] as $section_id => $section) {
                add_settings_section(
                    $section_id,
                    $section['title'],
                    function() use ($section) {
                        if (!empty($section['desc'])) {
                            echo '<p>' . wp_kses_post($section['desc']) . '</p>';
                        }
                    },
                    'maneli-' . $tab_key . '-settings-section'
                );

                if (empty($section['fields'])) continue;

                foreach ($section['fields'] as $field) {
                    add_settings_field(
                        $field['name'],
                        $field['label'],
                        [$this, 'render_field_html'],
                        'maneli-' . $tab_key . '-settings-section',
                        $section_id,
                        $field
                    );
                }
            }
        }
    }

    /**
     * Renders the HTML for a given settings field based on its type.
     */
    public function render_field_html($args) {
        $options = get_option($this->options_name, []);
        $name = $args['name'];
        $type = $args['type'] ?? 'text';
        $value = $options[$name] ?? ($args['default'] ?? '');
        $field_name = "{$this->options_name}[{$name}]";
        
        switch ($type) {
            case 'textarea':
                echo "<textarea name='{$field_name}' rows='5' class='large-text'>" . esc_textarea($value) . "</textarea>";
                break;
            case 'switch':
                echo '<label class="maneli-switch">';
                echo "<input type='checkbox' name='{$field_name}' value='1' " . checked('1', $value, false) . '>';
                echo '<span class="maneli-slider round"></span></label>';
                break;
            default: // Catches text, number, password, etc.
                echo "<input type='" . esc_attr($type) . "' name='{$field_name}' value='" . esc_attr($value) . "' class='regular-text' dir='ltr'>";
                break;
        }

        if (!empty($args['desc'])) {
            echo "<p class='description'>" . wp_kses_post($args['desc']) . "</p>";
        }
    }

    /**
     * Sanitizes and merges new options with old options, handling unchecked checkboxes.
     */
    public function sanitize_and_merge_options($input) {
        $old_options = get_option($this->options_name, []);
        $sanitized_input = [];
        $all_fields = $this->get_all_settings_fields();
        
        foreach ($all_fields as $tab) {
            if(empty($tab['sections'])) continue;
            foreach ($tab['sections'] as $section) {
                if (empty($section['fields'])) continue;
                foreach ($section['fields'] as $field) {
                    $key = $field['name'];
                    if (isset($input[$key])) {
                        $sanitized_input[$key] = ($field['type'] === 'textarea') ? sanitize_textarea_field($input[$key]) : sanitize_text_field($input[$key]);
                    }
                    if ($field['type'] === 'switch' && !isset($input[$key])) {
                        $sanitized_input[$key] = '0';
                    }
                }
            }
        }
        
        return array_merge($old_options, $sanitized_input);
    }
    
    /**
     * Handles saving settings from the frontend settings shortcode.
     */
    public function handle_frontend_settings_save() {
        check_admin_referer('maneli_save_frontend_settings_nonce');
        if (!current_user_can('manage_maneli_inquiries')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'maneli-car-inquiry'));
        }

        $options = isset($_POST[$this->options_name]) ? (array) $_POST[$this->options_name] : [];
        $sanitized_options = $this->sanitize_and_merge_options($options);
        update_option($this->options_name, $sanitized_options);
        
        $redirect_url = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url();
        wp_redirect(add_query_arg('settings-updated', 'true', $redirect_url));
        exit;
    }

    /**
     * Public method to get the settings fields structure for use in other classes.
     */
    public function get_all_settings_public() {
        return $this->get_all_settings_fields();
    }
    
    /**
     * Defines the entire structure of the plugin's settings, organized by tabs and sections.
     */
    private function get_all_settings_fields() {
        return [
            'gateways' => [
                'title' => esc_html__('Payment Gateway', 'maneli-car-inquiry'),
                'icon' => 'fas fa-money-check-alt',
                'sections' => [
                    'maneli_payment_general_section' => [
                        'title' => esc_html__('General Payment Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'payment_enabled', 'label' => esc_html__('Enable Payment Gateway', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('If enabled, the inquiry fee payment step will be shown to users.', 'maneli-car-inquiry')],
                            ['name' => 'inquiry_fee', 'label' => esc_html__('Inquiry Fee (Toman)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Enter 0 for free inquiries.', 'maneli-car-inquiry')],
                            ['name' => 'zero_fee_message', 'label' => esc_html__('Message for Free Inquiry', 'maneli-car-inquiry'), 'type' => 'textarea', 'default' => esc_html__('The inquiry fee is waived for you. Please click the button below to continue.', 'maneli-car-inquiry')],
                        ]
                    ],
                     'maneli_discount_section' => [
                        'title' => esc_html__('Discount Code Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'discount_code', 'label' => esc_html__('Discount Code', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Enter a code for 100% off the inquiry fee.', 'maneli-car-inquiry')],
                            ['name' => 'discount_code_text', 'label' => esc_html__('Discount Code Success Message', 'maneli-car-inquiry'), 'type' => 'text', 'default' => esc_html__('100% discount applied successfully.', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'sms' => [
                'title' => esc_html__('SMS', 'maneli-car-inquiry'),
                'icon' => 'fas fa-mobile-alt',
                'sections' => [
                    'maneli_sms_api_section' => [
                        'title' => esc_html__('MeliPayamak Panel Information', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'sms_username', 'label' => esc_html__('Username', 'maneli-car-inquiry'), 'type' => 'text'],
                            ['name' => 'sms_password', 'label' => esc_html__('Password', 'maneli-car-inquiry'), 'type' => 'password'],
                        ]
                    ],
                    'maneli_sms_patterns_section' => [
                        'title' => esc_html__('SMS Pattern Codes (Body ID) - General & Installment', 'maneli-car-inquiry'),
                        'desc' => esc_html__('Enter only the approved Pattern Code (Body ID) from your SMS panel. Variable order must match the descriptions.', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'admin_notification_mobile', 'label' => esc_html__('Admin Mobile Number', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('Mobile number to receive new request notifications.', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_new_inquiry', 'label' => esc_html__('Pattern: "New Request for Admin"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_pending', 'label' => esc_html__('Pattern: "Pending Review" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_approved', 'label' => esc_html__('Pattern: "Approved" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_rejected', 'label' => esc_html__('Pattern: "Rejected" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'maneli-car-inquiry')],
                            ['name' => 'sms_pattern_expert_referral', 'label' => esc_html__('Pattern: "Referral to Expert" (Installment)', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'maneli-car-inquiry')],
                        ]
                    ],
                    'maneli_cash_inquiry_sms_section' => [
                        'title' => esc_html__('SMS Pattern Codes - Cash Request', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'cash_inquiry_approved_pattern', 'label' => esc_html__('Pattern: "Cash Request Approved"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Sent to customer after down payment is set. Variables: 1. Customer Name 2. Car Name 3. Down Payment Amount', 'maneli-car-inquiry')],
                            ['name' => 'cash_inquiry_rejected_pattern', 'label' => esc_html__('Pattern: "Cash Request Rejected"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Sent to customer after rejection. Variables: 1. Customer Name 2. Car Name 3. Rejection Reason', 'maneli-car-inquiry')],
                            ['name' => 'cash_inquiry_expert_referral_pattern', 'label' => esc_html__('Pattern: "Cash Request Referral to Expert"', 'maneli-car-inquiry'), 'type' => 'number', 'desc' => esc_html__('Sent to expert. Variables: 1. Expert Name 2. Customer Name 3. Customer Mobile 4. Car Name', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'cash_inquiry' => [
                'title' => esc_html__('Cash Request', 'maneli-car-inquiry'),
                'icon' => 'fas fa-money-bill-wave',
                'sections' => [
                    'maneli_cash_inquiry_reasons_section' => [
                        'title' => esc_html__('Cash Request Process Settings', 'maneli-car-inquiry'),
                        'fields' => [
                             ['name' => 'cash_inquiry_rejection_reasons', 'label' => esc_html__('Predefined Rejection Reasons', 'maneli-car-inquiry'), 'type' => 'textarea', 'desc' => esc_html__('Enter one reason per line. These will be shown as a list to the admin when rejecting a request.', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ],
            'experts' => [
                'title' => esc_html__('Experts', 'maneli-car-inquiry'),
                'icon' => 'fas fa-users',
                'sections' => [
                    'maneli_experts_list_section' => [
                        'title' => esc_html__('Expert Management', 'maneli-car-inquiry'),
                        'desc' => wp_kses_post(__('The system automatically identifies all users with the <strong>"Maneli Expert"</strong> role. Requests are assigned to them in a round-robin fashion.<br>To add a new expert, simply create a new user from the <strong>Users > Add New</strong> menu with the "Maneli Expert" role and enter their mobile number in their profile.', 'maneli-car-inquiry')),
                        'fields' => [] // This section is for display only
                    ]
                ]
            ],
            'finotex' => [
                'title' => esc_html__('Finotex', 'maneli-car-inquiry'),
                'icon' => 'fas fa-university',
                'sections' => [
                    'maneli_finotex_cheque_section' => [
                        'title' => esc_html__('Cheque Color Inquiry Service', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'finotex_enabled', 'label' => esc_html__('Enable Finotex Inquiry', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('If enabled, a bank inquiry will be performed via Finotex upon submission.', 'maneli-car-inquiry')],
                            ['name' => 'finotex_client_id', 'label' => esc_html__('Client ID', 'maneli-car-inquiry'), 'type' => 'text'],
                            ['name' => 'finotex_api_key', 'label' => esc_html__('Access Token', 'maneli-car-inquiry'), 'type' => 'textarea'],
                        ]
                    ]
                ]
            ],
            'display' => [
                'title' => esc_html__('Display Settings', 'maneli-car-inquiry'),
                'icon' => 'fas fa-paint-brush',
                'sections' => [
                    'maneli_display_main_section' => [
                        'title' => esc_html__('General Display Settings', 'maneli-car-inquiry'),
                        'fields' => [
                            ['name' => 'hide_prices_for_customers', 'label' => esc_html__('Hide Prices for Customers', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('If enabled, prices will be hidden from non-admin users across the store.', 'maneli-car-inquiry')],
                            ['name' => 'enable_grouped_attributes', 'label' => esc_html__('Enable Grouped Attributes Display', 'maneli-car-inquiry'), 'type' => 'switch', 'desc' => esc_html__('If enabled, the attributes table will be grouped by category (e.g., Technical, Dimensions).', 'maneli-car-inquiry')],
                            ['name' => 'unavailable_product_message', 'label' => esc_html__('Unavailable Product Message', 'maneli-car-inquiry'), 'type' => 'text', 'desc' => esc_html__('This message is shown on the calculator for "Unavailable" products.', 'maneli-car-inquiry'), 'default' => esc_html__('This car is currently unavailable for purchase.', 'maneli-car-inquiry')],
                        ]
                    ]
                ]
            ]
        ];
    }
}