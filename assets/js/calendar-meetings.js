/**
 * Calendar Meetings JavaScript
 * FullCalendar with Persian Date Support
 */

(function() {
    'use strict';

    // Load FullCalendar immediately and independently
    function loadFullCalendar() {
        return new Promise((resolve, reject) => {
            if (typeof FullCalendar !== 'undefined') {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js';
            script.onload = () => {
                console.log('FullCalendar loaded successfully');
                resolve();
            };
            script.onerror = () => {
                console.error('Failed to load FullCalendar from CDN');
                reject();
            };
            document.head.appendChild(script);
        });
    }
    
    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, initializing calendar...');
        
        // Suppress all custom.js errors
        const originalConsoleError = console.error;
        console.error = function(...args) {
            if (args[0] && args[0].includes && args[0].includes('innerHTML')) {
                console.warn('Custom.js error suppressed:', ...args);
                return;
            }
            originalConsoleError.apply(console, args);
        };
        
        // Load FullCalendar immediately
        loadFullCalendar().then(() => {
            console.log('FullCalendar ready, initializing calendar...');
            initCalendar();
            initViewButtons();
            initTableFeatures();
        }).catch(() => {
            console.error('Failed to load FullCalendar');
        });
        
        // Also try the old method as backup
        setTimeout(function() {
        
        // Wait for FullCalendar to load
        function waitForFullCalendar() {
            if (typeof FullCalendar !== 'undefined') {
                console.log('FullCalendar is now available');
                initCalendar();
                initViewButtons();
                initTableFeatures();
            } else {
                console.log('Waiting for FullCalendar...');
                // Check if we've been waiting too long
                if (typeof waitForFullCalendar.attempts === 'undefined') {
                    waitForFullCalendar.attempts = 0;
                }
                waitForFullCalendar.attempts++;
                
                if (waitForFullCalendar.attempts > 30) { // 3 seconds max
                    console.error('FullCalendar failed to load after 3 seconds');
                    console.log('Trying to load FullCalendar manually...');
                    
                    // Try to load FullCalendar manually
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js';
                    script.onload = function() {
                        console.log('FullCalendar loaded manually');
                        initCalendar();
                        initViewButtons();
                        initTableFeatures();
                    };
                    script.onerror = function() {
                        console.error('Failed to load FullCalendar from CDN');
                    };
                    document.head.appendChild(script);
                    return;
                }
                
                setTimeout(waitForFullCalendar, 100);
            }
        }
        
        // Start waiting
        waitForFullCalendar();
        
        }, 1000); // Wait 1 second for custom.js to finish
    });
    
    // Global function for manual initialization
    window.initCalendarManually = function() {
        console.log('Manual calendar initialization started');
        initCalendar();
        initViewButtons();
        initTableFeatures();
    };

    /**
     * Initialize FullCalendar with Persian date support
     */
    function initCalendar() {
        const calendarEl = document.getElementById('meetings-calendar');
        if (!calendarEl) {
            console.error('Calendar element not found!');
            return;
        }
        
        console.log('Initializing calendar...');
        console.log('Calendar data:', window.meetingsCalendarData);

        // Persian locale configuration
        const persianLocale = {
            code: 'fa',
            week: {
                dow: 6, // Saturday is the first day of the week
                doy: 12 // The week that contains Jan 1st is the first week of the year
            },
            direction: 'rtl',
            buttonText: {
                prev: 'قبلی',
                next: 'بعدی',
                today: 'امروز',
                month: 'ماه',
                week: 'هفته',
                day: 'روز',
                list: 'لیست'
            },
            allDayText: 'تمام روز',
            moreLinkText: 'بیشتر',
            noEventsText: 'هیچ رویدادی وجود ندارد',
            weekText: 'هفته',
            dayText: 'روز',
            monthText: 'ماه'
        };

        // Check if FullCalendar is available
        if (typeof FullCalendar === 'undefined') {
            console.error('FullCalendar is not loaded!');
            console.log('Available globals:', Object.keys(window).filter(key => key.includes('Calendar')));
            return;
        }
        
        console.log('FullCalendar is available, creating calendar...');
        console.log('FullCalendar version:', FullCalendar.version || 'Unknown');
        
        // Initialize FullCalendar
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                right: 'prev,next today',
                center: 'title',
                left: ''
            },
            locale: persianLocale,
            direction: 'rtl',
            firstDay: 6, // Saturday
            height: 'auto',
            contentHeight: 600,
            aspectRatio: 1.8,
            dayMaxEvents: 3,
            moreLinkClick: 'popover',
            events: window.meetingsCalendarData || [],
            eventDisplay: 'block',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            eventClassNames: function(arg) {
                const props = arg.event.extendedProps;
                if (props.inquiry_type === 'cash') {
                    return ['meeting-cash'];
                } else if (props.inquiry_type === 'installment') {
                    return ['meeting-installment'];
                } else if (!props.can_view_details) {
                    return ['meeting-reserved'];
                }
                return [];
            },
            eventContent: function(arg) {
                const props = arg.event.extendedProps;
                const time = props.time || '';
                
                return {
                    html: `
                        <div class="fc-event-main-frame">
                            <div class="fc-event-title-container">
                                <div class="fc-event-title fc-sticky">${arg.event.title}</div>
                            </div>
                            <div class="fc-event-time">${time}</div>
                        </div>
                    `
                };
            },
            eventClick: function(info) {
                const props = info.event.extendedProps;
                if (props.can_view_details && props.inquiry_id) {
                    const url = `/dashboard/inquiries/${props.inquiry_type === 'cash' ? 'cash' : 'installment'}?${props.inquiry_type === 'cash' ? 'cash_inquiry_id' : 'inquiry_id'}=${props.inquiry_id}`;
                    window.open(url, '_blank');
                } else {
                    showMeetingDetails(info.event);
                }
            },
            eventMouseEnter: function(info) {
                const props = info.event.extendedProps;
                showTooltip(info.event, info.jsEvent);
            },
            eventMouseLeave: function() {
                hideTooltip();
            },
            datesSet: function(info) {
                updateCalendarTitle(info);
            },
            loading: function(bool) {
                if (bool) {
                    calendarEl.classList.add('fc-loading');
                } else {
                    calendarEl.classList.remove('fc-loading');
                }
            }
        });

        // Render calendar
        try {
            calendar.render();
            console.log('Calendar rendered successfully');
        } catch (error) {
            console.error('Error rendering calendar:', error);
            return;
        }

        // Store calendar instance globally for view buttons
        window.meetingsCalendar = calendar;
    }

    /**
     * Initialize view control buttons
     */
    function initViewButtons() {
        const todayBtn = document.getElementById('calendar-today');
        const dayBtn = document.getElementById('calendar-day');
        const weekBtn = document.getElementById('calendar-week');
        const monthBtn = document.getElementById('calendar-month');

        if (todayBtn) {
            todayBtn.addEventListener('click', function() {
                if (window.meetingsCalendar) {
                    window.meetingsCalendar.today();
                    updateActiveButton(this);
                }
            });
        }

        if (dayBtn) {
            dayBtn.addEventListener('click', function() {
                if (window.meetingsCalendar) {
                    window.meetingsCalendar.changeView('timeGridDay');
                    updateActiveButton(this);
                }
            });
        }

        if (weekBtn) {
            weekBtn.addEventListener('click', function() {
                if (window.meetingsCalendar) {
                    window.meetingsCalendar.changeView('timeGridWeek');
                    updateActiveButton(this);
                }
            });
        }

        if (monthBtn) {
            monthBtn.addEventListener('click', function() {
                if (window.meetingsCalendar) {
                    window.meetingsCalendar.changeView('dayGridMonth');
                    updateActiveButton(this);
                }
            });
        }
    }

    /**
     * Update active button styling
     */
    function updateActiveButton(activeBtn) {
        const buttons = document.querySelectorAll('#calendar-today, #calendar-day, #calendar-week, #calendar-month');
        buttons.forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        activeBtn.classList.remove('btn-outline-primary');
        activeBtn.classList.add('btn-primary');
    }

    /**
     * Initialize table features
     */
    function initTableFeatures() {
        const table = document.getElementById('meetings-table');
        if (!table) return;

        // Add hover effects
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'all 0.3s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });

        // Add click handlers for action buttons
        const actionButtons = table.querySelectorAll('a[href*="inquiries"]');
        actionButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                window.open(url, '_blank');
            });
        });
    }

    /**
     * Show meeting details in a modal or popover
     */
    function showMeetingDetails(event) {
        const props = event.extendedProps;
        const title = event.title;
        const time = props.time || '';
        const customer = props.customer || '';
        const mobile = props.mobile || '';
        const product = props.product || '';
        
        // Create modal content
        const modalContent = `
            <div class="modal fade" id="meetingDetailsModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="la la-calendar me-2"></i>
                                جزئیات جلسه
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">نام و نام خانوادگی:</label>
                                    <p class="form-control-plaintext">${customer}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">ساعت جلسه:</label>
                                    <p class="form-control-plaintext">${time}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">شماره تماس:</label>
                                    <p class="form-control-plaintext">${mobile}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">محصول:</label>
                                    <p class="form-control-plaintext">${product}</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal
        const existingModal = document.getElementById('meetingDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalContent);

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('meetingDetailsModal'));
        modal.show();
    }

    /**
     * Show tooltip for events
     */
    function showTooltip(event, jsEvent) {
        const props = event.extendedProps;
        const tooltip = document.createElement('div');
        tooltip.className = 'meeting-tooltip';
        tooltip.innerHTML = `
            <div class="tooltip-content">
                <div class="tooltip-title">${event.title}</div>
                <div class="tooltip-time">${props.time || ''}</div>
                <div class="tooltip-product">${props.product || ''}</div>
            </div>
        `;
        
        tooltip.style.cssText = `
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            z-index: 1000;
            pointer-events: none;
            max-width: 200px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = tooltip.getBoundingClientRect();
        const x = jsEvent.clientX - rect.width / 2;
        const y = jsEvent.clientY - rect.height - 10;
        
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    }

    /**
     * Hide tooltip
     */
    function hideTooltip() {
        const tooltip = document.querySelector('.meeting-tooltip');
        if (tooltip) {
            tooltip.remove();
        }
    }

    /**
     * Update calendar title with Persian date
     */
    function updateCalendarTitle(info) {
        const titleElement = document.querySelector('.fc-toolbar-title');
        if (titleElement && window.meetingsCalendarData) {
            // Convert to Persian date if possible
            const currentDate = info.view.calendar.getDate();
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1;
            const day = currentDate.getDate();
            
            // Simple Persian month names
            const persianMonths = [
                'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
                'مرداد', 'شهریور', 'مهر', 'آبان',
                'آذر', 'دی', 'بهمن', 'اسفند'
            ];
            
            // This is a simplified conversion - in production, use a proper Persian date library
            const persianYear = year - 621;
            const persianMonth = persianMonths[month - 1] || month;
            
            if (info.view.type === 'dayGridMonth') {
                titleElement.textContent = `${persianMonth} ${persianYear}`;
            } else if (info.view.type === 'timeGridWeek') {
                titleElement.textContent = `هفته ${persianMonth} ${persianYear}`;
            } else if (info.view.type === 'timeGridDay') {
                titleElement.textContent = `${day} ${persianMonth} ${persianYear}`;
            }
        }
    }

    /**
     * Add Persian number conversion
     */
    function toPersianNumbers(str) {
        const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.replace(/\d/g, function(digit) {
            return persianNumbers[parseInt(digit)];
        });
    }

    /**
     * Initialize table sorting and filtering
     */
    function initTableSorting() {
        const table = document.getElementById('meetings-table');
        if (!table) return;

        // Add sorting functionality
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, index);
            });
        });
    }

    /**
     * Sort table by column
     */
    function sortTable(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            // Handle different data types
            if (columnIndex === 1) { // Date column
                return new Date(aText) - new Date(bText);
            } else if (columnIndex === 2) { // Time column
                return aText.localeCompare(bText);
            } else {
                return aText.localeCompare(bText, 'fa');
            }
        });
        
        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    // Initialize table sorting when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initTableSorting();
    });

})();
