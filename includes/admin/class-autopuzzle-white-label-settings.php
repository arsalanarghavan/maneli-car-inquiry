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
        $settings = Autopuzzle_Branding_Helper::get_all_settings();
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
        
        ?>
        <div class="autopuzzle-white-label-settings">
            <div class="autopuzzle-settings-header">
                <h1><?php esc_html_e( 'White Label Settings', 'autopuzzle' ); ?></h1>
                <p><?php esc_html_e( 'Customize your branding and appearance', 'autopuzzle' ); ?></p>
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
            .autopuzzle-white-label-settings {
                max-width: 1200px;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                margin-top: 20px;
            }
            
            .autopuzzle-settings-header {
                margin-bottom: 30px;
                border-bottom: 2px solid #f0f0f0;
                padding-bottom: 20px;
            }
            
            .autopuzzle-settings-header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            
            .autopuzzle-settings-header p {
                color: #666;
                margin: 0;
            }
            
            .autopuzzle-settings-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 30px;
                border-bottom: 2px solid #e0e0e0;
            }
            
            .tab-link {
                padding: 12px 20px;
                color: #666;
                text-decoration: none;
                border-bottom: 3px solid transparent;
                margin-bottom: -2px;
                transition: all 0.3s;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .tab-link:hover {
                color: #333;
            }
            
            .tab-link.active {
                color: #007bff;
                border-bottom-color: #007bff;
            }
            
            .autopuzzle-tab-content {
                display: none;
            }
            
            .autopuzzle-settings-form {
                background: #fafafa;
                padding: 20px;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            
            .autopuzzle-form-group {
                margin-bottom: 20px;
            }
            
            .autopuzzle-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #333;
            }
            
            .autopuzzle-form-group input,
            .autopuzzle-form-group textarea {
                width: 100%;
                max-width: 500px;
            }
            
            .autopuzzle-form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            
            @media (max-width: 768px) {
                .autopuzzle-form-row {
                    grid-template-columns: 1fr;
                }
            }
            
            .autopuzzle-settings-footer {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            
            .autopuzzle-settings-footer button {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        </style>
        
        <script>
        jQuery(function($) {
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
                
                $.ajax({
                    type: 'POST',
                    url: ajaxurl,
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('<?php esc_html_e( 'Settings saved successfully!', 'autopuzzle' ); ?>');
                        } else {
                            alert('<?php esc_html_e( 'Error saving settings!', 'autopuzzle' ); ?>');
                        }
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
            wp_send_json_error( [ 'message' => 'Security check failed' ] );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ] );
        }
        
        $branding = isset( $_POST['branding'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['branding'] ) ) : [];
        
        if ( Autopuzzle_Branding_Helper::update_settings( $branding ) ) {
            wp_send_json_success( [ 'message' => 'Settings saved successfully' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Error saving settings' ] );
        }
    }
    
    /**
     * Handle logo upload via AJAX
     */
    public function handle_logo_upload() {
        if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'autopuzzle_branding_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed' ] );
        }
        
        if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'wp_handle_upload' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ] );
        }
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        if ( ! isset( $_FILES['logo'] ) ) {
            wp_send_json_error( [ 'message' => 'No file uploaded' ] );
        }
        
        $uploaded = wp_handle_upload( $_FILES['logo'], [ 'test_form' => false ] );
        
        if ( isset( $uploaded['error'] ) ) {
            wp_send_json_error( [ 'message' => $uploaded['error'] ] );
        }
        
        wp_send_json_success( [ 'url' => $uploaded['url'] ] );
    }
}

// Initialize the helper class
Autopuzzle_Branding_Helper::init();
