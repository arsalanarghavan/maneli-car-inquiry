<?php
/**
 * Template for the Customer's view of their Installment Inquiry List.
 *
 * This template displays a table of the current user's installment inquiries with their statuses and links to view details.
 *
 * @package Maneli_Car_Inquiry/Templates/Shortcodes/InquiryLists
 * @author  Gemini
 * @version 1.0.0
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
            <div class="card-header">
                <div class="card-title">
                    <i class="la la-list-alt me-2"></i>
                    <?php esc_html_e('Your Installment Inquiries List', 'maneli-car-inquiry'); ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$inquiries_query->have_posts()) : ?>
                    <div class="alert alert-warning" role="alert">
                        <i class="la la-info-circle me-2"></i>
                        <?php esc_html_e('You have not submitted any installment inquiries yet.', 'maneli-car-inquiry'); ?>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th><?php esc_html_e('ID', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Car', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Status', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Date', 'maneli-car-inquiry'); ?></th>
                                    <th><?php esc_html_e('Actions', 'maneli-car-inquiry'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($inquiries_query->have_posts()) : $inquiries_query->the_post(); ?>
                                    <?php
                                    $inquiry_id = get_the_ID();
                                    $product_id = get_post_meta($inquiry_id, 'product_id', true);
                                    $status = get_post_meta($inquiry_id, 'inquiry_status', true);
                                    $expert_status = get_post_meta($inquiry_id, 'expert_status', true);
                                    $report_url = add_query_arg('inquiry_id', $inquiry_id, $current_url);
                                    $expert_status_info = Maneli_Render_Helpers::get_expert_status_info($expert_status);
                                    ?>
                                    <tr>
                                        <td>#<?php echo esc_html($inquiry_id); ?></td>
                                        <td><?php echo esc_html(get_the_title($product_id)); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status === 'user_confirmed' ? 'success' : ($status === 'pending' ? 'warning' : ($status === 'rejected' ? 'danger' : 'secondary')); ?>">
                                                <?php echo esc_html(Maneli_CPT_Handler::get_status_label($status)); ?>
                                            </span>
                                            <?php if ($expert_status_info): ?>
                                                <br><span class="badge mt-1" style="background-color: <?php echo esc_attr($expert_status_info['color']); ?>;"><?php echo esc_html($expert_status_info['label']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(get_the_date('Y/m/d', $inquiry_id)); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url($report_url); ?>" class="btn btn-sm btn-primary-light">
                                                <i class="la la-eye me-1"></i>
                                                <?php esc_html_e('View Details', 'maneli-car-inquiry'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 text-center">
                        <?php
                        echo paginate_links([
                            'base'      => $current_url . '%_%',
                            'format'    => '?paged=%#%',
                            'current'   => max(1, get_query_var('paged')),
                            'total'     => $inquiries_query->max_num_pages,
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'maneli-car-inquiry'),
                            'next_text' => esc_html__('Next', 'maneli-car-inquiry') . ' &raquo;',
                            'type'      => 'plain',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
    </div>
</div>
