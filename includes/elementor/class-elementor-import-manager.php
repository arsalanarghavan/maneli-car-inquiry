<?php
/**
 * Elementor Template Import Manager
 * Handles importing Elementor templates from dashboard
 * 
 * @package Maneli_Car_Inquiry/Includes/Elementor
 */

if (!defined('ABSPATH')) {
    exit;
}

class Maneli_Elementor_Import_Manager {

    /**
     * Initialize import manager
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_import_menu']);
        add_action('admin_init', [__CLASS__, 'handle_import_request']);
        add_action('wp_ajax_maneli_import_template', [__CLASS__, 'ajax_import_template']);
    }

    /**
     * Handle non-AJAX import requests (fallback)
     * Prevents missing callback fatal and keeps hook safe.
     */
    public static function handle_import_request() {
        // Capability and page guard; currently imports are AJAX-only.
        if (!current_user_can('manage_options')) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        if ($page !== 'maneli-elementor-importer') {
            return;
        }

        // If future non-AJAX submits are added, process them here.
    }

    /**
     * Add import menu to dashboard
     */
    public static function add_import_menu() {
        // Tools > Elementor Templates (visible for admins)
        add_submenu_page(
            'tools.php',
            __('Elementor Template Importer', 'maneli-car-inquiry'),
            __('Elementor Templates', 'maneli-car-inquiry'),
            'manage_options',
            'maneli-elementor-importer',
            [__CLASS__, 'render_import_page']
        );

        // Elementor > Templates (if Elementor menu exists)
        add_submenu_page(
            'elementor',
            __('Elementor Template Importer', 'maneli-car-inquiry'),
            __('Template Importer', 'maneli-car-inquiry'),
            'manage_options',
            'maneli-elementor-importer',
            [__CLASS__, 'render_import_page']
        );
    }

    /**
     * Render import page
     */
    public static function render_import_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Access Denied', 'maneli-car-inquiry'));
        }

        // Get all available templates
        $templates = [
            'header' => [
                'name' => __('Header Template', 'maneli-car-inquiry'),
                'description' => __('Professional header with navigation and branding', 'maneli-car-inquiry'),
                'icon' => '🎨',
            ],
            'footer' => [
                'name' => __('Footer Template', 'maneli-car-inquiry'),
                'description' => __('4-column footer with contact info and social media', 'maneli-car-inquiry'),
                'icon' => '📍',
            ],
            'homepage' => [
                'name' => __('Homepage', 'maneli-car-inquiry'),
                'description' => __('Complete homepage with hero slider and products section', 'maneli-car-inquiry'),
                'icon' => '🏠',
            ],
            'theme_home' => [
                'name' => __('Exclusive Elementor Theme', 'maneli-car-inquiry'),
                'description' => __('Full bespoke homepage with hero, stats, steps, products, testimonials, CTA', 'maneli-car-inquiry'),
                'icon' => '✨',
            ],
        ];

        // Get already imported templates
        $imported = self::get_imported_templates();

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Elementor Template Importer', 'maneli-car-inquiry'); ?></h1>
            <p><?php esc_html_e('Import pre-designed Elementor templates into your website', 'maneli-car-inquiry'); ?></p>

            <div class="maneli-import-container">
                <div class="maneli-import-grid">
                    <?php foreach ($templates as $template_key => $template_data) : ?>
                        <?php $is_imported = isset($imported[$template_key]); ?>
                        <div class="maneli-import-card <?php echo $is_imported ? 'imported' : ''; ?>">
                            <div class="template-icon">
                                <?php echo $template_data['icon']; ?>
                            </div>
                            
                            <h3><?php echo esc_html($template_data['name']); ?></h3>
                            <p><?php echo esc_html($template_data['description']); ?></p>

                            <?php if ($is_imported) : ?>
                                <div class="template-status imported-badge">
                                    <span>✅ <?php esc_html_e('Imported', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <div class="template-actions">
                                    <a href="<?php echo esc_url(get_edit_post_link($imported[$template_key]['id'])); ?>" class="button button-primary" target="_blank">
                                        <?php esc_html_e('Edit', 'maneli-car-inquiry'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(get_permalink($imported[$template_key]['id'])); ?>" class="button" target="_blank">
                                        <?php esc_html_e('View', 'maneli-car-inquiry'); ?>
                                    </a>
                                    <button class="button button-secondary maneli-reimport-btn" data-template="<?php echo esc_attr($template_key); ?>">
                                        <?php esc_html_e('Re-import', 'maneli-car-inquiry'); ?>
                                    </button>
                                </div>
                            <?php else : ?>
                                <div class="template-status not-imported-badge">
                                    <span>⏳ <?php esc_html_e('Not Imported', 'maneli-car-inquiry'); ?></span>
                                </div>
                                <button class="button button-primary maneli-import-btn" data-template="<?php echo esc_attr($template_key); ?>">
                                    <?php esc_html_e('Import Template', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <style>
            .maneli-import-container {
                margin: 30px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 8px;
            }

            .maneli-import-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }

            .maneli-import-card {
                background: white;
                border: 2px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                transition: all 0.3s ease;
            }

            .maneli-import-card:hover {
                border-color: #0052CC;
                box-shadow: 0 4px 12px rgba(0, 82, 204, 0.15);
            }

            .maneli-import-card.imported {
                border-color: #28a745;
                background: #f0f8f0;
            }

            .template-icon {
                font-size: 48px;
                margin-bottom: 15px;
            }

            .maneli-import-card h3 {
                margin: 0 0 10px 0;
                font-size: 18px;
                color: #1e293b;
            }

            .maneli-import-card p {
                margin: 0 0 15px 0;
                color: #666;
                font-size: 14px;
            }

            .template-status {
                margin: 15px 0;
                padding: 10px;
                border-radius: 4px;
                font-weight: bold;
                font-size: 13px;
            }

            .imported-badge {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .not-imported-badge {
                background: #fff3cd;
                color: #856404;
                border: 1px solid #ffeaa7;
            }

            .template-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 15px;
            }

            .template-actions .button {
                flex: 1;
                min-width: 100px;
                padding: 8px 12px;
                font-size: 13px;
                margin: 0;
            }

            .maneli-import-btn,
            .maneli-reimport-btn {
                width: 100%;
                margin-top: 15px;
            }

            .maneli-import-btn:hover {
                background-color: #0047b3;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const importButtons = document.querySelectorAll('.maneli-import-btn, .maneli-reimport-btn');

                importButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const template = this.getAttribute('data-template');
                        const originalText = this.textContent;
                        
                        this.disabled = true;
                        this.textContent = '⏳ ' + '<?php esc_html_e('Importing...', 'maneli-car-inquiry'); ?>';

                        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'maneli_import_template',
                                template: template,
                                nonce: '<?php echo wp_create_nonce('maneli_import_nonce'); ?>'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                alert('✅ ' + data.data.message);
                                location.reload();
                            } else {
                                alert('❌ ' + (data.data?.message || 'Import failed'));
                                this.disabled = false;
                                this.textContent = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('❌ ' + '<?php esc_html_e('Network error', 'maneli-car-inquiry'); ?>');
                            this.disabled = false;
                            this.textContent = originalText;
                        });
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Handle import via AJAX
     */
    public static function ajax_import_template() {
        check_ajax_referer('maneli_import_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Access Denied', 'maneli-car-inquiry')]);
        }

        $template = isset($_POST['template']) ? sanitize_text_field(wp_unslash($_POST['template'])) : '';

        if (empty($template)) {
            wp_send_json_error(['message' => __('Template not specified', 'maneli-car-inquiry')]);
        }

        $result = self::import_template($template);

        if ($result) {
            wp_send_json_success([
                'message' => sprintf(__('%s imported successfully!', 'maneli-car-inquiry'), $result)
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to import template', 'maneli-car-inquiry')]);
        }
    }

    /**
     * Import template by type
     */
    public static function import_template($template_type) {
        if (!class_exists('\Elementor\Plugin')) {
            return false;
        }

        switch ($template_type) {
            case 'header':
                return self::import_header();
            case 'footer':
                return self::import_footer();
            case 'homepage':
                return self::import_homepage();
            case 'theme_home':
                return self::import_theme_home();
            default:
                return false;
        }
    }

    /**
     * Import header template
     */
    private static function import_header() {
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

        $header_id = !empty($existing) ? $existing[0]->ID : null;

        if (!$header_id) {
            // Create new header post
            $header_id = wp_insert_post([
                'post_title' => 'Maneli Header',
                'post_status' => 'publish',
                'post_type' => 'elementor_library',
                'post_content' => '',
            ]);

            if (!$header_id) {
                return false;
            }
        }

        // Get header data from auto setup
        $elementor_data = self::get_header_elementor_data();
        update_post_meta($header_id, '_elementor_template_type', 'header');
        update_post_meta($header_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($header_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($header_id, '_elementor_edit_mode', 'builder');

        // Register location if not already done
        update_option('elementor_location_header', $header_id);

        return __('Header Template', 'maneli-car-inquiry');
    }

    /**
     * Import footer template
     */
    private static function import_footer() {
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

        $footer_id = !empty($existing) ? $existing[0]->ID : null;

        if (!$footer_id) {
            // Create new footer post
            $footer_id = wp_insert_post([
                'post_title' => 'Maneli Footer',
                'post_status' => 'publish',
                'post_type' => 'elementor_library',
                'post_content' => '',
            ]);

            if (!$footer_id) {
                return false;
            }
        }

        // Get footer data from auto setup
        $elementor_data = self::get_footer_elementor_data();
        update_post_meta($footer_id, '_elementor_template_type', 'footer');
        update_post_meta($footer_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($footer_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($footer_id, '_elementor_edit_mode', 'builder');

        // Register location if not already done
        update_option('elementor_location_footer', $footer_id);

        return __('Footer Template', 'maneli-car-inquiry');
    }

    /**
     * Import homepage
     */
    private static function import_homepage() {
        // Check if homepage already exists
        $homepage = get_option('page_on_front');
        $home_id = null;

        if ($homepage && get_post($homepage)) {
            $home_id = $homepage;
        } else {
            // Create new homepage
            $home_id = wp_insert_post([
                'post_title' => 'صفحه اصلی',
                'post_name' => 'home',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '<!-- Maneli Elementor Homepage -->',
            ]);

            if (!$home_id) {
                return false;
            }

            // Set as front page
            update_option('page_on_front', $home_id);
            update_option('show_on_front', 'page');
        }

        // Get homepage data
        $elementor_data = self::get_homepage_elementor_data();
        update_post_meta($home_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($home_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($home_id, '_elementor_edit_mode', 'builder');
        update_post_meta($home_id, '_elementor_custom_header', 'yes');
        update_post_meta($home_id, '_elementor_custom_footer', 'yes');

        return __('Homepage', 'maneli-car-inquiry');
    }

    /**
     * Import bespoke Elementor theme homepage
     */
    private static function import_theme_home() {
        // Try to reuse existing dedicated page
        $theme_page_id = intval(get_option('maneli_theme_home_page_id'));
        if ($theme_page_id && !get_post($theme_page_id)) {
            $theme_page_id = 0; // reset if missing
        }

        if (!$theme_page_id) {
            // Create a dedicated page without forcing front-page override unless none set
            $theme_page_id = wp_insert_post([
                'post_title'   => 'صفحه اصلی (قالب اختصاصی)',
                'post_name'    => 'home-exclusive',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '<!-- Maneli Exclusive Elementor Theme -->',
            ]);

            if (!$theme_page_id) {
                return false;
            }

            update_option('maneli_theme_home_page_id', $theme_page_id);
            update_post_meta($theme_page_id, '_maneli_theme_home', 'yes');

            // Only set as front page if none is set
            $current_front = get_option('page_on_front');
            if (!$current_front) {
                update_option('page_on_front', $theme_page_id);
                update_option('show_on_front', 'page');
            }
        }

        $elementor_data = self::get_theme_home_elementor_data();
        update_post_meta($theme_page_id, '_elementor_data', wp_slash(wp_json_encode($elementor_data)));
        update_post_meta($theme_page_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0');
        update_post_meta($theme_page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($theme_page_id, '_elementor_custom_header', 'yes');
        update_post_meta($theme_page_id, '_elementor_custom_footer', 'yes');

        return __('Exclusive Elementor Theme', 'maneli-car-inquiry');
    }

    /**
     * Get imported templates
     */
    private static function get_imported_templates() {
        $imported = [];

        // Check header
        $header_args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => '_elementor_template_type', 'value' => 'header']
            ]
        ];
        $header = get_posts($header_args);
        if (!empty($header)) {
            $imported['header'] = ['id' => $header[0]->ID, 'title' => $header[0]->post_title];
        }

        // Check footer
        $footer_args = [
            'post_type' => 'elementor_library',
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => '_elementor_template_type', 'value' => 'footer']
            ]
        ];
        $footer = get_posts($footer_args);
        if (!empty($footer)) {
            $imported['footer'] = ['id' => $footer[0]->ID, 'title' => $footer[0]->post_title];
        }

        // Check homepage
        $homepage = get_option('page_on_front');
        if ($homepage && get_post($homepage)) {
            $imported['homepage'] = ['id' => $homepage, 'title' => get_the_title($homepage)];
        }

        // Check exclusive theme home (by option or meta)
        $theme_home = intval(get_option('maneli_theme_home_page_id'));
        if ($theme_home && get_post($theme_home)) {
            $imported['theme_home'] = ['id' => $theme_home, 'title' => get_the_title($theme_home)];
        } else {
            $theme_page = get_posts([
                'post_type'      => 'page',
                'posts_per_page' => 1,
                'meta_query'     => [
                    [
                        'key'   => '_maneli_theme_home',
                        'value' => 'yes',
                    ]
                ]
            ]);
            if (!empty($theme_page)) {
                update_option('maneli_theme_home_page_id', $theme_page[0]->ID);
                $imported['theme_home'] = ['id' => $theme_page[0]->ID, 'title' => get_the_title($theme_page[0]->ID)];
            }
        }

        return $imported;
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
                                    'col1_content' => 'مانلی خودرو بزرگ‌ترین پلتفرم خرید و فروش خودرو در ایران است.',
                                    'col2_title' => 'لینک‌های مفید',
                                    'col2_links' => [
                                        ['link_text' => 'درباره ما'],
                                        ['link_text' => 'تماس با ما'],
                                        ['link_text' => 'شرایط استفاده'],
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

    /**
     * Get bespoke Elementor theme homepage data
     */
    private static function get_theme_home_elementor_data() {
        return [
            // Hero section (two columns)
            [
                'id' => 'theme-hero-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#0f172a',
                    'padding' => ['top' => '80', 'right' => '0', 'bottom' => '80', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'theme-hero-col1-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 50, '_inline_size' => '50'],
                        'elements' => [
                            [
                                'id' => 'theme-hero-heading-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'قالب اختصاصی مانلی خودرو',
                                    'header_size' => 'h1',
                                    'title_color' => '#ffffff',
                                ],
                            ],
                            [
                                'id' => 'theme-hero-text-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'text-editor',
                                'settings' => [
                                    'editor' => 'تجربه خرید خودرو با طراحی اختصاصی، سریع و متناسب با برند مانلی.',
                                    'text_color' => '#cbd5e1',
                                ],
                            ],
                            [
                                'id' => 'theme-hero-buttons-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'button',
                                'settings' => [
                                    'text' => 'مشاهده خودروها',
                                    'button_type' => 'primary',
                                    'size' => 'lg',
                                ],
                            ],
                            [
                                'id' => 'theme-hero-ghost-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'button',
                                'settings' => [
                                    'text' => 'مشاوره رایگان',
                                    'button_type' => 'info',
                                    'size' => 'md',
                                    'background_color' => '#ffffff',
                                    'text_color' => '#0f172a',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'theme-hero-col2-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 50, '_inline_size' => '50'],
                        'elements' => [
                            [
                                'id' => 'theme-hero-image-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'image',
                                'settings' => [
                                    'image' => ['url' => ''],
                                    'align' => 'center',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Stats section
            [
                'id' => 'theme-stats-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#0b1224',
                    'padding' => ['top' => '40', 'right' => '0', 'bottom' => '40', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'theme-stats-col1-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 33, '_inline_size' => '33'],
                        'elements' => [
                            [
                                'id' => 'theme-stats-box1-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'icon-box',
                                'settings' => [
                                    'title_text' => '۲۴/۷ پشتیبانی',
                                    'description_text' => 'همراه شما در تمام مراحل خرید',
                                    'icon' => ['value' => 'eicon-headphones'],
                                    'title_color' => '#f8fafc',
                                    'description_color' => '#cbd5e1',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'theme-stats-col2-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 33, '_inline_size' => '33'],
                        'elements' => [
                            [
                                'id' => 'theme-stats-box2-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'icon-box',
                                'settings' => [
                                    'title_text' => 'تحویل سریع',
                                    'description_text' => 'تحویل در کمتر از یک ساعت',
                                    'icon' => ['value' => 'eicon-clock'],
                                    'title_color' => '#f8fafc',
                                    'description_color' => '#cbd5e1',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'theme-stats-col3-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 33, '_inline_size' => '33'],
                        'elements' => [
                            [
                                'id' => 'theme-stats-box3-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'icon-box',
                                'settings' => [
                                    'title_text' => 'اقساط منعطف',
                                    'description_text' => 'بدون ضامن، با شرایط ویژه',
                                    'icon' => ['value' => 'eicon-credit-card'],
                                    'title_color' => '#f8fafc',
                                    'description_color' => '#cbd5e1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Steps section
            [
                'id' => 'theme-steps-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#f8fafc',
                    'padding' => ['top' => '60', 'right' => '0', 'bottom' => '60', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'theme-steps-col-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 100, '_inline_size' => '100'],
                        'elements' => [
                            [
                                'id' => 'theme-steps-heading-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'سه گام تا تحویل خودرو',
                                    'header_size' => 'h2',
                                    'text_align' => 'center',
                                    'title_color' => '#0f172a',
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 'theme-steps-cols-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 100, '_inline_size' => '100'],
                        'elements' => [
                            [
                                'id' => 'theme-steps-inner-' . uniqid(),
                                'elType' => 'section',
                                'settings' => ['structure' => '33'],
                                'elements' => [
                                    [
                                        'id' => 'theme-step1-col-' . uniqid(),
                                        'elType' => 'column',
                                        'settings' => ['_column_size' => 33, '_inline_size' => '33'],
                                        'elements' => [
                                            [
                                                'id' => 'theme-step1-box-' . uniqid(),
                                                'elType' => 'widget',
                                                'widgetType' => 'icon-box',
                                                'settings' => [
                                                    'title_text' => 'ثبت درخواست',
                                                    'description_text' => 'مشخصات خودرو و شرایط را وارد کنید',
                                                    'icon' => ['value' => 'eicon-pencil'],
                                                    'title_color' => '#0f172a',
                                                    'description_color' => '#475569',
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'id' => 'theme-step2-col-' . uniqid(),
                                        'elType' => 'column',
                                        'settings' => ['_column_size' => 33, '_inline_size' => '33'],
                                        'elements' => [
                                            [
                                                'id' => 'theme-step2-box-' . uniqid(),
                                                'elType' => 'widget',
                                                'widgetType' => 'icon-box',
                                                'settings' => [
                                                    'title_text' => 'تایید و پیش‌پرداخت',
                                                    'description_text' => 'بررسی شرایط و پرداخت اولیه',
                                                    'icon' => ['value' => 'eicon-check-circle'],
                                                    'title_color' => '#0f172a',
                                                    'description_color' => '#475569',
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'id' => 'theme-step3-col-' . uniqid(),
                                        'elType' => 'column',
                                        'settings' => ['_column_size' => 33, '_inline_size' => '33'],
                                        'elements' => [
                                            [
                                                'id' => 'theme-step3-box-' . uniqid(),
                                                'elType' => 'widget',
                                                'widgetType' => 'icon-box',
                                                'settings' => [
                                                    'title_text' => 'تحویل خودرو',
                                                    'description_text' => 'سریع، امن و به نام شما',
                                                    'icon' => ['value' => 'eicon-car'],
                                                    'title_color' => '#0f172a',
                                                    'description_color' => '#475569',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Products section (reusing custom widget)
            [
                'id' => 'theme-products-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#ffffff',
                    'padding' => ['top' => '70', 'right' => '0', 'bottom' => '70', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'theme-products-col-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 100, '_inline_size' => '100'],
                        'elements' => [
                            [
                                'id' => 'theme-products-heading-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'پرفروش‌ترین خودروها',
                                    'header_size' => 'h2',
                                    'text_align' => 'center',
                                    'title_color' => '#0f172a',
                                ],
                            ],
                            [
                                'id' => 'theme-products-widget-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'maneli_products',
                                'settings' => [
                                    'products_per_page' => 8,
                                    'columns' => 4,
                                    'show_filters' => 'no',
                                    'orderby' => 'popularity',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Testimonials section
            [
                'id' => 'theme-testimonials-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#f8fafc',
                    'padding' => ['top' => '60', 'right' => '0', 'bottom' => '60', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'theme-testimonials-col-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 100, '_inline_size' => '100'],
                        'elements' => [
                            [
                                'id' => 'theme-testimonials-heading-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'مشتریان چه می‌گویند',
                                    'header_size' => 'h2',
                                    'text_align' => 'center',
                                    'title_color' => '#0f172a',
                                ],
                            ],
                            [
                                'id' => 'theme-testimonials-text-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'text-editor',
                                'settings' => [
                                    'editor' => 'دیدگاه‌های واقعی مشتریان درباره سرعت، شفافیت و کیفیت خدمات مانلی خودرو.',
                                    'text_color' => '#475569',
                                    'align' => 'center',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // CTA banner
            [
                'id' => 'theme-cta-' . uniqid(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#0f172a',
                    'padding' => ['top' => '50', 'right' => '0', 'bottom' => '50', 'left' => '0', 'unit' => 'px', 'isLinked' => false],
                ],
                'elements' => [
                    [
                        'id' => 'theme-cta-col-' . uniqid(),
                        'elType' => 'column',
                        'settings' => ['_column_size' => 100, '_inline_size' => '100'],
                        'elements' => [
                            [
                                'id' => 'theme-cta-heading-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'همین حالا شروع کنید',
                                    'header_size' => 'h2',
                                    'text_align' => 'center',
                                    'title_color' => '#ffffff',
                                ],
                            ],
                            [
                                'id' => 'theme-cta-button-' . uniqid(),
                                'elType' => 'widget',
                                'widgetType' => 'button',
                                'settings' => [
                                    'text' => 'درخواست مشاوره رایگان',
                                    'size' => 'lg',
                                    'align' => 'center',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

// Initialize
Maneli_Elementor_Import_Manager::init();
