/* Avroria wiki — общие утилиты и поиск. */
(function () {
    'use strict';

    const BASE = window.AVRORIA_BASE || '';
    const api = (p) => BASE + '/api/' + p;
    const I18N = window.AVRORIA_I18N || {};

    const Avroria = {
        BASE,
        api,
        I18N,
        /* Локализованная строка. Поддерживает %d/%s — подставляется первый аргумент. */
        t(key, arg) {
            let s = I18N[key] != null ? I18N[key] : key;
            if (arg !== undefined) s = s.replace(/%[ds]/, arg);
            return s;
        },
        el(tag, attrs, children) {
            const node = document.createElement(tag);
            if (attrs) {
                for (const k in attrs) {
                    if (k === 'class') node.className = attrs[k];
                    else if (k === 'html') node.innerHTML = attrs[k];
                    else if (k === 'text') node.textContent = attrs[k];
                    else if (k.startsWith('on') && typeof attrs[k] === 'function') {
                        node.addEventListener(k.slice(2), attrs[k]);
                    } else if (attrs[k] !== false && attrs[k] != null) {
                        node.setAttribute(k, attrs[k]);
                    }
                }
            }
            (children || []).forEach((c) =>
                node.appendChild(typeof c === 'string' ? document.createTextNode(c) : c)
            );
            return node;
        },
        debounce(fn, ms) {
            let t;
            return function (...a) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, a), ms);
            };
        },
        escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, (c) =>
                ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])
            );
        },
    };

    /* ---- Поиск (поддержка нескольких форм .searchbar) ---- */
    function wireSearch(form) {
        const input = form.querySelector('input[type="search"]');
        const box = form.querySelector('.searchbar__results');
        if (!input || !box) return;

        function render(results) {
            box.innerHTML = '';
            if (!results.length) {
                box.appendChild(Avroria.el('li', { class: 'sr-empty', text: Avroria.t('js.search_empty') }));
                box.hidden = false;
                return;
            }
            results.forEach((r) => {
                const a = Avroria.el('a', { href: r.url }, [r.name]);
                if (r.snippet) {
                    a.appendChild(Avroria.el('span', { class: 'sr-snippet', text: '…' + r.snippet + '…' }));
                }
                box.appendChild(Avroria.el('li', {}, [a]));
            });
            box.hidden = false;
        }

        const run = Avroria.debounce(function () {
            const q = input.value.trim();
            if (!q) { box.hidden = true; return; }
            fetch(api('search.php?q=') + encodeURIComponent(q))
                .then((r) => r.json())
                .then((d) => render(d.results || []))
                .catch(() => { box.hidden = true; });
        }, 220);

        input.addEventListener('input', run);
        input.addEventListener('focus', () => { if (input.value.trim()) run(); });
        document.addEventListener('click', (e) => {
            if (!box.contains(e.target) && e.target !== input) box.hidden = true;
        });
    }

    document.querySelectorAll('.searchbar').forEach(wireSearch);

    // Переход к первому результату при отправке формы.
    Avroria.search = function (event) {
        event.preventDefault();
        const form = event.target.closest('form');
        const first = form && form.querySelector('.searchbar__results a');
        if (first) window.location.href = first.href;
        return false;
    };

    window.Avroria = Avroria;
})();
