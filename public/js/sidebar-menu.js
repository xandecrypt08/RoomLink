/*
 * Menus that unfold inside the admin sidebar: Campuses, and Curriculum with
 * its colleges and programs. They unfold on hover and fold when the mouse
 * leaves the sidebar. Nothing folds while the mouse is in the sidebar, so the
 * items below never slide away from under it. With touch or the keyboard they
 * unfold when pressed.
 */
(function () {
    var sidebar = document.getElementById('sidebar');
    var items = sidebar ? Array.prototype.slice.call(sidebar.querySelectorAll('[data-menu-item]')) : [];

    if (!items.length) {
        return;
    }

    var foldTimer = null;

    function trigger(item) {
        return item.querySelector('[data-menu-trigger]');
    }

    function unfold(item, open) {
        item.classList.toggle('open', open);
        trigger(item).setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function isMouse(event) {
        return event.pointerType === 'mouse';
    }

    items.forEach(function (item) {
        var button = trigger(item);
        var hoverTimer = null;
        var byHover = false;
        var pressedWith = '';

        // A short wait, so passing over an item doesn't unfold it.
        button.addEventListener('pointerenter', function (event) {
            if (isMouse(event)) {
                hoverTimer = setTimeout(function () {
                    byHover = !item.classList.contains('open');
                    unfold(item, true);
                }, 120);
            }
        });

        button.addEventListener('pointerleave', function () { clearTimeout(hoverTimer); });
        button.addEventListener('pointerdown', function (event) { pressedWith = event.pointerType; });

        button.addEventListener('click', function (event) {
            clearTimeout(hoverTimer);

            if (button.tagName === 'A') {
                // Without hover, the first press unfolds and the second goes to the page.
                if (!item.classList.contains('open') && (pressedWith === 'touch' || pressedWith === 'pen')) {
                    event.preventDefault();
                    unfold(item, true);
                }
            } else {
                // A click right after hovering keeps open what the hover unfolded.
                unfold(item, byHover || !item.classList.contains('open'));
                byHover = false;
            }
        });

        item.addEventListener('keydown', function (event) {
            if (event.target !== button && event.key !== 'Escape' && event.key !== 'ArrowLeft') {
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                unfold(item, true);
            } else if ((event.key === 'ArrowLeft' || event.key === 'Escape') && item.classList.contains('open')) {
                event.preventDefault();
                event.stopPropagation();
                unfold(item, false);
                button.focus();
            }
        });
    });

    sidebar.addEventListener('pointerleave', function (event) {
        if (isMouse(event)) {
            foldTimer = setTimeout(function () {
                items.forEach(function (item) { unfold(item, false); });
            }, 350);
        }
    });

    sidebar.addEventListener('pointerenter', function () { clearTimeout(foldTimer); });
})();
