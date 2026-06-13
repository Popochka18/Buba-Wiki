/* Avroria wiki — окно импорта изображений. */
(function () {
    'use strict';

    const A = window.Avroria;
    const modal = document.getElementById('image-modal');
    if (!modal) return;

    const gallery = document.getElementById('gallery');
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('file-input');
    const progress = document.getElementById('upload-progress');
    const filterInput = document.getElementById('gallery-filter');

    let onPick = null;
    let images = [];

    function open(callback) {
        onPick = callback;
        modal.hidden = false;
        loadGallery();
    }

    function close() {
        modal.hidden = true;
        onPick = null;
    }

    function loadGallery() {
        gallery.innerHTML = '<div class="gallery__empty">Загрузка…</div>';
        fetch(A.api('images.php'))
            .then((r) => r.json())
            .then((d) => { images = d.images || []; renderGallery(); })
            .catch(() => { gallery.innerHTML = '<div class="gallery__empty">Не удалось загрузить</div>'; });
    }

    function renderGallery() {
        const filter = (filterInput.value || '').toLowerCase();
        const list = images.filter((im) => im.name.toLowerCase().includes(filter));
        gallery.innerHTML = '';
        if (!list.length) {
            gallery.innerHTML = '<div class="gallery__empty">Изображений пока нет. Загрузите первое выше.</div>';
            return;
        }
        list.forEach((im) => {
            const item = A.el('div', { class: 'gallery__item', title: im.name }, [
                A.el('img', { src: im.url, alt: im.name, loading: 'lazy' }),
                A.el('span', { class: 'gallery__name', text: im.name }),
            ]);
            item.addEventListener('click', () => {
                if (onPick) onPick({ name: im.name, url: im.url });
                close();
            });
            gallery.appendChild(item);
        });
    }

    function uploadFiles(files) {
        const list = [...files].filter((f) => f.type.startsWith('image/') || /\.(jpe?g|png|gif|webp|svg)$/i.test(f.name));
        if (!list.length) return;

        const fd = new FormData();
        list.forEach((f) => fd.append('file[]', f));

        progress.hidden = false;
        progress.textContent = 'Загрузка ' + list.length + ' файл(ов)…';

        fetch(A.api('upload.php'), { method: 'POST', body: fd })
            .then((r) => r.json())
            .then((d) => {
                if (!d.ok) {
                    progress.textContent = 'Ошибка: ' + (d.error || 'не удалось');
                    return;
                }
                progress.textContent = '✓ Загружено: ' + d.files.length;
                setTimeout(() => { progress.hidden = true; }, 1800);
                // Сразу выбираем первый загруженный файл.
                loadGallery();
                if (onPick && d.files[0]) {
                    onPick(d.files[0]);
                    close();
                }
            })
            .catch(() => { progress.textContent = 'Ошибка сети при загрузке'; });
    }

    /* Drag & drop */
    if (dropzone) {
        ['dragenter', 'dragover'].forEach((ev) =>
            dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.add('is-drag'); }));
        ['dragleave', 'drop'].forEach((ev) =>
            dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.remove('is-drag'); }));
        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer && e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
        });
    }
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) uploadFiles(fileInput.files);
            fileInput.value = '';
        });
    }
    if (filterInput) filterInput.addEventListener('input', renderGallery);

    /* Закрытие */
    modal.querySelectorAll('[data-close]').forEach((b) =>
        b.addEventListener('click', close));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) close();
    });

    window.AvroriaImages = { open, close };
})();
