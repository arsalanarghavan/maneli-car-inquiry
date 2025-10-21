/**
 * Handles JavaScript functionality for cash inquiry report pages (for experts and admins).
 * This includes meeting scheduling, expert decision, and admin approval actions.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function($) {
    'use strict';
    
    console.log('=== Cash Report Script Loaded ===');
    console.log('jQuery:', typeof $);
    console.log('Swal:', typeof Swal);
    console.log('maneliCashReport:', typeof maneliCashReport);
    console.log('kamaDatepicker:', typeof kamaDatepicker);
    
    // Initialize datepicker
    if ($('.maneli-datepicker').length && typeof kamaDatepicker !== 'undefined') {
        kamaDatepicker('meeting-date', {
            placeholder: 'انتخاب تاریخ',
            twodigit: true,
            closeAfterSelect: true,
            nextButtonIcon: 'la la-angle-left',
            previousButtonIcon: 'la la-angle-right'
        });
    }
    
    // Helper function to update inquiry status
    function updateInquiryStatus(newStatus, extraData = {}) {
        const inquiryId = $('.frontend-expert-report').data('inquiry-id');
        
        return $.ajax({
            url: maneliCashReport.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_update_cash_status',
                inquiry_id: inquiryId,
                new_status: newStatus,
                nonce: maneliCashReport.nonces.update_status,
                ...extraData
            }
        });
    }
    
    // 1. شروع پیگیری (new/referred → in_progress)
    $('#set-in-progress-btn').on('click', function() {
        Swal.fire({
            title: 'شروع پیگیری؟',
            text: 'وضعیت به "در حال پیگیری" تغییر می‌کند',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'بله، شروع کن',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('in_progress')
                    .done(function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'موفق!',
                                text: 'وضعیت به "در حال پیگیری" تغییر یافت',
                                icon: 'success',
                                confirmButtonText: 'باشه'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'خطا',
                                text: response.data.message || 'خطا در تغییر وضعیت',
                                icon: 'error',
                                confirmButtonText: 'باشه'
                            });
                        }
                    });
            }
        });
    });
    
    // 2. درخواست پیش‌پرداخت (in_progress → awaiting_downpayment)
    $('#request-downpayment-btn').on('click', function() {
        $('#downpayment-card').slideDown();
    });
    
    $('#submit-downpayment-btn').on('click', function() {
        const amount = $('#downpayment-amount').val();
        
        if (!amount || amount <= 0) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً مبلغ پیش‌پرداخت را وارد کنید',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        Swal.fire({
            title: 'ارسال درخواست پیش‌پرداخت؟',
            html: `مبلغ: <strong>${parseInt(amount).toLocaleString('fa-IR')} تومان</strong><br>پیامک برای مشتری ارسال می‌شود`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'بله، ارسال کن',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('awaiting_downpayment', { downpayment_amount: amount })
                    .done(function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'موفق!',
                                text: 'درخواست پیش‌پرداخت ارسال شد',
                                icon: 'success',
                                confirmButtonText: 'باشه'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'خطا',
                                text: response.data.message || 'خطا در ارسال',
                                icon: 'error',
                                confirmButtonText: 'باشه'
                            });
                        }
                    });
            }
        });
    });
    
    // 3. ثبت جلسه حضوری (downpayment_received → meeting_scheduled)
    $('#schedule-meeting-btn').on('click', function() {
        $('#meeting-card').slideDown();
    });
    
    $('#submit-meeting-btn').on('click', function() {
        const date = $('#meeting-date').val();
        const time = $('#meeting-time').val();
        
        if (!date || !time) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً تاریخ و ساعت را انتخاب کنید',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        updateInquiryStatus('meeting_scheduled', { meeting_date: date, meeting_time: time })
            .done(function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'موفق!',
                        text: 'جلسه حضوری ثبت شد',
                        icon: 'success',
                        confirmButtonText: 'باشه'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: response.data.message || 'خطا در ثبت',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            });
    });
    
    // 4. تایید نهایی (meeting_scheduled → approved)
    $('#approve-inquiry-btn').on('click', function() {
        Swal.fire({
            title: 'تایید نهایی؟',
            text: 'درخواست مشتری تایید می‌شود',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'بله، تایید کن',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('approved')
                    .done(function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'موفق!',
                                text: 'درخواست تایید شد',
                                icon: 'success',
                                confirmButtonText: 'باشه'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'خطا',
                                text: response.data.message || 'خطا در تایید',
                                icon: 'error',
                                confirmButtonText: 'باشه'
                            });
                        }
                    });
            }
        });
    });
    
    // 5. رد درخواست (meeting_scheduled → rejected)
    $('#reject-inquiry-btn').on('click', function() {
        Swal.fire({
            title: 'رد درخواست',
            html: '<textarea id="reject-reason" class="swal2-textarea" placeholder="دلیل رد درخواست را بنویسید..."></textarea>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ثبت رد درخواست',
            cancelButtonText: 'لغو',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            preConfirm: () => {
                const reason = $('#reject-reason').val();
                if (!reason) {
                    Swal.showValidationMessage('لطفاً دلیل رد را وارد کنید');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                updateInquiryStatus('rejected', { rejection_reason: result.value })
                    .done(function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'رد شد',
                                text: 'درخواست رد شد',
                                icon: 'success',
                                confirmButtonText: 'باشه'
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                title: 'خطا',
                                text: response.data.message || 'خطا در رد',
                                icon: 'error',
                                confirmButtonText: 'باشه'
                            });
                        }
                    });
            }
        });
    });
    
    // 6. ذخیره یادداشت
    $('#save-note-btn').on('click', function() {
        const inquiryId = $('.frontend-expert-report').data('inquiry-id');
        const note = $('#expert-note').val();
        
        $.ajax({
            url: maneliCashReport.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_expert_note',
                inquiry_id: inquiryId,
                note: note,
                nonce: maneliCashReport.nonces.save_note
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'ذخیره شد',
                        text: 'یادداشت شما ذخیره شد',
                        icon: 'success',
                        confirmButtonText: 'باشه'
                    });
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: response.data.message || 'خطا در ذخیره',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            }
        });
    });
    
    // Save meeting schedule (old - not used with new workflow, but kept for compatibility)
    $('#save-meeting-btn').on('click', function() {
        const inquiryId = $(this).closest('.frontend-expert-report').find('[data-inquiry-id]').data('inquiry-id');
        const date = $('#meeting-date').val();
        const time = $('#meeting-time').val();
        
        if (!date || !time) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً تاریخ و ساعت را انتخاب کنید',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        $.ajax({
            url: maneliCashReport.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_meeting_schedule',
                inquiry_id: inquiryId,
                inquiry_type: 'cash',
                meeting_date: date,
                meeting_time: time,
                nonce: maneliCashReport.nonces.save_meeting
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'در حال ذخیره...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => Swal.showLoading()
                });
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'موفق!',
                        text: 'زمان جلسه ذخیره شد',
                        icon: 'success',
                        confirmButtonText: 'باشه'
                    });
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: response.data.message || 'خطا در ذخیره',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'خطا',
                    text: 'خطای سرور رخ داد',
                    icon: 'error',
                    confirmButtonText: 'باشه'
                });
            }
        });
    });
    
    // Save expert decision
    $('#save-expert-decision-btn').on('click', function() {
        const inquiryId = $(this).closest('.frontend-expert-report').find('[data-inquiry-id]').data('inquiry-id');
        const decision = $('#expert-decision').val();
        const downpayment = $('#expert-downpayment').val();
        const note = $('#expert-note').val();
        
        if (!decision) {
            Swal.fire({
                title: 'خطا',
                text: 'لطفاً وضعیت سفارش را انتخاب کنید',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
            return;
        }
        
        $.ajax({
            url: maneliCashReport.ajax_url,
            type: 'POST',
            data: {
                action: 'maneli_save_expert_decision_cash',
                inquiry_id: inquiryId,
                decision: decision,
                downpayment: downpayment,
                note: note,
                nonce: maneliCashReport.nonces.expert_decision
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'در حال ذخیره...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => Swal.showLoading()
                });
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'موفق!',
                        text: 'تصمیم شما ذخیره شد',
                        icon: 'success',
                        confirmButtonText: 'باشه'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'خطا',
                        text: response.data.message || 'خطا در ذخیره',
                        icon: 'error',
                        confirmButtonText: 'باشه'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'خطا',
                    text: 'خطای سرور رخ داد',
                    icon: 'error',
                    confirmButtonText: 'باشه'
                });
            }
        });
    });
    
    // Admin final approval
    $('#admin-approve-btn').on('click', function() {
        const inquiryId = $(this).closest('.frontend-expert-report').find('[data-inquiry-id]').data('inquiry-id');
        
        Swal.fire({
            title: 'تایید نهایی؟',
            text: 'آیا این درخواست را به صورت نهایی تایید می‌کنید؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'بله، تایید می‌کنم',
            cancelButtonText: 'انصراف',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: maneliCashReport.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'maneli_admin_approve_cash',
                        inquiry_id: inquiryId,
                        nonce: maneliCashReport.nonces.admin_approve
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'موفق!',
                                text: 'درخواست به صورت نهایی تایید شد',
                                icon: 'success',
                                confirmButtonText: 'باشه'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'خطا',
                                text: response.data.message || 'خطا در تایید',
                                icon: 'error',
                                confirmButtonText: 'باشه'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'خطا',
                            text: 'خطای سرور رخ داد',
                            icon: 'error',
                            confirmButtonText: 'باشه'
                        });
                    }
                });
            }
        });
    });
});

