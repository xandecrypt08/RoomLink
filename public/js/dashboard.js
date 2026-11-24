/* Keeps the admin dashboard's live summaries up to date while it is open. */
(function () {
    var live = document.querySelector('[data-live-dashboard]');

    if (!live) {
        return;
    }

    var loading = false;

    function refresh() {
        // Nothing to show on a hidden tab; it catches up when shown again.
        if (loading || document.hidden) {
            return;
        }

        loading = true;

        fetch(live.dataset.liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) {
                // A login page after signing out must not land in the dashboard.
                return response.ok && !response.redirected ? response.text() : Promise.reject();
            })
            .then(function (html) { live.innerHTML = html; })
            .catch(function () { /* keep the last figures; the time shows their age */ })
            .then(function () { loading = false; });
    }

    setInterval(refresh, 15000);
    document.addEventListener('visibilitychange', refresh);
})();
