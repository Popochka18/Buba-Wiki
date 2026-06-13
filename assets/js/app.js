/* Avroria wiki — общие утилиты и поиск. */
(function () {
    'use strict';

    const BASE = window.AVRORIA_BASE || '';
    const api = (p) => BASE + '/api/' + p;

    const Avroria = {
        BASE,
        api,
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

    /* ---- Поиск ---- */
    const input = document.getElementById('site-search');
    const box = document.getElementById('search-results');

    function renderResults(results) {
        if (!box) return;
        box.innerHTML = '';
        if (!results.length) {
            box.appendChild(Avroria.el('li', { class: 'sr-empty', text: 'Ничего не найдено' }));
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

    const runSearch = Avroria.debounce(function () {
        const q = input.value.trim();
        if (!q) { box.hidden = true; return; }
        fetch(api('search.php?q=') + encodeURIComponent(q))
            .then((r) => r.json())
            .then((d) => renderResults(d.results || []))
            .catch(() => { box.hidden = true; });
    }, 220);

    if (input) {
        input.addEventListener('input', runSearch);
        input.addEventListener('focus', () => { if (input.value.trim()) runSearch(); });
        document.addEventListener('click', (e) => {
            if (box && !box.contains(e.target) && e.target !== input) box.hidden = true;
        });
    }

    Avroria.search = function (event) {
        event.preventDefault();
        const first = box && box.querySelector('a');
        if (first) window.location.href = first.href;
        return false;
    };

    window.Avroria = Avroria;
})();
