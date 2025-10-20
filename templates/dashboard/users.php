<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">مدیریت کاربران</div>
                <button class="btn btn-primary">
                    <i class="la la-plus me-1"></i>
                    کاربر جدید
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="user-table" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>شناسه</th>
                                <th>نام کاربر</th>
                                <th>ایمیل</th>
                                <th>نقش</th>
                                <th>وضعیت</th>
                                <th>تاریخ عضویت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users = get_users([
                                'number' => 20,
                                'orderby' => 'registered',
                                'order' => 'DESC'
                            ]);

                            foreach ($users as $user) {
                                $user_roles = $user->roles;
                                $role_display = !empty($user_roles) ? ucfirst($user_roles[0]) : 'بدون نقش';
                                ?>
                                <tr>
                                    <td><?php echo $user->ID; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm avatar-rounded me-2">
                                                <?php echo get_avatar($user->ID, 32, '', '', ['class' => 'avatar-img']); ?>
                                            </div>
                                            <div>
                                                <span class="fw-medium"><?php echo esc_html($user->display_name); ?></span>
                                                <br>
                                                <small class="text-muted"><?php echo esc_html($user->user_login); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><span class="badge bg-primary-transparent"><?php echo esc_html($role_display); ?></span></td>
                                    <td>
                                        <?php if ($user->user_status == 0): ?>
                                            <span class="badge bg-success">فعال</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">غیرفعال</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y/m/d', strtotime($user->user_registered)); ?></td>
                                    <td>
                                        <div class="btn-list">
                                            <a href="<?php echo get_edit_user_link($user->ID); ?>" class="btn btn-sm btn-primary-light" target="_blank">
                                                <i class="la la-edit"></i> ویرایش
                                            </a>
                                            <button class="btn btn-sm btn-danger-light" onclick="deleteUser(<?php echo $user->ID); ?>)">
                                                <i class="la la-trash"></i> حذف
                                            </button>
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

<script>
function deleteUser(userId) {
    if (confirm('آیا از حذف این کاربر اطمینان دارید؟')) {
        // AJAX call to delete user
        console.log('Deleting user:', userId);
    }
}
</script>

