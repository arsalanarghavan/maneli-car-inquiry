<!-- Start::row -->
<?php
/**
 * Expert Detail Page
 * Shows expert account information, inquiries, and permissions
 * Based on profile-settings.html design
 */

// Helper function to convert numbers to Persian
if (!function_exists('persian_numbers')) {
    function persian_numbers($str) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $str);
    }
}

// Permission check
if (!current_user_can('manage_autopuzzle_inquiries')) {
    wp_redirect(home_url('/dashboard'));
    exit;
}

// If we reach here, permission is granted - continue rendering
// Get expert ID from query vars or GET parameters
$view_expert_id = isset($_GET['view_expert']) ? intval($_GET['view_expert']) : (get_query_var('view_expert') ? intval(get_query_var('view_expert')) : 0);
$edit_expert_id = isset($_GET['edit_expert']) ? intval($_GET['edit_expert']) : (get_query_var('edit_expert') ? intval(get_query_var('edit_expert')) : 0);
$expert_id = $view_expert_id ?: $edit_expert_id;
$is_edit_mode = $edit_expert_id > 0;
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : ($is_edit_mode ? 'account' : 'account');

if (!$expert_id) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-warning">
                <i class="la la-exclamation-triangle me-2"></i>
                <?php esc_html_e('Expert not specified.', 'autopuzzle'); ?>
            </div>
        </div>
    </div>
    <?php
    return;
}

$expert = get_userdata($expert_id);
if (!$expert || !in_array('autopuzzle_expert', $expert->roles)) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger">
                <i class="la la-exclamation-triangle me-2"></i>
                <?php esc_html_e('Expert not found.', 'autopuzzle'); ?>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Get expert meta data
$mobile_number = get_user_meta($expert_id, 'mobile_number', true);
$is_active = get_user_meta($expert_id, 'expert_active', true) !== 'no';

// Get permissions
$permission_cash_inquiry = get_user_meta($expert_id, 'permission_cash_inquiry', true) !== 'no';
$permission_installment_inquiry = get_user_meta($expert_id, 'permission_installment_inquiry', true) !== 'no';
$permission_calendar = get_user_meta($expert_id, 'permission_calendar', true) !== 'no';

// Get expert inquiries (assigned to this expert)
$installment_inquiries = get_posts([
    'post_type' => 'inquiry',
    'post_status' => 'any',
    'meta_query' => [
        [
            'key' => 'assigned_expert_id',
            'value' => $expert_id,
            'compare' => '='
        ]
    ],
    'posts_per_page' => 50, // OPTIMIZED: Limit for memory
    'orderby' => 'date',
    'order' => 'DESC'
]);

$cash_inquiries = get_posts([
    'post_type' => 'cash_inquiry',
    'post_status' => 'any',
    'meta_query' => [
        [
            'key' => 'assigned_expert_id',
            'value' => $expert_id,
            'compare' => '='
        ]
    ],
    'posts_per_page' => 50, // OPTIMIZED: Limit for memory
    'orderby' => 'date',
    'order' => 'DESC'
]);
?>
<div class="main-content app-content">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
            <div>
                <nav>
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard')); ?>"><?php esc_html_e('Pages', 'autopuzzle'); ?></a></li>
                        <li class="breadcrumb-item"><a href="<?php echo esc_url(home_url('/dashboard/experts')); ?>"><?php esc_html_e('Expert Management', 'autopuzzle'); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $is_edit_mode ? esc_html__('Edit', 'autopuzzle') : esc_html__('View', 'autopuzzle'); ?> <?php esc_html_e('Expert', 'autopuzzle'); ?></li>
                    </ol>
                </nav>
                <h1 class="page-title fw-medium fs-18 mb-0"><?php echo $is_edit_mode ? esc_html__('Edit Expert', 'autopuzzle') : esc_html__('Expert Details', 'autopuzzle'); ?></h1>
            </div>
            <div>
                <a href="<?php echo esc_url(home_url('/dashboard/experts')); ?>" class="btn btn-primary btn-wave">
                    <i class="la la-arrow-right me-1"></i>
                    <?php esc_html_e('Back to List', 'autopuzzle'); ?>
                </a>
            </div>
        </div>
        <!-- Page Header Close -->

        <!-- Start::row -->
        <div class="row gap-3 justify-content-center">
            <div class="col-xl-9">
                <div class="card custom-card">
                    <ul class="nav nav-tabs tab-style-8 scaleX rounded m-3 profile-settings-tab gap-2" id="expertDetailTabs" role="tablist">
                        <li class="nav-item me-1" role="presentation">
                            <button class="nav-link px-4 bg-primary-transparent <?php echo ($active_tab === 'account' || $active_tab === '') ? 'active' : ''; ?>" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-pane" type="button" role="tab" aria-controls="account-pane" aria-selected="<?php echo ($active_tab === 'account' || $active_tab === '') ? 'true' : 'false'; ?>">
                                <?php esc_html_e('Account', 'autopuzzle'); ?>
                            </button>
                        </li>
                        <li class="nav-item me-1" role="presentation">
                            <button class="nav-link px-4 bg-primary-transparent <?php echo $active_tab === 'inquiries' ? 'active' : ''; ?>" id="inquiries-tab" data-bs-toggle="tab" data-bs-target="#inquiries-pane" type="button" role="tab" aria-controls="inquiries-pane" aria-selected="<?php echo $active_tab === 'inquiries' ? 'true' : 'false'; ?>">
                                <?php esc_html_e('Registered Inquiries', 'autopuzzle'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link px-4 bg-primary-transparent <?php echo $active_tab === 'permissions' ? 'active' : ''; ?>" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions-pane" type="button" role="tab" aria-controls="permissions-pane" aria-selected="<?php echo $active_tab === 'permissions' ? 'true' : 'false'; ?>">
                                <?php esc_html_e('Permissions', 'autopuzzle'); ?>
                            </button>
                        </li>
                    </ul>
                    <div class="p-3 border-bottom border-top border-block-end-dashed tab-content">
                        <!-- Account Tab -->
                        <div class="tab-pane <?php echo ($active_tab === 'account' || $active_tab === '') ? 'show active' : ''; ?> overflow-hidden p-0 border-0" id="account-pane" role="tabpanel" aria-labelledby="account-tab" tabindex="0">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                    <div class="fw-semibold d-block fs-15"><?php echo $is_edit_mode ? esc_html__('Edit Account', 'autopuzzle') : esc_html__('Account Information', 'autopuzzle'); ?>:</div>
                                    <?php if ($is_edit_mode): ?>
                                        <button class="btn btn-primary btn-sm" onclick="saveExpertDetails(<?php echo $expert_id; ?>)">
                                            <i class="la la-save me-1"></i><?php esc_html_e('Save Changes', 'autopuzzle'); ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url(add_query_arg(['edit_expert' => $expert_id], home_url('/dashboard/experts'))); ?>" class="btn btn-primary btn-sm">
                                            <i class="la la-edit me-1"></i><?php esc_html_e('Edit', 'autopuzzle'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('First Name', 'autopuzzle'); ?> <?php echo $is_edit_mode ? '*' : ''; ?>:</label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-expert-first-name" value="<?php echo esc_attr($expert->first_name); ?>" placeholder="<?php esc_attr_e('First Name', 'autopuzzle'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($expert->first_name)) : esc_html($expert->first_name); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Last Name', 'autopuzzle'); ?> <?php echo $is_edit_mode ? '*' : ''; ?>:</label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="text" class="form-control" id="edit-expert-last-name" value="<?php echo esc_attr($expert->last_name); ?>" placeholder="<?php esc_attr_e('Last Name', 'autopuzzle'); ?>">
                                        <?php else: ?>
                                            <div class="form-control-plaintext"><?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($expert->last_name)) : esc_html($expert->last_name); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Mobile Number', 'autopuzzle'); ?> <?php echo $is_edit_mode ? '*' : ''; ?>:</label>
                                        <?php if ($is_edit_mode): ?>
                                            <input type="tel" class="form-control" id="edit-expert-mobile" value="<?php echo esc_attr($mobile_number); ?>" placeholder="09123456789">
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <a href="tel:<?php echo esc_attr($mobile_number); ?>" class="text-primary text-decoration-none">
                                                    <?php echo function_exists('persian_numbers_no_separator') ? persian_numbers_no_separator(esc_html($mobile_number)) : esc_html($mobile_number); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label"><?php esc_html_e('Status:', 'autopuzzle'); ?></label>
                                        <?php if ($is_edit_mode): ?>
                                            <select class="form-select" id="edit-expert-status">
                                                <option value="yes" <?php selected($is_active, true); ?>><?php esc_html_e('Active', 'autopuzzle'); ?></option>
                                                <option value="no" <?php selected($is_active, false); ?>><?php esc_html_e('Inactive', 'autopuzzle'); ?></option>
                                            </select>
                                        <?php else: ?>
                                            <div class="form-control-plaintext">
                                                <span class="badge bg-<?php echo $is_active ? 'success' : 'danger'; ?>">
                                                    <?php echo $is_active ? esc_html__('Active', 'autopuzzle') : esc_html__('Inactive', 'autopuzzle'); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Inquiries Tab -->
                        <div class="tab-pane <?php echo $active_tab === 'inquiries' ? 'show active' : ''; ?> overflow-hidden p-0 border-0" id="inquiries-pane" role="tabpanel" aria-labelledby="inquiries-tab" tabindex="0">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                    <div class="fw-semibold d-block fs-15"><?php esc_html_e('Assigned Inquiries:', 'autopuzzle'); ?></div>
                                    <div class="text-muted">
                                        <span class="badge bg-info-transparent me-2"><?php esc_html_e('Installment Inquiries', 'autopuzzle'); ?>: <?php echo persian_numbers(number_format_i18n(count($installment_inquiries))); ?></span>
                                        <span class="badge bg-warning-transparent"><?php esc_html_e('Cash Inquiries', 'autopuzzle'); ?>: <?php echo persian_numbers(number_format_i18n(count($cash_inquiries))); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Installment Inquiries -->
                                <?php if (!empty($installment_inquiries)): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="la la-bank me-2"></i>
                                            <?php esc_html_e('Installment Inquiries', 'autopuzzle'); ?> (<?php echo persian_numbers(number_format_i18n(count($installment_inquiries))); ?>)
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Car', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Total Price', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Down Payment', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Date', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($installment_inquiries as $inquiry): 
                                                        $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                                        $product = wc_get_product($product_id);
                                                        $total_price = get_post_meta($inquiry->ID, 'autopuzzle_inquiry_total_price', true);
                                                        $down_payment = get_post_meta($inquiry->ID, 'autopuzzle_inquiry_down_payment', true);
                                                        $status = get_post_meta($inquiry->ID, 'inquiry_status', true);
                                                        $status_display = [
                                                            'pending' => ['label' => esc_html__('Pending', 'autopuzzle'), 'class' => 'warning'],
                                                            'approved' => ['label' => esc_html__('Approved', 'autopuzzle'), 'class' => 'success'],
                                                            'rejected' => ['label' => esc_html__('Rejected', 'autopuzzle'), 'class' => 'danger'],
                                                            'new' => ['label' => esc_html__('New', 'autopuzzle'), 'class' => 'info']
                                                        ];
                                                        $status_info = isset($status_display[$status]) ? $status_display[$status] : ['label' => $status, 'class' => 'secondary'];
                                                    ?>
                                                        <tr>
                                                            <td><?php echo persian_numbers('#' . $inquiry->ID); ?></td>
                                                            <td><?php echo $product ? persian_numbers(esc_html($product->get_name())) : '-'; ?></td>
                                                            <td><?php echo $total_price ? persian_numbers(number_format_i18n($total_price)) . ' ' . esc_html__('Toman', 'autopuzzle') : '-'; ?></td>
                                                            <td><?php echo $down_payment ? persian_numbers(number_format_i18n($down_payment)) . ' ' . esc_html__('Toman', 'autopuzzle') : '-'; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo esc_attr($status_info['class']); ?>">
                                                                    <?php echo esc_html($status_info['label']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo persian_numbers(date_i18n('Y/m/d H:i', strtotime($inquiry->post_date))); ?></td>
                                                            <td>
                                                                <a href="<?php echo esc_url(home_url('/dashboard/installment-inquiries?view=' . $inquiry->ID)); ?>" class="btn btn-sm btn-primary-light">
                                                                    <i class="la la-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Cash Inquiries -->
                                <?php if (!empty($cash_inquiries)): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-semibold mb-3">
                                            <i class="la la-money-bill-wave me-2"></i>
                                            <?php esc_html_e('Cash Inquiries', 'autopuzzle'); ?> (<?php echo persian_numbers(number_format_i18n(count($cash_inquiries))); ?>)
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?php esc_html_e('ID', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Car', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Price', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Status', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Date', 'autopuzzle'); ?></th>
                                                        <th><?php esc_html_e('Actions', 'autopuzzle'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cash_inquiries as $inquiry): 
                                                        $product_id = get_post_meta($inquiry->ID, 'product_id', true);
                                                        $product = wc_get_product($product_id);
                                                        $total_price = get_post_meta($inquiry->ID, 'cash_total_price', true);
                                                        $status = get_post_meta($inquiry->ID, 'cash_inquiry_status', true);
                                                        $status_display = [
                                                            'new' => ['label' => esc_html__('New', 'autopuzzle'), 'class' => 'info'],
                                                            'in_progress' => ['label' => esc_html__('Under Review', 'autopuzzle'), 'class' => 'warning'],
                                                            'approved' => ['label' => esc_html__('Approved', 'autopuzzle'), 'class' => 'success'],
                                                            'rejected' => ['label' => esc_html__('Rejected', 'autopuzzle'), 'class' => 'danger'],
                                                            'completed' => ['label' => esc_html__('Completed', 'autopuzzle'), 'class' => 'primary']
                                                        ];
                                                        $status_info = isset($status_display[$status]) ? $status_display[$status] : ['label' => $status, 'class' => 'secondary'];
                                                    ?>
                                                        <tr>
                                                            <td><?php echo persian_numbers('#' . $inquiry->ID); ?></td>
                                                            <td><?php echo $product ? persian_numbers(esc_html($product->get_name())) : '-'; ?></td>
                                                            <td><?php echo $total_price ? persian_numbers(number_format_i18n($total_price)) . ' ' . esc_html__('Toman', 'autopuzzle') : '-'; ?></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo esc_attr($status_info['class']); ?>">
                                                                    <?php echo esc_html($status_info['label']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo persian_numbers(date_i18n('Y/m/d H:i', strtotime($inquiry->post_date))); ?></td>
                                                            <td>
                                                                <a href="<?php echo esc_url(home_url('/dashboard/cash-inquiries?view=' . $inquiry->ID)); ?>" class="btn btn-sm btn-primary-light">
                                                                    <i class="la la-eye"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (empty($installment_inquiries) && empty($cash_inquiries)): ?>
                                    <div class="alert alert-info">
                                        <i class="la la-info-circle me-2"></i>
                                        <?php esc_html_e('No inquiries have been assigned to this expert.', 'autopuzzle'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Permissions Tab -->
                        <div class="tab-pane <?php echo $active_tab === 'permissions' ? 'show active' : ''; ?> overflow-hidden p-0 border-0" id="permissions-pane" role="tabpanel" aria-labelledby="permissions-tab" tabindex="0">
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-1">
                                    <div class="fw-semibold d-block fs-15"><?php esc_html_e('Permission Management:', 'autopuzzle'); ?></div>
                                    <button class="btn btn-primary btn-sm" onclick="saveExpertPermissions(<?php echo $expert_id; ?>)">
                                        <i class="la la-save me-1"></i><?php esc_html_e('Save Changes', 'autopuzzle'); ?>
                                    </button>
                                </div>
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="permission-cash-inquiry" <?php checked($permission_cash_inquiry, true); ?>>
                                            <label class="form-check-label fw-semibold" for="permission-cash-inquiry">
                                                <i class="la la-money-bill-wave me-2"></i>
                                                <?php esc_html_e('Access to Cash Inquiries', 'autopuzzle'); ?>
                                            </label>
                                            <p class="text-muted fs-12 mt-1 mb-0"><?php esc_html_e('Expert can view and manage cash inquiries.', 'autopuzzle'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="permission-installment-inquiry" <?php checked($permission_installment_inquiry, true); ?>>
                                            <label class="form-check-label fw-semibold" for="permission-installment-inquiry">
                                                <i class="la la-bank me-2"></i>
                                                <?php esc_html_e('Access to Installment Inquiries', 'autopuzzle'); ?>
                                            </label>
                                            <p class="text-muted fs-12 mt-1 mb-0"><?php esc_html_e('Expert can view and manage installment inquiries.', 'autopuzzle'); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-xl-12">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="permission-calendar" <?php checked($permission_calendar, true); ?>>
                                            <label class="form-check-label fw-semibold" for="permission-calendar">
                                                <i class="la la-calendar me-2"></i>
                                                <?php esc_html_e('Access to Meeting Calendar', 'autopuzzle'); ?>
                                            </label>
                                            <p class="text-muted fs-12 mt-1 mb-0"><?php esc_html_e('Expert can view and manage meetings.', 'autopuzzle'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End::row -->
    </div>
</div>

<script>
var autopuzzleAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
// Translation object
const maneliTranslations = {
    error: <?php echo wp_json_encode(esc_html__('Error!', 'autopuzzle')); ?>,
    fillFields: <?php echo wp_json_encode(esc_html__('Please fill all required fields.', 'autopuzzle')); ?>,
    saving: <?php echo wp_json_encode(esc_html__('Saving...', 'autopuzzle')); ?>,
    success: <?php echo wp_json_encode(esc_html__('Success!', 'autopuzzle')); ?>,
    expertUpdated: <?php echo wp_json_encode(esc_html__('Expert information updated successfully.', 'autopuzzle')); ?>,
    permissionsUpdated: <?php echo wp_json_encode(esc_html__('Permissions updated successfully.', 'autopuzzle')); ?>,
    updateError: <?php echo wp_json_encode(esc_html__('Error updating', 'autopuzzle')); ?>,
    serverErrorRetry: <?php echo wp_json_encode(esc_html__('Server connection error. Please try again.', 'autopuzzle')); ?>
};

// Helper function to generate email from mobile
function generateEmailFromMobile(mobile) {
    return mobile + '@manelikhodro.com';
}

// Save expert details
function saveExpertDetails(expertId) {
    const firstName = jQuery('#edit-expert-first-name').val();
    const lastName = jQuery('#edit-expert-last-name').val();
    const mobile = jQuery('#edit-expert-mobile').val();
    const status = jQuery('#edit-expert-status').val();
    
    if (!firstName || !lastName || !mobile) {
        Swal.fire({
            icon: 'error',
            title: maneliTranslations.error,
            text: maneliTranslations.fillFields
        });
        return;
    }
    
    Swal.fire({
        title: maneliTranslations.saving,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const email = generateEmailFromMobile(mobile);
    
    jQuery.ajax({
        url: autopuzzleAjaxUrl,
        type: 'POST',
        data: {
            action: 'autopuzzle_update_expert',
            user_id: expertId,
            first_name: firstName,
            last_name: lastName,
            display_name: firstName + ' ' + lastName,
            mobile_number: mobile,
            email: email,
            expert_active: status,
            nonce: '<?php echo wp_create_nonce('autopuzzle_update_expert_nonce'); ?>'
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire(maneliTranslations.success, maneliTranslations.expertUpdated, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire(maneliTranslations.error, response.data?.message || maneliTranslations.updateError, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error, xhr.responseText);
            Swal.fire(maneliTranslations.error, maneliTranslations.serverErrorRetry, 'error');
        }
    });
}

// Save expert permissions
function saveExpertPermissions(expertId) {
    const cashInquiry = jQuery('#permission-cash-inquiry').is(':checked');
    const installmentInquiry = jQuery('#permission-installment-inquiry').is(':checked');
    const calendar = jQuery('#permission-calendar').is(':checked');
    
    Swal.fire({
        title: maneliTranslations.saving,
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    jQuery.ajax({
        url: autopuzzleAjaxUrl,
        type: 'POST',
        data: {
            action: 'autopuzzle_update_expert_permissions',
            user_id: expertId,
            'permissions[cash_inquiry]': cashInquiry ? 1 : 0,
            'permissions[installment_inquiry]': installmentInquiry ? 1 : 0,
            'permissions[calendar]': calendar ? 1 : 0,
            nonce: '<?php echo wp_create_nonce('autopuzzle_update_expert_permissions_nonce'); ?>'
        },
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire(maneliTranslations.success, maneliTranslations.permissionsUpdated, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire(maneliTranslations.error, response.data?.message || maneliTranslations.updateError, 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX Error:', error, xhr.responseText);
            Swal.fire(maneliTranslations.error, maneliTranslations.serverErrorRetry, 'error');
        }
    });
}

// Auto-activate tab from URL parameter
jQuery(document).ready(function($) {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const tabButton = $('#permissions-tab');
        if (tabButton.length) {
            tabButton.click();
        }
    }
});
</script>

<style>
.tab-style-8 .nav-link.active {
    background-color: var(--primary-color) !important;
    color: white !important;
}

.form-control-plaintext {
    min-height: 38px;
    padding: 0.375rem 0.75rem;
    line-height: 1.5;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.03);
}
</style>
