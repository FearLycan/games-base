/**
 * Admin module client glue.
 *
 * Two interactions are handled without a full page reload:
 *  - inline status toggle  (.js-status-switch)  -> POST toggle-status, flip checkbox
 *  - inline row deletion    (.js-ajax-delete)   -> POST delete, drop the <tr>
 *
 * Both POST to the controller action whose URL lives on the element, sending the
 * CSRF token from the <meta> tags Yii renders into the layout.
 */
(function () {
    'use strict';

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : null;
    }

    function post(url) {
        var token = meta('csrf-token');
        var param = meta('csrf-param');
        var body = new URLSearchParams();
        if (param && token) {
            body.append(param, token);
        }

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': token || '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: body
        }).then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: 'Unexpected server response.' };
            });
        });
    }

    function toast(message, ok) {
        var container = document.getElementById('admin-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'admin-toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1090';
            document.body.appendChild(container);
        }

        var el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-' + (ok ? 'success' : 'danger') + ' border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex">' +
            '<div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>';
        container.appendChild(el);

        if (window.bootstrap && window.bootstrap.Toast) {
            var t = new window.bootstrap.Toast(el, { delay: 2500 });
            t.show();
            el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        } else {
            window.setTimeout(function () { el.remove(); }, 2500);
        }
    }

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input.classList || !input.classList.contains('js-status-switch')) {
            return;
        }

        var url = input.getAttribute('data-url');
        input.disabled = true;

        post(url).then(function (data) {
            if (data && data.success) {
                input.checked = !!data.active;
                toast(data.message || 'Status updated.', true);
            } else {
                input.checked = !input.checked;
                toast((data && data.message) || 'Could not update status.', false);
            }
        }).catch(function () {
            input.checked = !input.checked;
            toast('Network error.', false);
        }).finally(function () {
            input.disabled = false;
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest) {
            return;
        }

        // Sidebar toggle (mobile)
        if (e.target.closest('.js-sidebar-toggle')) {
            e.preventDefault();
            var shell = document.querySelector('.admin-shell');
            if (shell) {
                shell.classList.toggle('is-sidebar-open');
            }
            return;
        }

        var del = e.target.closest('.js-ajax-delete');
        if (del) {
            e.preventDefault();
            var message = del.getAttribute('data-confirm-text') || 'Are you sure you want to delete this item?';
            if (!window.confirm(message)) {
                return;
            }
            post(del.getAttribute('href') || del.getAttribute('data-url')).then(function (data) {
                if (data && data.success) {
                    removeRow(del);
                    toast(data.message || 'Item deleted.', true);
                } else {
                    toast((data && data.message) || 'Could not delete item.', false);
                }
            }).catch(function () { toast('Network error.', false); });
            return;
        }

        var accept = e.target.closest('.js-ajax-accept');
        if (accept) {
            e.preventDefault();
            post(accept.getAttribute('href') || accept.getAttribute('data-url')).then(function (data) {
                if (data && data.success) {
                    removeRow(accept);
                    toast(data.message || 'Offer accepted.', true);
                } else {
                    toast((data && data.message) || 'Could not accept offer.', false);
                }
            }).catch(function () { toast('Network error.', false); });
            return;
        }

        var reject = e.target.closest('.js-ajax-reject');
        if (reject) {
            e.preventDefault();
            post(reject.getAttribute('href') || reject.getAttribute('data-url')).then(function (data) {
                if (data && data.success) {
                    removeRow(reject);
                    toast(data.message || 'Offer rejected.', true);
                } else {
                    toast((data && data.message) || 'Could not reject offer.', false);
                }
            }).catch(function () { toast('Network error.', false); });
        }
    });

    function removeRow(el) {
        var row = el.closest('tr');
        if (row) {
            row.style.transition = 'opacity .2s';
            row.style.opacity = '0';
            window.setTimeout(function () { row.remove(); }, 200);
        }
    }
})();
