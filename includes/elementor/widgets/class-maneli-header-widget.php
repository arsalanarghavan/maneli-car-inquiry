<?php
/**
 * Maneli Header Widget for Elementor
 * Professional header with full customization
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor/Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Utils;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;

class Maneli_Header_Widget extends Widget_Base {

    public function get_name() {
        return 'maneli_header';
    }

    public function get_title() {
        return __('Maneli Header', 'maneli-car-inquiry');
    }

    public function get_icon() {
        return 'eicon-header';
    }

    public function get_categories() {
        return ['maneli'];
    }

    public function get_script_depends() {
        return ['maneli-elementor'];
    }

    protected function _register_controls() {
        // Logo Section
        $this->start_controls_section(
            'section_logo',
            [
                'label' => __('Logo & Branding', 'maneli-car-inquiry'),
            ]
        );

        $this->add_control(
            'logo_image',
            [
                'label' => __('Logo', 'maneli-car-inquiry'),
                'type' => Controls_Manager::MEDIA,
                'default' => [
                    'url' => MANELI_INQUIRY_PLUGIN_URL . 'assets/images/logo.png',
                ],
            ]
        );

        $this->add_control(
            'logo_text_fa',
            [
                'label' => __('Logo Text (Farsi)', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'مانلی خودرو',
            ]
        );

        $this->add_control(
            'logo_text_en',
            [
                'label' => __('Logo Text (English)', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'MANELI AUTO',
            ]
        );

        $this->add_control(
            'phone_number',
            [
                'label' => __('Phone Number', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => '021-9981212-27',
            ]
        );

        $this->end_controls_section();

        // Menu Section
        $this->start_controls_section(
            'section_menu',
            [
                'label' => __('Menu Items', 'maneli-car-inquiry'),
            ]
        );

        $this->add_control(
            'menu_items',
            [
                'label' => __('Menu Items', 'maneli-car-inquiry'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'menu_text',
                        'label' => __('Text', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => __('Menu Item', 'maneli-car-inquiry'),
                    ],
                    [
                        'name' => 'menu_url',
                        'label' => __('URL', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::URL,
                        'placeholder' => __('https://example.com', 'maneli-car-inquiry'),
                    ],
                    [
                        'name' => 'is_active',
                        'label' => __('Active', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::SWITCHER,
                        'default' => 'no',
                    ],
                ],
                'default' => [
                    ['menu_text' => 'صفحه اصلی', 'is_active' => 'yes'],
                    ['menu_text' => 'فروشگاه'],
                    ['menu_text' => 'خرید اقساطی'],
                    ['menu_text' => 'درباره ما'],
                    ['menu_text' => 'تماس با ما'],
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Style', 'maneli-car-inquiry'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'header_background',
                'label' => __('Background', 'maneli-car-inquiry'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .maneli-header-widget',
            ]
        );

        $this->add_control(
            'header_height',
            [
                'label' => __('Header Height', 'maneli-car-inquiry'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 150,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 80,
                ],
                'selectors' => [
                    '{{WRAPPER}} .maneli-header-widget' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $menu_items = isset($settings['menu_items']) ? $settings['menu_items'] : [];
        ?>
        <header class="maneli-header-widget">
            <div class="header-container">
                <!-- Info Bar -->
                <div class="header-info-bar">
                    <div class="info-left">
                        <button class="btn-login"><?php _e('دسته‌کاری', 'maneli-car-inquiry'); ?></button>
                        <a href="#" class="link-signup"><?php _e('ثبت نام', 'maneli-car-inquiry'); ?></a>
                    </div>
                    <div class="info-right">
                        <span class="phone-number"><?php echo esc_html($settings['phone_number']); ?></span>
                    </div>
                </div>

                <!-- Main Header -->
                <div class="header-main">
                    <!-- Navigation -->
                    <nav class="header-nav">
                        <?php foreach ($menu_items as $item) : ?>
                            <?php 
                            // Extract URL from Elementor URL control (which returns an array)
                            $menu_url = '';
                            if (is_array($item['menu_url'])) {
                                $menu_url = isset($item['menu_url']['url']) ? $item['menu_url']['url'] : '';
                            } else {
                                $menu_url = $item['menu_url'];
                            }
                            ?>
                            <a href="<?php echo esc_url($menu_url); ?>" 
                               class="nav-item <?php echo ($item['is_active'] === 'yes') ? 'active' : ''; ?>">
                                <?php echo esc_html($item['menu_text']); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <!-- Logo -->
                    <div class="logo-section">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo-link">
                            <?php if (!empty($settings['logo_image']['url'])) : ?>
                                <img src="<?php echo esc_url($settings['logo_image']['url']); ?>" 
                                     alt="<?php echo esc_attr($settings['logo_text_fa']); ?>" 
                                     class="logo-img">
                            <?php endif; ?>
                            <div class="logo-text">
                                <div class="logo-fa"><?php echo esc_html($settings['logo_text_fa']); ?></div>
                                <div class="logo-en"><?php echo esc_html($settings['logo_text_en']); ?></div>
                            </div>
                        </a>
                    </div>

                    <!-- Actions -->
                    <div class="header-actions">
                        <button class="btn-search" id="search-toggle">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="#" class="btn-user">
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <button class="btn-menu-toggle" id="mobile-menu-toggle">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="header-search-bar" id="search-bar">
                    <div class="search-input-group">
                        <input type="search" placeholder="<?php _e('جستجو برای خودرو...', 'maneli-car-inquiry'); ?>" 
                               class="search-input">
                        <button class="search-btn"><?php _e('جستجو', 'maneli-car-inquiry'); ?></button>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
}
