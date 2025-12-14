<?php
/**
 * Maneli Hero Widget for Elementor
 * Full-width hero slider section
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor/Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Maneli_Hero_Widget extends Widget_Base {

    public function get_name() {
        return 'maneli_hero';
    }

    public function get_title() {
        return __('Maneli Hero Slider', 'maneli-car-inquiry');
    }

    public function get_icon() {
        return 'eicon-slides';
    }

    public function get_categories() {
        return ['maneli'];
    }

    public function get_script_depends() {
        return ['swiper'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'section_slides',
            ['label' => __('Slides', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'slides',
            [
                'label' => __('Slides', 'maneli-car-inquiry'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'slide_title',
                        'label' => __('Title', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::TEXT,
                        'placeholder' => __('Slide Title', 'maneli-car-inquiry'),
                    ],
                    [
                        'name' => 'slide_subtitle',
                        'label' => __('Subtitle', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'slide_image',
                        'label' => __('Image', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::MEDIA,
                    ],
                    [
                        'name' => 'slide_button_text',
                        'label' => __('Button Text', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'slide_button_url',
                        'label' => __('Button URL', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::URL,
                    ],
                ],
                'default' => [
                    [
                        'slide_title' => 'خرید خودرو با اقساط آسان',
                        'slide_subtitle' => 'بدون ضامن، بدون تاخیر',
                        'slide_button_text' => 'مشاهده خودروها',
                    ],
                    [
                        'slide_title' => 'تحویل در کمتر از یک ساعت',
                        'slide_subtitle' => 'خدمات سریع و قابل اعتماد',
                        'slide_button_text' => 'بیشتر بدانید',
                    ],
                    [
                        'slide_title' => 'سند به نام شما از اول',
                        'slide_subtitle' => 'مالکیت واقعی از روز اول',
                        'slide_button_text' => 'درخواست مشاوره',
                    ],
                ],
            ]
        );

        $this->end_controls_section();

        // Settings
        $this->start_controls_section(
            'section_settings',
            ['label' => __('Settings', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'autoplay',
            [
                'label' => __('Autoplay', 'maneli-car-inquiry'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'autoplay_delay',
            [
                'label' => __('Autoplay Delay (ms)', 'maneli-car-inquiry'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5000,
            ]
        );

        $this->add_control(
            'height',
            [
                'label' => __('Height (px)', 'maneli-car-inquiry'),
                'type' => Controls_Manager::NUMBER,
                'default' => 600,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $slides = isset($settings['slides']) ? $settings['slides'] : [];
        ?>
        <section class="hero-slider-section" style="height: <?php echo intval($settings['height']); ?>px;">
            <div class="swiper-container hero-swiper" data-autoplay="<?php echo esc_attr($settings['autoplay']); ?>" data-delay="<?php echo intval($settings['autoplay_delay']); ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($slides as $slide) : ?>
                        <div class="swiper-slide hero-slide">
                            <?php if (!empty($slide['slide_image']['url'])) : ?>
                                <div class="slide-bg" style="background-image: url('<?php echo esc_url($slide['slide_image']['url']); ?>');">
                            <?php else : ?>
                                <div class="slide-bg">
                            <?php endif; ?>
                                <div class="slide-overlay"></div>
                                <div class="slide-content">
                                    <h1 class="slide-title"><?php echo esc_html($slide['slide_title']); ?></h1>
                                    <p class="slide-subtitle"><?php echo esc_html($slide['slide_subtitle']); ?></p>
                                    <?php if (!empty($slide['slide_button_text'])) : ?>
                                        <a href="<?php echo esc_url($slide['slide_button_url']); ?>" class="btn btn-primary btn-lg">
                                            <?php echo esc_html($slide['slide_button_text']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Navigation -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-pagination"></div>
            </div>
        </section>
        <?php
    }
}
