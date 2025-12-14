<?php
/**
 * Maneli Footer Widget for Elementor
 * Professional footer with full customization
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor/Widgets
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Maneli_Footer_Widget extends Widget_Base {

    public function get_name() {
        return 'maneli_footer';
    }

    public function get_title() {
        return __('Maneli Footer', 'maneli-car-inquiry');
    }

    public function get_icon() {
        return 'eicon-footer';
    }

    public function get_categories() {
        return ['maneli'];
    }

    protected function _register_controls() {
        // Column 1
        $this->start_controls_section(
            'section_col1',
            ['label' => __('Column 1 - About', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'col1_title',
            [
                'label' => __('Title', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'درباره مانلی',
            ]
        );

        $this->add_control(
            'col1_content',
            [
                'label' => __('Content', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => 'مانلی خودرو بزرگ‌ترین پلتفرم خرید و فروش خودرو در ایران است.',
            ]
        );

        $this->end_controls_section();

        // Column 2
        $this->start_controls_section(
            'section_col2',
            ['label' => __('Column 2 - Links', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'col2_title',
            [
                'label' => __('Title', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'لینک‌های مفید',
            ]
        );

        $this->add_control(
            'col2_links',
            [
                'label' => __('Links', 'maneli-car-inquiry'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'link_text',
                        'label' => __('Text', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::TEXT,
                    ],
                    [
                        'name' => 'link_url',
                        'label' => __('URL', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::URL,
                    ],
                ],
                'default' => [
                    ['link_text' => 'درباره ما'],
                    ['link_text' => 'تماس با ما'],
                    ['link_text' => 'شرایط استفاده'],
                ],
            ]
        );

        $this->end_controls_section();

        // Column 3
        $this->start_controls_section(
            'section_col3',
            ['label' => __('Column 3 - Contact', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'col3_title',
            [
                'label' => __('Title', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'تماس با ما',
            ]
        );

        $this->add_control(
            'col3_phone',
            [
                'label' => __('Phone', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => '021-99812129',
            ]
        );

        $this->add_control(
            'col3_email',
            [
                'label' => __('Email', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'info@maneli.com',
            ]
        );

        $this->add_control(
            'col3_address',
            [
                'label' => __('Address', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => 'تهران - خیابان وکیل - پلاک ۱۲۳',
            ]
        );

        $this->end_controls_section();

        // Column 4
        $this->start_controls_section(
            'section_col4',
            ['label' => __('Column 4 - Social', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'col4_title',
            [
                'label' => __('Title', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXT,
                'default' => 'شبکه‌های اجتماعی',
            ]
        );

        $this->add_control(
            'col4_socials',
            [
                'label' => __('Social Links', 'maneli-car-inquiry'),
                'type' => Controls_Manager::REPEATER,
                'fields' => [
                    [
                        'name' => 'social_name',
                        'label' => __('Name', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::TEXT,
                        'options' => [
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'twitter' => 'Twitter',
                            'linkedin' => 'LinkedIn',
                        ],
                    ],
                    [
                        'name' => 'social_url',
                        'label' => __('URL', 'maneli-car-inquiry'),
                        'type' => Controls_Manager::URL,
                    ],
                ],
                'default' => [
                    ['social_name' => 'facebook'],
                    ['social_name' => 'instagram'],
                    ['social_name' => 'twitter'],
                ],
            ]
        );

        $this->end_controls_section();

        // Copyright
        $this->start_controls_section(
            'section_copyright',
            ['label' => __('Copyright', 'maneli-car-inquiry')]
        );

        $this->add_control(
            'copyright_text',
            [
                'label' => __('Copyright Text', 'maneli-car-inquiry'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '© 2025 مانلی خودرو. تمام حقوق محفوظ است.',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <footer class="maneli-footer-widget">
            <div class="footer-container">
                <div class="footer-grid">
                    <!-- Column 1 -->
                    <div class="footer-column">
                        <h3 class="footer-title"><?php echo esc_html($settings['col1_title']); ?></h3>
                        <p><?php echo wp_kses_post($settings['col1_content']); ?></p>
                    </div>

                    <!-- Column 2 -->
                    <div class="footer-column">
                        <h3 class="footer-title"><?php echo esc_html($settings['col2_title']); ?></h3>
                        <ul class="footer-links">
                            <?php foreach ($settings['col2_links'] as $link) : ?>
                                <?php 
                                // Extract URL from Elementor URL control (which returns an array)
                                $link_url = '';
                                if (is_array($link['link_url'])) {
                                    $link_url = isset($link['link_url']['url']) ? $link['link_url']['url'] : '';
                                } else {
                                    $link_url = $link['link_url'];
                                }
                                ?>
                                <li>
                                    <a href="<?php echo esc_url($link_url); ?>">
                                        <?php echo esc_html($link['link_text']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Column 3 -->
                    <div class="footer-column">
                        <h3 class="footer-title"><?php echo esc_html($settings['col3_title']); ?></h3>
                        <div class="footer-contact">
                            <p><strong><?php _e('تلفن:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($settings['col3_phone']); ?></p>
                            <p><strong><?php _e('ایمیل:', 'maneli-car-inquiry'); ?></strong> <?php echo esc_html($settings['col3_email']); ?></p>
                            <p><strong><?php _e('آدرس:', 'maneli-car-inquiry'); ?></strong> <?php echo wp_kses_post($settings['col3_address']); ?></p>
                        </div>
                    </div>

                    <!-- Column 4 -->
                    <div class="footer-column">
                        <h3 class="footer-title"><?php echo esc_html($settings['col4_title']); ?></h3>
                        <div class="footer-socials">
                            <?php foreach ($settings['col4_socials'] as $social) : ?>
                                <a href="<?php echo esc_url($social['social_url']); ?>" 
                                   class="social-link social-<?php echo esc_attr($social['social_name']); ?>"
                                   target="_blank" rel="noopener noreferrer">
                                    <i class="fab fa-<?php echo esc_attr($social['social_name']); ?>"></i>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="footer-copyright">
                    <p><?php echo wp_kses_post($settings['copyright_text']); ?></p>
                </div>
            </div>
        </footer>
        <?php
    }
}
