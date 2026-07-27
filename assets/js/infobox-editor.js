/* Avroria wiki — редактор инфобокса (поля карточки). */
(function () {
    'use strict';

    const A = window.Avroria;

    const fieldsBox = document.getElementById('ib-fields');
    const addBtn = document.getElementById('ib-add-field');
    const templateSelect = document.getElementById('ib-template');
    const collectionsInput = document.getElementById('ib-collections');
    const imagesBox = document.getElementById('ib-images');
    const imageAdd = document.getElementById('ib-image-add');

    let reserved = [];
    let templates = [];
    let imageFiles = []; // Несколько изображений — карусель в статье.

    function renderImages() {
        imagesBox.innerHTML = '';
        if (!imageFiles.length) {
            imagesBox.appendChild(A.el('span', { class: 'muted', text: A.t('js.img_none') }));
            return;
        }
        imageFiles.forEach((file, i) => {
            const left = A.el('button', { class: 'ib-image__btn', type: 'button', title: A.t('js.move_left'), text: '◀' });
            const right = A.el('button', { class: 'ib-image__btn', type: 'button', title: A.t('js.move_right'), text: '▶' });
            const del = A.el('button', { class: 'ib-image__btn ib-image__btn--del', type: 'button', title: A.t('js.delete'), text: '✕' });

            left.addEventListener('click', () => moveImage(i, -1));
            right.addEventListener('click', () => moveImage(i, 1));
            del.addEventListener('click', () => { imageFiles.splice(i, 1); renderImages(); });

            imagesBox.appendChild(A.el('div', { class: 'ib-image', title: file }, [
                A.el('img', { src: window.AvroriaMarkdown.imageUrl(file), alt: file }),
                A.el('div', { class: 'ib-image__btns' }, [left, right, del]),
            ]));
        });
    }

    function moveImage(i, d) {
        const j = i + d;
        if (j < 0 || j >= imageFiles.length) return;
        [imageFiles[i], imageFiles[j]] = [imageFiles[j], imageFiles[i]];
        renderImages();
    }

    function setImages(files) {
        imageFiles = (files || []).map(String).filter(Boolean);
        renderImages();
    }

    function addImage(file) {
        if (file && !imageFiles.includes(file)) {
            imageFiles.push(file);
            renderImages();
        }
    }

    function makeField(key, value) {
        const keyInput = A.el('input', {
            class: 'ib-field__key', type: 'text', value: key || '', placeholder: A.t('js.field_key_ph'),
        });
        const valInput = A.el('textarea', {
            class: 'ib-field__val', rows: '1', placeholder: A.t('js.field_val_ph'),
        });
        valInput.value = value || '';
        autoGrow(valInput);
        valInput.addEventListener('input', () => autoGrow(valInput));

        const up = A.el('button', { class: 'ib-field__btn', type: 'button', title: A.t('js.move_up'), text: '▲' });
        const down = A.el('button', { class: 'ib-field__btn', type: 'button', title: A.t('js.move_down'), text: '▼' });
        const del = A.el('button', { class: 'ib-field__btn ib-field__btn--del', type: 'button', title: A.t('js.delete'), text: '✕' });

        const row = A.el('div', { class: 'ib-field__row' }, [
            keyInput,
            A.el('div', { class: 'ib-field__btns' }, [up, down, del]),
        ]);
        const field = A.el('div', { class: 'ib-field' }, [row, valInput]);

        up.addEventListener('click', () => {
            if (field.previousElementSibling) fieldsBox.insertBefore(field, field.previousElementSibling);
        });
        down.addEventListener('click', () => {
            if (field.nextElementSibling) fieldsBox.insertBefore(field.nextElementSibling, field);
        });
        del.addEventListener('click', () => field.remove());

        return field;
    }

    function autoGrow(ta) {
        ta.style.height = 'auto';
        ta.style.height = Math.max(ta.scrollHeight, 24) + 'px';
    }

    function addField(key, value) {
        fieldsBox.appendChild(makeField(key, value));
    }

    /* Существующие названия полей (для пропуска дублей при вставке шаблона). */
    function existingKeys() {
        const keys = new Set();
        fieldsBox.querySelectorAll('.ib-field__key').forEach((inp) => {
            const k = inp.value.trim().toLowerCase();
            if (k) keys.add(k);
        });
        return keys;
    }

    /* Подставляет поля выбранного шаблона, не трогая уже заполненные. */
    function applyTemplate(id) {
        const tpl = templates.find((t) => t.id === id);
        if (!tpl) return;
        const have = existingKeys();
        let firstAdded = null;
        tpl.fields.forEach((label) => {
            if (have.has(label.toLowerCase())) return;
            const field = makeField(label, '');
            fieldsBox.appendChild(field);
            if (!firstAdded) firstAdded = field;
        });
        if (firstAdded) {
            const v = firstAdded.querySelector('.ib-field__val');
            if (v) v.focus();
        }
    }

    function init(data) {
        reserved = (data.reserved || []).map((s) => s.toLowerCase());
        templates = data.templates || [];
        const meta = data.meta || {};

        Object.keys(meta).forEach((key) => {
            const lower = key.toLowerCase();
            if (lower === 'image') {
                setImages(Array.isArray(meta[key]) ? meta[key] : [meta[key]]);
                return;
            }
            if (lower === 'collections' || lower === 'коллекции') {
                const v = meta[key];
                collectionsInput.value = Array.isArray(v) ? v.join(', ') : String(v);
                return;
            }
            if (lower === 'title') return;
            const val = Array.isArray(meta[key]) ? meta[key].join(', ') : meta[key];
            addField(key, val);
        });

        renderImages();
    }

    /* Возвращает мету в порядке: image → поля → collections. */
    function getMeta() {
        const meta = {};
        if (imageFiles.length === 1) meta.image = imageFiles[0];
        else if (imageFiles.length > 1) meta.image = imageFiles.slice();

        fieldsBox.querySelectorAll('.ib-field').forEach((f) => {
            const key = f.querySelector('.ib-field__key').value.trim();
            const val = f.querySelector('.ib-field__val').value.trim();
            if (key) meta[key] = val;
        });

        const cols = (collectionsInput.value || '')
            .split(',').map((s) => s.trim()).filter(Boolean);
        if (cols.length) meta.collections = cols;

        return meta;
    }

    if (addBtn) addBtn.addEventListener('click', () => { addField('', ''); });
    if (templateSelect) templateSelect.addEventListener('change', () => {
        const id = templateSelect.value;
        if (id) applyTemplate(id);
        templateSelect.value = '';
    });
    if (imageAdd) imageAdd.addEventListener('click', () => {
        window.AvroriaImages.open((img) => addImage(img.name));
    });

    window.AvroriaInfobox = { init, getMeta };
})();
