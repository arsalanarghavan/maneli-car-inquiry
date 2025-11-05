<?php
/**
 * SMS History Modal - Shared Template
 * 
 * This template is used across all pages that need to display SMS history.
 * It includes the modal structure and loading states.
 *
 * @package Maneli_Car_Inquiry/Templates/Partials
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- SMS History Modal -->
<div class="modal fade" id="sms-history-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="la la-sms me-2"></i>
                    <?php esc_html_e('SMS History', 'maneli-car-inquiry'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="sms-history-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'maneli-car-inquiry'); ?></span>
                    </div>
                    <p class="mt-2 text-muted"><?php esc_html_e('Loading SMS history...', 'maneli-car-inquiry'); ?></p>
                </div>
                <div id="sms-history-content" class="maneli-initially-hidden">
                    <div id="sms-history-table-container"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?php esc_html_e('Close', 'maneli-car-inquiry'); ?></button>
            </div>
        </div>
    </div>
</div>

