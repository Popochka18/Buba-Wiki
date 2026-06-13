/* Avroria wiki — визуальный редактор: панель инструментов и сохранение. */
(function () {
    'use strict';

    const A = window.Avroria;
    const MD = window.AvroriaMarkdown;

    const root = document.getElementById('editor');
    if (!root) return;
    const init = JSON.parse(root.dataset.init);

    const canvas = document.getElementById('visual-editor');
    const source = document.getElementById('source-editor');
    const nameInput = document.getElementById('page-name');
    const status = document.getElementById('save-status');
    const toggleBtn = document.getElementById('toggle-source');
    const saveBtn = document.getElementById('btn-save');

    let mode = 'visual'; // 'visual' | 'source'

    /* ---- Инициализация содержимого ---- */
    canvas.innerHTML = MD.mdToHtml(init.body || '');
    source.value = init.body || '';
    window.AvroriaInfobox.init(init);

    /* ---- Получение тела как Markdown ---- */
    function getBody() {
        return mode === 'source' ? source.value : MD.htmlToMd(canvas);
    }

    /* ---- Переключение режима ---- */
    toggleBtn.addEventListener('click', () => {
        if (mode === 'visual') {
            source.value = MD.htmlToMd(canvas);
            source.hidden = false;
            canvas.hidden = true;
            toggleBtn.textContent = '⟰ Визуально';
            mode = 'source';
        } else {
            canvas.innerHTML = MD.mdToHtml(source.value);
            canvas.hidden = false;
            source.hidden = true;
            toggleBtn.textContent = '⟱ Исходник';
            mode = 'visual';
        }
    });

    /* ---- Вставка узла по месту курсора ---- */
    function insertHtmlAtCursor(html) {
        canvas.focus();
        const sel = window.getSelection();
        if (!sel.rangeCount || !canvas.contains(sel.anchorNode)) {
            canvas.insertAdjacentHTML('beforeend', html);
            return;
        }
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const tpl = document.createElement('template');
        tpl.innerHTML = html;
        const frag = tpl.content;
        const last = frag.lastChild;
        range.insertNode(frag);
        if (last) {
            range.setStartAfter(last);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    function selectedText() {
        const sel = window.getSelection();
        return sel && sel.rangeCount ? sel.toString() : '';
    }

    /* ---- Команды панели инструментов ---- */
    const commands = {
        bold:   () => document.execCommand('bold'),
        italic: () => document.execCommand('italic'),
        h2:     () => document.execCommand('formatBlock', false, 'h2'),
        h3:     () => document.execCommand('formatBlock', false, 'h3'),
        p:      () => document.execCommand('formatBlock', false, 'p'),
        ul:     () => document.execCommand('insertUnorderedList'),
        ol:     () => document.execCommand('insertOrderedList'),
        quote:  () => document.execCommand('formatBlock', false, 'blockquote'),
        hr:     () => insertHtmlAtCursor('<hr><p><br></p>'),
        code:   () => {
            const t = selectedText();
            if (t) insertHtmlAtCursor('<code>' + A.escapeHtml(t) + '</code>');
        },
        link: () => {
            const label = selectedText();
            const url = prompt('Адрес ссылки (http://…):', 'https://');
            if (!url) return;
            insertHtmlAtCursor('<a href="' + A.escapeHtml(url) + '">' +
                A.escapeHtml(label || url) + '</a>');
        },
        wikilink: () => {
            const label = selectedText();
            const target = prompt('Название страницы для вики-ссылки:', label || '');
            if (!target) return;
            const t = target.trim();
            const text = (label || t).trim();
            insertHtmlAtCursor('<a class="wikilink" data-wiki="' + A.escapeHtml(t) +
                '" href="' + MD.wikiHref(t) + '">' + A.escapeHtml(text) + '</a>&nbsp;');
        },
        image: () => {
            window.AvroriaImages.open((img) => {
                insertHtmlAtCursor('<img src="' + img.url + '" data-file="' +
                    A.escapeHtml(img.name) + '" alt="' + A.escapeHtml(img.name) + '">');
            });
        },
    };

    document.getElementById('vis-toolbar').addEventListener('click', (e) => {
        const btn = e.target.closest('.tb[data-cmd]');
        if (!btn) return;
        e.preventDefault();
        const cmd = btn.dataset.cmd;
        if (mode === 'source' && !['image', 'wikilink', 'link'].includes(cmd)) {
            // В режиме исходника команды форматирования неактивны.
            return;
        }
        if (commands[cmd]) commands[cmd]();
        canvas.focus();
    });

    /* ---- Горячие клавиши ---- */
    canvas.addEventListener('keydown', (e) => {
        if (!(e.ctrlKey || e.metaKey)) return;
        const k = e.key.toLowerCase();
        if (k === 'b') { e.preventDefault(); commands.bold(); }
        else if (k === 'i') { e.preventDefault(); commands.italic(); }
        else if (k === 's') { e.preventDefault(); save(); }
    });
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault(); save();
        }
    });

    // Не переходить по вики-ссылкам внутри редактора.
    canvas.addEventListener('click', (e) => {
        const a = e.target.closest('a');
        if (a) e.preventDefault();
    });

    /* ---- Сохранение ---- */
    let saving = false;
    function save() {
        if (saving) return;
        const name = nameInput.value.trim();
        if (!name) {
            setStatus('Укажите название', 'err');
            nameInput.focus();
            return;
        }
        saving = true;
        setStatus('Сохранение…', '');

        const payload = {
            name,
            originalName: init.name,
            body: getBody(),
            meta: window.AvroriaInfobox.getMeta(),
        };

        fetch(init.urls.save, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then((r) => r.json())
            .then((d) => {
                saving = false;
                if (d.ok) {
                    setStatus('✓ Сохранено', 'ok');
                    window.location.href = d.url;
                } else {
                    setStatus('Ошибка: ' + (d.error || 'не сохранено'), 'err');
                }
            })
            .catch(() => { saving = false; setStatus('Ошибка сети', 'err'); });
    }

    function setStatus(text, kind) {
        status.textContent = text;
        status.className = 'editor__status' + (kind ? ' is-' + kind : '');
    }

    saveBtn.addEventListener('click', save);

    /* Предупреждение о несохранённых изменениях. */
    let dirty = false;
    [canvas, source, nameInput].forEach((el) =>
        el.addEventListener('input', () => { dirty = true; }));
    root.querySelector('.editor__side').addEventListener('input', () => { dirty = true; });
    window.addEventListener('beforeunload', (e) => {
        if (dirty && !saving) { e.preventDefault(); e.returnValue = ''; }
    });
    saveBtn.addEventListener('click', () => { dirty = false; });
})();
