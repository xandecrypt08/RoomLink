/* Classrooms page: search and remembering which buildings are open. */
(function () {
    var page = document.querySelector('.classroom-page');

    if (!page) {
        return;
    }

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
})();
