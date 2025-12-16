<?php
/**
 * Handles custom meta boxes and script/style enqueues for the product editor page.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.2 (Added Localization for product-editor.js AJAX error string)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Product_Editor_Page {

    public function __construct() {
        // Enqueue scripts and styles on the product editor screen
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Add custom meta box for car inquiry settings
        add_action('add_meta_boxes', [$this, 'add_product_meta_box']);

        // Save the custom fields data
        add_action('woocommerce_process_product_meta', [$this, 'save_product_meta_data']);
    }

    /**
     * Enqueues admin scripts and styles.
     * * @param string $hook The current admin page.
     */
    public function admin_enqueue_scripts($hook) {
        global $post;

        // Only load on the product edit screen
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            if ($post && $post->post_type !== 'product') {
                return;
            }
            
            // Register Select2 assets (assuming they are used for product attributes/fields)
            wp_enqueue_style('select2');
            wp_enqueue_script('select2');
            
            // Enqueue custom admin styles
            wp_enqueue_style(
                'autopuzzle-admin-styles',
                AUTOPUZZLE_INQUIRY_URL . 'assets/css/admin-styles.css',
                [],
                AUTOPUZZLE_INQUIRY_VERSION
            );

            // Enqueue the main admin product editor logic script
            wp_enqueue_script(
                'autopuzzle-admin-product-editor-js',
                AUTOPUZZLE_INQUIRY_URL . 'assets/js/admin/product-editor.js',
                ['jquery', 'select2'], 
                AUTOPUZZLE_INQUIRY_VERSION,
                true // Load in footer
            );

            // FIX: Localize the hardcoded error message for product-editor.js
            wp_localize_script(
                'autopuzzle-admin-product-editor-js', 
                'maneliAdminProductEditor', 
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('autopuzzle_product_editor_nonce'),
                    'text'     => [
                        // The hardcoded string "An AJAX error occurred." is now localizable
                        'ajax_error' => esc_html__('An AJAX error occurred.', 'autopuzzle'), 
                    ],
                ]
            );
        }
    }

    /**
     * Adds the custom meta box to the product post type.
     */
    public function add_product_meta_box() {
        add_meta_box(
            'autopuzzle_inquiry_meta_box',
            esc_html__('AutoPuzzle Car Inquiry Settings', 'autopuzzle'),
            [$this, 'render_product_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Renders the content of the custom meta box.
     */
    public function render_product_meta_box($post) {
        // Add a nonce field so we can check it later for security
        wp_nonce_field('autopuzzle_save_product_data', 'autopuzzle_product_meta_nonce');

        // Retrieve existing values
        $is_for_sale       = get_post_meta($post->ID, 'is_for_sale', true);
        $min_downpayment   = get_post_meta($post->ID, 'min_downpayment', true);
        $downpayment_type  = get_post_meta($post->ID, 'downpayment_type', true);
        $loan_max_term     = get_post_meta($post->ID, 'loan_max_term', true);
        $custom_attribute_group = get_post_meta($post->ID, 'custom_attribute_group', true);

        // Include the meta box template (if exists)
        // Note: Assumed existence of a template for the fields
        $template_path = AUTOPUZZLE_INQUIRY_DIR . 'templates/admin/meta-box-inquiry-details.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Basic output if template is missing
            echo '<p>' . esc_html__('Meta box template missing.', 'autopuzzle') . '</p>';
        }
        
        // --- Custom Fields Rendering (Example) ---
        // Input for Is For Sale
        ?>
        <p>
            <label for="autopuzzle_is_for_sale"><strong><?php esc_html_e('Available for Installment Inquiry:', 'autopuzzle'); ?></strong></label>
            <input type="checkbox" id="autopuzzle_is_for_sale" name="is_for_sale" value="yes" <?php checked($is_for_sale, 'yes'); ?> />
            <br><small><?php esc_html_e('Check this box if customers can submit installment inquiries for this product.', 'autopuzzle'); ?></small>
        </p>

        <p class="form-field autopuzzle-field-min-downpayment">
            <label for="autopuzzle_min_downpayment"><?php esc_html_e('Minimum Down Payment Value:', 'autopuzzle'); ?></label>
            <input type="text" id="autopuzzle_min_downpayment" name="min_downpayment" value="<?php echo esc_attr($min_downpayment); ?>" placeholder="0" />
            <br><small><?php esc_html_e('Enter the minimum down payment amount. This can be Toman (if product price is fixed) or a percentage (if price is variable).', 'autopuzzle'); ?></small>
        </p>
        
        <p class="form-field autopuzzle-field-downpayment-type">
            <label for="autopuzzle_downpayment_type"><?php esc_html_e('Down Payment Type:', 'autopuzzle'); ?></label>
            <select id="autopuzzle_downpayment_type" name="downpayment_type">
                <option value="fixed" <?php selected($downpayment_type, 'fixed'); ?>><?php esc_html_e('Fixed Amount (Toman)', 'autopuzzle'); ?></option>
                <option value="percent" <?php selected($downpayment_type, 'percent'); ?>><?php esc_html_e('Percentage of Price (%)', 'autopuzzle'); ?></option>
            </select>
        </p>
        
        <p class="form-field autopuzzle-field-loan-max-term">
            <label for="autopuzzle_loan_max_term"><?php esc_html_e('Maximum Loan Term (Months):', 'autopuzzle'); ?></label>
            <input type="number" id="autopuzzle_loan_max_term" name="loan_max_term" value="<?php echo esc_attr($loan_max_term); ?>" min="12" max="36" step="6" placeholder="36" />
        </p>
        <?php
    }

    /**
     * Saves the custom meta box data when the product is saved.
     *
     * @param int $post_id The ID of the post (product) being saved.
     */
    public function save_product_meta_data($post_id) {
        // Security checks
        if (!isset($_POST['autopuzzle_product_meta_nonce']) || !wp_verify_nonce(sanitize_key($_POST['autopuzzle_product_meta_nonce']), 'autopuzzle_save_product_data')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        // Save 'is_for_sale'
        $is_for_sale = isset($_POST['is_for_sale']) ? 'yes' : 'no';
        update_post_meta($post_id, 'is_for_sale', sanitize_text_field($is_for_sale));

        // Save 'min_downpayment'
        if (isset($_POST['min_downpayment'])) {
            $min_downpayment = sanitize_text_field(wp_unslash($_POST['min_downpayment']));
            update_post_meta($post_id, 'min_downpayment', $min_downpayment);
        }

        // Save 'downpayment_type'
        if (isset($_POST['downpayment_type'])) {
            $downpayment_type = sanitize_text_field(wp_unslash($_POST['downpayment_type']));
            update_post_meta($post_id, 'downpayment_type', $downpayment_type);
        }
        
        // Save 'loan_max_term'
        if (isset($_POST['loan_max_term'])) {
            $loan_max_term = sanitize_text_field(wp_unslash($_POST['loan_max_term']));
            update_post_meta($post_id, 'loan_max_term', $loan_max_term);
        }
    }
}