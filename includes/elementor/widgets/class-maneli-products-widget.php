<?php
/**
 * Maneli Products Widget for Elementor
 * WooCommerce products grid with filtering
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor/Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Maneli_Products_Widget extends Widget_Base {

    public function get_name() {
        return 'maneli_products';
    }

    public function get_title() {
        return __('Maneli Products', 'maneli-car-inquiry');
    }

    public function get_icon() {
        return 'eicon-products';
    }

    public function get_categories() {
        return ['maneli'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'section_settings',
            ['label' => __('Settings', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'products_per_page',
            [
                'label' => __('Products Per Page', 'maneli-car-inquiry'),
                'type' => Controls_Manager::NUMBER,
                'default' => 12,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'maneli-car-inquiry'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6',
                ],
                'default' => '4',
            ]
        );

        $this->add_control(
            'show_filters',
            [
                'label' => __('Show Filters', 'maneli-car-inquiry'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'maneli-car-inquiry'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'date' => 'Date',
                    'price' => 'Price',
                    'popularity' => 'Popularity',
                    'rating' => 'Rating',
                ],
                'default' => 'date',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $per_page = intval($settings['products_per_page']);
        $columns = intval($settings['columns']);

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $per_page,
            'orderby' => $settings['orderby'],
            'order' => 'DESC',
        ];

        $products = get_posts($args);
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ]);
        ?>
        <section class="products-section">
            <div class="container">
                <h2 class="section-title"><?php _e('محصولات ما', 'maneli-car-inquiry'); ?></h2>

                <?php if ($settings['show_filters'] === 'yes' && !empty($categories)) : ?>
                    <div class="products-filters">
                        <button class="filter-btn active" data-filter="all">
                            <?php _e('همه', 'maneli-car-inquiry'); ?>
                        </button>
                        <?php foreach ($categories as $category) : ?>
                            <button class="filter-btn" data-filter="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="products-grid" style="grid-template-columns: repeat(<?php echo intval($columns); ?>, 1fr);">
                    <?php
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            set_post_data($product);
                            $woo_product = wc_get_product($product->ID);
                            $categories = get_the_terms($product->ID, 'product_cat');
                            $category_slug = !empty($categories) ? $categories[0]->slug : 'all';
                            ?>
                            <div class="product-card" data-filter="<?php echo esc_attr($category_slug); ?>">
                                <div class="product-image">
                                    <?php
                                    if (has_post_thumbnail($product->ID)) {
                                        echo get_the_post_thumbnail($product->ID, 'medium', ['class' => 'w-100']);
                                    } else {
                                        echo '<img src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr($product->post_title) . '">';
                                    }
                                    ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo esc_html($product->post_title); ?></h3>
                                    <div class="product-price">
                                        <?php
                                        if ($woo_product) {
                                            echo wp_kses_post($woo_product->get_price_html());
                                        }
                                        ?>
                                    </div>
                                    <a href="<?php echo esc_url($product->guid); ?>" class="btn-view-details">
                                        <?php _e('مشاهده جزئیات', 'maneli-car-inquiry'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        </section>
        <?php
    }
}
