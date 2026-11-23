/* Classrooms page: pop-up forms, search, and remembering which buildings are open. */
(function () {
    var page = document.querySelector('.classroom-page');

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
            document.getElementById('delete-facility').action = deleteUrl || '';
        }

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

    /* Open buildings, kept while moving around the site */

    var buildings = Array.prototype.slice.call(page.querySelectorAll('[data-building]'));
    var storeKey = 'roomlink-open-buildings';
    var searching = false;

    function remember() {
        if (searching) {
            return;
        }

        try {
            sessionStorage.setItem(storeKey, JSON.stringify(
                buildings.filter(function (item) { return item.open; }).map(function (item) { return item.id; })
            ));
        } catch (error) { /* storage is off; buildings just start closed */ }
    }

    function restore() {
        var open = [];

        try {
            open = JSON.parse(sessionStorage.getItem(storeKey)) || [];
        } catch (error) { /* storage is off */ }

        buildings.forEach(function (item) {
            item.open = buildings.length === 1 || open.indexOf(item.id) !== -1;
        });
    }

    restore();

    // After saving, the address ends in the building or floor just changed.
    var target = location.hash ? document.getElementById(location.hash.slice(1)) : null;
    var targetBuilding = target ? target.closest('[data-building]') : null;

    if (targetBuilding) {
        targetBuilding.open = true;
        target.scrollIntoView({ block: 'center' });
    }

    buildings.forEach(function (item) { item.addEventListener('toggle', remember); });

    var expand = page.querySelector('[data-expand-all]');

    expand.addEventListener('click', function () {
        var open = buildings.some(function (item) { return !item.open; });

        buildings.forEach(function (item) { item.open = open; });
        expand.textContent = open ? 'Collapse all' : 'Expand all';
    });

    /* Search */

    var search = page.querySelector('[data-classroom-search]');
    var status = page.querySelector('[data-classroom-status]');
    var noResults = page.querySelector('[data-no-results]');

    function filter() {
        var text = search.value.trim().toLowerCase();
        var active = text !== '' || status.value !== '';
        var any = false;

        searching = searching || active;

        page.querySelectorAll('[data-campus]').forEach(function (campus) {
            var campusShown = false;

            campus.querySelectorAll('[data-building]').forEach(function (item) {
                var named = item.dataset.search.indexOf(text) !== -1;
                var showsEmpty = named && !status.value;
                var rooms = 0;

                item.querySelectorAll('[data-floor]').forEach(function (floor) {
                    var floorRooms = 0;

                    floor.querySelectorAll('[data-room]').forEach(function (room) {
                        var shown = (named || room.dataset.search.indexOf(text) !== -1)
                            && (!status.value || room.dataset.status === status.value);

                        room.hidden = !shown;
                        floorRooms += shown ? 1 : 0;
                    });

                    floor.hidden = active && !floorRooms && !showsEmpty;
                    rooms += floorRooms;
                });

                var shown = !active || rooms > 0 || showsEmpty;

                item.hidden = !shown;
                campusShown = campusShown || shown;

                if (active && shown) {
                    item.open = true;
                }
            });

            campus.hidden = active && !campusShown;
            any = any || campusShown;
        });

        noResults.hidden = !active || any;

        if (!active && searching) {
            restore();
            // Toggle events arrive a little later; they aren't the admin's choice.
            setTimeout(function () { searching = false; }, 50);
        }
    }

    search.addEventListener('input', filter);
    status.addEventListener('change', filter);

    /* Show a form again after a validation error, or when a link asked for it. */

    var old = page.querySelector('[data-old-input]');
    var openNow = page.querySelector('[data-open-now]');

    if (old) {
        var values = JSON.parse(old.textContent);

        if (values._dialog && page.querySelector('[data-dialog="' + CSS.escape(values._dialog) + '"]')) {
            var selector = '[data-open="' + CSS.escape(values._dialog) + '"]';
            var trigger = page.querySelector(selector + (values._record
                ? '[data-record="' + CSS.escape(values._record) + '"]'
                : ':not([data-record])'));

            openDialog(
                values._dialog,
                values._record,
                values,
                trigger ? trigger.dataset.title : 'Fix the details below',
                trigger ? trigger.dataset.deleteUrl : '',
                true
            );
        }
    } else if (openNow) {
        openNow.closest('[data-building]').open = true;
        openFrom(openNow);
        // Reloading the page shouldn't open the form again.
        history.replaceState(null, '', location.pathname);
    }
})();
