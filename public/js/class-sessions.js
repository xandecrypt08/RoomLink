/* Class Sessions page: search, and the parts of the class form that depend on each other. */
(function () {
    var page = document.querySelector('[data-session-page]');

    if (!page) {
        return;
    }

    /* Search */

    var search = page.querySelector('[data-session-search]');
    var day = page.querySelector('[data-session-day]');
    var status = page.querySelector('[data-session-status]');
    var rows = Array.prototype.slice.call(page.querySelectorAll('[data-session]'));
    var count = page.querySelector('[data-session-count]');
    var noResults = page.querySelector('[data-no-results]');

    function filter() {
        var text = search.value.trim().toLowerCase();
        var shown = 0;

        rows.forEach(function (row) {
            var match = row.dataset.search.indexOf(text) !== -1
                && (!day.value || row.dataset.day === day.value)
                && (!status.value || row.dataset.status === status.value);

            row.hidden = !match;
            shown += match ? 1 : 0;
        });

        count.textContent = (shown === rows.length ? '' : shown + ' of ')
            + rows.length + ' class session' + (rows.length === 1 ? '' : 's');

        if (noResults) {
            noResults.hidden = shown > 0;
        }
    }

    search.addEventListener('input', filter);
    day.addEventListener('change', filter);
    status.addEventListener('change', filter);
    filter();

    /* Class form */

    var dialog = page.querySelector('[data-dialog="session"]');
    var form = dialog.querySelector('form');
    var field = function (name) { return form.elements[name]; };
    var days = Array.prototype.slice.call(form.querySelectorAll('[name="days[]"]'));
    var editing = false;

    // Compared without case, spaces or dashes, like the server does: "it 109" is IT-109.
    var key = function (text) { return text.toLowerCase().replace(/[^a-z0-9]/g, ''); };
    var subjects = {};
    var sections = {};
    var listed = JSON.parse(page.querySelector('[data-subjects]').textContent);

    Object.keys(listed).forEach(function (code) { subjects[key(code)] = listed[code]; });
    document.querySelectorAll('#section-names option').forEach(function (option) {
        sections[option.value.trim().toLowerCase()] = true;
    });

    function syncSubject() {
        var code = key(field('subject_code').value);
        var title = field('subject_name');
        var known = Object.prototype.hasOwnProperty.call(subjects, code);

        if (known) {
            title.value = subjects[code];
        } else if (title.readOnly) {
            title.value = '';
        }

        title.readOnly = known;
        title.required = code !== '' && !known;
        dialog.querySelector('[data-new-subject]').hidden = code === '' || known;
    }

    function syncSection() {
        var name = field('section_name').value.trim().toLowerCase();

        dialog.querySelector('[data-new-section]').hidden = name === '' || Object.prototype.hasOwnProperty.call(sections, name);
    }

    function syncFaculty() {
        var box = dialog.querySelector('[data-new-faculty]');

        box.hidden = field('faculty_id').value !== 'new';
        box.querySelectorAll('input').forEach(function (input) { input.disabled = box.hidden; });
    }

    field('subject_code').addEventListener('input', syncSubject);
    field('section_name').addEventListener('input', syncSection);
    field('faculty_id').addEventListener('change', syncFaculty);

    // A class being edited is on one day, so ticking another moves it.
    days.forEach(function (box) {
        box.addEventListener('change', function () {
            if (editing && box.checked) {
                days.forEach(function (other) { other.checked = other === box; });
            }
        });
    });

    dialog.addEventListener('dialog:filled', function (event) {
        editing = event.detail.record !== '';
        // Titles of known subjects are filled from the list, not the record.
        field('subject_name').readOnly = false;
        syncSubject();
        syncSection();
        syncFaculty();
        dialog.querySelector('[data-add-only]').hidden = editing;
        dialog.querySelector('[data-days-help]').hidden = editing;
    });

    form.addEventListener('submit', function (event) {
        var first = days[0];
        var chosen = days.some(function (box) { return box.checked; });

        first.setCustomValidity(chosen ? '' : 'Choose at least one day.');

        if (!chosen) {
            event.preventDefault();
            first.reportValidity();
            first.setCustomValidity('');
        }
    });

    /* Reading a photo or scan takes a while; show it and prevent a second upload. */

    var load = page.querySelector('[data-dialog="load"]');

    load.querySelector('form').addEventListener('submit', function () {
        var button = load.querySelector('[data-dialog-submit]');

        button.disabled = true;
        button.textContent = 'Reading the load…';
    });
})();
