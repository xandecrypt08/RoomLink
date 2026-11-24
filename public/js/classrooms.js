/* Campuses page: the chosen campus, search, and remembering which buildings are open. */
(function () {
    var page = document.querySelector('.classroom-page');

    if (!page) {
        return;
    }

    /* The campus chosen in the sidebar menu, or all of them; kept after saving */

    var campuses = Array.prototype.slice.call(page.querySelectorAll('[data-campus]'));
    var campusKey = 'roomlink-campus';
    var chosen = '';
    var noResults = page.querySelector('[data-no-results]');

    function inView(campus) {
        return chosen === '' || campus.id === chosen;
    }

    function showCampus(id) {
        var campus = campuses.filter(function (item) { return item.id === id; })[0];

        chosen = campus ? id : '';

        page.querySelector('[data-campus-title]').textContent = campus ? campus.dataset.campusName : 'Campuses';
        page.querySelector('[data-campus-scope]').textContent = campus ? 'Only this campus.' : 'All campuses';
        page.querySelector('[data-show-all]').hidden = !campus;

        // "+ Building" at the top adds to the campus being looked at.
        page.querySelectorAll('[data-campus-preset]').forEach(function (button) {
            var only = campus || (campuses.length === 1 ? campuses[0] : null);

            button.dataset.values = JSON.stringify(only ? { campus_id: only.dataset.campusId } : {});
        });

        try {
            sessionStorage.setItem(campusKey, chosen);
        } catch (error) { /* storage is off; all campuses are shown */ }

        filter();
    }

    // A campus chosen in the menu shows its buildings with their floors and rooms.
    function openCampus(id) {
        showCampus(id);

        buildings.forEach(function (item) {
            if (chosen !== '' && item.closest('[data-campus]').id === chosen) {
                item.open = true;
            }
        });
    }

    function followAddress() {
        var id = location.hash.slice(1);

        if (id === 'all' || campuses.some(function (campus) { return campus.id === id; })) {
            openCampus(id);
            window.scrollTo(0, 0);

            return true;
        }

        return false;
    }

    window.addEventListener('hashchange', followAddress);

    // Clicking the campus already in the address sends no hashchange.
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href*="#"]');

        if (link && link.hash === location.hash && link.pathname === location.pathname) {
            followAddress();
        }
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
    var opening = page.querySelector('[data-open-now]');
    var ownCampus = (target || opening) ? (target || opening).closest('[data-campus]') : null;
    var targetBuilding = target ? target.closest('[data-building]') : null;

    if (targetBuilding) {
        targetBuilding.open = true;
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

    function filter() {
        var text = search.value.trim().toLowerCase();
        var active = text !== '' || status.value !== '';

        searching = searching || active;

        var any = false;

        campuses.forEach(function (campus) {
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

            campus.hidden = !inView(campus) || (active && !campusShown);
            any = any || !campus.hidden;
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

    if (!followAddress()) {
        var saved = '';

        try {
            saved = sessionStorage.getItem(campusKey) || '';
        } catch (error) { /* storage is off */ }

        showCampus(ownCampus && saved !== '' ? ownCampus.id : saved);

        if (targetBuilding) {
            target.scrollIntoView({ block: 'center' });
        }
    }
})();
