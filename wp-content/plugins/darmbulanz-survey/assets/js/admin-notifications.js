(function () {
    var config = window.DarmbulanzAdminNotifications;
    if (!config) {
        return;
    }

    var lastKnownId = parseInt(config.initialLatestId, 10) || 0;
    var toastContainer = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getToastContainer() {
        if (toastContainer) {
            return toastContainer;
        }

        toastContainer = document.createElement('div');
        toastContainer.className = 'db-toast-container';
        toastContainer.setAttribute('aria-live', 'polite');
        document.body.appendChild(toastContainer);

        return toastContainer;
    }

    function showToast(name) {
        var container = getToastContainer();
        var toast = document.createElement('div');
        toast.className = 'db-toast';
        toast.innerHTML =
            '<a class="db-toast__link" href="' + escapeHtml(config.applicationsUrl) + '">' +
            '<strong>New Darmbulanz registration</strong>' +
            '<span>' + escapeHtml(name || 'A new registration') + ' is waiting for review.</span>' +
            '</a>' +
            '<button type="button" class="db-toast__close" aria-label="Dismiss">&times;</button>';

        container.appendChild(toast);

        var removeToast = function () {
            toast.classList.add('db-toast--leaving');
            setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 200);
        };

        toast.querySelector('.db-toast__close').addEventListener('click', function (event) {
            event.preventDefault();
            removeToast();
        });

        setTimeout(removeToast, 8000);
    }

    function findMenuLinks(menuItem) {
        var links = [];
        var topLink = menuItem.querySelector(':scope > a.menu-top');
        if (topLink) {
            links.push(topLink);
        }

        menuItem.querySelectorAll('.wp-submenu a').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            if (/[?&]page=darmbulanz-survey(&|$)/.test(href)) {
                links.push(link);
            }
        });

        return links;
    }

    function updateMenuBadges(count) {
        var menuItem = document.getElementById('toplevel_page_darmbulanz-survey');
        if (!menuItem) {
            return;
        }

        findMenuLinks(menuItem).forEach(function (link) {
            var badge = link.querySelector('.awaiting-mod');

            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.innerHTML = '<span class="pending-count"></span>';
                    link.appendChild(document.createTextNode(' '));
                    link.appendChild(badge);
                }
                badge.className = 'awaiting-mod count-' + count;
                var label = badge.querySelector('.pending-count');
                if (label) {
                    label.textContent = String(count);
                }
            } else if (badge && badge.parentNode) {
                badge.parentNode.removeChild(badge);
            }
        });
    }

    function poll() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', config.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status !== 200) {
                return;
            }

            var response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (error) {
                return;
            }

            if (!response || !response.success || !response.data) {
                return;
            }

            var data = response.data;
            var latestId = parseInt(data.latestId, 10) || 0;

            updateMenuBadges(parseInt(data.pendingCount, 10) || 0);

            if (latestId > 0 && latestId !== lastKnownId) {
                lastKnownId = latestId;
                showToast(data.latestName);
            }
        };

        var body = 'action=' + encodeURIComponent(config.action) + '&nonce=' + encodeURIComponent(config.nonce);
        xhr.send(body);
    }

    setInterval(poll, parseInt(config.pollIntervalMs, 10) || 15000);
})();
