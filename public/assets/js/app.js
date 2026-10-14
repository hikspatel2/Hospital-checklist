/**
 * ==============================================================================
 * Sheth L.G. General Hospital, Ahmedabad
 * Multi-Floor Inspection & Complaint Facility Management System
 * Core Client-side Scripts (Vanilla JavaScript)
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initLiveClock();
    initCsrfAjaxHelper();
    initAutoDismissAlerts();
});

/**
 * 1. Mobile & Tablet Responsive Sidebar Navigation
 */
function initSidebar() {
    const sidebar = document.getElementById('hospitalSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!sidebar) return;

    const openSidebar = () => {
        sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
        sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    };

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });
}

/**
 * 2. Asia/Kolkata Real-time Synchronized Digital Clock
 */
function initLiveClock() {
    const clockEl = document.getElementById('liveClock');
    if (!clockEl) return;

    const updateClock = () => {
        const now = new Date();
        const options = {
            timeZone: 'Asia/Kolkata',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        try {
            clockEl.textContent = new Intl.DateTimeFormat('en-IN', options).format(now);
        } catch (e) {
            // Fallback for older browsers
            clockEl.textContent = now.toLocaleTimeString();
        }
    };

    setInterval(updateClock, 1000);
}

/**
 * 3. Global CSRF Token Helper for AJAX Requests
 */
function initCsrfAjaxHelper() {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) return;

    const token = csrfMeta.getAttribute('content');

    // Intercept standard window.fetch to automatically append CSRF headers
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        options.headers = options.headers || {};

        if (options.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method.toUpperCase())) {
            if (options.headers instanceof Headers) {
                if (!options.headers.has('X-CSRF-TOKEN')) {
                    options.headers.append('X-CSRF-TOKEN', token);
                }
            } else if (typeof options.headers === 'object') {
                if (!options.headers['X-CSRF-TOKEN']) {
                    options.headers['X-CSRF-TOKEN'] = token;
                }
            }
        }
        return originalFetch(url, options);
    };
}

/**
 * 4. Auto-dismiss Flash Alerts
 */
function initAutoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach((alert) => {
        setTimeout(() => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.click();
            }
        }, 7000);
    });
}
