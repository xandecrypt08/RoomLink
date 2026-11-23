/* Pop-up forms for pages that manage records in place (Classrooms, Class Sessions). */
document.addEventListener('DOMContentLoaded', function () {
    var page = document.querySelector('[data-record-page]');

    if (!page) {
        return;
    }

    /* Pop-up forms */

    function fill(form, values) {
        Array.prototype.forEach.call(form.elements, function (field) {
            var name = field.name.replace(/\[\]$/, '');

            if (!name || name.charAt(0) === '_' || !(name in values)) {
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = [].concat(values[name] || []).map(String).indexOf(field.value) !== -1;
            } else if (field.type === 'radio') {
                field.checked = field.value === String(values[name]);
            } else {
                field.value = values[name] === null ? '' : values[name];
            }
        });
    }

    function openDialog(kind, record, values, title, deleteUrl, keepErrors) {
        var dialog = page.querySelector('[data-dialog="' + kind + '"]');
        var form = dialog.querySelector('form');
        var errors = dialog.querySelector('[data-dialog-errors]');
        var remove = dialog.querySelector('[data-dialog-delete]');

        form.reset();
        fill(form, values);

        form.action = record
            ? dialog.dataset.updateUrl.replace('__ID__', encodeURIComponent(record))
            : dialog.dataset.storeUrl;
        dialog.querySelector('[data-dialog-method]').disabled = !record;
        dialog.querySelector('[data-dialog-record]').value = record || '';
        dialog.querySelector('[data-dialog-title]').textContent = title;

        if (errors && !keepErrors) {
            errors.remove();
        }

        if (remove) {
            remove.hidden = !deleteUrl;
            document.getElementById('delete-record').action = deleteUrl || '';
        }

        // Lets the page adjust fields that depend on each other.
        dialog.dispatchEvent(new CustomEvent('dialog:filled', { detail: { record: record || '' } }));
        dialog.showModal();
    }

    function openFrom(button) {
        openDialog(
            button.dataset.open,
            button.dataset.record,
            JSON.parse(button.dataset.values || '{}'),
            button.dataset.title,
            button.dataset.deleteUrl,
            false
        );
    }

    page.querySelectorAll('[data-open]').forEach(function (button) {
        button.addEventListener('click', function () { openFrom(button); });
    });

    page.querySelectorAll('[data-dialog-close]').forEach(function (button) {
        button.addEventListener('click', function () { button.closest('dialog').close(); });
    });

    /* Show a form again after a validation error, to add another, or when a link asked for it. */

    function json(selector) {
        var element = page.querySelector(selector);

        return element ? JSON.parse(element.textContent) : null;
    }

    function triggerFor(kind, record) {
        return page.querySelector('[data-open="' + CSS.escape(kind) + '"]' + (record
            ? '[data-record="' + CSS.escape(record) + '"]'
            : ':not([data-record])'));
    }

    var old = json('[data-old-input]');
    var repeat = json('[data-repeat]');
    var openNow = page.querySelector('[data-open-now]');

    if (old && old._dialog && page.querySelector('[data-dialog="' + CSS.escape(old._dialog) + '"]')) {
        var trigger = triggerFor(old._dialog, old._record);

        openDialog(
            old._dialog,
            old._record,
            old,
            trigger ? trigger.dataset.title : 'Fix the details below',
            trigger ? trigger.dataset.deleteUrl : '',
            true
        );
    } else if (repeat) {
        openDialog(repeat._dialog, '', repeat, triggerFor(repeat._dialog, '').dataset.title, '', false);
    } else if (openNow) {
        var section = openNow.closest('details');

        if (section) {
            section.open = true;
        }

        openFrom(openNow);
        // Reloading the page shouldn't open the form again.
        history.replaceState(null, '', location.pathname);
    }
});
