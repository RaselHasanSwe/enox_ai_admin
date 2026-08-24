const STORE_KEY = 'enox-admin-notifications';
const SEEN_KEY = 'enox-admin-seen-keys';
const SUMMARY_KEY = 'enox-admin-summary';

function loadList(key, fallback = []) {
    try {
        const parsed = JSON.parse(localStorage.getItem(key) || 'null');
        return Array.isArray(parsed) ? parsed : fallback;
    } catch {
        return fallback;
    }
}

function saveList(key, items) {
    localStorage.setItem(key, JSON.stringify(items.slice(0, 50)));
}

function loadSeen() {
    try {
        const parsed = JSON.parse(localStorage.getItem(SEEN_KEY) || 'null');
        return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
        return {};
    }
}

function saveSeen(seen) {
    localStorage.setItem(SEEN_KEY, JSON.stringify(seen));
}

function playAlertSound() {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        const ctx = playAlertSound.ctx || new Ctx();
        playAlertSound.ctx = ctx;
        if (ctx.state === 'suspended') ctx.resume();
        const now = ctx.currentTime;
        [880, 1174].forEach((freq, i) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.12, now + 0.02 + i * 0.08);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28 + i * 0.12);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now + i * 0.08);
            osc.stop(now + 0.35 + i * 0.12);
        });
    } catch {
        // Autoplay can be blocked until a click; ignore.
    }
}

function itemKey(item) {
    return `${item.type}:${item.id}:${item.at || item.created_at || ''}`;
}

function unreadCount(items) {
    return items.filter((item) => !item.read).length;
}

function formatCategory(category) {
    return (category || 'inquiry').replace(/_/g, ' ');
}

function handoffFromPayload(payload) {
    const data = payload?.data || payload || {};
    const conv = data.conversation || data;
    const id = conv.id || conv.conversation_id || data.conversation_id;
    if (!id) return null;
    return {
        type: 'handoff',
        id,
        title: conv.user_name || 'Customer',
        subtitle: conv.handoff_reason || data.handoff_reason || 'Customer requested human support',
        created_at: conv.queued_at || new Date().toISOString(),
        at: Date.now(),
        read: false,
        url: (() => {
            const cfg = window.ENOX_ADMIN || {};
            const url = new URL(cfg.queueUrl || '/handoff', window.location.origin);
            url.searchParams.set('focus', String(id));
            return url.toString();
        })(),
    };
}

function inquiryFromPayload(payload) {
    const data = payload?.data || payload || {};
    const inquiry = data.inquiry || data;
    const id = inquiry.id;
    if (!id) return null;
    const cfg = window.ENOX_ADMIN || {};
    const url = new URL(cfg.inquiriesUrl || '/inquiries', window.location.origin);
    url.pathname = `${url.pathname.replace(/\/$/, '')}/${id}`;
    return {
        type: 'inquiry',
        id,
        title: inquiry.reference || `Inquiry #${id}`,
        subtitle: `${formatCategory(inquiry.category)} — ${inquiry.name || inquiry.email || 'Customer'}`,
        created_at: inquiry.created_at || new Date().toISOString(),
        at: Date.now(),
        read: false,
        url: url.toString(),
    };
}

function renderBell() {
    const items = loadList(STORE_KEY);
    const badge = document.getElementById('notify-badge');
    const list = document.getElementById('notify-list');
    const count = unreadCount(items);
    if (badge) {
        badge.textContent = count > 9 ? '9+' : String(count);
        badge.classList.toggle('hidden', count === 0);
    }
    const bell = document.getElementById('notify-bell');
    if (bell) bell.classList.toggle('has-unread', count > 0);
    if (!list) return;
    if (!items.length) {
        list.innerHTML = '<p class="notify-empty">No new alerts yet.</p>';
        return;
    }
    list.innerHTML = items.map((item) => `
        <button type="button" class="notify-item ${item.read ? '' : 'is-unread'}" data-notify-key="${window.EnoxChat.escapeHtml(itemKey(item))}">
            <span class="notify-item-title">${window.EnoxChat.escapeHtml(item.title)}</span>
            <span class="notify-item-reason">${window.EnoxChat.escapeHtml(item.subtitle)}</span>
            <span class="notify-item-time" data-wait-since="${window.EnoxChat.escapeHtml(item.created_at)}">${window.EnoxChat.formatWait(item.created_at)}</span>
        </button>
    `).join('');
}

function showBrowserNotification(item) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    const title = item.type === 'handoff' ? 'New handoff request' : 'New inquiry';
    const options = {
        body: `${item.title}: ${item.subtitle}`,
        tag: itemKey(item),
        data: { url: item.url },
        icon: '/favicon.ico',
    };
    try {
        if (navigator.serviceWorker?.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: 'SHOW_NOTIFICATION',
                title,
                options,
            });
            return;
        }
        const notification = new Notification(title, options);
        notification.onclick = () => {
            window.focus();
            if (item.url) window.location.href = item.url;
            notification.close();
        };
    } catch {
        // Ignore notification errors in unsupported browsers.
    }
}

function pushNotification(item, { play = true, notify = true } = {}) {
    const seen = loadSeen();
    const key = itemKey(item);
    if (seen[key]) return;
    seen[key] = Date.now();
    saveSeen(seen);

    const items = loadList(STORE_KEY).filter((row) => itemKey(row) !== key);
    items.unshift(item);
    saveList(STORE_KEY, items);
    renderBell();
    if (play) playAlertSound();
    if (notify && document.hidden) showBrowserNotification(item);
}

function openNotification(key) {
    const items = loadList(STORE_KEY).map((item) => (
        itemKey(item) === key ? { ...item, read: true } : item
    ));
    const target = items.find((item) => itemKey(item) === key);
    saveList(STORE_KEY, items);
    renderBell();
    if (target?.url) window.location.href = target.url;
}

function markAllRead() {
    saveList(STORE_KEY, loadList(STORE_KEY).map((item) => ({ ...item, read: true })));
    renderBell();
}

window.toggleNotifyDropdown = function (event) {
    event.stopPropagation();
    const menu = document.getElementById('notify-menu');
    if (!menu) return;
    const userMenu = document.getElementById('user-dropdown-menu');
    if (userMenu) userMenu.style.display = 'none';
    const isHidden = menu.style.display === '' || menu.style.display === 'none';
    menu.style.display = isHidden ? 'block' : 'none';
};

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    try {
        await navigator.serviceWorker.register('/sw.js');
    } catch {
        // Service worker registration is optional.
    }
}

async function requestNotificationPermission() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
        try {
            await Notification.requestPermission();
        } catch {
            // User dismissed the prompt.
        }
    }
}

async function pollNotificationSummary(cfg) {
    try {
        const response = await fetch(`${cfg.apiUrl.replace(/\/$/, '')}/admin/notifications/summary`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${cfg.token}`,
            },
        });
        if (!response.ok) return;
        const summary = await response.json();
        const previous = JSON.parse(localStorage.getItem(SUMMARY_KEY) || '{}');
        localStorage.setItem(SUMMARY_KEY, JSON.stringify(summary));

        if (summary.pending_inquiries > (previous.pending_inquiries || 0)) {
            pushNotification({
                type: 'inquiry',
                id: `summary-${summary.pending_inquiries}`,
                title: 'New inquiries waiting',
                subtitle: `${summary.pending_inquiries} pending inquiry request(s)`,
                created_at: new Date().toISOString(),
                at: Date.now(),
                read: false,
                url: cfg.inquiriesUrl || '/inquiries',
            }, { play: false, notify: document.hidden });
        }
        if (summary.queued_handoffs > (previous.queued_handoffs || 0)) {
            pushNotification({
                type: 'handoff',
                id: `summary-${summary.queued_handoffs}`,
                title: 'Handoff queue updated',
                subtitle: `${summary.queued_handoffs} customer(s) waiting for an agent`,
                created_at: new Date().toISOString(),
                at: Date.now(),
                read: false,
                url: cfg.queueUrl || '/handoff',
            }, { play: false, notify: document.hidden });
        }
    } catch {
        // Polling fallback should not break the UI.
    }
}

export function initNotifications() {
    const cfg = window.ENOX_ADMIN;
    if (!cfg?.token) return;

    renderBell();
    registerServiceWorker();
    requestNotificationPermission();
    window.EnoxChat?.tickWaitTimes();
    setInterval(() => window.EnoxChat?.tickWaitTimes(), 1000);

    document.addEventListener('click', (event) => {
        const menu = document.getElementById('notify-menu');
        const btn = document.getElementById('notify-bell');
        if (!menu || !btn) return;
        if (menu.style.display !== 'none' && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
        }
        const item = event.target.closest('[data-notify-key]');
        if (item) openNotification(item.getAttribute('data-notify-key'));
    });

    document.getElementById('notify-mark-read')?.addEventListener('click', (e) => {
        e.stopPropagation();
        markAllRead();
    });

    document.addEventListener('click', () => {
        playAlertSound.ctx?.resume?.();
        requestNotificationPermission();
    }, { once: true });

    const handlePayload = (payload) => {
        if (payload?.event === 'handoff_requested') {
            const item = handoffFromPayload(payload);
            if (item) pushNotification(item, { play: true, notify: true });
        }
        if (payload?.event === 'inquiry_created') {
            const item = inquiryFromPayload(payload);
            if (item) pushNotification(item, { play: true, notify: true });
        }
        window.dispatchEvent(new CustomEvent('enox:queue-refresh', { detail: payload }));
    };

    try {
        const ws = new WebSocket(`${cfg.wsUrl.replace(/\/$/, '')}/ws/admin/queue?token=${encodeURIComponent(cfg.token)}`);
        ws.onmessage = (event) => {
            try { handlePayload(JSON.parse(event.data)); } catch {}
        };
    } catch {}

    try {
        const es = new EventSource(`${cfg.apiUrl.replace(/\/$/, '')}/admin/handoff/stream?token=${encodeURIComponent(cfg.token)}`);
        es.onmessage = (event) => {
            try { handlePayload(JSON.parse(event.data)); } catch {}
        };
    } catch {}

    pollNotificationSummary(cfg);
    setInterval(() => pollNotificationSummary(cfg), 30000);
}
