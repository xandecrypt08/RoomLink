/*
 * Menus in the admin sidebar: Campuses, and Curriculum with its colleges and
 * programs. On a computer a menu opens beside the sidebar on hover; with
 * touch, a small screen or the keyboard it opens when pressed. The sidebar
 * scrolls and would cut the menu off, so on wide screens the menu is moved
 * out of it.
 */
(function () {
    var closers = [];

    Array.prototype.forEach.call(document.querySelectorAll('[data-sidebar-menu]'), function (menu) {
        var flyout = menu.querySelector('[data-menu-flyout]');

        if (!flyout) {
            return;
        }

        var button = menu.querySelector('[data-menu-button]');
        var colleges = Array.prototype.slice.call(flyout.querySelectorAll('[data-menu-college]'));
        var wide = window.matchMedia('(min-width: 901px)');
        var closeTimer = null;
        var switchTimer = null;

        function collegeButton(college) {
            return college.querySelector('[data-menu-college-button]');
        }

        function showCollege(college) {
            clearTimeout(switchTimer);
            colleges.forEach(function (item) {
                var shown = item === college;

                item.classList.toggle('open', shown);
                item.querySelector('ul').hidden = !shown;
                collegeButton(item).setAttribute('aria-expanded', shown ? 'true' : 'false');
            });

            place();
        }

        function place() {
            if (!wide.matches || flyout.hidden) {
                return;
            }

            var edge = menu.closest('.sidebar').getBoundingClientRect().right;
            var top = button.getBoundingClientRect().top;

            flyout.style.left = edge + 'px';
            // Move up when the menu would not fit below the button.
            flyout.style.top = Math.max(8, Math.min(top, window.innerHeight - flyout.offsetHeight - 8)) + 'px';

            // A long list of programs also moves up to stay on the screen.
            var programs = flyout.querySelector('.open > ul');

            if (programs) {
                var room = window.innerHeight - 8 - programs.offsetHeight - flyout.getBoundingClientRect().top;

                programs.style.top = Math.min(0, room) + 'px';
            }
        }

        function open() {
            clearTimeout(closeTimer);

            if (!flyout.hidden) {
                return;
            }

            // Only one menu is open at a time.
            closers.forEach(function (other) {
                if (other !== close) {
                    other();
                }
            });

            (wide.matches ? document.body : menu).appendChild(flyout);
            flyout.classList.toggle('floating', wide.matches);
            flyout.style.left = '';
            flyout.style.top = '';
            flyout.hidden = false;
            button.setAttribute('aria-expanded', 'true');
            place();
        }

        function close() {
            clearTimeout(closeTimer);
            flyout.hidden = true;
            button.setAttribute('aria-expanded', 'false');
            showCollege(null);
        }

        // A short wait, so crossing the gap to the menu doesn't close it.
        function closeSoon() {
            clearTimeout(closeTimer);
            closeTimer = setTimeout(close, 400);
        }

        function isMouse(event) {
            return event.pointerType === 'mouse';
        }

        [menu, flyout].forEach(function (area) {
            area.addEventListener('pointerenter', function (event) {
                if (wide.matches && isMouse(event)) {
                    open();
                }
            });
            area.addEventListener('pointerleave', function (event) {
                if (wide.matches && isMouse(event)) {
                    closeSoon();
                }
            });
        });

        // Without hover, the first press opens the menu and the second goes to the page.
        var pressedWith = '';

        button.addEventListener('pointerdown', function (event) { pressedWith = event.pointerType; });
        button.addEventListener('click', function (event) {
            if (flyout.hidden && (pressedWith === 'touch' || pressedWith === 'pen' || !wide.matches)) {
                event.preventDefault();
                open();
            }
        });

        button.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                event.preventDefault();
                open();
                flyout.querySelector('button, a').focus();
            }
        });

        colleges.forEach(function (college) {
            var header = collegeButton(college);

            college.addEventListener('pointerenter', function (event) {
                if (!wide.matches || !isMouse(event)) {
                    return;
                }

                // Moving diagonally to a program can cross another college; that
                // one only takes over if the mouse stays on it.
                clearTimeout(switchTimer);

                if (flyout.querySelector('.open') === null) {
                    showCollege(college);
                } else if (!college.classList.contains('open')) {
                    switchTimer = setTimeout(function () { showCollege(college); }, 220);
                }
            });

            college.addEventListener('pointerleave', function () { clearTimeout(switchTimer); });

            header.addEventListener('click', function (event) {
                // A mouse already opened it by hovering; a click must not close it again.
                var opened = college.classList.contains('open') && !(wide.matches && event.detail > 0 && event.pointerType !== 'touch');

                showCollege(opened ? null : college);
            });

            header.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    showCollege(college);
                    college.querySelector('a').focus();
                }
            });
        });

        flyout.addEventListener('keydown', function (event) {
            var items = Array.prototype.slice.call(flyout.querySelectorAll('button, a')).filter(function (item) { return item.offsetParent; });
            var step = event.key === 'ArrowDown' ? 1 : (event.key === 'ArrowUp' ? -1 : 0);

            if (step) {
                event.preventDefault();
                items[(items.indexOf(document.activeElement) + step + items.length) % items.length].focus();
            } else if (event.key === 'ArrowLeft' && event.target.closest('.sidebar-flyout-programs')) {
                event.preventDefault();
                collegeButton(event.target.closest('[data-menu-college]')).focus();
            }
        });

        // Going to a program on the page already open doesn't load a new page.
        flyout.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                close();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !flyout.hidden) {
                var inside = flyout.contains(document.activeElement);

                close();

                if (inside) {
                    button.focus();
                }
            }
        });

        document.addEventListener('pointerdown', function (event) {
            if (!flyout.hidden && !flyout.contains(event.target) && !menu.contains(event.target)) {
                close();
            }
        });

        closers.push(close);
        wide.addEventListener('change', close);
        window.addEventListener('resize', place);
    });
})();
