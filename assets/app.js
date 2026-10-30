(() => {
    let pendingAction = null;
    let pendingSubmitter = null;

    const dialog = document.createElement('dialog');
    dialog.className = 'app-confirm-dialog';
    dialog.setAttribute('aria-labelledby', 'app-confirm-title');
    dialog.innerHTML = `
        <div class="confirm-dialog-icon" aria-hidden="true">!</div>
        <h2 id="app-confirm-title">Xác nhận thao tác</h2>
        <p class="confirm-dialog-message"></p>
        <div class="confirm-dialog-actions">
            <button type="button" class="confirm-cancel">Hủy</button>
            <button type="button" class="confirm-accept">Tiếp tục</button>
        </div>
    `;
    document.body.appendChild(dialog);

    const message = dialog.querySelector('.confirm-dialog-message');

    document.querySelectorAll('.toast-notice').forEach(notice => {
        window.setTimeout(() => {
            notice.classList.add('is-hiding');
            window.setTimeout(() => notice.remove(), 300);
        }, 5000);
    });

    function showConfirmation(target, submitter) {
        if (typeof dialog.showModal !== 'function') {
            if (!window.confirm(target.dataset.confirm)) return false;

            if (target instanceof HTMLFormElement) {
                target.dataset.confirmed = 'true';
                if (submitter && submitter.form === target) {
                    target.requestSubmit(submitter);
                } else {
                    target.requestSubmit();
                }
            } else if (target instanceof HTMLAnchorElement) {
                window.location.assign(target.href);
            }
            return true;
        }

        pendingAction = target;
        pendingSubmitter = submitter || null;
        message.textContent = target.dataset.confirm;
        dialog.showModal();
        dialog.querySelector('.confirm-cancel').focus();
        return false;
    }

    document.addEventListener('submit', event => {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        showConfirmation(form, event.submitter);
    });

    document.addEventListener('click', event => {
        const link = event.target.closest('a[data-confirm]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        showConfirmation(link, null);
    });

    dialog.querySelector('.confirm-cancel').addEventListener('click', () => {
        dialog.close();
        pendingAction = null;
        pendingSubmitter = null;
    });

    dialog.querySelector('.confirm-accept').addEventListener('click', () => {
        const target = pendingAction;
        const submitter = pendingSubmitter;
        pendingAction = null;
        pendingSubmitter = null;
        dialog.close();

        if (target instanceof HTMLFormElement) {
            target.dataset.confirmed = 'true';
            if (submitter && submitter.form === target) {
                target.requestSubmit(submitter);
            } else {
                target.requestSubmit();
            }
        } else if (target instanceof HTMLAnchorElement) {
            window.location.assign(target.href);
        }
    });
})();
