/* Curriculum page: subject search and filter, and remembering which colleges are open. */
(function () {
    var page = document.querySelector('[data-curriculum-page]');

    if (!page) {
        return;
    }

    /* Subjects */

    var search = page.querySelector('[data-subject-search]');
    var program = page.querySelector('[data-subject-program]');
    var rows = Array.prototype.slice.call(page.querySelectorAll('[data-subject]'));
    var count = page.querySelector('[data-subject-count]');
    var noResults = page.querySelector('[data-no-results]');

    function filter() {
        var text = search.value.trim().toLowerCase();
        var shown = 0;

        rows.forEach(function (row) {
            var programs = row.dataset.programs === '' ? [] : row.dataset.programs.split(' ');
            var match = row.dataset.search.indexOf(text) !== -1
                && (program.value === ''
                    || (program.value === 'none' ? programs.length === 0 : programs.indexOf(program.value) !== -1));

            row.hidden = !match;
            shown += match ? 1 : 0;
        });

        count.textContent = (shown === rows.length ? '' : shown + ' of ')
            + rows.length + ' subject' + (rows.length === 1 ? '' : 's');

        if (noResults) {
            noResults.hidden = shown > 0;
        }
    }

    search.addEventListener('input', filter);
    program.addEventListener('change', filter);
    filter();

    page.querySelectorAll('[data-show-program]').forEach(function (button) {
        button.addEventListener('click', function () {
            program.value = button.dataset.showProgram;
            search.value = '';
            filter();
            document.getElementById('subjects').scrollIntoView({ behavior: 'smooth' });
        });
    });

    /* Open colleges, kept after saving */

    var colleges = Array.prototype.slice.call(page.querySelectorAll('[data-college]'));
    var storeKey = 'roomlink-open-colleges';
    var open = [];

    try {
        open = JSON.parse(sessionStorage.getItem(storeKey)) || [];
    } catch (error) { /* storage is off; colleges start closed */ }

    colleges.forEach(function (college) {
        college.open = open.indexOf(college.id) !== -1;
        college.addEventListener('toggle', function () {
            try {
                sessionStorage.setItem(storeKey, JSON.stringify(
                    colleges.filter(function (item) { return item.open; }).map(function (item) { return item.id; })
                ));
            } catch (error) { /* storage is off */ }
        });
    });

    /* Reading a scan takes a while; show it and prevent a second upload. */

    var upload = page.querySelector('[data-dialog="import"]');

    upload.querySelector('form').addEventListener('submit', function () {
        var button = upload.querySelector('[data-dialog-submit]');

        button.disabled = true;
        button.textContent = 'Reading the document…';
    });
})();
