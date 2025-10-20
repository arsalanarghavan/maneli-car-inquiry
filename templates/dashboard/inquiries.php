<!-- Start::row -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">لیست استعلامات</div>
                <a href="<?php echo home_url('/dashboard/new-inquiry'); ?>" class="btn btn-primary">
                    <i class="la la-plus me-1"></i>
                    استعلام جدید
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable-basic" class="table table-bordered text-nowrap w-100">
                        <thead>
                            <tr>
                                <th>شماره استعلام</th>
                                <th>نام کاربر</th>
                                <th>شماره موبایل</th>
                                <th>نوع خودرو</th>
                                <th>وضعیت</th>
                                <th>تاریخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Get inquiries from database
                            $inquiries = get_posts([
                                'post_type' => 'inquiry',
                                'post_status' => 'publish',
                                'numberposts' => 10,
                                'meta_query' => [
                                    [
                                        'key' => 'inquiry_status',
                                        'compare' => 'EXISTS'
                                    ]
                                ]
                            ]);

                            foreach ($inquiries as $inquiry) {
                                $status = get_post_meta($inquiry->ID, 'inquiry_status', true);
                                $user_name = get_post_meta($inquiry->ID, 'inquiry_name', true);
                                $user_phone = get_post_meta($inquiry->ID, 'inquiry_phone', true);
                                $car_type = get_post_meta($inquiry->ID, 'inquiry_car_type', true);
                                $date = get_the_date('Y/m/d', $inquiry->ID);

                                $status_badge = '';
                                switch ($status) {
                                    case 'successful':
                                        $status_badge = '<span class="badge bg-success">موفق</span>';
                                        break;
                                    case 'pending':
                                        $status_badge = '<span class="badge bg-warning">در انتظار</span>';
                                        break;
                                    case 'failed':
                                        $status_badge = '<span class="badge bg-danger">ناموفق</span>';
                                        break;
                                    default:
                                        $status_badge = '<span class="badge bg-secondary">نامشخص</span>';
                                }
                                ?>
                                <tr>
                                    <td>#INQ<?php echo $inquiry->ID; ?></td>
                                    <td><?php echo esc_html($user_name ?: 'نامشخص'); ?></td>
                                    <td><?php echo esc_html($user_phone ?: 'نامشخص'); ?></td>
                                    <td><?php echo esc_html($car_type ?: 'نامشخص'); ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td><?php echo esc_html($date); ?></td>
                                    <td>
                                        <div class="btn-list">
                                            <a href="javascript:void(0);" class="btn btn-sm btn-primary-light">
                                                <i class="la la-eye"></i> مشاهده
                                            </a>
                                            <a href="javascript:void(0);" class="btn btn-sm btn-info-light">
                                                <i class="la la-edit"></i> ویرایش
                                            </a>
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

