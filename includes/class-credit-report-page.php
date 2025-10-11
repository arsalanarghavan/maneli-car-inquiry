<?php
/**
 * Creates a dedicated admin page for viewing a detailed credit report for an inquiry.
 * NOTE: This functionality is largely superseded by the frontend report view available via the [maneli_inquiry_list] shortcode.
 * This class is maintained for legacy purposes or specific backend workflows.
 *
 * @package Maneli_Car_Inquiry/Includes
 * @author  Arsalan Arghavan (Refactored by Gemini)
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Credit_Report_Page {

    /**
     * The hook suffix for the custom admin page.
     * @var string
     */
    private $page_hook_suffix;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_report_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Adds the report page as a hidden submenu page under the 'inquiry' CPT.
     * It won't appear in the menu but will be accessible via a direct link.
     */
    public function add_report_page() {
        $this->page_hook_suffix = add_submenu_page(
            null, // No parent menu, making it hidden.
            esc_html__('Full Credit Report', 'maneli-car-inquiry'),
            esc_html__('Full Credit Report', 'maneli-car-inquiry'),
            'manage_maneli_inquiries', // Capability check
            'maneli-credit-report',
            [$this, 'render_report_page_html']
        );
    }
    
    /**
     * Enqueues CSS and JS assets specifically for the credit report admin page.
     *
     * @param string $hook The hook suffix for the current admin page.
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== $this->page_hook_suffix) {
            return;
        }

        $version = '1.0.0'; // Or use filemtime for cache busting
        wp_enqueue_style('maneli-admin-styles', MANELI_INQUIRY_PLUGIN_URL . 'assets/css/admin-styles.css', [], $version);
        
        wp_enqueue_script('maneli-admin-report-js', MANELI_INQUIRY_PLUGIN_URL . 'assets/js/admin/admin-report.js', ['jquery'], $version, true);
    }

    /**
     * Renders the HTML for the credit report page by fetching data and loading a template.
     */
    public function render_report_page_html() {
        if (!isset($_GET['inquiry_id'])) {
            wp_die(esc_html__('Inquiry ID is not specified.', 'maneli-car-inquiry'));
        }

        $inquiry_id = intval($_GET['inquiry_id']);
        $inquiry = get_post($inquiry_id);

        if (!$inquiry || $inquiry->post_type !== 'inquiry') {
            wp_die(esc_html__('The requested inquiry was not found.', 'maneli-car-inquiry'));
        }

        // Prepare all necessary data for the template
        $template_args = [
            'inquiry_id'   => $inquiry_id,
            'inquiry'      => $inquiry,
            'post_meta'    => get_post_meta($inquiry_id),
            'finotex_data' => get_post_meta($inquiry_id, '_finotex_response_data', true),
        ];
        
        // Load the template file to render the page content
        maneli_get_template_part('admin/credit-report-page', $template_args);
    }
}