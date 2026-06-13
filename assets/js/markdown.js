/* Avroria wiki — конвертер Markdown ↔ HTML для визуального редактора.
   Подмножество синтаксиса согласовано с серверным рендером (lib/Markdown.php). */
(function () {
    'use strict';

    const BASE = window.AVRORIA_BASE || '';
    const esc = (s) => String(s).replace(/[&<>"]/g, (c) =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    const wikiHref = (t) => BASE + '/wiki/' + encodeURIComponent(t);
    const imageUrl = (f) => /^(https?:|\/)/.test(f) ? f : BASE + '/images/' + encodeURIComponent(f.replace(/^images\//, ''));

    /* ---------- Markdown → HTML ---------- */

    function inlineToHtml(text) {
        text = esc(text);

        // ![alt](src)
        text = text.replace(/!\[([^\]]*)\]\(([^)\s]+)\)/g, (_, alt, src) =>
            '<img src="' + imageUrl(src) + '" alt="' + alt + '" data-file="' + esc(src.replace(/^images\//, '')) + '">');

        // [[Страница|текст]] и [[Страница]]
        text = text.replace(/\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/g, (_, target, label) => {
            target = target.trim();
            label = (label || target).trim();
            return '<a class="wikilink" data-wiki="' + esc(target) + '" href="' + wikiHref(target) + '">' + esc(label) + '</a>';
        });

        // [текст](url)
        text = text.replace(/\[([^\]]+)\]\((https?:[^)\s]+|\/[^)\s]*)\)/g,
            (_, t, u) => '<a href="' + u + '">' + t + '</a>');

        text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/(?<![\*\w])\*([^*\n]+)\*(?![\*\w])/g, '<em>$1</em>');
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

        return text.replace(/\n/g, '<br>');
    }

    function mdToHtml(md) {
        md = String(md).replace(/\r\n?/g, '\n');
        const lines = md.split('\n');
        const out = [];
        let i = 0;

        while (i < lines.length) {
            const line = lines[i];

            if (line.trim() === '') { i++; continue; }

            if (/^\s*(---|\*\*\*|___)\s*$/.test(line)) { out.push('<hr>'); i++; continue; }

            let m = line.match(/^(#{2,6})\s+(.*)$/);
            if (m) {
                const lvl = m[1].length;
                out.push('<h' + lvl + '>' + inlineToHtml(m[2].trim()) + '</h' + lvl + '>');
                i++; continue;
            }

            if (/^\s*>\s?/.test(line)) {
                const buf = [];
                while (i < lines.length && /^\s*>\s?/.test(lines[i])) {
                    buf.push(lines[i].replace(/^\s*>\s?/, '')); i++;
                }
                out.push('<blockquote>' + inlineToHtml(buf.join('\n')) + '</blockquote>');
                continue;
            }

            if (/^\s*[-*+]\s+/.test(line)) {
                const buf = [];
                while (i < lines.length && /^\s*[-*+]\s+/.test(lines[i])) {
                    buf.push('<li>' + inlineToHtml(lines[i].replace(/^\s*[-*+]\s+/, '')) + '</li>'); i++;
                }
                out.push('<ul>' + buf.join('') + '</ul>');
                continue;
            }

            if (/^\s*\d+[.)]\s+/.test(line)) {
                const buf = [];
                while (i < lines.length && /^\s*\d+[.)]\s+/.test(lines[i])) {
                    buf.push('<li>' + inlineToHtml(lines[i].replace(/^\s*\d+[.)]\s+/, '')) + '</li>'); i++;
                }
                out.push('<ol>' + buf.join('') + '</ol>');
                continue;
            }

            // Абзац
            const buf = [];
            while (i < lines.length && lines[i].trim() !== ''
                && !/^(#{2,6}\s|\s*>|\s*[-*+]\s|\s*\d+[.)]\s)/.test(lines[i])
                && !/^\s*(---|\*\*\*|___)\s*$/.test(lines[i])) {
                buf.push(lines[i]); i++;
            }
            out.push('<p>' + inlineToHtml(buf.join('\n')) + '</p>');
        }

        return out.join('\n');
    }

    /* ---------- HTML → Markdown ---------- */

    function inlineToMd(node) {
        let md = '';
        node.childNodes.forEach((child) => {
            if (child.nodeType === 3) {
                md += child.nodeValue.replace(/\n+/g, ' ');
                return;
            }
            if (child.nodeType !== 1) return;

            const tag = child.tagName.toLowerCase();
            switch (tag) {
                case 'strong': case 'b':
                    md += '**' + inlineToMd(child).trim() + '**'; break;
                case 'em': case 'i':
                    md += '*' + inlineToMd(child).trim() + '*'; break;
                case 'code':
                    md += '`' + child.textContent + '`'; break;
                case 'br':
                    md += '\n'; break;
                case 'img': {
                    const file = child.getAttribute('data-file') || child.getAttribute('src') || '';
                    const alt = child.getAttribute('alt') || '';
                    md += '![' + alt + '](' + file + ')'; break;
                }
                case 'a': {
                    const wiki = child.getAttribute('data-wiki');
                    const label = inlineToMd(child).trim() || child.textContent.trim();
                    if (wiki) {
                        md += (label && label !== wiki) ? '[[' + wiki + '|' + label + ']]' : '[[' + wiki + ']]';
                    } else {
                        md += '[' + label + '](' + (child.getAttribute('href') || '') + ')';
                    }
                    break;
                }
                default:
                    md += inlineToMd(child);
            }
        });
        return md;
    }

    function blockToMd(node) {
        if (node.nodeType === 3) {
            const t = node.nodeValue.trim();
            return t ? t : null;
        }
        if (node.nodeType !== 1) return null;

        const tag = node.tagName.toLowerCase();
        switch (tag) {
            case 'h1': return '## ' + inlineToMd(node).trim();
            case 'h2': return '## ' + inlineToMd(node).trim();
            case 'h3': return '### ' + inlineToMd(node).trim();
            case 'h4': case 'h5': case 'h6': return '#### ' + inlineToMd(node).trim();
            case 'hr': return '---';
            case 'blockquote':
                return inlineToMd(node).trim().split('\n').map((l) => '> ' + l).join('\n');
            case 'ul':
                return [...node.children].filter((li) => li.tagName === 'LI')
                    .map((li) => '- ' + inlineToMd(li).trim()).join('\n');
            case 'ol':
                return [...node.children].filter((li) => li.tagName === 'LI')
                    .map((li, n) => (n + 1) + '. ' + inlineToMd(li).trim()).join('\n');
            case 'br': return null;
            default: {
                const md = inlineToMd(node).trim();
                return md ? md : null;
            }
        }
    }

    function htmlToMd(root) {
        const blocks = [];
        root.childNodes.forEach((node) => {
            const md = blockToMd(node);
            if (md != null && md !== '') blocks.push(md);
        });
        return blocks.join('\n\n').replace(/\n{3,}/g, '\n\n').trim();
    }

    window.AvroriaMarkdown = { mdToHtml, htmlToMd, inlineToHtml, imageUrl, wikiHref };
})();
