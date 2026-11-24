/* Campus Spaces page: campus tabs, search, and remembering which buildings are open. */
(function () {
    var page = document.querySelector('.classroom-page');

    if (!page) {
        return;
    }

    /* One tab per campus; the open one is kept while moving around the site */

    var campuses = Array.prototype.slice.call(page.querySelectorAll('[data-campus]'));
    var tabs = Array.prototype.slice.call(page.querySelectorAll('[data-campus-tab]'));
    var tabKey = 'roomlink-campus-tab';
    var noResults = page.querySelector('[data-no-results]');

    function showCampus(id) {
        if (!campuses.some(function (campus) { return campus.id === id; })) {
            id = campuses.length ? campuses[0].id : '';
        }

        campuses.forEach(function (campus) { campus.hidden = campus.id !== id; });
        tabs.forEach(function (tab) {
            var selected = tab.dataset.campusTab === id;

            tab.classList.toggle('active', selected);
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        // "+ Building" at the top adds to the campus being looked at.
        page.querySelectorAll('[data-campus-preset]').forEach(function (button) {
            var campus = document.getElementById(id);

            button.dataset.values = JSON.stringify({ campus_id: campus.dataset.campusId });
        });

        try {
            sessionStorage.setItem(tabKey, id);
        } catch (error) { /* storage is off; the first campus is shown */ }

        showNoResults();
    }

    function showNoResults() {
        var campus = campuses.filter(function (item) { return !item.hidden; })[0];

        noResults.hidden = !campus || campus.dataset.empty !== 'true';
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { showCampus(tab.dataset.campusTab); });
    });

    var savedTab = '';

    try {
        savedTab = sessionStorage.getItem(tabKey) || '';
    } catch (error) { /* storage is off */ }

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

    showCampus(ownCampus ? ownCampus.id : savedTab);

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

    function filter() {
        var text = search.value.trim().toLowerCase();
        var active = text !== '' || status.value !== '';

        searching = searching || active;

        campuses.forEach(function (campus, index) {
            var campusShown = false;
            var campusRooms = 0;

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
                campusRooms += rooms;

                if (active && shown) {
                    item.open = true;
                }
            });

            campus.dataset.empty = active && !campusShown ? 'true' : 'false';
            tabs[index].querySelector('[data-campus-count]').textContent = campusRooms;
        });

        showNoResults();

        if (!active && searching) {
            restore();
            // Toggle events arrive a little later; they aren't the admin's choice.
            setTimeout(function () { searching = false; }, 50);
        }
    }

    search.addEventListener('input', filter);
    status.addEventListener('change', filter);
})();
