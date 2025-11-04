# لیست کامل داده‌های قابل دریافت از APIهای Finnotech

این فایل شامل تمام داده‌هایی است که می‌توان از APIهای Finnotech دریافت کرد.

---

## 1. API: وثایق و تضامین ضمانت‌نامه (credit-guarantee-collaterals)
**URL:** `https://docs.finnotech.ir/credit-guarantee-collaterals.html`

### داده‌های قابل دریافت:

#### الف) اطلاعات وثایق (Collaterals)
- **نوع وثیقه** (collateralType)
- **مبلغ ارزیابی** (evaluationAmount)
- **مبلغ ترهینی** (foreclosureAmount)
- **تاریخ ارزیابی** (evaluationDate)
- **تاریخ ترهینی** (foreclosureDate)
- **وضعیت وثیقه** (status)
- **توضیحات** (description)

#### ب) اطلاعات تضامین (Guarantees)
- **نوع تضمین** (guaranteeType)
- **مبلغ تضمین** (guaranteeAmount)
- **تاریخ صدور** (issueDate)
- **تاریخ انقضا** (expiryDate)
- **وضعیت تضمین** (status)
- **نام ضامن** (guarantorName)
- **کد ملی ضامن** (guarantorNationalCode)

#### ج) اطلاعات خلاصه قراردادها (وام‌ها/تسهیلات)
- **تعداد کل قراردادها** (totalContracts)
- **مجموع مبلغ وام‌ها** (totalLoanAmount)
- **مجموع مبلغ تسهیلات** (totalFacilityAmount)
- **تاریخ شروع قرارداد** (contractStartDate)
- **تاریخ پایان قرارداد** (contractEndDate)
- **نوع قرارداد** (contractType)
- **وضعیت قرارداد** (contractStatus)
- **نام بانک** (bankName)
- **شماره قرارداد** (contractNumber)

---

## 2. API: استعلام اعتبار معاملاتی افراد (kyc-transanctionCreditReport)
**URL:** `https://docs.finnotech.ir/kyc-transanctionCreditReport.html`

### داده‌های قابل دریافت:

#### الف) ریسک بانکی شخص
- **وضعیت ممنوع‌المعامله** (prohibitedTransactionStatus)
  - ممنوع‌المعامله بودن یا نبودن
  - تاریخ ممنوع‌المعامله شدن
  - دلیل ممنوع‌المعامله شدن
- **ریسک اعتباری** (creditRisk)
  - سطح ریسک (کم، متوسط، بالا)
  - امتیاز ریسک (riskScore)
- **وضعیت محکومیت‌های مالی** (financialJudgmentStatus)
  - وجود محکومیت مالی
  - تعداد محکومیت‌ها
  - مجموع مبلغ محکومیت‌ها
  - تاریخ آخرین محکومیت

#### ب) وضعیت اعتبار معاملاتی
- **وضعیت اعتبار** (creditStatus)
- **محدودیت‌های معاملاتی** (transactionLimitations)
- **تاریخ آخرین استعلام** (lastInquiryDate)

#### ج) اطلاعات محکومیت‌های مالی
- **لیست محکومیت‌ها** (judgments)
  - شماره پرونده (caseNumber)
  - مبلغ محکومیت (judgmentAmount)
  - تاریخ صدور حکم (judgmentDate)
  - وضعیت اجرا (executionStatus)
  - تاریخ اجرا (executionDate)
  - نوع محکومیت (judgmentType)

---

## 3. API: تأیید استعلام اعتبار معاملاتی (kyc-transactionCreditInquiryVerify)
**URL:** `https://docs.finnotech.ir/kyc-transactionCreditInquiryVerify.html`

### داده‌های قابل دریافت:

#### الف) نتیجه تأیید استعلام
- **وضعیت تأیید** (verificationStatus)
- **کد تأیید** (verificationCode)
- **تاریخ تأیید** (verificationDate)
- **اعتبار استعلام** (inquiryValidity)

#### ب) اطلاعات استعلام
- **شماره استعلام** (inquiryNumber)
- **تاریخ استعلام** (inquiryDate)
- **وضعیت استعلام** (inquiryStatus)
- **نتیجه استعلام** (inquiryResult)

---

## 4. API: گزارش استعلام اعتبار معاملاتی (kyc-transactionCreditInquiryReport)
**URL:** `https://docs.finnotech.ir/kyc-transactionCreditInquiryReport.html`

### داده‌های قابل دریافت:

#### الف) گزارش کامل اعتبار معاملاتی
- **وضعیت کلی** (overallStatus)
- **امتیاز اعتباری** (creditScore)
- **تاریخ آخرین بروزرسانی** (lastUpdateDate)

#### ب) دلایل کاهش امتیاز اعتباری
- **لیست عوامل منفی** (negativeFactors)
  - نوع عامل (factorType)
  - تأثیر بر امتیاز (impactOnScore)
  - تاریخ وقوع (occurrenceDate)
  - توضیحات (description)
- **مثال عوامل:**
  - تأخیر در پرداخت اقساط
  - چک‌های برگشتی
  - استفاده بیش از حد از تسهیلات
  - محکومیت‌های مالی
  - ممنوع‌المعامله بودن

#### ج) تاریخچه تغییرات امتیاز
- **تاریخچه امتیاز** (scoreHistory)
  - تاریخ (date)
  - امتیاز قبلی (previousScore)
  - امتیاز جدید (newScore)
  - دلیل تغییر (changeReason)

#### د) جزئیات معاملات
- **تعداد معاملات** (transactionCount)
- **مجموع مبلغ معاملات** (totalTransactionAmount)
- **آخرین معامله** (lastTransaction)
  - تاریخ (date)
  - مبلغ (amount)
  - نوع (type)

---

## 5. API: استعلام مکنا (credit-macna-inquiry-get)
**URL:** `https://docs.finnotech.ir/credit-macna-inquiry-get.html`

### داده‌های قابل دریافت:

#### الف) اطلاعات کارت‌های اعتباری
- **لیست کارت‌های اعتباری** (creditCards)
  - شماره کارت (cardNumber)
  - نوع کارت (cardType)
  - تاریخ صدور (issueDate)
  - تاریخ انقضا (expiryDate)
  - وضعیت کارت (cardStatus)
    - فعال (active)
    - باطل شده (cancelled)
    - درخواست المثنی (replacementRequested)
  - مانده اعتبار (creditBalance)
  - سقف اعتبار (creditLimit)
  - مانده استفاده شده (usedCredit)

#### ب) تاریخچه کارت‌ها
- **تاریخچه تغییرات** (cardHistory)
  - تاریخ تغییر (changeDate)
  - نوع تغییر (changeType)
  - توضیحات (description)

#### ج) اطلاعات مکنا
- **مجموع اعتبارات** (totalCredits)
- **مجموع استفاده شده** (totalUsed)
- **اعتبار باقیمانده** (remainingCredit)
- **تعداد کارت‌های فعال** (activeCardsCount)
- **تعداد کارت‌های باطل شده** (cancelledCardsCount)

---

## 6. API: استعلام رنگ چک (chequeColorInquiry) - موجود در پروژه
**URL:** `https://api.finnotech.ir/credit/v2/clients/{clientId}/chequeColorInquiry`

### داده‌های قابل دریافت:

#### الف) آخرین وضعیت چک‌های صیادی
- **کد رنگ چک** (chequeColor)
  - 0: نامشخص/ندارد
  - 1: سفید (بدون مشکل)
  - 2: زرد (هشدار)
  - 3: نارنجی (ریسک متوسط)
  - 4: قرمز (ریسک بالا)
- **تاریخ استعلام** (inquiryDate)
- **وضعیت استعلام** (inquiryStatus)

#### ب) جزئیات چک‌های برگشتی
- **تعداد چک‌های برگشتی** (bouncedChequesCount)
- **لیست چک‌های برگشتی** (bouncedCheques)
  - شماره چک (chequeNumber)
  - مبلغ چک (chequeAmount)
  - تاریخ برگشت (bounceDate)
  - دلیل برگشت (bounceReason)
  - نام بانک (bankName)
  - شماره حساب (accountNumber)

#### ج) جزئیات چک‌های رفع سوءاثر شده
- **تعداد چک‌های رفع سوءاثر شده** (clearedChequesCount)
- **لیست چک‌های رفع سوءاثر شده** (clearedCheques)
  - شماره چک (chequeNumber)
  - مبلغ چک (chequeAmount)
  - تاریخ رفع سوءاثر (clearanceDate)
  - تاریخ برگشت اولیه (originalBounceDate)
  - دلیل برگشت اولیه (originalBounceReason)

#### د) اطلاعات شناسه چک صیادی
- **شناسه چک صیادی** (sadadChequeId)
- **وضعیت صیادی** (sadadStatus)
- **تاریخ ثبت در صیادی** (sadadRegistrationDate)

---

## خلاصه داده‌های مورد نیاز شما

### ✅ 1. ریسک بانکی شخص
**از APIهای زیر قابل دریافت:**
- `kyc-transanctionCreditReport`:
  - `creditRisk` (سطح ریسک)
  - `riskScore` (امتیاز ریسک)
  - `prohibitedTransactionStatus` (وضعیت ممنوع‌المعامله)
  - `financialJudgmentStatus` (وضعیت محکومیت‌های مالی)

### ✅ 2. دلایل کاهش امتیاز اعتباری
**از API زیر قابل دریافت:**
- `kyc-transactionCreditInquiryReport`:
  - `negativeFactors` (لیست عوامل منفی)
  - `scoreHistory` (تاریخچه تغییرات امتیاز)

### ✅ 3. آخرین وضعیت چک‌های صیادی
**از API زیر قابل دریافت:**
- `chequeColorInquiry` (موجود در پروژه):
  - `chequeColor` (کد رنگ چک)
  - `sadadStatus` (وضعیت صیادی)
  - `sadadChequeId` (شناسه چک صیادی)

### ✅ 4. جزئیات چک‌های برگشتی و رفع سوءاثر شده
**از API زیر قابل دریافت:**
- `chequeColorInquiry`:
  - `bouncedCheques` (لیست چک‌های برگشتی)
  - `clearedCheques` (لیست چک‌های رفع سوءاثر شده)

### ✅ 5. خلاصه قراردادها (وام‌ها/تسهیلات)
**از API زیر قابل دریافت:**
- `credit-guarantee-collaterals`:
  - `totalContracts` (تعداد کل قراردادها)
  - `totalLoanAmount` (مجموع مبلغ وام‌ها)
  - `totalFacilityAmount` (مجموع مبلغ تسهیلات)
  - لیست قراردادها با جزئیات کامل

---

## ❌ APIهای غیرضروری (نیاز نیستند)

### 1. API: تأیید استعلام اعتبار معاملاتی (kyc-transactionCreditInquiryVerify)
**دلیل غیرضروری بودن:**
- این API فقط برای **تأیید** یک استعلام قبلی است
- داده‌های جدیدی ارائه نمی‌دهد
- فقط وضعیت تأیید یک استعلام قبلی را برمی‌گرداند
- برای نیازهای شما (ریسک بانکی، دلایل کاهش امتیاز، چک‌ها، قراردادها) کاربردی ندارد

**نتیجه:** ❌ **نیاز نیست**

---

### 2. API: استعلام مکنا (credit-macna-inquiry-get)
**دلیل غیرضروری بودن:**
- این API فقط اطلاعات **کارت‌های اعتباری** را نشان می‌دهد
- به نیازهای شما (ریسک بانکی، دلایل کاهش امتیاز، چک‌ها، قراردادها) مربوط نیست
- کارت‌های اعتباری مکنا با وام‌ها و تسهیلات متفاوت است

**نتیجه:** ❌ **نیاز نیست**

---

## ✅ APIهای ضروری (نیاز هستند)

### 1. ✅ chequeColorInquiry (موجود در پروژه)
- برای: وضعیت چک‌های صیادی، چک‌های برگشتی، رفع سوءاثر شده

### 2. ✅ kyc-transanctionCreditReport
- برای: ریسک بانکی شخص، محکومیت‌های مالی، ممنوع‌المعامله بودن

### 3. ✅ kyc-transactionCreditInquiryReport
- برای: دلایل کاهش امتیاز اعتباری، تاریخچه امتیاز

### 4. ✅ credit-guarantee-collaterals
- برای: خلاصه قراردادها (وام‌ها/تسهیلات)، وثایق و تضامین

---

## ⚠️ نکته درباره همپوشانی APIها

بین `kyc-transanctionCreditReport` و `kyc-transactionCreditInquiryReport` ممکن است همپوشانی وجود داشته باشد:

- **kyc-transanctionCreditReport**: بیشتر روی ریسک بانکی و محکومیت‌ها تمرکز دارد
- **kyc-transactionCreditInquiryReport**: بیشتر روی دلایل کاهش امتیاز و تاریخچه تغییرات تمرکز دارد

**پیشنهاد:** هر دو را پیاده‌سازی کنید تا داده‌های کامل‌تری داشته باشید.

---

## نکات مهم

1. **احراز هویت:** تمام APIها نیاز به Bearer Token دارند که باید در هدر `Authorization` ارسال شود.

2. **Rate Limiting:** توجه داشته باشید که ممکن است محدودیت در تعداد درخواست‌ها وجود داشته باشد.

3. **Sandbox:** برخی از APIها دارای محیط sandbox هستند که برای تست استفاده می‌شوند.

4. **Error Handling:** همیشه باید پاسخ‌های خطا را بررسی کنید:
   - `status: "DONE"` - موفقیت‌آمیز
   - `status: "FAILED"` - خطا
   - `status: "PENDING"` - در حال پردازش

5. **Data Privacy:** تمام داده‌های اعتباری محرمانه هستند و باید با احتیاط ذخیره و نمایش داده شوند.

---

## مثال ساختار پاسخ API

```json
{
  "status": "DONE",
  "result": {
    // داده‌های اصلی API
  },
  "trackId": "unique-track-id",
  "errors": []
}
```

---

**تاریخ ایجاد:** 2025-11-04  
**آخرین بروزرسانی:** 2025-11-04
