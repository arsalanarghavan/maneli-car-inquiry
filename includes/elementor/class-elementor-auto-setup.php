<?php
/**
 * Elementor Auto Setup
 * Automatically creates homepage and theme builder templates
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Elementor_Auto_Setup {

    /**
     * Initialize auto setup
     */
    public static function init() {
        add_action('after_switch_theme', [__CLASS__, 'setup_elementor']);
        add_action('maneli_setup_elementor', [__CLASS__, 'setup_elementor']);
    }

    /**
     * Setup Elementor homepage and theme builder
     */
    public static function setup_elementor() {
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        // Create header template
        self::create_header_template();
        
        // Create footer template
        self::create_footer_template();
        
        // Create homepage
        self::create_homepage();
    }

    /**
     * Create Elementor Header Template
     */
    private static function create_header_template() {
        // Check if header template already exists
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'header',
                ]
            ]
        ];
        $existing = get_posts($args);
        
        if (!empty($existing)) {
            return; // Already exists
        }

        // Create header post
        $header_id = wp_insert_post([
            'post_title' => 'Maneli Header',
            'post_status' => 'publish',
            'post_type' => 'elementor_library',
            'post_content' => '',
        ]);

        if (!$header_id) {
            return;
        }

        // Set as header template
        update_post_meta($header_id, '_elementor_template_type', 'header');
        
        // Add Elementor data with Header widget
        $elementor_data = self::get_header_elementor_data();
        update_post_meta($header_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($header_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($header_id, '_elementor_edit_mode', 'builder');
    }

    /**
     * Create Elementor Footer Template
     */
    private static function create_footer_template() {
        // Check if footer template already exists
        $args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => 'footer',
                ]
            ]
        ];
        $existing = get_posts($args);
        
        if (!empty($existing)) {
            return; // Already exists
        }

        // Create footer post
        $footer_id = wp_insert_post([
            'post_title' => 'Maneli Footer',
            'post_status' => 'publish',
            'post_type' => 'elementor_library',
            'post_content' => '',
        ]);

        if (!$footer_id) {
            return;
        }

        // Set as footer template
        update_post_meta($footer_id, '_elementor_template_type', 'footer');
        
        // Add Elementor data with Footer widget
        $elementor_data = self::get_footer_elementor_data();
        update_post_meta($footer_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($footer_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($footer_id, '_elementor_edit_mode', 'builder');
    }

    /**
     * Create Homepage
     */
    private static function create_homepage() {
        // Check if homepage already exists
        $homepage = get_option('page_on_front');
        if ($homepage && get_post($homepage)) {
            return; // Already exists
        }

        // Create homepage post
        $home_id = wp_insert_post([
            'post_title' => 'صفحه اصلی',
            'post_name' => 'home',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '<!-- Maneli Elementor Homepage -->',
        ]);

        if (!$home_id) {
            return;
        }

        // Add Elementor data
        $elementor_data = self::get_homepage_elementor_data();
        update_post_meta($home_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($home_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($home_id, '_elementor_edit_mode', 'builder');
        update_post_meta($home_id, '_elementor_page_settings', ['post_title' => 'yes']);

        // Set as front page
        update_option('page_on_front', $home_id);
        update_option('show_on_front', 'page');

        // Also set homepage to use theme builder header/footer
        update_post_meta($home_id, '_elementor_custom_header', 'yes');
        update_post_meta($home_id, '_elementor_custom_footer', 'yes');
    }

    /**
     * Get Header Elementor Data
     */
    private static function get_header_elementor_data() {
        return [
            [
                'id' => 'header-section-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#0052CC',
                    'padding' => ['top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px', 'isLinked' => true],
                ],
                'elements' => [
                    [
                        'id' => 'header-column-' . uniqid(),
                        'elType' => 'column',
                        'settings' => [
                            '_column_size' => 100,
                            '_inline_size' => '100',
                        ],
                        'elements' => [
                            [
                                'id' => 'header-widget-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'maneli_header',
                                'settings' => [
                                    'logo_text_fa' => 'مانلی خودرو',
                                    'logo_text_en' => 'MANELI AUTO',
                                    'phone_number' => '021-99812129-27',
                                    'menu_items' => [
                                        ['menu_text' => 'صفحه اصلی', 'is_active' => 'yes'],
                                        ['menu_text' => 'فروشگاه', 'is_active' => 'no'],
                                        ['menu_text' => 'خرید اقساطی', 'is_active' => 'no'],
                                        ['menu_text' => 'درباره ما', 'is_active' => 'no'],
                                        ['menu_text' => 'تماس با ما', 'is_active' => 'no'],
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get Footer Elementor Data
     */
    private static function get_footer_elementor_data() {
        return [
            [
                'id' => 'footer-section-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#1e293b',
                    'padding' => ['top' => '40', 'right' => '0', 'bottom' => '20', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'footer-column-' . uniqid(),
                        'elType' => 'column',
                        'settings' => [
                            '_column_size' => 100,
                            '_inline_size' => '100',
                        ],
                        'elements' => [
                            [
                                'id' => 'footer-widget-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'maneli_footer',
                                'settings' => [
                                    'col1_title' => 'درباره مانلی',
                                    'col1_content' => 'مانلی خودرو بزرگ‌ترین پلتفرم خرید و فروش خودرو در ایران است. ما تعهد داریم بهترین خدمات را ارائه دهیم.',
                                    'col2_title' => 'لینک‌های مفید',
                                    'col2_links' => [
                                        ['link_text' => 'درباره ما'],
                                        ['link_text' => 'تماس با ما'],
                                        ['link_text' => 'شرایط استفاده'],
                                        ['link_text' => 'حریم خصوصی'],
                                    ],
                                    'col3_title' => 'تماس با ما',
                                    'col3_phone' => '021-99812129',
                                    'col3_email' => 'info@maneli.com',
                                    'col3_address' => 'تهران - خیابان وکیل - پلاک ۱۲۳',
                                    'col4_title' => 'شبکه‌های اجتماعی',
                                    'col4_socials' => [
                                        ['social_name' => 'facebook'],
                                        ['social_name' => 'instagram'],
                                        ['social_name' => 'twitter'],
                                    ],
                                    'copyright_text' => '© 2025 مانلی خودرو. تمام حقوق محفوظ است.',
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Get Homepage Elementor Data
     */
    private static function get_homepage_elementor_data() {
        return [
            // Hero Section
            [
                'id' => 'hero-section-' . uniqid(),
                'elType' => 'section',
                'settings' => [],
                'elements' => [
                    [
                        'id' => 'hero-column-' . uniqid(),
                        'elType' => 'column',
                        'settings' => [
                            '_column_size' => 100,
                            '_inline_size' => '100',
                        ],
                        'elements' => [
                            [
                                'id' => 'hero-widget-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'maneli_hero',
                                'settings' => [
                                    'autoplay' => 'yes',
                                    'autoplay_delay' => 5000,
                                    'height' => 600,
                                    'slides' => [
                                        [
                                            'slide_title' => 'خرید خودرو با اقساط آسان',
                                            'slide_subtitle' => 'بدون ضامن، بدون تاخیر - فقط سه مرحله ساده',
                                            'slide_button_text' => 'مشاهده خودروها',
                                        ],
                                        [
                                            'slide_title' => 'تحویل خودرو در کمتر از یک ساعت',
                                            'slide_subtitle' => 'خدمات سریع و قابل اعتماد',
                                            'slide_button_text' => 'بیشتر بدانید',
                                        ],
                                        [
                                            'slide_title' => 'سند به نام شما از اول',
                                            'slide_subtitle' => 'مالکیت واقعی از روز اول',
                                            'slide_button_text' => 'درخواست مشاوره',
                                        ],
                                    ],
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            // Products Section
            [
                'id' => 'products-section-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#f8fafc',
                    'padding' => ['top' => '80', 'right' => '0', 'bottom' => '80', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'products-column-' . uniqid(),
                        'elType' => 'column',
                        'settings' => [
                            '_column_size' => 100,
                            '_inline_size' => '100',
                        ],
                        'elements' => [
                            [
                                'id' => 'products-heading-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'محصولات ما',
                                    'header_size' => 'h2',
                                    'text_align' => 'center',
                                    'title_color' => '#1e293b',
                                ],
                            ],
                            [
                                'id' => 'products-widget-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'maneli_products',
                                'settings' => [
                                    'products_per_page' => 12,
                                    'columns' => 4,
                                    'show_filters' => 'yes',
                                    'orderby' => 'date',
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}

// Initialize
Maneli_Elementor_Auto_Setup::init();

// Trigger setup on plugin activation
register_activation_hook(MANELI_INQUIRY_PLUGIN_FILE, function() {
    Maneli_Elementor_Auto_Setup::setup_elementor();
});
