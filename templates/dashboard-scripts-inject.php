<?php
if (!defined('ABSPATH')) exit;

function autopuzzle_inject_inquiry_scripts($dashboard_html, $page, $inquiry_id, $cash_inquiry_id) {
    if (!$page && $inquiry_id === 0 && $cash_inquiry_id === 0) {
        return $dashboard_html;
    }
    
    $scripts_html = PHP_EOL . '<!-- Inquiry Scripts -->' . PHP_EOL;
    // Use local SweetAlert2 if available
    $sweetalert2_path = AUTOPUZZLE_PLUGIN_PATH . 'assets/libs/sweetalert2/sweetalert2.min.js';
    if (file_exists($sweetalert2_path)) {
        $scripts_html .= '<link rel="stylesheet" href="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.css') . '">' . PHP_EOL;
        $scripts_html .= '<script src="' . esc_url(AUTOPUZZLE_PLUGIN_URL . 'assets/libs/sweetalert2/sweetalert2.min.js') . '"></script>' . PHP_EOL;
    } else {
        // Fallback to CDN
        $scripts_html .= '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>' . PHP_EOL;
    }
    // Note: Select2 is not available locally, keep CDN (but not blocked)
    $scripts_html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">' . PHP_EOL;
    $scripts_html .= '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>' . PHP_EOL;
    $scripts_html .= '<script src="' . AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/inquiry-lists.js?v=' . time() . '"></script>' . PHP_EOL;
    
    if ($inquiry_id > 0) {
        $scripts_html .= '<script src="' . AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/installment-report.js?v=' . time() . '"></script>' . PHP_EOL;
    }
    if ($cash_inquiry_id > 0) {
        $scripts_html .= '<script src="' . AUTOPUZZLE_PLUGIN_URL . 'assets/js/frontend/cash-report.js?v=' . time() . '"></script>' . PHP_EOL;
    }
    
    $experts_query = get_users(['role' => 'autopuzzle_expert', 'orderby' => 'display_name', 'order' => 'ASC']);
    $experts_list = [];
    foreach ($experts_query as $expert) {
        $experts_list[] = ['id' => $expert->ID, 'name' => $expert->display_name ?: $expert->user_login];
    }
    
    $options = get_option('autopuzzle_inquiry_all_options', []);
    $localize_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'experts' => $experts_list,
        'nonces' => [
            'cash_filter' => wp_create_nonce('autopuzzle_cash_inquiry_filter_nonce'),
            'inquiry_filter' => wp_create_nonce('autopuzzle_inquiry_filter_nonce'),
            'assign_expert' => wp_create_nonce('autopuzzle_inquiry_assign_expert_nonce'),
            'inquiry_delete' => wp_create_nonce('autopuzzle_inquiry_delete_nonce'),
        ],
        'text' => [
            'error' => esc_html__('Error!', 'autopuzzle'),
            'success' => esc_html__('Success!', 'autopuzzle')
        ]
    ];
    
    $scripts_html .= '<script>'
        . 'window.autopuzzleInquiryLists = ' . json_encode($localize_data, JSON_UNESCAPED_UNICODE) . ';'
        . 'window.maneliInquiryLists = window.autopuzzleInquiryLists;'
        . 'console.log("✅ Loaded");'
        . '</script>' . PHP_EOL;
    return str_replace('</body>', $scripts_html . '</body>', $dashboard_html);
}
