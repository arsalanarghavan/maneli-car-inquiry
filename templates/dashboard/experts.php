<!-- Start::row -->
<?php
/**
 * Experts Management Page
 * Only accessible by Administrators
 */

// Permission check - Only Admin can access
if (!current_user_can('manage_maneli_inquiries')) {
    ?>
    <div class="row">
        <div class="col-xl-12">
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="la la-exclamation-triangle me-2"></i>
                <strong>دسترسی محدود!</strong> شما به این صفحه دسترسی ندارید.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php
    return;
}

// Statistics for experts
global $wpdb;

// Total experts
$total_experts = count(get_users(['role' => 'maneli_expert']));

// Active/Inactive experts
$active_experts = 0;
$inactive_experts = 0;
$experts = get_users(['role' => 'maneli_expert']);
foreach ($experts as $exp) {
    $is_active = get_user_meta($exp->ID, 'expert_active', true) !== 'no';
    if ($is_active) {
        $active_experts++;
    } else {
        $inactive_experts++;
    }
}

// Experts with inquiries
$experts_with_inquiries = $wpdb->get_var("
    SELECT COUNT(DISTINCT meta_value)
    FROM {$wpdb->postmeta}
    WHERE meta_key = 'assigned_expert_id'
    AND meta_value != ''
");

// Total inquiries assigned to experts
$total_assigned = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type IN ('inquiry', 'cash_inquiry')
    AND p.post_status = 'publish'
    AND pm.meta_key = 'assigned_expert_id'
    AND pm.meta_value != ''
");

// Completed by experts
$completed_by_experts = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
    INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id
    WHERE p.post_type = 'inquiry'
    AND pm_status.meta_key = 'tracking_status'
    AND pm_status.meta_value = 'completed'
    AND pm_expert.meta_key = 'assigned_expert_id'
    AND pm_expert.meta_value != ''
");

$cash_completed_by_experts = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id
    INNER JOIN {$wpdb->postmeta} pm_expert ON p.ID = pm_expert.post_id
    WHERE p.post_type = 'cash_inquiry'
    AND pm_status.meta_key = 'cash_inquiry_status'
    AND pm_status.meta_value = 'completed'
    AND pm_expert.meta_key = 'assigned_expert_id'
    AND pm_expert.meta_value != ''
");

$total_completed = $completed_by_experts + $cash_completed_by_experts;

// In progress
$in_progress_count = $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type IN ('inquiry', 'cash_inquiry')
    AND p.post_status = 'publish'
    AND pm.meta_key IN ('tracking_status', 'cash_inquiry_status')
    AND pm.meta_value = 'in_progress'
    AND p.ID IN (
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = 'assigned_expert_id' AND meta_value != ''
    )
");

// Today's assignments
$today_assigned = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*)
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type IN ('inquiry', 'cash_inquiry')
    AND pm.meta_key = 'assigned_expert_id'
    AND pm.meta_value != ''
    AND p.post_date >= %s
    AND p.post_date <= %s
", date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')));
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-user-tie fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">کل کارشناسان</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($total_experts); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent">
                                    <i class="la la-user-check fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">فعال</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($active_experts); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-danger-transparent">
                                    <i class="la la-user-slash fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">غیرفعال</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-danger"><?php echo number_format_i18n($inactive_experts); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-tasks fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">کل ارجاعات</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($total_assigned); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-warning-transparent">
                                    <i class="la la-spinner fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">در حال پیگیری</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($in_progress_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-cyan-transparent">
                                    <i class="la la-check-circle fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">تکمیل شده</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-cyan"><?php echo number_format_i18n($total_completed); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-user-tie me-2"></i>
                    مدیریت کارشناسان
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpertModal">
                        <i class="la la-user-plus me-1"></i>
                        افزودن کارشناس جدید
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="la la-search"></i>
                            </span>
                            <input type="search" id="expert-search-input" class="form-control" placeholder="جستجوی نام، ایمیل یا موبایل...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="expert-status-filter">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap" id="experts-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 25%;">نام کارشناس</th>
                                <th style="width: 20%;">ایمیل</th>
                                <th style="width: 15%;">موبایل</th>
                                <th style="width: 15%;">تعداد استعلامات</th>
                                <th style="width: 10%;">وضعیت</th>
                                <th style="width: 10%;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $experts = get_users(['role' => 'maneli_expert', 'orderby' => 'display_name']);
                            $counter = 1;
                            
                            if (!empty($experts)) {
                                foreach ($experts as $expert) {
                                    $mobile = get_user_meta($expert->ID, 'mobile_number', true);
                                    $is_active = get_user_meta($expert->ID, 'expert_active', true) !== 'no';
                                    
                                    // شمارش استعلامات کارشناس
                                    $inquiry_count = count(get_posts([
                                        'post_type' => ['inquiry', 'cash_inquiry'],
                                        'post_status' => 'any',
                                        'author' => $expert->ID,
                                        'posts_per_page' => -1
                                    ]));
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php echo get_avatar($expert->ID, 32, '', '', ['class' => 'avatar avatar-sm rounded-circle me-2']); ?>
                                                <span class="fw-medium"><?php echo esc_html($expert->display_name); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo esc_html($expert->user_email); ?></td>
                                        <td>
                                            <?php if ($mobile) : ?>
                                                <i class="la la-mobile me-1"></i><?php echo esc_html($mobile); ?>
                                            <?php else : ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-transparent">
                                                <?php echo $inquiry_count; ?> استعلام
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($is_active) : ?>
                                                <span class="badge bg-success">فعال</span>
                                            <?php else : ?>
                                                <span class="badge bg-danger">غیرفعال</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-sm btn-info-light" 
                                                        onclick="viewExpertStats(<?php echo $expert->ID; ?>)"
                                                        title="آمار">
                                                    <i class="la la-chart-bar"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-<?php echo $is_active ? 'warning' : 'success'; ?>-light" 
                                                        onclick="toggleExpertStatus(<?php echo $expert->ID; ?>, <?php echo $is_active ? 'false' : 'true'; ?>)"
                                                        title="<?php echo $is_active ? 'غیرفعال کردن' : 'فعال کردن'; ?>">
                                                    <i class="la la-toggle-<?php echo $is_active ? 'on' : 'off'; ?>"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-info mb-0">
                                            <i class="la la-info-circle me-2"></i>
                                            هیچ کارشناسی ثبت نشده است.
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<!-- Add Expert Modal -->
<div class="modal fade" id="addExpertModal" tabindex="-1" aria-labelledby="addExpertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addExpertModalLabel">افزودن کارشناس جدید</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="add-expert-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="expert-username" class="form-label">نام کاربری *</label>
                            <input type="text" class="form-control" id="expert-username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-email" class="form-label">ایمیل *</label>
                            <input type="email" class="form-control" id="expert-email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-first-name" class="form-label">نام *</label>
                            <input type="text" class="form-control" id="expert-first-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-last-name" class="form-label">نام خانوادگی *</label>
                            <input type="text" class="form-control" id="expert-last-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="expert-mobile" class="form-label">شماره موبایل</label>
                            <input type="text" class="form-control" id="expert-mobile" placeholder="09xxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label for="expert-password" class="form-label">رمز عبور *</label>
                            <input type="password" class="form-control" id="expert-password" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="send-credentials" checked>
                                <label class="form-check-label" for="send-credentials">
                                    ارسال اطلاعات ورود به ایمیل کارشناس
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="saveExpert()">
                    <i class="la la-save me-1"></i>
                    ذخیره کارشناس
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search experts
    $('#expert-search-input').on('keyup', function() {
        const search = $(this).val().toLowerCase();
        $('#experts-table tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(search));
        });
    });

    // Filter by status
    $('#expert-status-filter').on('change', function() {
        const status = $(this).val();
        $('#experts-table tbody tr').each(function() {
            if (!status) {
                $(this).show();
            } else {
                const badge = $(this).find('.badge');
                const isActive = badge.hasClass('bg-success');
                if ((status === 'active' && isActive) || (status === 'inactive' && !isActive)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });
    });
});

// Save expert
function saveExpert() {
    const form = document.getElementById('add-expert-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    Swal.fire({
        title: 'در حال ذخیره...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'maneli_add_expert',
            username: jQuery('#expert-username').val(),
            email: jQuery('#expert-email').val(),
            first_name: jQuery('#expert-first-name').val(),
            last_name: jQuery('#expert-last-name').val(),
            mobile: jQuery('#expert-mobile').val(),
            password: jQuery('#expert-password').val(),
            send_credentials: jQuery('#send-credentials').is(':checked'),
            nonce: '<?php echo wp_create_nonce('maneli_add_expert'); ?>'
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'موفق!',
                    text: 'کارشناس با موفقیت افزوده شد.',
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا!',
                    text: response.data.message || 'خطا در افزودن کارشناس'
                });
            }
        }
    });
}

// Toggle expert status
function toggleExpertStatus(userId, activate) {
    Swal.fire({
        title: activate ? 'فعال‌سازی کارشناس؟' : 'غیرفعال‌سازی کارشناس؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'بله',
        cancelButtonText: 'خیر'
    }).then((result) => {
        if (result.isConfirmed) {
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'maneli_toggle_expert_status',
                user_id: userId,
                active: activate,
                nonce: '<?php echo wp_create_nonce('maneli_toggle_expert'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    Swal.fire('خطا', response.data.message, 'error');
                }
            });
        }
    });
}

// View expert stats
function viewExpertStats(userId) {
    Swal.fire({
        title: 'در حال بارگذاری...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'maneli_get_expert_stats',
        user_id: userId,
        nonce: '<?php echo wp_create_nonce('maneli_expert_stats'); ?>'
    }, function(response) {
        if (response.success) {
            Swal.fire({
                title: 'آمار کارشناس',
                html: response.data.html,
                width: 600,
                showCloseButton: true
            });
        } else {
            Swal.fire('خطا', 'خطا در بارگذاری آمار', 'error');
        }
    });
}
</script>

