<style>
    .runtime-loader {
        position: fixed;
        top: 72px;
        right: 0;
        bottom: 0;
        left: var(--sidebar-width, 250px);
        z-index: 20000;
        display: block;
        padding: 1.5rem;
        overflow: hidden;
        cursor: wait;
        pointer-events: auto;
        touch-action: none;
        background: #f3f7f4;
    }

    .runtime-loader[hidden] { display: none !important; }
    body.runtime-locked { overflow: hidden; cursor: wait; }
    .runtime-content-skeleton { min-height: calc(100vh - 120px); padding: 1.4rem; border: 1px solid #e0e9e3; border-radius: 18px; background: #fff; box-shadow: 0 12px 34px rgba(17,62,38,.06); }
    .runtime-content-skeleton__heading { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
    .runtime-content-skeleton__cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
    .runtime-content-skeleton__card { min-height: 122px; padding: 1rem; border: 1px solid #e5ece7; border-radius: 14px; }
    .runtime-content-skeleton__table { margin-top: 1rem; padding: 1rem; border: 1px solid #e5ece7; border-radius: 14px; }
    .runtime-content-skeleton__row { display: grid; grid-template-columns: 1.2fr .8fr .65fr; gap: 1rem; padding: .85rem 0; border-bottom: 1px solid #edf1ef; }
    .runtime-content-skeleton__row:last-child { border-bottom: 0; }
    .runtime-content-skeleton__block { position: relative; height: 12px; overflow: hidden; border-radius: 8px; background: #e5ece7; }
    .runtime-content-skeleton__block::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.72), transparent); animation: runtime-shimmer 1.25s ease-in-out infinite; }

    .runtime-spinner { width: 1rem; height: 1rem; flex: 0 0 1rem; border: 2px solid currentColor; border-right-color: transparent; border-radius: 50%; animation: runtime-spin .7s linear infinite; }
    button .runtime-spinner { width: .9rem; height: .9rem; flex-basis: .9rem; }
    form[data-runtime-submitting='true'] { cursor: wait; }
    .runtime-logout-modal { position: fixed; inset: 0; z-index: 20001; display: grid; place-items: center; padding: 1rem; background: rgba(10, 29, 18, .46); backdrop-filter: blur(2px); }
    .runtime-logout-modal[hidden] { display: none !important; }
    .runtime-logout-modal__dialog { display: flex; align-items: center; gap: .9rem; width: min(100%, 320px); padding: 1.15rem 1.25rem; border: 1px solid rgba(255, 255, 255, .7); border-radius: 16px; background: #fff; color: #173b27; box-shadow: 0 22px 60px rgba(8, 34, 19, .24); }
    .runtime-logout-modal__spinner { width: 1.5rem; height: 1.5rem; flex: 0 0 1.5rem; border: 3px solid #dbe9df; border-top-color: #198754; border-radius: 50%; animation: runtime-spin .75s linear infinite; }
    .runtime-logout-modal__title { display: block; font-size: .95rem; font-weight: 700; line-height: 1.25; }
    .runtime-logout-modal__copy { display: block; margin-top: .15rem; color: #66756c; font-size: .78rem; line-height: 1.35; }

    @keyframes runtime-spin { to { transform: rotate(360deg); } }
    @keyframes runtime-shimmer { 100% { transform: translateX(100%); } }

    @media (max-width: 991.98px) {
        .runtime-loader { left: 0; }
        .runtime-content-skeleton__cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .runtime-loader { top: 64px; padding: .8rem; }
        .runtime-content-skeleton { padding: 1rem; border-radius: 14px; }
        .runtime-content-skeleton__cards { grid-template-columns: 1fr; }
        .runtime-content-skeleton__card:nth-child(3) { display: none; }
        .runtime-content-skeleton__row { grid-template-columns: 1fr .55fr; }
        .runtime-content-skeleton__row > :last-child { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .runtime-spinner { animation-duration: 2s; }
        .runtime-content-skeleton__block::after { animation: none; }
    }
</style>

<div id="runtimeLoader" class="runtime-loader" role="status" aria-live="polite" aria-atomic="true" tabindex="-1" hidden>
    <span id="runtimeLoaderMessage" class="visually-hidden">Loading&hellip;</span>
    <div class="runtime-content-skeleton" aria-hidden="true">
        <div class="runtime-content-skeleton__heading">
            <div class="d-grid gap-2" style="width: min(360px, 65%)">
                <div class="runtime-content-skeleton__block" style="width: 62%"></div>
                <div class="runtime-content-skeleton__block" style="width: 88%"></div>
            </div>
            <div class="runtime-content-skeleton__block" style="width: 110px; height: 38px"></div>
        </div>
        <div class="runtime-content-skeleton__cards">
            @for($card = 0; $card < 3; $card++)
                <div class="runtime-content-skeleton__card d-grid align-content-start gap-3">
                    <div class="runtime-content-skeleton__block" style="width: 38%"></div>
                    <div class="runtime-content-skeleton__block" style="width: 54%; height: 28px"></div>
                    <div class="runtime-content-skeleton__block" style="width: 88%"></div>
                </div>
            @endfor
        </div>
        <div class="runtime-content-skeleton__table">
            @for($row = 0; $row < 6; $row++)
                <div class="runtime-content-skeleton__row">
                    <div class="runtime-content-skeleton__block" style="width: 88%"></div>
                    <div class="runtime-content-skeleton__block" style="width: 68%"></div>
                    <div class="runtime-content-skeleton__block" style="width: 68%"></div>
                </div>
            @endfor
        </div>
    </div>
</div>

<div id="runtimeLogoutModal" class="runtime-logout-modal" role="dialog" aria-modal="true" aria-labelledby="runtimeLogoutTitle" tabindex="-1" hidden>
    <div class="runtime-logout-modal__dialog">
        <span class="runtime-logout-modal__spinner" aria-hidden="true"></span>
        <span>
            <span id="runtimeLogoutTitle" class="runtime-logout-modal__title">Logging out…</span>
            <span class="runtime-logout-modal__copy">Please wait while we securely end your session.</span>
        </span>
    </div>
</div>

<script>
    (() => {
        if (window.__htcRuntimeGuardLoaded) return;
        window.__htcRuntimeGuardLoaded = true;

        const loader = document.getElementById('runtimeLoader');
        const loaderMessage = document.getElementById('runtimeLoaderMessage');
        const logoutModal = document.getElementById('runtimeLogoutModal');
        const lockedForms = new Set();
        const nativeSubmit = HTMLFormElement.prototype.submit;
        let runtimeLocked = false;
        let logoutPending = false;
        let previousFocus = null;

        // Legacy form.submit() skips submit events. Route it through requestSubmit()
        // so filter forms receive the same loader and duplicate-submit protection.
        HTMLFormElement.prototype.submit = function () {
            if (typeof this.requestSubmit === 'function') {
                this.requestSubmit();
                return;
            }
            nativeSubmit.call(this);
        };

        const showLoader = (message = 'Loading&hellip;') => {
            if (!loader) return;
            if (!runtimeLocked && document.activeElement instanceof HTMLElement) {
                previousFocus = document.activeElement;
            }
            loaderMessage.textContent = message.replace('&hellip;', '…');
            loader.hidden = false;
            runtimeLocked = true;
            document.body.classList.add('runtime-locked');
            document.documentElement.setAttribute('aria-busy', 'true');
            loader.focus({preventScroll: true});
        };

        const showLogoutModal = () => {
            if (!logoutModal) return;
            if (!runtimeLocked && document.activeElement instanceof HTMLElement) {
                previousFocus = document.activeElement;
            }
            logoutPending = true;
            runtimeLocked = true;
            logoutModal.hidden = false;
            document.body.classList.add('runtime-locked');
            document.documentElement.setAttribute('aria-busy', 'true');
            logoutModal.focus({preventScroll: true});
        };

        const hideLoader = () => {
            runtimeLocked = false;
            logoutPending = false;
            document.body.classList.remove('runtime-locked');
            document.documentElement.removeAttribute('aria-busy');
            if (loader) loader.hidden = true;
            if (logoutModal) logoutModal.hidden = true;
            if (previousFocus?.isConnected) previousFocus.focus({preventScroll: true});
            previousFocus = null;
        };

        const clearRuntimeCache = () => {
            try {
                Object.keys(sessionStorage)
                    .filter(key => key.startsWith('htc-runtime-state:'))
                    .forEach(key => sessionStorage.removeItem(key));
                sessionStorage.setItem('htc-runtime-mutated-at', String(Date.now()));
            } catch (error) {
                // Storage can be unavailable in private browsing; submission still works.
            }
        };

        const unlockForm = form => {
            delete form.dataset.runtimeSubmitting;
            form.querySelectorAll('[data-runtime-disabled]').forEach(button => {
                button.disabled = false;
                button.innerHTML = button.__runtimeOriginalHtml || button.innerHTML;
                button.removeAttribute('aria-busy');
                button.removeAttribute('data-runtime-disabled');
            });
            form.querySelectorAll('[data-runtime-submitter]').forEach(input => input.remove());
            lockedForms.delete(form);
        };

        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented || form.dataset.noLoader !== undefined) return;

            if (form.dataset.runtimeSubmitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.runtimeSubmitting = 'true';
            lockedForms.add(form);

            const submitter = event.submitter;
            if (submitter?.name) {
                const value = document.createElement('input');
                value.type = 'hidden';
                value.name = submitter.name;
                value.value = submitter.value;
                value.dataset.runtimeSubmitter = 'true';
                form.appendChild(value);
            }

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
                if (button.disabled) return;
                button.__runtimeOriginalHtml = button.innerHTML;
                button.dataset.runtimeDisabled = 'true';
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');

                if (button.tagName === 'BUTTON') {
                    const label = button.dataset.loadingText || 'Please wait…';
                    const spinner = document.createElement('span');
                    const text = document.createElement('span');
                    spinner.className = 'runtime-spinner';
                    spinner.setAttribute('aria-hidden', 'true');
                    text.textContent = label;
                    button.replaceChildren(spinner, text);
                }
            });

            if ((form.method || 'get').toLowerCase() !== 'get') clearRuntimeCache();
            if (form.dataset.logoutForm !== undefined) {
                showLogoutModal();
            } else {
                showLoader(form.dataset.loadingMessage || 'Processing request…');
            }

            queueMicrotask(() => {
                if (!event.defaultPrevented) return;
                unlockForm(form);
                hideLoader();
            });

        });

        // The overlay absorbs pointer input. Capture keyboard-triggered actions too,
        // so users cannot start another operation while a request is in flight.
        document.addEventListener('click', event => {
            if (!runtimeLocked || loader?.contains(event.target) || logoutModal?.contains(event.target)) return;
            event.preventDefault();
            event.stopImmediatePropagation();
        }, true);

        document.addEventListener('keydown', event => {
            if (!runtimeLocked) return;
            event.preventDefault();
            event.stopImmediatePropagation();
        }, true);

        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || link.dataset.noLoader !== undefined || link.target === '_blank' || link.hasAttribute('download')) return;
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

            const destination = new URL(link.href, window.location.href);
            const sameDocumentAnchor = destination.origin === window.location.origin
                && destination.pathname === window.location.pathname
                && destination.search === window.location.search
                && destination.hash;
            if (!['http:', 'https:'].includes(destination.protocol)
                || destination.origin !== window.location.origin
                || destination.href === window.location.href
                || sameDocumentAnchor
                || link.dataset.bsToggle) return;
            showLoader('Opening page…');
        });

        window.addEventListener('beforeunload', () => {
            if (!logoutPending) showLoader('Loading page…');
        });
        window.addEventListener('pageshow', () => {
            lockedForms.forEach(unlockForm);
            hideLoader();
        });
    })();
</script>
