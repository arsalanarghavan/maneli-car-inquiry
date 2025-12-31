<?php
/**
 * Manages automatic product tags based on cash and installment price availability.
 *
 * @package Autopuzzle_Car_Inquiry/Includes
 * @author  Auto
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Autopuzzle_Product_Tags_Manager {

    /**
     * Tag slugs for cash and installment
     */
    const TAG_CASH = 'cash';
    const TAG_INSTALLMENT = 'installment';

    /**
     * Tag names in Persian
     */
    const TAG_CASH_NAME = 'نقدی';
    const TAG_INSTALLMENT_NAME = 'اقساطی';

    /**
     * Initialize the tags manager
     */
    public function __construct() {
        // Ensure tags exist on init
        add_action('init', [$this, 'ensure_tags_exist'], 20);
    }

    /**
     * Ensure that cash and installment tags exist in product_tag taxonomy
     */
    public function ensure_tags_exist() {
        if (!taxonomy_exists('product_tag')) {
            return;
        }

        // Create cash tag if it doesn't exist
        $cash_tag = get_term_by('slug', self::TAG_CASH, 'product_tag');
        if (!$cash_tag) {
            wp_insert_term(
                self::TAG_CASH_NAME,
                'product_tag',
                [
                    'slug' => self::TAG_CASH,
                    'description' => esc_html__('Products available for cash purchase', 'autopuzzle'),
                ]
            );
        }

        // Create installment tag if it doesn't exist
        $installment_tag = get_term_by('slug', self::TAG_INSTALLMENT, 'product_tag');
        if (!$installment_tag) {
            wp_insert_term(
                self::TAG_INSTALLMENT_NAME,
                'product_tag',
                [
                    'slug' => self::TAG_INSTALLMENT,
                    'description' => esc_html__('Products available for installment purchase', 'autopuzzle'),
                ]
            );
        }
    }

    /**
     * Update tags for a specific product based on its prices
     *
     * @param int $product_id Product ID
     * @return bool True on success, false on failure
     */
    public function update_product_tags($product_id) {
        if (!$product_id || !function_exists('wc_get_product')) {
            return false;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        // Get current tags
        $current_tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'slugs']);
        if (is_wp_error($current_tags)) {
            $current_tags = [];
        }

        // Get prices
        $cash_price = $this->get_cash_price($product);
        $installment_price = $this->get_installment_price($product_id);

        // Determine which tags should be present
        $tags_to_assign = [];

        if ($cash_price > 0) {
            $tags_to_assign[] = self::TAG_CASH;
        }

        if ($installment_price > 0) {
            $tags_to_assign[] = self::TAG_INSTALLMENT;
        }

        // Get tags to remove (current tags that shouldn't be there)
        $tags_to_remove = array_diff($current_tags, $tags_to_assign);
        // Remove cash and installment tags that shouldn't be there
        $tags_to_remove = array_intersect($tags_to_remove, [self::TAG_CASH, self::TAG_INSTALLMENT]);

        // Get tags to add (tags that should be there but aren't)
        $tags_to_add = array_diff($tags_to_assign, $current_tags);

        // Remove tags
        if (!empty($tags_to_remove)) {
            wp_remove_object_terms($product_id, $tags_to_remove, 'product_tag');
        }

        // Add tags
        if (!empty($tags_to_add)) {
            wp_set_object_terms($product_id, $tags_to_add, 'product_tag', true);
        }

        return true;
    }

    /**
     * Get cash price from product
     *
     * @param WC_Product $product Product object
     * @return int Cash price (0 if not available)
     */
    private function get_cash_price($product) {
        $cash_price_raw = $product->get_regular_price();

        // Handle empty/null values
        if ($cash_price_raw === '' || $cash_price_raw === null || $cash_price_raw === false || $cash_price_raw === 0 || $cash_price_raw === '0') {
            return 0;
        }

        // Convert to string and process formatting
        $cash_price_str = (string)$cash_price_raw;
        // Convert Persian digits to English
        $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $cash_price_str = str_replace($persian_digits, $english_digits, $cash_price_str);
        // Remove all non-numeric characters
        $cash_price_str = preg_replace('/[^\d]/', '', $cash_price_str);
        $cash_price = (!empty($cash_price_str) && $cash_price_str !== '0') ? (int)$cash_price_str : 0;

        return $cash_price;
    }

    /**
     * Get installment price from product meta
     *
     * @param int $product_id Product ID
     * @return int Installment price (0 if not available)
     */
    private function get_installment_price($product_id) {
        $installment_price_raw = get_post_meta($product_id, 'installment_price', true);

        if (is_string($installment_price_raw) && !empty($installment_price_raw)) {
            // Convert Persian digits to English
            $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $installment_price_raw = str_replace($persian_digits, $english_digits, $installment_price_raw);
            // Remove all non-numeric characters
            $installment_price_raw = preg_replace('/[^\d]/', '', $installment_price_raw);
        }

        $installment_price = (!empty($installment_price_raw) && $installment_price_raw !== '0') ? (int)$installment_price_raw : 0;

        return $installment_price;
    }

    /**
     * Get payment tags for a product
     *
     * @param int $product_id Product ID
     * @return array Array with 'cash' and 'installment' keys, values are true/false
     */
    public function get_product_payment_tags($product_id) {
        if (!$product_id || !function_exists('wc_get_product')) {
            return ['cash' => false, 'installment' => false];
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return ['cash' => false, 'installment' => false];
        }

        $cash_price = $this->get_cash_price($product);
        $installment_price = $this->get_installment_price($product_id);

        return [
            'cash' => $cash_price > 0,
            'installment' => $installment_price > 0,
        ];
    }

    /**
     * Update tags for all products (migration function)
     *
     * @param int $limit Number of products to process per batch (default: 50)
     * @return array Statistics: ['processed' => int, 'updated' => int]
     */
    public function update_all_products_tags($limit = 50) {
        $args = [
            'post_type' => 'product',
            'post_status' => 'any',
            'posts_per_page' => $limit,
            'fields' => 'ids',
        ];

        $products = get_posts($args);
        $processed = 0;
        $updated = 0;

        foreach ($products as $product_id) {
            $processed++;
            if ($this->update_product_tags($product_id)) {
                $updated++;
            }
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
        ];
    }
}

