<?php
/**
 * Handles the registration of Custom Post Types (CPTs) and customization of their admin list table columns.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_CPT_Handler {

    public function __construct() {
        add_action('init', [$this, 'register_post_types']);

        // Hooks for customizing the 'inquiry' CPT list table
        add_filter('manage_inquiry_posts_columns', [$this, 'set_custom_inquiry_columns']);
        add_action('manage_inquiry_posts_custom_column', [$this, 'render_custom_inquiry_columns'], 10, 2);
        
        // Hooks for customizing the 'cash_inquiry' CPT list table
        add_filter('manage_cash_inquiry_posts_columns', [$this, 'set_custom_cash_inquiry_columns']);
        add_action('manage_cash_inquiry_posts_custom_column', [$this, 'render_custom_cash_inquiry_columns'], 10, 2);

        // Hooks for meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_inquiry', [$this, 'save_inquiry_meta_data']);
    }

    /**
     * Registers both 'inquiry' and 'cash_inquiry' post types.
     */
    public function register_post_types() {
        $this->register_inquiry_post_type();
        $this->register_cash_inquiry_post_type();
    }

    /**
     * Registers the 'inquiry' post type for installment requests.
     */
    private function register_inquiry_post_type() {
        $labels = [
            'name'               => esc_html__('Installment Inquiries', 'maneli-car-inquiry'),
            'singular_name'      => esc_html__('Installment Inquiry', 'maneli-car-inquiry'),
            'menu_name'          => esc_html__('Bank Inquiries', 'maneli-car-inquiry'),
            'name_admin_bar'     => esc_html__('Inquiry', 'maneli-car-inquiry'),
            'all_items'          => esc_html__('All Inquiries', 'maneli-car-inquiry'),
            'add_new_item'       => esc_html__('Add New Inquiry', 'maneli-car-inquiry'),
            'add_new'            => esc_html__('Add New', 'maneli-car-inquiry'),
            'new_item'           => esc_html__('New Inquiry', 'maneli-car-inquiry'),
            'edit_item'          => esc_html__('Edit Inquiry', 'maneli-car-inquiry'),
            'view_item'          => esc_html__('View Inquiry', 'maneli-car-inquiry'),
            'search_items'       => esc_html__('Search Inquiries', 'maneli-car-inquiry'),
            'not_found'          => esc_html__('No inquiries found.', 'maneli-car-inquiry'),
            'not_found_in_trash' => esc_html__('No inquiries found in Trash.', 'maneli-car-inquiry'),
        ];
        $args = [
            'labels'             => $labels,
            'supports'           => ['title', 'editor', 'author'],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'capability_type'    => 'post',
            'capabilities'       => [
                'create_posts' => 'do_not_allow', // Prevent manual creation from the admin UI
            ],
            'map_meta_cap'       => true,
            'menu_icon'          => 'dashicons-clipboard',
            'rewrite'            => false,
        ];
        register_post_type('inquiry', $args);
    }
    
    /**
     * Registers the 'cash_inquiry' post type for cash purchase requests.
     */
    private function register_cash_inquiry_post_type() {
        $labels = [
            'name'               => esc_html__('Cash Requests', 'maneli-car-inquiry'),
            'singular_name'      => esc_html__('Cash Request', 'maneli-car-inquiry'),
            'menu_name'          => esc_html__('Cash Requests', 'maneli-car-inquiry'),
            'all_items'          => esc_html__('All Cash Requests', 'maneli-car-inquiry'),
            'edit_item'          => esc_html__('Edit Request', 'maneli-car-inquiry'),
            'view_item'          => esc_html__('View Request', 'maneli-car-inquiry'),
            'search_items'       => esc_html__('Search Requests', 'maneli-car-inquiry'),
        ];
        $args = [
            'labels'             => $labels,
            'supports'           => ['title', 'author'],
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=inquiry', // Nested under the main CPT menu
            'capability_type'    => 'post',
            'capabilities'       => ['create_posts' => 'do_not_allow'],
            'map_meta_cap'       => true,
            'rewrite'            => false,
        ];
        register_post_type('cash_inquiry', $args);
    }

    /**
     * Sets the custom columns for the 'inquiry' post type list table.
     */
    public function set_custom_inquiry_columns($columns) {
        unset($columns['author'], $columns['date'], $columns['title']);
        return [
            'cb'              => $columns['cb'],
            'title'           => esc_html__('Inquiry Subject', 'maneli-car-inquiry'),
            'inquiry_user'    => esc_html__('Customer', 'maneli-car-inquiry'),
            'assigned_expert' => esc_html__('Assigned Expert', 'maneli-car-inquiry'),
            'inquiry_status'  => esc_html__('Status', 'maneli-car-inquiry'),
            'inquiry_date'    => esc_html__('Date', 'maneli-car-inquiry'),
            'actions'         => esc_html__('Actions', 'maneli-car-inquiry'),
        ];
    }

    /**
     * Renders the content for the custom columns in the 'inquiry' list table.
     */
    public function render_custom_inquiry_columns($column, $post_id) {
        switch ($column) {
            case 'inquiry_user':
                $user_id = get_post_field('post_author', $post_id);
                $user = get_userdata($user_id);
                echo $user ? esc_html($user->display_name) : esc_html__('User not found', 'maneli-car-inquiry');
                break;
            case 'assigned_expert':
                $expert_name = get_post_meta($post_id, 'assigned_expert_name', true);
                if (empty($expert_name)) {
                    $expert_id = get_post_meta($post_id, 'created_by_expert_id', true);
                    if ($expert_id) {
                        $expert_user = get_userdata($expert_id);
                        $expert_name = $expert_user ? $expert_user->display_name : esc_html__('Deleted Expert', 'maneli-car-inquiry');
                    }
                }
                echo $expert_name ? esc_html($expert_name) : '—';
                break;
            case 'inquiry_status':
                $status = get_post_meta($post_id, 'inquiry_status', true);
                echo '<span class="status-indicator status-' . esc_attr($status) . '">' . esc_html(self::get_status_label($status)) . '</span>';
                break;
            case 'inquiry_date':
                echo esc_html(get_the_date('Y/m/d', $post_id)); // WordPress handles date localization
                break;
            case 'actions':
                $report_url = home_url('/dashboard/?endp=inf_menu_4&inquiry_id=' . $post_id);
                printf('<a href="%s" class="button button-primary" target="_blank">%s</a>', esc_url($report_url), esc_html__('View Report', 'maneli-car-inquiry'));
                break;
        }
    }

    /**
     * Sets the custom columns for the 'cash_inquiry' post type list table.
     */
    public function set_custom_cash_inquiry_columns($columns) {
        unset($columns['author'], $columns['date'], $columns['title']);
        return [
            'cb'              => $columns['cb'],
            'title'           => esc_html__('Request Subject', 'maneli-car-inquiry'),
            'customer'        => esc_html__('Customer', 'maneli-car-inquiry'),
            'mobile'          => esc_html__('Mobile Number', 'maneli-car-inquiry'),
            'car'             => esc_html__('Car', 'maneli-car-inquiry'),
            'assigned_expert' => esc_html__('Assigned Expert', 'maneli-car-inquiry'),
            'status'          => esc_html__('Status', 'maneli-car-inquiry'),
            'date'            => esc_html__('Date', 'maneli-car-inquiry'),
        ];
    }

    /**
     * Renders the content for the custom columns in the 'cash_inquiry' list table.
     */
    public function render_custom_cash_inquiry_columns($column, $post_id) {
        switch ($column) {
            case 'customer':
                $first_name = get_post_meta($post_id, 'cash_first_name', true);
                $last_name = get_post_meta($post_id, 'cash_last_name', true);
                echo esc_html($first_name . ' ' . $last_name);
                break;
            case 'mobile':
                echo esc_html(get_post_meta($post_id, 'mobile_number', true));
                break;
            case 'car':
                $product_id = get_post_meta($post_id, 'product_id', true);
                echo $product_id ? esc_html(get_the_title($product_id)) : '—';
                break;
            case 'assigned_expert':
                $expert_name = get_post_meta($post_id, 'assigned_expert_name', true);
                echo $expert_name ? esc_html($expert_name) : '—';
                break;
            case 'status':
                $status_key = get_post_meta($post_id, 'cash_inquiry_status', true);
                echo esc_html(self::get_cash_inquiry_status_label($status_key));
                break;
        }
    }

    /**
     * Adds meta boxes to the 'inquiry' post type edit screen.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'inquiry_status_box',
            esc_html__('Inquiry Status Management', 'maneli-car-inquiry'),
            [$this, 'render_status_meta_box'],
            'inquiry',
            'side',
            'high'
        );
        add_meta_box(
            'inquiry_details_box',
            esc_html__('Full Inquiry Details', 'maneli-car-inquiry'),
            [$this, 'render_details_meta_box'],
            'inquiry',
            'normal',
            'high'
        );
    }

    /**
     * Renders the HTML for the inquiry details meta box.
     */
    public function render_details_meta_box($post) {
        // To keep this class clean, the complex HTML is moved to a template file.
        maneli_get_template_part('admin/meta-box-inquiry-details', ['post_id' => $post->ID]);
    }
    
    /**
     * Renders the HTML for the status management meta box.
     */
    public function render_status_meta_box($post) {
        wp_nonce_field('save_inquiry_status_nonce', 'inquiry_status_nonce');
        $current_status = get_post_meta($post->ID, 'inquiry_status', true);
        ?>
        <select name="inquiry_status" id="inquiry_status" style="width: 100%;">
            <?php foreach (self::get_all_statuses() as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($current_status, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('To send SMS notifications, please use the actions on the full report page.', 'maneli-car-inquiry'); ?>
        </p>
        <?php
    }

    /**
     * Saves the meta data from the status meta box.
     */
    public function save_inquiry_meta_data($post_id) {
        if (!isset($_POST['inquiry_status_nonce']) || !wp_verify_nonce($_POST['inquiry_status_nonce'], 'save_inquiry_status_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['inquiry_status'])) {
            $new_status = sanitize_text_field($_POST['inquiry_status']);
            update_post_meta($post_id, 'inquiry_status', $new_status);
        }
    }

    /**
     * Returns an array of all possible statuses for an installment inquiry.
     * @return array
     */
    public static function get_all_statuses() {
        return [
            'pending'        => esc_html__('Pending Review', 'maneli-car-inquiry'),
            'user_confirmed' => esc_html__('Approved and Referred', 'maneli-car-inquiry'),
            'more_docs'      => esc_html__('More Documents Required', 'maneli-car-inquiry'),
            'rejected'       => esc_html__('Rejected', 'maneli-car-inquiry'),
            'failed'         => esc_html__('Inquiry Failed', 'maneli-car-inquiry'),
        ];
    }
    
    /**
     * Gets the human-readable label for a given status key.
     * @param string $status_key The status key (e.g., 'user_confirmed').
     * @return string The status label.
     */
    public static function get_status_label($status_key) {
        $statuses = self::get_all_statuses();
        return $statuses[$status_key] ?? esc_html__('Unknown', 'maneli-car-inquiry');
    }

    /**
     * Returns an array of all possible statuses for a cash inquiry.
     * @return array
     */
    public static function get_all_cash_inquiry_statuses() {
        return [
            'pending'          => esc_html__('Follow-up in Progress', 'maneli-car-inquiry'),
            'approved'         => esc_html__('Referred', 'maneli-car-inquiry'),
            'rejected'         => esc_html__('Rejected', 'maneli-car-inquiry'),
            'awaiting_payment' => esc_html__('Awaiting Payment', 'maneli-car-inquiry'),
            'completed'        => esc_html__('Completed', 'maneli-car-inquiry'),
        ];
    }

    /**
     * Gets the human-readable label for a given cash inquiry status key.
     * @param string $status_key The status key.
     * @return string The status label.
     */
    public static function get_cash_inquiry_status_label($status_key) {
        $statuses = self::get_all_cash_inquiry_statuses();
        return $statuses[$status_key] ?? esc_html__('Unknown', 'maneli-car-inquiry');
    }
}