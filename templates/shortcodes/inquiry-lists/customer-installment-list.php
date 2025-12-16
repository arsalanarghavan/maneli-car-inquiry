<?php
/**
 * Template for the Customer's view of their Installment Inquiry List.
 * Modern redesign with Bootstrap theme styling.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @version 2.0.0 (Modern redesign)
 *
 * @var WP_Query $inquiries_query The WP_Query object for the user's installment inquiries.
 * @var string   $current_url     The base URL for generating action links.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header bg-info-transparent">
                <div class="card-title">
                    <i class="la la-credit-card me-2 fs-20"></i>
                    <?php esc_html_e('My Installment Purchase Inquiries', 'autopuzzle'); ?>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-info d-flex align-items-start" role="alert">
                    <i class="la la-lightbulb fs-20 me-2 mt-1"></i>
                    <div>
                        <strong><?php esc_html_e('Guide:', 'autopuzzle'); ?></strong>
                        <?php esc_html_e('After submitting your inquiry, our expert will contact you and guide you through the required documents.', 'autopuzzle'); ?>
                    </div>
                </div>

                <?php if (!$inquiries_query->have_posts()): ?>
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="la la-file-invoice" style="font-size: 80px; color: #dee2e6;"></i>
                        </div>
                        <h5 class="text-muted mb-2"><?php esc_html_e('No Installment Inquiries Yet', 'autopuzzle'); ?></h5>
                        <p class="text-muted mb-4"><?php esc_html_e('To purchase a car with installments, use the loan calculator.', 'autopuzzle'); ?></p>
                        <a href="<?php echo esc_url(home_url('/loan-calculator')); ?>" class="btn btn-info btn-wave">
                            <i class="la la-calculator me-1"></i>
                            <?php esc_html_e('Loan Calculator', 'autopuzzle'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-info">
                                <tr>
                                    <th><i class="la la-hashtag me-1"></i><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                    <th><i class="la la-car me-1"></i><?php esc_html_e('Car', 'autopuzzle'); ?></th>
                                    <th><i class="la la-info-circle me-1"></i><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                    <th><i class="la la-calendar me-1"></i><?php esc_html_e('Registration Date', 'autopuzzle'); ?></th>
                                    <th><i class="la la-wrench me-1"></i><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($inquiries_query->have_posts()): $inquiries_query->the_post(); ?>
                                    <?php
                                    $inquiry_id = get_the_ID();
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $status = get_post_meta($inquiry_id, 'inquiry_status', true);
                                    $expert_status = get_post_meta($inquiry_id, 'expert_status', true);
                                    $report_url = add_query_arg('inquiry_id', $inquiry_id, $current_url);
                                    $expert_status_info = Autopuzzle_Render_Helpers::get_expert_status_info($expert_status);
                                    
                                    // Check Finnotech API data availability for customer view
                                    $options = get_option('autopuzzle_inquiry_all_options', []);
                                    $credit_risk_data = get_post_meta($inquiry_id, '_finnotech_credit_risk_data', true);
                                    $credit_score_data = get_post_meta($inquiry_id, '_finnotech_credit_score_data', true);
                                    $collaterals_data = get_post_meta($inquiry_id, '_finnotech_collaterals_data', true);
                                    $cheque_color_data = get_post_meta($inquiry_id, '_finnotech_cheque_color_data', true);
                                    
                                    $credit_risk_enabled = !empty($options['finnotech_credit_risk_enabled']) && $options['finnotech_credit_risk_enabled'] === '1';
                                    $credit_score_enabled = !empty($options['finnotech_credit_score_enabled']) && $options['finnotech_credit_score_enabled'] === '1';
                                    $collaterals_enabled = !empty($options['finnotech_collaterals_enabled']) && $options['finnotech_collaterals_enabled'] === '1';
                                    $cheque_color_enabled = !empty($options['finnotech_cheque_color_enabled']) && $options['finnotech_cheque_color_enabled'] === '1';
                                    
                                    $has_finnotech_data = ($credit_risk_enabled && !empty($credit_risk_data)) || 
                                                          ($credit_score_enabled && !empty($credit_score_data)) || 
                                                          ($collaterals_enabled && !empty($collaterals_data)) || 
                                                          ($cheque_color_enabled && !empty($cheque_color_data));
                                    
                                    $status_data = [
                                        'pending' => ['label' => esc_html__('Pending Review', 'autopuzzle'), 'class' => 'warning'],
                                        'user_confirmed' => ['label' => esc_html__('Confirmed and Referred', 'autopuzzle'), 'class' => 'success'],
                                        'approved' => ['label' => esc_html__('Final Approval', 'autopuzzle'), 'class' => 'success'],
                                        'rejected' => ['label' => esc_html__('Rejected', 'autopuzzle'), 'class' => 'danger'],
                                        'more_docs' => ['label' => esc_html__('Documents Required', 'autopuzzle'), 'class' => 'warning'],
                                    ];
                                    $badge = $status_data[$status] ?? ['label' => esc_html__('Unknown', 'autopuzzle'), 'class' => 'secondary'];
                                    
                                    // Convert to Jalali
                                    $timestamp = strtotime(get_the_date('Y-m-d', $inquiry_id));
                                    if (function_exists('autopuzzle_gregorian_to_jalali')) {
                                        $date = autopuzzle_gregorian_to_jalali(
                                            date('Y', $timestamp),
                                            date('m', $timestamp),
                                            date('d', $timestamp),
                                            'Y/m/d'
                                        );
                                    } else {
                                        $date = get_the_date('Y/m/d', $inquiry_id);
                                    }
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo esc_html($inquiry_id); ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="la la-car text-info me-2 fs-18"></i>
                                                <span><?php echo esc_html(get_the_title($product_id)); ?></span>
                                                <?php if ($has_finnotech_data): ?>
                                                    <i class="la la-check-circle text-success ms-2" title="<?php esc_attr_e('Credit Information Available', 'autopuzzle'); ?>" style="font-size: 14px;"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $badge['class']; ?>">
                                                <?php echo $badge['label']; ?>
                                            </span>
                                            <?php if ($expert_status_info): ?>
                                                <br><span class="badge mt-1" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>; color: white;">
                                                    <?php echo esc_html($expert_status_info['label']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($date); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light">
                                                <i class="la la-eye me-1"></i>
                                                <?php esc_html_e('View Details', 'autopuzzle'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($inquiries_query->max_num_pages > 1): ?>
                        <div class="mt-4 text-center">
                            <nav>
                                <?php
                                echo paginate_links([
                                    'base' => $current_url . '%_%',
                                    'format' => '?paged=%#%',
                                    'current' => max(1, get_query_var('paged')),
                                    'total' => $inquiries_query->max_num_pages,
                                    'prev_text' => '<i class="la la-angle-right"></i> ' . esc_html__('Previous', 'autopuzzle'),
                                    'next_text' => esc_html__('Next', 'autopuzzle') . ' <i class="la la-angle-left"></i>',
                                    'type' => 'plain',
                                    'before_page_number' => '<span class="btn btn-sm btn-light mx-1">',
                                    'after_page_number' => '</span>',
                                ]);
                                ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
</div>

<style>
.table-info th {
    background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);
    color: white;
    font-weight: 600;
}

.table-hover tbody tr:hover {
    background-color: rgba(23, 162, 184, 0.05);
    transform: scale(1.005);
    transition: all 0.3s ease;
}

.la-file-invoice {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.bg-info-transparent {
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, transparent 100%);
    border-bottom: 2px solid #17a2b8;
}

.bg-warning-transparent {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, transparent 100%);
    border-bottom: 2px solid #ffc107;
}
</style>
