<?php
/**
 * AutoPuzzle White Label Settings Page
 * 
 * Admin settings page for white label/branding customization
 * 
 * @package AutoPuzzle
 * @subpackage Admin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autopuzzle_White_Label_Settings {
    
    /**
     * Hook name for this settings tab
     */
    const HOOK_NAME = 'autopuzzle_white_label_settings';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_ajax_autopuzzle_save_branding_settings', [ $this, 'save_branding_settings' ] );
        add_action( 'wp_ajax_autopuzzle_upload_branding_logo', [ $this, 'handle_logo_upload' ] );
    }
    
    /**
     * Render the white label settings page
     * 
     * @return void
     */
    public static function render() {
        // Load media uploader for logo pickers
        if ( function_exists( 'wp_enqueue_media' ) ) {
            wp_enqueue_media();
        }

        $settings   = Autopuzzle_Branding_Helper::get_all_settings();
        $tab        = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
        $brand_logo = esc_url( autopuzzle_logo( 'main' ) );
        $brand_name = esc_html( autopuzzle_brand_name() );
        
        ?>
        <div class="autopuzzle-white-label-settings">
            <?php if ( isset( $_GET['reset'] ) && (int) $_GET['reset'] === 1 ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Branding settings have been reset to defaults.', 'autopuzzle' ); ?></p></div>
            <?php endif; ?>

            <div class="autopuzzle-settings-hero">
                <div class="ap-hero-left">
                    <div class="ap-brand-chip">
                        <span class="ap-chip-dot"></span>
                        <img src="<?php echo $brand_logo; ?>" alt="<?php echo $brand_name; ?>" class="ap-brand-chip__logo">
                        <div class="ap-brand-chip__text">
                            <strong><?php echo $brand_name; ?></strong>
                            <small><?php esc_html_e( 'White Label & Branding', 'autopuzzle' ); ?></small>
                        </div>
                    </div>
                    <h1 class="ap-hero-title"><?php esc_html_e( 'White Label Settings', 'autopuzzle' ); ?></h1>
                    <p class="ap-hero-desc"><?php esc_html_e( 'Design how your brand appears across dashboard, auth pages, and public templates.', 'autopuzzle' ); ?></p>
                    <ul class="ap-hero-meta">
                        <li><?php esc_html_e( 'Applies to dashboard & auth headers', 'autopuzzle' ); ?></li>
                        <li><?php esc_html_e( 'Multi-language ready via translation files', 'autopuzzle' ); ?></li>
                        <li><?php esc_html_e( 'Logo variants: main, light, dark, favicon', 'autopuzzle' ); ?></li>
                    </ul>
                </div>
                <div class="ap-hero-right">
                    <div class="ap-preview-card">
                        <div class="ap-preview-header">
                            <span class="ap-preview-dot dot-red"></span>
                            <span class="ap-preview-dot dot-amber"></span>
                            <span class="ap-preview-dot dot-green"></span>
                        </div>
                        <div class="ap-preview-body">
                            <img src="<?php echo $brand_logo; ?>" alt="<?php echo $brand_name; ?>" class="ap-preview-logo">
                            <p class="ap-preview-text"><?php echo esc_html( $settings['brand_tagline'] ); ?></p>
                            <div class="ap-preview-colors">
                                <span class="ap-color-chip" style="background: <?php echo esc_attr( $settings['primary_color'] ); ?>"></span>
                                <span class="ap-color-chip" style="background: <?php echo esc_attr( $settings['secondary_color'] ); ?>"></span>
                                <span class="ap-color-chip" style="background: <?php echo esc_attr( $settings['accent_color'] ); ?>"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="autopuzzle-settings-tabs">
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'general' ) ); ?>" class="tab-link <?php echo $tab === 'general' ? 'active' : ''; ?>">
                    <i class="la la-cog"></i> <?php esc_html_e( 'General', 'autopuzzle' ); ?>
                </a>
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'branding' ) ); ?>" class="tab-link <?php echo $tab === 'branding' ? 'active' : ''; ?>">
                    <i class="la la-palette"></i> <?php esc_html_e( 'Branding', 'autopuzzle' ); ?>
                </a>
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'logos' ) ); ?>" class="tab-link <?php echo $tab === 'logos' ? 'active' : ''; ?>">
                    <i class="la la-image"></i> <?php esc_html_e( 'Logos & Images', 'autopuzzle' ); ?>
                </a>
                <a href="<?php echo esc_url( add_query_arg( 'tab', 'contact' ) ); ?>" class="tab-link <?php echo $tab === 'contact' ? 'active' : ''; ?>">
                    <i class="la la-phone"></i> <?php esc_html_e( 'Contact Info', 'autopuzzle' ); ?>
                </a>
            </div>
            
            <form method="post" id="autopuzzle-branding-form" class="autopuzzle-settings-form">
                <?php wp_nonce_field( 'autopuzzle_branding_nonce' ); ?>

                <div id="autopuzzle-branding-notice" style="display:none;" class="notice"><p></p></div>
                
                <div class="autopuzzle-tab-content" id="tab-general" style="display: <?php echo $tab === 'general' ? 'block' : 'none'; ?>;">
                    <?php self::render_general_tab( $settings ); ?>
                </div>
                
                <div class="autopuzzle-tab-content" id="tab-branding" style="display: <?php echo $tab === 'branding' ? 'block' : 'none'; ?>;">
                    <?php self::render_branding_tab( $settings ); ?>
                </div>
                
                <div class="autopuzzle-tab-content" id="tab-logos" style="display: <?php echo $tab === 'logos' ? 'block' : 'none'; ?>;">
                    <?php self::render_logos_tab( $settings ); ?>
                </div>
                
                <div class="autopuzzle-tab-content" id="tab-contact" style="display: <?php echo $tab === 'contact' ? 'block' : 'none'; ?>;">
                    <?php self::render_contact_tab( $settings ); ?>
                </div>
                
                <div class="autopuzzle-settings-footer">
                    <button type="submit" class="button button-primary">
                        <i class="la la-save"></i> <?php esc_html_e( 'Save Settings', 'autopuzzle' ); ?>
                    </button>
                    <button type="button" class="button" id="autopuzzle-reset-defaults">
                        <i class="la la-refresh"></i> <?php esc_html_e( 'Reset to Defaults', 'autopuzzle' ); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <style>
            @font-face {
                font-family: 'IranSans';
                src: url('<?php echo esc_url( AUTOPUZZLE_PLUGIN_URL . 'assets/fonts/iransans/woff2/IRANSansWeb(FaNum).woff2' ); ?>') format('woff2');
                font-weight: 400;
                font-display: swap;
            }

            @font-face {
                font-family: 'IranSans';
                src: url('<?php echo esc_url( AUTOPUZZLE_PLUGIN_URL . 'assets/fonts/iransans/woff2/IRANSansWeb(FaNum)_Bold.woff2' ); ?>') format('woff2');
                font-weight: 700;
                font-display: swap;
            }

            .autopuzzle-white-label-settings {
                max-width: 1280px;
                background: linear-gradient(135deg, #0f172a 0%, #111827 25%, #0b1220 60%, #0f172a 100%);
                padding: 28px;
                border-radius: 16px;
                margin-top: 20px;
                box-shadow: 0 25px 70px rgba(0,0,0,0.28);
                color: #e5e7eb;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .autopuzzle-settings-hero {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 24px;
                align-items: stretch;
                margin-bottom: 24px;
            }

            .ap-hero-left {
                background: rgba(255,255,255,0.02);
                border: 1px solid rgba(255,255,255,0.06);
                border-radius: 14px;
                padding: 20px 22px;
                backdrop-filter: blur(6px);
            }

            .ap-brand-chip {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 10px 12px;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 999px;
                margin-bottom: 14px;
                position: relative;
            }

            .ap-chip-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #22d3ee;
                box-shadow: 0 0 0 6px rgba(34,211,238,0.12);
            }

            .ap-brand-chip__logo {
                width: 32px;
                height: 32px;
                object-fit: contain;
                border-radius: 6px;
                background: #fff;
                padding: 3px;
            }

            .ap-brand-chip__text {
                display: flex;
                flex-direction: column;
                line-height: 1.2;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .ap-brand-chip__text small { 
                color: #9ca3af;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .ap-hero-title {
                margin: 4px 0 10px;
                font-size: 26px;
                color: #f9fafb;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                font-weight: 700;
            }

            .ap-hero-desc {
                margin: 0 0 12px;
                color: #cbd5e1;
                max-width: 720px;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .ap-hero-meta {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }

            .ap-hero-meta li {
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 10px;
                padding: 8px 12px;
                color: #d1d5db;
                font-size: 13px;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .ap-hero-right {
                display: flex;
                align-items: stretch;
            }

            .ap-preview-card {
                width: 100%;
                border-radius: 14px;
                background: linear-gradient(145deg, rgba(255,255,255,0.07), rgba(255,255,255,0.02));
                border: 1px solid rgba(255,255,255,0.08);
                box-shadow: 0 10px 30px rgba(0,0,0,0.24);
                overflow: hidden;
            }

            .ap-preview-header {
                display: flex;
                gap: 6px;
                padding: 10px 12px;
                background: rgba(255,255,255,0.06);
            }

            .ap-preview-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                display: inline-block;
            }
            .dot-red { background: #ef4444; }
            .dot-amber { background: #f59e0b; }
            .dot-green { background: #22c55e; }

            .ap-preview-body {
                padding: 20px 18px 22px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
                text-align: center;
                color: #e5e7eb;
            }

            .ap-preview-logo {
                max-width: 180px;
                max-height: 64px;
                object-fit: contain;
                background: #fff;
                padding: 10px;
                border-radius: 10px;
            }

            .ap-preview-text {
                color: #cbd5e1;
                margin: 0;
                font-size: 14px;
            }

            .ap-preview-colors {
                display: flex;
                gap: 8px;
                margin-top: 6px;
            }

            .ap-color-chip {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                border: 1px solid rgba(255,255,255,0.16);
                box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
            }

            .autopuzzle-settings-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 16px;
                border-bottom: 1px solid rgba(255,255,255,0.08);
                padding-bottom: 6px;
                flex-wrap: wrap;
            }
            
            .tab-link {
                padding: 10px 14px;
                color: #cbd5e1;
                text-decoration: none;
                border-bottom: 2px solid transparent;
                transition: all 0.25s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                border-radius: 10px;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            
            .tab-link:hover {
                color: #f8fafc;
                background: rgba(255,255,255,0.05);
            }
            
            .tab-link.active {
                color: #22d3ee;
                border-bottom-color: #22d3ee;
                background: rgba(34,211,238,0.08);
            }
            
            .autopuzzle-tab-content {
                display: none;
            }
            
            .autopuzzle-settings-form {
                background: rgba(255,255,255,0.02);
                padding: 20px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,0.06);
            }
            
            .autopuzzle-form-group {
                margin-bottom: 16px;
            }
            
            .autopuzzle-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #e5e7eb;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            
            .autopuzzle-form-group input,
            .autopuzzle-form-group textarea {
                width: 100%;
                max-width: 520px;
                background: rgba(255,255,255,0.06);
                border: 1px solid rgba(255,255,255,0.1);
                color: #e5e7eb;
                border-radius: 10px;
                padding: 10px 12px;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }

            .autopuzzle-form-group small {
                color: #9ca3af;
                font-family: 'IranSans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            
            .autopuzzle-form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 18px;
            }
            
            @media (max-width: 900px) {
                .autopuzzle-settings-hero {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 768px) {
                .autopuzzle-form-row {
                    grid-template-columns: 1fr;
                }
            }
            
            .autopuzzle-settings-footer {
                display: flex;
                gap: 10px;
                margin-top: 18px;
                flex-wrap: wrap;
            }
            
            .autopuzzle-settings-footer button {
                display: flex;
                align-items: center;
                gap: 8px;
                border-radius: 10px;
                border: none;
                padding: 10px 14px;
                cursor: pointer;
            }

            .autopuzzle-settings-footer .button-primary {
                background: linear-gradient(135deg, #22d3ee, #3b82f6);
                color: #0b1220;
                font-weight: 600;
            }

            .autopuzzle-settings-footer .button {
                background: rgba(255,255,255,0.06);
                color: #e5e7eb;
                border: 1px solid rgba(255,255,255,0.1);
            }

            .notice {
                margin: 0 0 12px;
                border-radius: 10px;
            }
        </style>
        
        <script>
        jQuery(function($) {
            var $notice = $('#autopuzzle-branding-notice');

            function showNotice(message, type) {
                $notice.removeClass('notice-success notice-error').addClass('notice notice-' + type);
                $notice.find('p').text(message);
                $notice.show();
            }

            // Tab switching
            $('.tab-link').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).attr('href').split('tab=')[1];
                
                $('.tab-link').removeClass('active');
                $(this).addClass('active');
                
                $('.autopuzzle-tab-content').hide();
                $('#tab-' + tab).show();
            });
            
            // Form submission
            $('#autopuzzle-branding-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'autopuzzle_save_branding_settings');
                formData.append('_ajax_nonce', $('[name="_wpnonce"]').val());

                // Simple client-side validation for required fields
                var brandName = $('#brand_name').val();
                if (!brandName) {
                    showNotice('<?php esc_html_e( 'Brand name is required.', 'autopuzzle' ); ?>', 'error');
                    return;
                }
                
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response && response.success) {
                            showNotice(response.data && response.data.message ? response.data.message : '<?php esc_html_e( 'Settings saved successfully!', 'autopuzzle' ); ?>', 'success');
                        } else {
                            var msg = (response && response.data && response.data.message) ? response.data.message : '<?php esc_html_e( 'Error saving settings!', 'autopuzzle' ); ?>';
                            showNotice(msg, 'error');
                        }
                    },
                    error: function() {
                        showNotice('<?php esc_html_e( 'Network error while saving settings.', 'autopuzzle' ); ?>', 'error');
                    }
                });
            });
            
            // Reset to defaults
            $('#autopuzzle-reset-defaults').on('click', function(e) {
                e.preventDefault();
                if (confirm('<?php esc_html_e( 'Are you sure you want to reset to default settings?', 'autopuzzle' ); ?>')) {
                    window.location.href = '<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'autopuzzle_reset_branding' ), 'autopuzzle_reset_branding_nonce' ) ); ?>';
                }
            });

            // Media uploader for logos
            var autopuzzleMediaFrame;
            $('.autopuzzle-upload-logo').on('click', function(e) {
                e.preventDefault();
                var field = $(this).data('field');

                // Re-use frame when possible
                if (autopuzzleMediaFrame) {
                    autopuzzleMediaFrame.open();
                    autopuzzleMediaFrame.field = field;
                    return;
                }

                autopuzzleMediaFrame = wp.media({
                    title: '<?php echo esc_js( __( 'Select or Upload Logo', 'autopuzzle' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Use this logo', 'autopuzzle' ) ); ?>' },
                    multiple: false
                });

                autopuzzleMediaFrame.field = field;

                autopuzzleMediaFrame.on('select', function() {
                    var attachment = autopuzzleMediaFrame.state().get('selection').first().toJSON();
                    if (attachment && attachment.url) {
                        $('#' + autopuzzleMediaFrame.field).val(attachment.url);
                    }
                });

                autopuzzleMediaFrame.open();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render general tab
     */
    private static function render_general_tab( $settings ) {
        ?>
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="brand_name"><?php esc_html_e( 'Brand Name (English)', 'autopuzzle' ); ?></label>
                <input type="text" id="brand_name" name="branding[brand_name]" value="<?php echo esc_attr( $settings['brand_name'] ); ?>" class="regular-text">
                <small><?php esc_html_e( 'Used throughout the plugin interface', 'autopuzzle' ); ?></small>
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="brand_name_persian"><?php esc_html_e( 'Brand Name (Persian)', 'autopuzzle' ); ?></label>
                <input type="text" id="brand_name_persian" name="branding[brand_name_persian]" value="<?php echo esc_attr( $settings['brand_name_persian'] ); ?>" class="regular-text" dir="rtl">
                <small><?php esc_html_e( 'Used in Persian/RTL interfaces', 'autopuzzle' ); ?></small>
            </div>
        </div>
        
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="brand_tagline"><?php esc_html_e( 'Tagline (English)', 'autopuzzle' ); ?></label>
                <input type="text" id="brand_tagline" name="branding[brand_tagline]" value="<?php echo esc_attr( $settings['brand_tagline'] ); ?>" class="regular-text">
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="brand_tagline_persian"><?php esc_html_e( 'Tagline (Persian)', 'autopuzzle' ); ?></label>
                <input type="text" id="brand_tagline_persian" name="branding[brand_tagline_persian]" value="<?php echo esc_attr( $settings['brand_tagline_persian'] ); ?>" class="regular-text" dir="rtl">
            </div>
        </div>
        
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="company_name"><?php esc_html_e( 'Company Name (English)', 'autopuzzle' ); ?></label>
                <input type="text" id="company_name" name="branding[company_name]" value="<?php echo esc_attr( $settings['company_name'] ); ?>" class="regular-text">
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="company_name_persian"><?php esc_html_e( 'Company Name (Persian)', 'autopuzzle' ); ?></label>
                <input type="text" id="company_name_persian" name="branding[company_name_persian]" value="<?php echo esc_attr( $settings['company_name_persian'] ); ?>" class="regular-text" dir="rtl">
            </div>
        </div>
        <?php
    }
    
    /**
     * Render branding tab
     */
    private static function render_branding_tab( $settings ) {
        ?>
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="primary_color"><?php esc_html_e( 'Primary Color', 'autopuzzle' ); ?></label>
                <input type="color" id="primary_color" name="branding[primary_color]" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" style="width: 100px; height: 40px;">
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="secondary_color"><?php esc_html_e( 'Secondary Color', 'autopuzzle' ); ?></label>
                <input type="color" id="secondary_color" name="branding[secondary_color]" value="<?php echo esc_attr( $settings['secondary_color'] ); ?>" style="width: 100px; height: 40px;">
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="accent_color"><?php esc_html_e( 'Accent Color', 'autopuzzle' ); ?></label>
                <input type="color" id="accent_color" name="branding[accent_color]" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" style="width: 100px; height: 40px;">
            </div>
        </div>
        
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="copyright_text"><?php esc_html_e( 'Copyright Text (English)', 'autopuzzle' ); ?></label>
                <textarea id="copyright_text" name="branding[copyright_text]" rows="3" class="regular-text"><?php echo esc_textarea( $settings['copyright_text'] ); ?></textarea>
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="copyright_text_persian"><?php esc_html_e( 'Copyright Text (Persian)', 'autopuzzle' ); ?></label>
                <textarea id="copyright_text_persian" name="branding[copyright_text_persian]" rows="3" class="regular-text" dir="rtl"><?php echo esc_textarea( $settings['copyright_text_persian'] ); ?></textarea>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render logos tab
     */
    private static function render_logos_tab( $settings ) {
        ?>
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="logo_url"><?php esc_html_e( 'Main Logo', 'autopuzzle' ); ?></label>
                <input type="text" id="logo_url" name="branding[logo_url]" value="<?php echo esc_attr( $settings['logo_url'] ); ?>" class="regular-text">
                <button type="button" class="button autopuzzle-upload-logo" data-field="logo_url"><?php esc_html_e( 'Upload', 'autopuzzle' ); ?></button>
                <?php if ( ! empty( $settings['logo_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $settings['logo_url'] ); ?>" style="max-width: 200px; margin-top: 10px;">
                <?php endif; ?>
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="logo_light_url"><?php esc_html_e( 'Light Logo', 'autopuzzle' ); ?></label>
                <input type="text" id="logo_light_url" name="branding[logo_light_url]" value="<?php echo esc_attr( $settings['logo_light_url'] ); ?>" class="regular-text">
                <button type="button" class="button autopuzzle-upload-logo" data-field="logo_light_url"><?php esc_html_e( 'Upload', 'autopuzzle' ); ?></button>
                <?php if ( ! empty( $settings['logo_light_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $settings['logo_light_url'] ); ?>" style="max-width: 200px; margin-top: 10px;">
                <?php endif; ?>
            </div>
        </div>
        
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="logo_dark_url"><?php esc_html_e( 'Dark Logo', 'autopuzzle' ); ?></label>
                <input type="text" id="logo_dark_url" name="branding[logo_dark_url]" value="<?php echo esc_attr( $settings['logo_dark_url'] ); ?>" class="regular-text">
                <button type="button" class="button autopuzzle-upload-logo" data-field="logo_dark_url"><?php esc_html_e( 'Upload', 'autopuzzle' ); ?></button>
                <?php if ( ! empty( $settings['logo_dark_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $settings['logo_dark_url'] ); ?>" style="max-width: 200px; margin-top: 10px;">
                <?php endif; ?>
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="favicon_url"><?php esc_html_e( 'Favicon', 'autopuzzle' ); ?></label>
                <input type="text" id="favicon_url" name="branding[favicon_url]" value="<?php echo esc_attr( $settings['favicon_url'] ); ?>" class="regular-text">
                <button type="button" class="button autopuzzle-upload-logo" data-field="favicon_url"><?php esc_html_e( 'Upload', 'autopuzzle' ); ?></button>
                <?php if ( ! empty( $settings['favicon_url'] ) ) : ?>
                    <img src="<?php echo esc_url( $settings['favicon_url'] ); ?>" style="max-width: 50px; margin-top: 10px;">
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render contact tab
     */
    private static function render_contact_tab( $settings ) {
        ?>
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="email"><?php esc_html_e( 'Email Address', 'autopuzzle' ); ?></label>
                <input type="email" id="email" name="branding[email]" value="<?php echo esc_attr( $settings['email'] ); ?>" class="regular-text">
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="phone"><?php esc_html_e( 'Phone Number', 'autopuzzle' ); ?></label>
                <input type="tel" id="phone" name="branding[phone]" value="<?php echo esc_attr( $settings['phone'] ); ?>" class="regular-text">
            </div>
        </div>
        
        <div class="autopuzzle-form-row">
            <div class="autopuzzle-form-group">
                <label for="support_email"><?php esc_html_e( 'Support Email', 'autopuzzle' ); ?></label>
                <input type="email" id="support_email" name="branding[support_email]" value="<?php echo esc_attr( $settings['support_email'] ); ?>" class="regular-text">
            </div>
            
            <div class="autopuzzle-form-group">
                <label for="support_phone"><?php esc_html_e( 'Support Phone', 'autopuzzle' ); ?></label>
                <input type="tel" id="support_phone" name="branding[support_phone]" value="<?php echo esc_attr( $settings['support_phone'] ); ?>" class="regular-text">
            </div>
        </div>
        
        <div class="autopuzzle-form-group">
            <label for="website"><?php esc_html_e( 'Website URL', 'autopuzzle' ); ?></label>
            <input type="url" id="website" name="branding[website]" value="<?php echo esc_attr( $settings['website'] ); ?>" class="regular-text">
        </div>
        <?php
    }
    
    /**
     * Handle saving branding settings via AJAX
     */
    public function save_branding_settings() {
        if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'autopuzzle_branding_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed', 'autopuzzle' ) ] );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'autopuzzle' ) ] );
        }
        $raw       = isset( $_POST['branding'] ) ? wp_unslash( $_POST['branding'] ) : [];
        $defaults  = Autopuzzle_Branding_Helper::get_defaults();

        $branding  = [];
        $branding['brand_name']             = sanitize_text_field( $raw['brand_name'] ?? $defaults['brand_name'] );
        $branding['brand_name_persian']     = sanitize_text_field( $raw['brand_name_persian'] ?? $defaults['brand_name_persian'] );
        $branding['brand_tagline']          = sanitize_text_field( $raw['brand_tagline'] ?? $defaults['brand_tagline'] );
        $branding['brand_tagline_persian']  = sanitize_text_field( $raw['brand_tagline_persian'] ?? $defaults['brand_tagline_persian'] );
        $branding['company_name']           = sanitize_text_field( $raw['company_name'] ?? $defaults['company_name'] );
        $branding['company_name_persian']   = sanitize_text_field( $raw['company_name_persian'] ?? $defaults['company_name_persian'] );

        // URLs/logos
        $branding['logo_url']       = esc_url_raw( $raw['logo_url'] ?? '' );
        $branding['logo_light_url'] = esc_url_raw( $raw['logo_light_url'] ?? '' );
        $branding['logo_dark_url']  = esc_url_raw( $raw['logo_dark_url'] ?? '' );
        $branding['favicon_url']    = esc_url_raw( $raw['favicon_url'] ?? '' );
        $branding['website']        = esc_url_raw( $raw['website'] ?? $defaults['website'] );

        // Colors
        $branding['primary_color']   = sanitize_hex_color( $raw['primary_color'] ?? '' ) ?: $defaults['primary_color'];
        $branding['secondary_color'] = sanitize_hex_color( $raw['secondary_color'] ?? '' ) ?: $defaults['secondary_color'];
        $branding['accent_color']    = sanitize_hex_color( $raw['accent_color'] ?? '' ) ?: $defaults['accent_color'];

        // Contact
        $branding['email']          = sanitize_email( $raw['email'] ?? $defaults['email'] );
        $branding['support_email']  = sanitize_email( $raw['support_email'] ?? $defaults['support_email'] );

        $branding['phone']          = isset( $raw['phone'] ) ? preg_replace( '/[^0-9\+\-\(\)\s]/', '', $raw['phone'] ) : $defaults['phone'];
        $branding['support_phone']  = isset( $raw['support_phone'] ) ? preg_replace( '/[^0-9\+\-\(\)\s]/', '', $raw['support_phone'] ) : $defaults['support_phone'];

        // Copy text
        $branding['copyright_text']         = sanitize_text_field( $raw['copyright_text'] ?? $defaults['copyright_text'] );
        $branding['copyright_text_persian'] = sanitize_text_field( $raw['copyright_text_persian'] ?? $defaults['copyright_text_persian'] );

        // Toggles (if added later)
        $branding['enable_footer_branding'] = isset( $raw['enable_footer_branding'] ) ? (bool) $raw['enable_footer_branding'] : (bool) $defaults['enable_footer_branding'];
        $branding['enable_admin_branding']  = isset( $raw['enable_admin_branding'] ) ? (bool) $raw['enable_admin_branding'] : (bool) $defaults['enable_admin_branding'];

        if ( Autopuzzle_Branding_Helper::update_settings( $branding ) ) {
            wp_send_json_success( [ 'message' => __( 'Settings saved successfully', 'autopuzzle' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Error saving settings', 'autopuzzle' ) ] );
        }
    }
    
    /**
     * Handle logo upload via AJAX
     */
    public function handle_logo_upload() {
        if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'autopuzzle_branding_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed', 'autopuzzle' ) ] );
        }
        
        if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'wp_handle_upload' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions', 'autopuzzle' ) ] );
        }
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        if ( ! isset( $_FILES['logo'] ) ) {
            wp_send_json_error( [ 'message' => __( 'No file uploaded', 'autopuzzle' ) ] );
        }

        $file       = $_FILES['logo'];
        $size_limit = apply_filters( 'autopuzzle_branding_logo_size_limit', 2 * 1024 * 1024 ); // 2MB default
        $allowed_mimes = apply_filters( 'autopuzzle_branding_allowed_mimes', [
            'jpg|jpeg' => 'image/jpeg',
            'png'      => 'image/png',
            'gif'      => 'image/gif',
            'svg'      => 'image/svg+xml',
            'ico'      => 'image/vnd.microsoft.icon',
            'webp'     => 'image/webp',
        ] );

        if ( ! empty( $file['size'] ) && $file['size'] > $size_limit ) {
            wp_send_json_error( [ 'message' => sprintf( __( 'File is too large. Max size: %s MB', 'autopuzzle' ), intval( $size_limit / 1024 / 1024 ) ) ] );
        }

        $checked_type = wp_check_filetype( $file['name'], $allowed_mimes );
        if ( empty( $checked_type['type'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid file type. Allowed: jpg, png, gif, svg, ico, webp.', 'autopuzzle' ) ] );
        }
        
        $uploaded = wp_handle_upload( $file, [ 'test_form' => false, 'mimes' => $allowed_mimes ] );
        
        if ( isset( $uploaded['error'] ) ) {
            wp_send_json_error( [ 'message' => $uploaded['error'] ] );
        }
        
        wp_send_json_success( [ 'url' => $uploaded['url'] ] );
    }
}

// Initialize the helper class
Autopuzzle_Branding_Helper::init();
