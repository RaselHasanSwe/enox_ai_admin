function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function stripJsonFences(text) {
    return String(text || '')
        .replace(/^```(?:json)?\s*/i, '')
        .replace(/\s*```$/i, '')
        .trim();
}

function looksLikeJson(text) {
    const stripped = stripJsonFences(text);
    return stripped.startsWith('{') && stripped.endsWith('}');
}

function tryParseJson(text) {
    try {
        return JSON.parse(stripJsonFences(text));
    } catch {
        return null;
    }
}

function uploadUrl(imagePath) {
    if (!imagePath) return null;
    if (/^(https?:|data:)/i.test(imagePath)) return imagePath;
    const api = window.ENOX_ADMIN?.apiUrl || '';
    return api.replace(/\/$/, '') + '/chat/uploads/' + String(imagePath).replace(/^\//, '');
}

function productImageUrl(image) {
    if (Array.isArray(image)) image = image[0];
    if (image == null || image === '') return null;
    const value = String(image).trim();
    if (!value) return null;
    if (/^(https?:|data:)/i.test(value)) return value;
    const cdn = (window.ENOX_ADMIN?.productCdn || 'https://images.enorsia.com').replace(/\/$/, '');
    const file = value.replace(/^\//, '');
    if (file.includes('/pdrelimg')) return `${cdn}/${file}`;
    return `${cdn}/${file}/pdrelimg`;
}

function senderLabel(sender, agentName = null) {
    const value = String(sender || '').toLowerCase();
    if (['ai', 'assistant', 'bot'].includes(value)) return 'AI';
    if (value === 'user') return 'User';
    if (value === 'agent') return agentName || window.ENOX_ADMIN?.agentName || 'Agent';
    if (value === 'system') return 'System';
    return sender ? String(sender).charAt(0).toUpperCase() + String(sender).slice(1) : 'AI';
}

function extractImages(raw) {
    const images = [];
    let text = String(raw || '');
    text = text.replace(/data:image\/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+/=\s]+/g, (m) => {
        images.push(m.replace(/\s+/g, ''));
        return '';
    });
    text = text.replace(/!\[[^\]]*]\(([^)]+)\)/g, (_, src) => {
        images.push(src.trim().replace(/^["']|["']$/g, ''));
        return '';
    });
    return { text: text.trim(), images };
}

function markdownToHtml(text) {
    let html = escapeHtml(text).replace(/\r\n/g, '\n');
    html = html.replace(/```([\s\S]*?)```/g, (_, code) => `<pre class="chat-json">${code.trim()}</pre>`);
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/(^|\s)\*([^*]+)\*/g, '$1<em>$2</em>');
    html = html.replace(/\[([^\]]+)]\((https?:[^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
    html = html.replace(/^### (.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^## (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^# (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^\s*[-*] (.+)$/gm, '<li>$1</li>');
    html = html.replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>');
    html = html.replace(/\n{2,}/g, '</p><p>');
    html = html.replace(/\n/g, '<br>');
    return `<p>${html}</p>`;
}

function parseMessage(raw, extras = {}) {
    const images = [];
    if (extras.image_path) {
        const url = uploadUrl(extras.image_path);
        if (url) images.push(url);
    }
    const extracted = extractImages(raw || '');
    images.push(...extracted.images);

    let plain = extracted.text.replace(/[A-Za-z0-9+/=]{200,}/g, '[binary data omitted]').trim();
    let products = [];
    let jsonPretty = null;

    if (looksLikeJson(plain) || plain.includes('"product_data"') || plain.includes('"products"')) {
        const parsed = tryParseJson(plain) || tryParseJson((plain.match(/\{[\s\S]*\}/) || [])[0] || '');
        if (parsed && typeof parsed === 'object') {
            const list = []
                .concat(Array.isArray(parsed.product_data) ? parsed.product_data : [])
                .concat(Array.isArray(parsed.products) ? parsed.products : [])
                .filter((item) => item && item.product_name)
                .map((item) => ({ ...item, product_image: productImageUrl(item.product_image) }));
            if (parsed.message || list.length) {
                products = list;
                plain = (parsed.message || '').trim();
                if (!plain && products.length) {
                    plain = 'I found these products: ' + products.map((p) => p.product_name).join(', ');
                }
            } else {
                jsonPretty = JSON.stringify(parsed, null, 2);
                plain = '';
            }
        }
    }

    return { plain, images: [...new Set(images)], products, jsonPretty };
}

function productHtml(products) {
    return products.map((product) => {
        const href = escapeHtml(product.product_url || '#');
        const src = productImageUrl(product.product_image);
        const img = src
            ? `<img src="${escapeHtml(src)}" alt="" class="chat-product-img">`
            : '';
        const price = product.discount_price ?? product.price;
        const currency = product.currency || 'GBP';
        const symbol = currency === 'GBP' ? '£' : currency === 'USD' ? '$' : currency === 'EUR' ? '€' : `${currency} `;
        return `<a class="chat-product" href="${href}" target="_blank" rel="noopener">${img}<span class="chat-product-body"><span class="chat-product-name">${escapeHtml(product.product_name || 'Product')}</span>${price != null ? `<span class="chat-product-price">${symbol}${escapeHtml(price)}</span>` : ''}</span></a>`;
    }).join('');
}

export function renderMessageHtml(raw, extras = {}) {
    const sender = extras.sender || 'ai';
    const parsed = parseMessage(raw, extras);
    const images = parsed.images.map((src) => `<a href="${escapeHtml(src)}" target="_blank" rel="noopener" class="chat-image-link"><img src="${escapeHtml(src)}" alt="Attachment" class="chat-image"></a>`).join('');
    const md = parsed.plain ? `<div class="chat-md">${markdownToHtml(parsed.plain)}</div>` : '';
    const json = parsed.jsonPretty ? `<pre class="chat-json">${escapeHtml(parsed.jsonPretty)}</pre>` : '';
    const products = parsed.products.length ? `<div class="chat-products">${productHtml(parsed.products)}</div>` : '';
    const stamp = extras.timestamp ? formatTimestamp(extras.timestamp) : '';
    const label = extras.senderLabel || senderLabel(sender, extras.agentName);
    const meta = extras.showMeta
        ? `<div class="chat-meta">${escapeHtml(label)}${stamp ? ' · ' + escapeHtml(stamp) : ''}</div>`
        : '';
    const align = sender === 'user' ? 'user' : sender === 'agent' ? 'agent' : 'ai';
    return `<div class="chat-row is-${align}"><div class="chat-bubble msg-${escapeHtml(sender)}">${meta}${images}${md}${json}${products}</div></div>`;
}

export function readableLine(text) {
    let value = String(text || '').replace(/data:image\/[a-zA-Z0-9.+-]+;base64,[A-Za-z0-9+/=\s]+/g, '[Image attached]').trim();
    const best = extractBestMessage(value);
    if (best) {
        value = best;
    }
    value = value.replace(/\\n/g, ' ').replace(/\\"/g, '"').replace(/[A-Za-z0-9+/=]{80,}/g, '').replace(/```(?:json)?/gi, '');
    value = value.replace(/^\s*[{}\[\],]+\s*/, '').replace(/\s+/g, ' ').trim().replace(/^["'`]+|["'`]+$/g, '');
    if (['[JSON payload]', '[binary data omitted]', 'json'].includes(value) || value.startsWith('{')) return '';
    return value;
}

function extractBestMessage(text) {
    const re = /"message"\s*:\s*"/g;
    const candidates = [];
    let match;
    while ((match = re.exec(text))) {
        const start = match.index + match[0].length;
        let out = '';
        for (let i = start; i < text.length; i++) {
            const ch = text[i];
            if (ch === '\\' && i + 1 < text.length) {
                const next = text[i + 1];
                out += ({ n: ' ', t: ' ', r: '', '"': '"', '\\': '\\' }[next] || next);
                i++;
                continue;
            }
            if (ch === '"') break;
            out += ch;
        }
        const trimmed = out.trim();
        if (trimmed && !trimmed.startsWith('```') && !trimmed.startsWith('{')) {
            candidates.push(trimmed);
        }
    }
    return candidates.length ? candidates[candidates.length - 1] : null;
}

export function summaryText(raw) {
    const source = String(raw || '');
    const re = /\[(ai|user|agent|bot|assistant|system)]\s*(.*?)(?=\s*\[(?:ai|user|agent|bot|assistant|system)]|\s*Tools used:|\s*$)/gis;
    let lastUser = '';
    let lastAi = '';
    let match;
    while ((match = re.exec(source))) {
        const sender = match[1].toLowerCase();
        const text = readableLine(match[2]);
        if (!text) continue;
        if (sender === 'user') lastUser = text;
        if (sender === 'ai' || sender === 'bot' || sender === 'assistant') lastAi = text;
    }
    const text = lastUser || lastAi || readableLine(source);
    return text ? text.slice(0, 140) : '—';
}

export function formatTimestamp(iso) {
    if (!iso) return '';
    const raw = String(iso).trim().replace(' ', 'T');
    const date = new Date(/Z|[+-]\d{2}:\d{2}$/.test(raw) ? raw : raw + 'Z');
    if (Number.isNaN(date.getTime())) return String(iso);
    const now = new Date();
    const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    const time = date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: false });
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    if (sameDay(date, now)) return `Today, ${time}`;
    if (sameDay(date, yesterday)) return `Yesterday, ${time}`;
    const day = date.toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: date.getFullYear() === now.getFullYear() ? undefined : 'numeric',
    });
    return `${day}, ${time}`;
}

export function formatWait(iso) {
    if (!iso) return '—';
    const date = new Date(/Z|[+-]\d{2}:\d{2}$/.test(iso) ? iso : iso + 'Z');
    if (Number.isNaN(date.getTime())) return iso;
    const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
    if (seconds < 8) return 'Just now';
    if (seconds < 60) return `${seconds}s ago`;
    if (seconds < 3600) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return s ? `${m}m ${s}s ago` : `${m}m ago`;
    }
    if (seconds < 86400) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        return m ? `${h}h ${m}m ago` : `${h}h ago`;
    }
    return date.toLocaleString(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function tickWaitTimes(root = document) {
    root.querySelectorAll('[data-wait-since]').forEach((el) => {
        el.textContent = formatWait(el.getAttribute('data-wait-since'));
    });
}

window.EnoxChat = { renderMessageHtml, summaryText, formatWait, formatTimestamp, tickWaitTimes, escapeHtml };
