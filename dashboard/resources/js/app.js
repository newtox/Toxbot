const escapeHtml = (text) =>
    String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

function renderDiscordMessage(text, ctx) {
    let html = escapeHtml(text ?? '');

    html = html
        .replaceAll('&amp;user', escapeHtml(`${ctx.username} (${ctx.displayName})`))
        .replaceAll('&amp;server', escapeHtml(ctx.server))
        .replaceAll('&amp;mention', `<span class="dc-mention">@${escapeHtml(ctx.displayName)}</span>`);

    html = html
        .replace(/&lt;#(\d+)&gt;/g, (_, id) =>
            `<span class="dc-mention">#${escapeHtml(ctx.channels?.[id] ?? ctx.unknownChannel)}</span>`)
        .replace(/&lt;@&amp;(\d+)&gt;/g, (_, id) => {
            const role = ctx.roles?.[id];
            if (!role?.color) {
                return `<span class="dc-mention">@${escapeHtml(role?.name ?? ctx.unknownRole)}</span>`;
            }
            return `<span class="dc-mention" style="color:${role.color};background:${role.color}26">@${escapeHtml(role.name)}</span>`;
        })
        .replace(/&lt;@!?(\d+)&gt;/g, () => `<span class="dc-mention">@${escapeHtml(ctx.unknownUser)}</span>`)
        .replace(/&lt;(a?):\w+:(\d+)&gt;/g, (_, animated, id) =>
            `<img src="https://cdn.discordapp.com/emojis/${id}.${animated ? 'gif' : 'webp'}?size=48" alt="" class="inline size-[1.375em] -mt-1 align-middle">`);

    return html
        .replace(/`([^`\n]+)`/g, '<code class="dc-code">$1</code>')
        .replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>')
        .replace(/__([^_\n]+)__/g, '<u>$1</u>')
        .replace(/~~([^~\n]+)~~/g, '<s>$1</s>')
        .replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>')
        .replace(/(^|[^_\w])_([^_\n]+)_(?!\w)/g, '$1<em>$2</em>');
}

function insertAtCursor(textarea, token) {
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? textarea.value.length;

    textarea.value = textarea.value.slice(0, start) + token + textarea.value.slice(end);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    textarea.focus();
    textarea.setSelectionRange(start + token.length, start + token.length);
}

function discordTimestamp(template) {
    const time = new Date().toLocaleTimeString(document.documentElement.lang || undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });

    return template.replace(':time', time);
}

const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16));

const luminance = (hex) => {
    const [r, g, b] = rgb(hex).map((v) => {
        const c = v / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

const contrast = (a, b) => {
    const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (hi + 0.05) / (lo + 0.05);
};

const mix = (a, b, t) => {
    const [ca, cb] = [rgb(a), rgb(b)];
    return '#' + ca.map((v, i) => Math.round(v + (cb[i] - v) * t).toString(16).padStart(2, '0')).join('');
};

function setAccent(hex) {
    const accent = hex.toLowerCase();
    const on = contrast(accent, '#ffffff') >= 3 ? '#ffffff' : '#101113';

    let fg = '#ffffff';
    for (let t = 0; t <= 1; t += 0.1) {
        const candidate = mix(accent, '#ffffff', t);
        if (contrast(candidate, '#1e1f22') >= 4.5) {
            fg = candidate;
            break;
        }
    }

    const style = document.documentElement.style;
    style.setProperty('--color-accent', accent);
    style.setProperty('--color-on-accent', on);
    style.setProperty('--color-accent-fg', fg);
}

window.toxbot = { renderDiscordMessage, insertAtCursor, discordTimestamp, setAccent };
