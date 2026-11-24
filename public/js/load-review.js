/* Teaching load review: check the classes again as they are edited, before saving. */
(function () {
    var form = document.querySelector('[data-load-review]');

    if (!form) {
        return;
    }

    var rows = Array.prototype.slice.call(form.querySelectorAll('[data-load-row]'));
    var count = form.querySelector('[data-load-count]');
    var timer = null;
    var latest = 0;

    function show(results) {
        var ready = 0;
        var problems = 0;
        var duplicates = 0;

        results.forEach(function (result, index) {
            var row = rows[index];
            var title = row.querySelector('[name$="[title]"]');

            row.querySelector('[data-row-status]').innerHTML = result.status;
            row.classList.toggle('load-row-problem', result.problem);

            if (result.title !== null) {
                title.value = result.title;
            } else if (title.readOnly) {
                title.value = '';
            }

            title.readOnly = result.title !== null;

            if (result.duplicate) {
                duplicates++;
            } else if (result.problem) {
                problems++;
            } else {
                ready++;
            }
        });

        count.textContent = ready + ' ready'
            + (problems ? ' · ' + problems + (problems === 1 ? ' needs' : ' need') + ' attention' : '')
            + (duplicates ? ' · ' + duplicates + ' already in the schedule' : '');
    }

    function check() {
        var request = ++latest;

        fetch(form.dataset.checkUrl, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : Promise.reject();
        }).then(function (result) {
            // A slower answer to an earlier edit is out of date.
            if (request === latest) {
                show(result.rows);
            }
        }).catch(function () { /* can't check right now; saving still checks */ });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(check, 350);
    }

    form.addEventListener('input', schedule);
    // Select all ticks the rows from a script, which sends no input event for each.
    form.addEventListener('change', schedule);
})();
