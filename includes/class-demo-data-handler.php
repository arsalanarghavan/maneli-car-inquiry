<?php
/**
 * Demo Data AJAX Handler
 * 
 * Handles AJAX requests for importing and deleting demo data
 * 
 * @package ManaliCarInquiry
 * @subpackage Includes
 */

class Maneli_Demo_Data_Handler {

    public function __construct() {
        add_action( 'wp_ajax_maneli_import_demo_data', [ $this, 'handle_import_demo_data' ] );
        add_action( 'wp_ajax_maneli_delete_demo_data', [ $this, 'handle_delete_demo_data' ] );
    }

    /**
     * Handle import demo data AJAX request
     */
    public function handle_import_demo_data() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'maneli_import_demo_data' ) ) {
            wp_send_json_error( [
                'message' => esc_html__( 'Security check failed', 'maneli-car-inquiry' )
            ] );
        }

        // Check user permissions
        if ( ! current_user_can( 'manage_maneli_inquiries' ) ) {
            wp_send_json_error( [
                'message' => esc_html__( 'You do not have permission to perform this action', 'maneli-car-inquiry' )
            ] );
        }

        try {
            // Require the demo data generator class
            if ( ! class_exists( 'Maneli_Demo_Data_Generator' ) ) {
                require_once MANELI_CAR_INQUIRY_PLUGIN_DIR . 'includes/class-demo-data-generator.php';
            }

            $generator = new Maneli_Demo_Data_Generator();
            $results = $generator->generate_demo_data();

            $total = $results['customers'] + $results['experts'] + $results['cars'] + $results['cash_inquiries'] + $results['installment_inquiries'];
            $message = sprintf(
                esc_html__( 'Demo data imported successfully! Created: %d customers, %d experts, %d cars, %d cash inquiries, %d installment inquiries', 'maneli-car-inquiry' ),
                $results['customers'],
                $results['experts'],
                $results['cars'],
                $results['cash_inquiries'],
                $results['installment_inquiries']
            );

            wp_send_json_success( [
                'message' => $message,
                'results' => $results
            ] );

        } catch ( Exception $e ) {
            wp_send_json_error( [
                'message' => sprintf(
                    esc_html__( 'Error importing demo data: %s', 'maneli-car-inquiry' ),
                    $e->getMessage()
                )
            ] );
        }
    }

    /**
     * Handle delete demo data AJAX request
     */
    public function handle_delete_demo_data() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'maneli_delete_demo_data' ) ) {
            wp_send_json_error( [
                'message' => esc_html__( 'Security check failed', 'maneli-car-inquiry' )
            ] );
        }

        // Check user permissions
        if ( ! current_user_can( 'manage_maneli_inquiries' ) ) {
            wp_send_json_error( [
                'message' => esc_html__( 'You do not have permission to perform this action', 'maneli-car-inquiry' )
            ] );
        }

        try {
            // Require the demo data generator class
            if ( ! class_exists( 'Maneli_Demo_Data_Generator' ) ) {
                require_once MANELI_CAR_INQUIRY_PLUGIN_DIR . 'includes/class-demo-data-generator.php';
            }

            $generator = new Maneli_Demo_Data_Generator();
            $results = $generator->delete_demo_data();

            $total = $results['customers_deleted'] + $results['experts_deleted'] + $results['cars_deleted'] + $results['inquiries_deleted'];
            $message = sprintf(
                esc_html__( 'Demo data deleted successfully! Deleted: %d customers, %d experts, %d cars, %d inquiries', 'maneli-car-inquiry' ),
                $results['customers_deleted'],
                $results['experts_deleted'],
                $results['cars_deleted'],
                $results['inquiries_deleted']
            );

            wp_send_json_success( [
                'message' => $message,
                'results' => $results
            ] );

        } catch ( Exception $e ) {
            wp_send_json_error( [
                'message' => sprintf(
                    esc_html__( 'Error deleting demo data: %s', 'maneli-car-inquiry' ),
                    $e->getMessage()
                )
            ] );
        }
    }
}

// Instantiate the handler
new Maneli_Demo_Data_Handler();
