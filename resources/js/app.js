import './bootstrap';
import { tickWaitTimes } from './chat-content';
import { initNotifications } from './notifications';
import { initDashboardCharts } from './dashboard';

const DARK_KEY = 'enox-admin-dark';
const SIDEBAR_KEY = 'enox-sidebar';

function applyDark(dark) {
    document.documentElement.classList.toggle('dark', dark);
    const sun = document.getElementById('iconSun');
    const moon = document.getElementById('iconMoon');
    if (sun) sun.classList.toggle('hidden', !dark);
    if (moon) moon.classList.toggle('hidden', dark);
}

(function initDarkMode() {
    const saved = localStorage.getItem(DARK_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyDark(saved !== null ? saved === 'true' : prefersDark);
})();

window.toggleDark = function () {
    const current = document.documentElement.classList.contains('dark');
    localStorage.setItem(DARK_KEY, String(!current));
    applyDark(!current);
};

window.toggleSidebar = function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;
    const isOpen = sidebar.classList.contains('mobile-open');
    sidebar.classList.toggle('mobile-open', !isOpen);
    if (backdrop) backdrop.classList.toggle('hidden', isOpen);
};

window.closeSidebar = function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) sidebar.classList.remove('mobile-open');
    if (backdrop) backdrop.classList.add('hidden');
};

window.toggleUserDropdown = function (event) {
    event.stopPropagation();
    const menu = document.getElementById('user-dropdown-menu');
    const icon = document.getElementById('user-dropdown-icon');
    const notify = document.getElementById('notify-menu');
    if (notify) notify.style.display = 'none';
    if (!menu) return;
    const isHidden = menu.style.display === '' || menu.style.display === 'none';
    menu.style.display = isHidden ? 'block' : 'none';
    if (icon) icon.classList.toggle('rotate-180', isHidden);
};

document.addEventListener('click', function (event) {
    const menu = document.getElementById('user-dropdown-menu');
    const btn = document.getElementById('user-dropdown-btn');
    if (!menu || !btn) return;
    if (menu.style.display !== 'none' && !btn.contains(event.target)) {
        menu.style.display = 'none';
        const icon = document.getElementById('user-dropdown-icon');
        if (icon) icon.classList.remove('rotate-180');
    }
});

function updateSidebarIcons() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    const isSmall = sidebar.classList.contains('small-sidebar');
    const menuIcon = document.getElementById('menuIcon');
    const closeIcon = document.getElementById('closeIcon');
    if (menuIcon) menuIcon.classList.toggle('hidden', isSmall);
    if (closeIcon) closeIcon.classList.toggle('hidden', !isSmall);
}

document.addEventListener('DOMContentLoaded', function () {
    tickWaitTimes();
    setInterval(tickWaitTimes, 1000);
    initNotifications();
    initDashboardCharts();
    const customToggle = document.getElementById('dash-custom-toggle');
    const customFields = document.getElementById('dash-custom-fields');
    if (customToggle && customFields) {
        customToggle.addEventListener('click', () => {
            customFields.classList.remove('hidden');
            document.querySelectorAll('.dash-filter-pills .dash-filter').forEach((el) => {
                el.classList.toggle('is-active', el === customToggle);
            });
        });
    }
    document.querySelectorAll('.handoff-panel-thread').forEach((el) => {
        el.scrollTop = el.scrollHeight;
    });

    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!sidebar) return;

    if (localStorage.getItem(SIDEBAR_KEY) === 'small') {
        sidebar.classList.add('small-sidebar');
    }
    document.documentElement.classList.remove('sidebar-small-layout');
    updateSidebarIcons();

    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('small-sidebar');
            localStorage.setItem(SIDEBAR_KEY, sidebar.classList.contains('small-sidebar') ? 'small' : 'large');
            updateSidebarIcons();
        });
    }

    sidebar.addEventListener('mouseenter', function () {
        if (sidebar.classList.contains('small-sidebar')) {
            sidebar.classList.add('expanded-hover');
        }
    });

    sidebar.addEventListener('mouseleave', function () {
        sidebar.classList.remove('expanded-hover');
    });

    const active = sidebar.querySelector('.nav-active');
    if (active) {
        active.scrollIntoView({ block: 'center' });
    }
});
