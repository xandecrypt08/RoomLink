/* Curriculum page: the subject list by program, and remembering which colleges are open. */
(function () {
    var page = document.querySelector('[data-curriculum-page]');

    if (!page) {
        return;
    }

    /* Subjects */

    var search = page.querySelector('[data-subject-search]');
    var program = page.querySelector('[data-subject-program]');
    var groups = Array.prototype.slice.call(page.querySelectorAll('[data-subject-group]'));
    var count = page.querySelector('[data-subject-count]');
    var noResults = page.querySelector('[data-no-results]');

    // A shared subject is listed under each of its programs but counted once.
    function countSubjects(rows) {
        var ids = {};

        rows.forEach(function (row) { ids[row.dataset.subject] = true; });

        return Object.keys(ids).length;
    }

    function filter() {
        var text = search.value.trim().toLowerCase();
        var all = [];
        var shown = [];

        groups.forEach(function (group) {
            var matches = 0;

            group.querySelectorAll('[data-subject]').forEach(function (row) {
                row.hidden = row.dataset.search.indexOf(text) === -1;
                all.push(row);

                if (!row.hidden) {
                    shown.push(row);
                    matches++;
                }
            });

            group.hidden = text !== '' && matches === 0;
        });

        var total = countSubjects(all);
        var found = countSubjects(shown);

        count.textContent = (found === total ? '' : found + ' of ') + total + ' subject' + (total === 1 ? '' : 's');

        if (noResults) {
            noResults.hidden = text === '' || found > 0;
        }
    }

    search.addEventListener('input', filter);
    filter();

    // Scroll the full list to a program and point it out.
    function goTo(id) {
        var group = groups.filter(function (item) { return item.id === id; })[0];

        if (!group) {
            return;
        }

        search.value = '';
        filter();
        groups.forEach(function (item) { item.classList.toggle('pointed-out', item === group); });
        group.scrollIntoView({ block: 'start' });
    }

    program.addEventListener('change', function () {
        goTo(program.value);
        program.value = '';
    });

    page.querySelectorAll('[data-show-program]').forEach(function (button) {
        button.addEventListener('click', function () { goTo(button.dataset.showProgram); });
    });

    window.addEventListener('hashchange', function () { goTo(location.hash.slice(1)); });

    // Clicking the program already in the address sends no hashchange.
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href*="#program-"]');

        if (link && link.hash === location.hash && link.pathname === location.pathname) {
            goTo(link.hash.slice(1));
        }
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

    // Once the colleges above are opened or closed, the list is where it stays.
    var subjects = document.getElementById('subjects');

    goTo(location.hash ? location.hash.slice(1) : (subjects.dataset.goTo || ''));

    /* Reading a scan takes a while; show it and prevent a second upload. */

    var upload = page.querySelector('[data-dialog="import"]');

    upload.querySelector('form').addEventListener('submit', function () {
        var button = upload.querySelector('[data-dialog-submit]');

        button.disabled = true;
        button.textContent = 'Reading the document…';
    });
})();
