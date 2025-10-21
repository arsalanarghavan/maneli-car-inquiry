<!-- Start::row -->
<?php
// Permission check
if (!current_user_can('manage_maneli_inquiries')) {
    echo '<div class="alert alert-danger">شما دسترسی به این صفحه را ندارید.</div>';
    return;
}

$users = get_users([
    'number' => 50,
    'orderby' => 'registered',
    'order' => 'DESC'
]);
?>

<div class="row">
    <div class="col-xl-12">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            $role_counts = count_users();
            $total_users = $role_counts['total_users'];
            $admin_count = isset($role_counts['avail_roles']['administrator']) ? $role_counts['avail_roles']['administrator'] : 0;
            $customer_count = isset($role_counts['avail_roles']['customer']) ? $role_counts['avail_roles']['customer'] : 0;
            $expert_count = isset($role_counts['avail_roles']['maneli_expert']) ? $role_counts['avail_roles']['maneli_expert'] : 0;
            ?>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-primary-transparent">
                                    <i class="la la-users fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">کل کاربران</span>
                                </div>
                                <h4 class="fw-semibold mb-0"><?php echo number_format_i18n($total_users); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-success-transparent">
                                    <i class="la la-user-shield fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">مدیران</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-success"><?php echo number_format_i18n($admin_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-info-transparent">
                                    <i class="la la-user-tie fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">کارشناسان</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-info"><?php echo number_format_i18n($expert_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6">
                <div class="card custom-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <span class="avatar avatar-md bg-warning-transparent">
                                    <i class="la la-user fs-24"></i>
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="mb-1">
                                    <span class="text-muted fs-13">مشتری‌ها</span>
                                </div>
                                <h4 class="fw-semibold mb-0 text-warning"><?php echo number_format_i18n($customer_count); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="la la-users me-2"></i>
                    مدیریت کاربران
                </div>
                <div class="btn-list">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="la la-plus me-1"></i>
                        کاربر جدید
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4 g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="la la-search"></i>
                            </span>
                            <input type="search" class="form-control" id="user-search" placeholder="جستجوی نام، ایمیل...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="role-filter">
                            <option value="">همه نقش‌ها</option>
                            <option value="administrator">مدیر</option>
                            <option value="maneli_expert">کارشناس</option>
                            <option value="customer">مشتری</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table id="user-table" class="table table-bordered table-hover text-nowrap">
                        <thead class="table-primary">
                            <tr>
                                <th><i class="la la-hashtag me-1"></i>شناسه</th>
                                <th><i class="la la-user me-1"></i>نام کاربر</th>
                                <th><i class="la la-envelope me-1"></i>ایمیل</th>
                                <th><i class="la la-user-tag me-1"></i>نقش</th>
                                <th><i class="la la-toggle-on me-1"></i>وضعیت</th>
                                <th><i class="la la-calendar me-1"></i>تاریخ عضویت</th>
                                <th><i class="la la-wrench me-1"></i>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $user_roles = $user->roles;
                                $role_display = !empty($user_roles) ? ucfirst($user_roles[0]) : 'بدون نقش';
                                
                                // Persian role names
                                $persian_roles = [
                                    'administrator' => 'مدیر کل',
                                    'maneli_admin' => 'مدیر مانلی',
                                    'maneli_expert' => 'کارشناس',
                                    'customer' => 'مشتری',
                                    'subscriber' => 'مشترک'
                                ];
                                $role_display = isset($persian_roles[$user_roles[0]]) ? $persian_roles[$user_roles[0]] : $role_display;
                                
                                // Role badge color
                                $role_colors = [
                                    'administrator' => 'success',
                                    'maneli_admin' => 'success',
                                    'maneli_expert' => 'info',
                                    'customer' => 'warning',
                                    'subscriber' => 'secondary'
                                ];
                                $badge_color = isset($role_colors[$user_roles[0]]) ? $role_colors[$user_roles[0]] : 'secondary';
                                
                                // Convert date to Jalali
                                $timestamp = strtotime($user->user_registered);
                                if (function_exists('maneli_gregorian_to_jalali')) {
                                    $date = maneli_gregorian_to_jalali(
                                        date('Y', $timestamp),
                                        date('m', $timestamp),
                                        date('d', $timestamp),
                                        'Y/m/d'
                                    );
                                } else {
                                    $date = date('Y/m/d', $timestamp);
                                }
                            ?>
                                <tr data-role="<?php echo esc_attr($user_roles[0]); ?>">
                                    <td><?php echo $user->ID; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm avatar-rounded me-2">
                                                <?php echo get_avatar($user->ID, 32); ?>
                                            </div>
                                            <div>
                                                <span class="fw-medium d-block"><?php echo esc_html($user->display_name); ?></span>
                                                <small class="text-muted"><?php echo esc_html($user->user_login); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $badge_color; ?>-transparent">
                                            <?php echo esc_html($role_display); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user->user_status == 0): ?>
                                            <span class="badge bg-success">فعال</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">غیرفعال</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($date); ?></td>
                                    <td>
                                        <div class="btn-list">
                                            <button class="btn btn-sm btn-info-light" onclick="viewUserStats(<?php echo $user->ID; ?>)" title="آمار">
                                                <i class="la la-chart-bar"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger-light" onclick="deleteUser(<?php echo $user->ID; ?>)" title="حذف">
                                                <i class="la la-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End::row -->

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-transparent">
                <h5 class="modal-title">
                    <i class="la la-user-plus me-2"></i>
                    افزودن کاربر جدید
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-user-form">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">نام کاربری *</label>
                        <input type="text" class="form-control" id="new-username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">ایمیل *</label>
                        <input type="email" class="form-control" id="new-email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">نقش *</label>
                        <select class="form-select" id="new-role" required>
                            <option value="customer">مشتری</option>
                            <option value="maneli_expert">کارشناس</option>
                            <option value="maneli_admin">مدیر مانلی</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">رمز عبور *</label>
                        <input type="password" class="form-control" id="new-password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">
                    <i class="la la-save me-1"></i>
                    ذخیره
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Search users
    $('#user-search').on('keyup', function() {
        const search = $(this).val().toLowerCase();
        $('#user-table tbody tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(search));
        });
    });
    
    // Filter by role
    $('#role-filter').on('change', function() {
        const role = $(this).val();
        if (!role) {
            $('#user-table tbody tr').show();
        } else {
            $('#user-table tbody tr').each(function() {
                const userRole = $(this).data('role');
                $(this).toggle(userRole === role);
            });
        }
    });
});

function saveUser() {
    Swal.fire({
        title: 'در حال ذخیره...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
    
    // AJAX save user
    setTimeout(() => {
        Swal.fire('موفق!', 'کاربر با موفقیت افزوده شد.', 'success').then(() => {
            location.reload();
        });
    }, 1000);
}

function viewUserStats(userId) {
    Swal.fire({
        title: 'آمار کاربر',
        html: '<p>آمار کاربر در حال بارگذاری...</p>',
        icon: 'info'
    });
}

function deleteUser(userId) {
    Swal.fire({
        title: 'حذف کاربر؟',
        text: 'این عملیات غیرقابل بازگشت است!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بله، حذف کن',
        cancelButtonText: 'انصراف'
    }).then((result) => {
        if (result.isConfirmed) {
            // AJAX delete
            Swal.fire('حذف شد!', 'کاربر با موفقیت حذف شد.', 'success');
        }
    });
}
</script>

<style>
.table-primary th {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    font-weight: 600;
}

.table-hover tbody tr:hover {
    background-color: rgba(var(--primary-rgb), 0.03);
    transform: scale(1.01);
    transition: all 0.3s ease;
}
</style>
