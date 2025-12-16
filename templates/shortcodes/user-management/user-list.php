<?php
/**
 * Template for the main User List view, rendered by the [autopuzzle_user_list] shortcode.
 *
 * This template displays statistical widgets, filter controls, and the table of users.
 * The table body is populated initially and then updated via AJAX.
 *
 * @package Autopuzzle_Car_Inquiry/Templates/Shortcodes/UserManagement
 * @author  Gemini
 * @version 1.0.0
 *
 * @var string    $user_stats_widgets_html HTML for the user statistics widgets.
 * @var WP_User_Query $initial_user_query  The initial WP_User_Query object.
 * @var string    $current_url             The base URL for generating action links.
 * @var array     $feedback_messages       An array of success/error messages to display.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="row">
    <div class="col-xl-12">
        
        <?php echo $user_stats_widgets_html; // Already escaped in the generating function ?>

        <?php if (!empty($feedback_messages['success'])) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php foreach ($feedback_messages['success'] as $message) : ?>
                    <p class="mb-0"><?php echo esc_html($message); ?></p>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($feedback_messages['error'])) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($feedback_messages['error'] as $message) : ?>
                    <p class="mb-0"><?php echo esc_html($message); ?></p>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title"><?php esc_html_e('Complete User List', 'autopuzzle'); ?></div>
                <a href="<?php echo esc_url(add_query_arg('add_user', 'true', $current_url)); ?>" class="btn btn-primary">
                    <i class="la la-user-plus me-1"></i>
                    <?php esc_html_e('Add New User', 'autopuzzle'); ?>
                </a>
            </div>
            
            <div class="card-body">
                <form id="autopuzzle-user-filter-form" onsubmit="return false;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="la la-search"></i>
                                </span>
                                <input type="search" id="user-search-input" name="s" class="form-control" placeholder="<?php esc_attr_e('Search by name, email, mobile, or national ID...', 'autopuzzle'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="role-filter" class="form-label"><?php esc_html_e('User Role:', 'autopuzzle'); ?></label>
                            <select name="role" id="role-filter" class="form-select">
                                <option value=""><?php esc_html_e('All Roles', 'autopuzzle'); ?></option>
                                <option value="customer"><?php esc_html_e('Customer', 'autopuzzle'); ?></option>
                                <option value="autopuzzle_expert"><?php esc_html_e('AutoPuzzle Expert', 'autopuzzle'); ?></option>
                                <option value="autopuzzle_admin"><?php esc_html_e('AutoPuzzle Manager', 'autopuzzle'); ?></option>
                                <option value="administrator"><?php esc_html_e('General Manager', 'autopuzzle'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="orderby-filter" class="form-label"><?php esc_html_e('Order By:', 'autopuzzle'); ?></label>
                            <select name="orderby" id="orderby-filter" class="form-select">
                                <option value="display_name"><?php esc_html_e('Display Name', 'autopuzzle'); ?></option>
                                <option value="user_registered"><?php esc_html_e('Registration Date', 'autopuzzle'); ?></option>
                                <option value="user_login"><?php esc_html_e('Username', 'autopuzzle'); ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="order-filter" class="form-label"><?php esc_html_e('Order:', 'autopuzzle'); ?></label>
                            <select name="order" id="order-filter" class="form-select">
                                <option value="ASC"><?php esc_html_e('Ascending', 'autopuzzle'); ?></option>
                                <option value="DESC"><?php esc_html_e('Descending', 'autopuzzle'); ?></option>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th><?php esc_html_e('Display Name', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Username', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Email', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Role', 'autopuzzle'); ?></th>
                                <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="autopuzzle-user-list-tbody">
                            <?php
                            $all_users = $initial_user_query->get_results();
                            if (!empty($all_users)) :
                                foreach ($all_users as $user) :
                                    if ($user->ID === get_current_user_id()) continue; // Don't show the current user in the list
                                    Autopuzzle_Render_Helpers::render_user_list_row($user, $current_url);
                                endforeach;
                            else :
                                ?>
                                <tr><td colspan="5" class="text-center"><?php esc_html_e('No users found.', 'autopuzzle'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="user-list-loader" style="display:none; text-align:center; padding: 40px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden"><?php esc_html_e('Loading...', 'autopuzzle'); ?></span>
                    </div>
                </div>
                
                <div class="autopuzzle-pagination-wrapper mt-3 text-center">
                    <?php
                    $total_users = $initial_user_query->get_total();
                    $total_pages = ceil($total_users / 50);
                    echo paginate_links([
                        'base'      => '#', // Handled by JS
                        'format'    => '?paged=%#%',
                        'current'   => max(1, get_query_var('paged')),
                        'total'     => $total_pages,
                        'prev_text' => '&laquo; ' . esc_html__('Previous', 'autopuzzle'),
                        'next_text' => esc_html__('Next', 'autopuzzle') . ' &raquo;',
                        'type'      => 'plain',
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
