jQuery(document).ready(function($) {
    $('body').on('click', '.view-inquiry-details', function(e) {
        e.preventDefault(); // <-- این خط اضافه شد
        const inquiryId = $(this).data('id');
        const button = $(this);
        button.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin: 0;"></span>');

        $.post(maneli_inquiry_ajax.ajax_url, {
            action: 'maneli_get_inquiry_details',
            nonce: maneli_inquiry_ajax.details_nonce,
            inquiry_id: inquiryId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                const finotex_colors = {
                    '1': { text: 'سفید', desc: 'فاقد هرگونه سابقه چک برگشتی.' },
                    '2': { text: 'زرد', desc: 'یک فقره چک برگشتی یا حداکثر مبلغ 50 میلیون ریال تعهد برگشتی.' },
                    '3': { text: 'نارنجی', desc: 'دو الی چهار فقره چک برگشتی یا حداکثر مبلغ 200 میلیون ریال تعهد برگشتی.' },
                    '4': { text: 'قهوه‌ای', desc: 'پنج تا ده فقره چک برگشتی یا حداکثر مبلغ 500 میلیون ریال تعهد برگشتی.' },
                    '5': { text: 'قرمز', desc: 'بیش از ده فقره چک برگشتی یا بیش از مبلغ 500 میلیون ریال تعهد برگشتی.' },
                     0: { text: 'نامشخص', desc: 'اطلاعاتی از فینوتک دریافت نشد.' }
                };
                const color_info = finotex_colors[data.finotex.color_code] || finotex_colors[0];
                
                let issuerHtml = '';
                if (data.issuer_type === 'other' && data.issuer) {
                    issuerHtml = `
                        <h4 class="report-section-divider">اطلاعات صادر کننده چک</h4>
                        <div class="swal-grid">
                            <div><strong>نام:</strong> ${data.issuer.first_name} ${data.issuer.last_name}</div>
                            <div><strong>موبایل:</strong> ${data.issuer.mobile}</div>
                            <div><strong>نام پدر:</strong> ${data.issuer.father_name}</div>
                            <div><strong>تاریخ تولد:</strong> ${data.issuer.birth_date}</div>
                            <div class="full-width"><strong>کد ملی:</strong> ${data.issuer.national_code}</div>
                        </div>`;
                }
                
                let finotexHtml = data.finotex.skipped
                    ? `<p>استعلام بانکی انجام نشده است.</p>`
                    : `<table class="summary-table">
                           <tr><td><strong>وضعیت چک صیادی:</strong></td><td><strong class="cheque-color-${data.finotex.color_code}">${color_info.text}</strong></td></tr>
                           <tr><td><strong>توضیح وضعیت:</strong></td><td>${color_info.desc}</td></tr>
                       </table>`;

                let statusHtml = `<div class="status-box status-bg-${data.status_key}"><p><strong>وضعیت: ${data.status_label}</strong></p>`;
                if (data.status_key === 'rejected' && data.rejection_reason) {
                    statusHtml += `<p><strong>دلیل رد:</strong> ${data.rejection_reason}</p>`;
                }
                statusHtml += `</div>`;

                Swal.fire({
                    title: `جزئیات استعلام #${data.id}`,
                    html: `
                        <div class="swal-content-container">
                            ${statusHtml}
                            <div class="report-box">
                                <h3 class="report-box-title">خودروی درخواستی</h3>
                                ${data.car.image ? `<img src="${data.car.image}" class="swal-car-image">` : ''}
                                <table class="summary-table">
                                    <tr><td><strong>خودرو:</strong></td><td>${data.car.name}</td></tr>
                                    <tr><td><strong>پیش پرداخت:</strong></td><td>${data.car.down_payment} تومان</td></tr>
                                    <tr><td><strong>اقساط:</strong></td><td>${data.car.term} ماهه</td></tr>
                                    <tr><td><strong>مبلغ هر قسط:</strong></td><td>${data.car.installment} تومان</td></tr>
                                </table>
                            </div>
                            <div class="report-box">
                                <h3 class="report-box-title">اطلاعات خریدار</h3>
                                <div class="swal-grid">
                                    <div><strong>نام:</strong> ${data.buyer.first_name} ${data.buyer.last_name}</div>
                                    <div><strong>موبایل:</strong> ${data.buyer.mobile}</div>
                                    <div><strong>نام پدر:</strong> ${data.buyer.father_name}</div>
                                    <div><strong>تاریخ تولد:</strong> ${data.buyer.birth_date}</div>
                                    <div class="full-width"><strong>کد ملی:</strong> ${data.buyer.national_code}</div>
                                </div>
                                ${issuerHtml}
                            </div>
                            <div class="report-box">
                                <h3 class="report-box-title">نتیجه اعتبارسنجی</h3>
                                ${finotexHtml}
                            </div>
                        </div>`,
                    width: '800px',
                    showConfirmButton: false,
                    showCloseButton: true,
                });

            } else {
                Swal.fire('خطا', response.data.message || 'خطایی در دریافت اطلاعات رخ داد.', 'error');
            }
        }).always(function() {
            button.prop('disabled', false).html('مشاهده جزئیات');
        });
    });
});