document.addEventListener("DOMContentLoaded", function () {
    // --- TAB SWITCHING LOGIC ---
    const tabButtons = document.querySelectorAll(".sale-type-tab-btn");
    const tabContents = document.querySelectorAll(".sale-type-content");

    tabButtons.forEach(button => {
        button.addEventListener("click", function () {
            tabButtons.forEach(btn => btn.classList.remove("active"));
            this.classList.add("active");

            const targetId = this.dataset.target;
            tabContents.forEach(content => {
                if (content.id === targetId) {
                    content.classList.add("active");
                } else {
                    content.classList.remove("active");
                }
            });
        });
    });
    
    // --- CALCULATOR LOGIC (existing code) ---
    const calcForm = document.querySelector("form.loan-calculator-form");
    if (!calcForm) return;

    const formatMoney = (num) => Number(num).toLocaleString('fa-IR');
    const parseMoney = (str) => parseInt(String(str).replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[^0-9]/g, '')) || 0;
    const clamp = (num, min, max) => Math.min(Math.max(num, min), max);

    const actionBtn = calcForm.querySelector(".loan-action-btn");
    if (actionBtn && typeof maneli_ajax_object !== 'undefined') {
        actionBtn.addEventListener("click", function (e) {
            e.preventDefault();
            const productIdInput = calcForm.querySelector('input[name="product_id"]');
            const nonceInput = calcForm.querySelector('input[name="_wpnonce"]');
            if (!productIdInput || !nonceInput) {
                alert("خطایی رخ داده است (کد ۱). لطفاً صفحه را رفرش کنید.");
                return;
            }
            actionBtn.disabled = true;
            actionBtn.textContent = "در حال ارسال اطلاعات...";

            const downPayment = parseMoney(document.getElementById('downPaymentInput').value);
            const termMonths = document.querySelector('.term-btn.active').dataset.months;
            const installmentAmount = parseMoney(document.getElementById('installmentAmount').innerText);
            const totalPrice = document.getElementById('loan-calculator').dataset.price;

            const formData = new FormData();
            formData.append('action', 'maneli_select_car_ajax');
            formData.append('product_id', productIdInput.value);
            formData.append('nonce', nonceInput.value);
            formData.append('down_payment', downPayment);
            formData.append('term_months', termMonths);
            formData.append('installment_amount', installmentAmount);
            formData.append('total_price', totalPrice);
            
            fetch(maneli_ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = maneli_ajax_object.inquiry_page_url;
                } else {
                    alert("خطا در ارسال اطلاعات: " + data.data.message);
                    actionBtn.disabled = false;
                    actionBtn.textContent = "استعلام سنجی بانکی جهت خرید خودرو";
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("یک خطای ناشناخته در ارتباط با سرور رخ داد.");
                actionBtn.disabled = false;
                actionBtn.textContent = "استعلام سنجی بانکی جهت خرید خودرو";
            });
        });
    }
    
    const calc = document.getElementById("loan-calculator");
    if (!calc) return;
    
    const productPrice = parseInt(calc.dataset.price) || 0;
    const minDown = parseInt(calc.dataset.minDown) || 0;
    const maxDown = parseInt(calc.dataset.maxDown) || (productPrice * 0.8);
    const input = document.getElementById("downPaymentInput");
    const slider = document.getElementById("downPaymentSlider");
    const minDisplay = document.getElementById("minDownDisplay");
    const installmentEl = document.getElementById("installmentAmount");
    
    function updateSliderLook() {
        if (!slider) return;
        const percentage = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
        slider.style.setProperty('--value-percent', percentage + '%');
    }
    function calculateInstallment() {
        if (!input || !installmentEl) return;
        const dp = parseMoney(input.value);
        const activeBtn = document.querySelector(".term-btn.active");
        if (!activeBtn) return;
        const selectedMonths = parseInt(activeBtn.dataset.months);
        const loanAmount = productPrice - dp;
        if (loanAmount <= 0) { installmentEl.innerText = "0"; return; }
        const monthlyInterestAmount = loanAmount * 0.035;
        const totalInterest = monthlyInterestAmount * (selectedMonths + 1);
        const totalRepayment = loanAmount + totalInterest;
        const installment = totalRepayment / selectedMonths;
        installmentEl.innerText = formatMoney(Math.ceil(installment));
    }
    function initializeCalculator() {
        if (!slider || !input || !minDisplay) return;
        slider.min = 0;
        slider.max = productPrice;
        slider.value = minDown;
        input.value = formatMoney(minDown);
        minDisplay.innerText = formatMoney(minDown);
        updateSliderLook();
        calculateInstallment();
    }
    if (slider) {
        slider.addEventListener("input", () => { if (input) input.value = formatMoney(slider.value); updateSliderLook(); calculateInstallment(); });
        slider.addEventListener("change", () => {
            let value = parseInt(slider.value);
            let clampedValue = clamp(value, minDown, maxDown);
            if (value !== clampedValue) { slider.value = clampedValue; if (input) input.value = formatMoney(clampedValue); updateSliderLook(); }
            calculateInstallment();
        });
    }
    if(input) {
        input.addEventListener('input', () => { let value = parseMoney(input.value); if(slider) slider.value = clamp(value, 0, productPrice); updateSliderLook(); calculateInstallment(); });
        input.addEventListener('blur', () => { let value = parseMoney(input.value); let clampedValue = clamp(value, minDown, maxDown); if(slider) slider.value = clampedValue; input.value = formatMoney(clampedValue); updateSliderLook(); calculateInstallment(); });
    }
    document.querySelectorAll(".term-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelector(".term-btn.active")?.classList.remove("active");
            btn.classList.add("active");
            calculateInstallment();
        });
    });
    if (productPrice > 0) {
       initializeCalculator();
    }
});