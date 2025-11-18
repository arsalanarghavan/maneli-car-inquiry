<?php
/**
 * License Activation Page
 *
 * @package Maneli_Car_Inquiry
 */

if (!defined('ABSPATH')) {
    exit;
}

$license = Maneli_License::instance();
$license_status = $license->get_license_status();
$license_nonce = wp_create_nonce('maneli_license_nonce');
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card custom-card">
                <div class="card-header border-bottom">
                    <h4 class="card-title mb-0">
                        <i class="ri-key-line me-2"></i><?php echo esc_html__('License Activation', 'maneli-car-inquiry'); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($license_status['is_active']): ?>
                        <div class="alert alert-success">
                            <i class="ri-checkbox-circle-line me-2"></i>
                            <strong><?php echo esc_html__('License is active', 'maneli-car-inquiry'); ?></strong>
                            <?php if ($license_status['expiry_date']): ?>
                                <br>
                                <small><?php echo esc_html__('Expiry date:', 'maneli-car-inquiry'); ?> <?php echo esc_html(date_i18n('Y/m/d', strtotime($license_status['expiry_date']))); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="ri-alert-line me-2"></i>
                            <strong><?php echo esc_html__('License is inactive', 'maneli-car-inquiry'); ?></strong>
                            <p class="mb-0 mt-2"><?php echo esc_html__('The license is automatically checked based on the current domain. Click the "Activate License" button.', 'maneli-car-inquiry'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form id="license-activation-form" class="mt-4">
                        <?php wp_nonce_field('maneli_license_nonce', 'nonce'); ?>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="ri-information-line me-2"></i>
                                <strong><?php echo esc_html__('The license is automatically activated based on the current domain.', 'maneli-car-inquiry'); ?></strong>
                                <br>
                                <small><?php echo esc_html__('Current domain:', 'maneli-car-inquiry'); ?> <strong><?php 
                                    $current_domain = parse_url(home_url(), PHP_URL_HOST);
                                    $current_domain = preg_replace('/^www\./', '', $current_domain);
                                    echo esc_html($current_domain); 
                                ?></strong></small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="activate-license-btn">
                                <i class="ri-check-line me-1"></i><?php echo esc_html__('Activate License', 'maneli-car-inquiry'); ?>
                            </button>
                            <?php if ($license_status['is_active']): ?>
                                <button type="button" class="btn btn-secondary" id="check-license-btn">
                                    <i class="ri-refresh-line me-1"></i><?php echo esc_html__('Check License', 'maneli-car-inquiry'); ?>
                                </button>
                                <button type="button" class="btn btn-danger" id="deactivate-license-btn">
                                    <i class="ri-close-line me-1"></i><?php echo esc_html__('Deactivate', 'maneli-car-inquiry'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <div id="license-message" class="mt-3"></div>
                </div>
            </div>

            <?php if ($license_status['is_active']): ?>
                <div class="card custom-card mt-4">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0"><?php echo esc_html__('License Information', 'maneli-car-inquiry'); ?></h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4"><?php echo esc_html__('Status:', 'maneli-car-inquiry'); ?></dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-success"><?php echo esc_html__('Active', 'maneli-car-inquiry'); ?></span>
                            </dd>
                            
                            <?php if ($license_status['expiry_date']): ?>
                                <dt class="col-sm-4"><?php echo esc_html__('Expiry Date:', 'maneli-car-inquiry'); ?></dt>
                                <dd class="col-sm-8"><?php echo esc_html(date_i18n('Y/m/d H:i', strtotime($license_status['expiry_date']))); ?></dd>
                            <?php endif; ?>
                            
                            <dt class="col-sm-4"><?php echo esc_html__('Current Domain:', 'maneli-car-inquiry'); ?></dt>
                            <dd class="col-sm-8">
                                <?php 
                                $current_domain = parse_url(home_url(), PHP_URL_HOST);
                                $current_domain = preg_replace('/^www\./', '', $current_domain);
                                echo esc_html($current_domain); 
                                ?>
                            </dd>
                            
                            
                            <?php if ($license_status['is_demo']): ?>
                                <dt class="col-sm-4"><?php echo esc_html__('Mode:', 'maneli-car-inquiry'); ?></dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-info"><?php echo esc_html__('Demo', 'maneli-car-inquiry'); ?></span>
                                </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Activate license
    $('#license-activation-form').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#activate-license-btn');
        var $message = $('#license-message');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line me-1 ri-spin"></i><?php echo esc_js(__('Activating...', 'maneli-car-inquiry')); ?>');
        $message.html('');
        
        var formData = {
            action: 'maneli_activate_license',
            nonce: $('#license-activation-form input[name="nonce"]').val(),
        };
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $message.html('<div class="alert alert-success"><i class="ri-checkbox-circle-line me-2"></i>' + response.data.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $message.html('<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>' + (response.data.message || '<?php echo esc_js(__('Error activating license', 'maneli-car-inquiry')); ?>') + '</div>');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                $message.html('<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i><?php echo esc_js(__('Server connection error', 'maneli-car-inquiry')); ?></div>');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Check license
    $('#check-license-btn').on('click', function() {
        var $btn = $(this);
        var $message = $('#license-message');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line me-1 ri-spin"></i><?php echo esc_js(__('Checking...', 'maneli-car-inquiry')); ?>');
        $message.html('');
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_check_license',
                nonce: '<?php echo esc_js($license_nonce); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<div class="alert alert-success"><i class="ri-checkbox-circle-line me-2"></i>' + response.data.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $message.html('<div class="alert alert-warning"><i class="ri-alert-line me-2"></i>' + (response.data.message || '<?php echo esc_js(__('License is not valid', 'maneli-car-inquiry')); ?>') + '</div>');
                }
                $btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                $message.html('<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i><?php echo esc_js(__('Server connection error', 'maneli-car-inquiry')); ?></div>');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Deactivate license
    $('#deactivate-license-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to deactivate the license?', 'maneli-car-inquiry')); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var $message = $('#license-message');
        var originalText = $btn.html();
        
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line me-1 ri-spin"></i><?php echo esc_js(__('Deactivating...', 'maneli-car-inquiry')); ?>');
        $message.html('');
        
        $.ajax({
            url: maneli_ajax.url,
            type: 'POST',
            data: {
                action: 'maneli_deactivate_license',
                nonce: '<?php echo esc_js($license_nonce); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $message.html('<div class="alert alert-success"><i class="ri-checkbox-circle-line me-2"></i>' + response.data.message + '</div>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $message.html('<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>' + (response.data.message || '<?php echo esc_js(__('Error deactivating license', 'maneli-car-inquiry')); ?>') + '</div>');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                $message.html('<div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i><?php echo esc_js(__('Server connection error', 'maneli-car-inquiry')); ?></div>');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

