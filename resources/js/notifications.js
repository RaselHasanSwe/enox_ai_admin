const STORE_KEY = 'enox-handoff-notifications';
const SEEN_KEY = 'enox-handoff-seen-ids';

function loadList(key, fallback = []) {
    try {
        const parsed = JSON.parse(localStorage.getItem(key) || 'null');
        return Array.isArray(parsed) ? parsed : fallback;
    } catch {
        return fallback;
    }
}

function saveList(key, items) {
    localStorage.setItem(key, JSON.stringify(items.slice(0, 40)));
}

function playHandoffSound() {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return;
        const ctx = playHandoffSound.ctx || new Ctx();
        playHandoffSound.ctx = ctx;
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

function conversationFromPayload(payload) {
    const data = payload?.data || payload || {};
    const conv = data.conversation || data;
    const id = conv.id || conv.conversation_id || data.conversation_id;
    if (!id) return null;
    return {
        id,
        user_name: conv.user_name || 'Customer',
        user_email: conv.user_email || '',
        reason: conv.handoff_reason || data.handoff_reason || 'Customer requested human support',
        queued_at: conv.queued_at || new Date().toISOString(),
        at: Date.now(),
        read: false,
    };
}

function unreadCount(items) {
    return items.filter((item) => !item.read).length;
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
        list.innerHTML = '<p class="notify-empty">No handoff requests yet.</p>';
        return;
    }
    list.innerHTML = items.map((item) => `
        <button type="button" class="notify-item ${item.read ? '' : 'is-unread'}" data-notify-id="${item.id}">
            <span class="notify-item-title">${window.EnoxChat.escapeHtml(item.user_name)}</span>
            <span class="notify-item-reason">${window.EnoxChat.escapeHtml(item.reason)}</span>
            <span class="notify-item-time" data-wait-since="${window.EnoxChat.escapeHtml(item.queued_at)}">${window.EnoxChat.formatWait(item.queued_at)}</span>
        </button>
    `).join('');
}

function pushNotification(item, { play = true } = {}) {
    const seen = new Set(loadList(SEEN_KEY));
    const seenKey = `${item.id}:${item.queued_at || item.at}`;
    if (seen.has(seenKey)) return;
    seen.add(seenKey);
    saveList(SEEN_KEY, [...seen]);

    const items = loadList(STORE_KEY).filter((row) => row.id !== item.id);
    items.unshift(item);
    saveList(STORE_KEY, items);
    renderBell();
    if (play) playHandoffSound();
}

function openNotification(id) {
    const items = loadList(STORE_KEY).map((item) => item.id === Number(id) || String(item.id) === String(id) ? { ...item, read: true } : item);
    saveList(STORE_KEY, items);
    const cfg = window.ENOX_ADMIN || {};
    const url = new URL(cfg.queueUrl || '/handoff', window.location.origin);
    url.searchParams.set('focus', String(id));
    window.location.href = url.toString();
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

export function initNotifications() {
    const cfg = window.ENOX_ADMIN;
    if (!cfg?.token) return;

    renderBell();
    window.EnoxChat?.tickWaitTimes();
    setInterval(() => window.EnoxChat?.tickWaitTimes(), 1000);

    document.addEventListener('click', (event) => {
        const menu = document.getElementById('notify-menu');
        const btn = document.getElementById('notify-bell');
        if (!menu || !btn) return;
        if (menu.style.display !== 'none' && !btn.contains(event.target) && !menu.contains(event.target)) {
            menu.style.display = 'none';
        }
        const item = event.target.closest('[data-notify-id]');
        if (item) openNotification(item.getAttribute('data-notify-id'));
    });

    document.getElementById('notify-mark-read')?.addEventListener('click', (e) => {
        e.stopPropagation();
        markAllRead();
    });

    document.addEventListener('click', () => {
        playHandoffSound.ctx?.resume?.();
    }, { once: true });

    const handlePayload = (payload) => {
        if (payload?.event === 'handoff_requested') {
            const item = conversationFromPayload(payload);
            if (item) pushNotification(item, { play: true });
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
}
