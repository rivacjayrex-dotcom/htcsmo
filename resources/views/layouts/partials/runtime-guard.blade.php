<style>
    .runtime-loader {
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: grid;
        place-items: center;
        padding: 1.25rem;
        cursor: wait;
        pointer-events: auto;
        touch-action: none;
        background: rgba(248, 250, 249, .5);
        backdrop-filter: blur(0.5px);
    }

    .runtime-loader[hidden] { display: none !important; }
    body.runtime-locked { overflow: hidden; cursor: wait; }
    .runtime-loader::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 42%;
        height: 3px;
        background: #198754;
        box-shadow: 0 0 12px rgba(25, 135, 84, .45);
        animation: runtime-progress 1.15s ease-in-out infinite;
    }

    .runtime-loader__message {
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        padding: .7rem .9rem;
        color: #244c34;
        font-size: .76rem;
        font-weight: 700;
        background: rgba(255, 255, 255, .97);
        border: 1px solid #d8e5dc;
        border-radius: .75rem;
        box-shadow: 0 12px 35px rgba(20, 55, 34, .15);
    }

    .runtime-spinner {
        width: 1rem;
        height: 1rem;
        flex: 0 0 1rem;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: runtime-spin .7s linear infinite;
    }

    button .runtime-spinner { width: .9rem; height: .9rem; flex-basis: .9rem; }
    form[data-runtime-submitting='true'] { cursor: wait; }

    @keyframes runtime-spin { to { transform: rotate(360deg); } }
    @keyframes runtime-progress {
        0% { transform: translateX(-110%); }
        100% { transform: translateX(340%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .runtime-spinner, .runtime-loader::before { animation-duration: 2s; }
    }
</style>

<div id="runtimeLoader" class="runtime-loader" role="status" aria-live="polite" aria-atomic="true" tabindex="-1" hidden>
    <div class="runtime-loader__message">
        <span class="runtime-spinner" aria-hidden="true"></span>
        <span id="runtimeLoaderMessage">Loading&hellip;</span>
    </div>
</div>

<script>
    (() => {
        if (window.__htcRuntimeGuardLoaded) return;
        window.__htcRuntimeGuardLoaded = true;

        const loader = document.getElementById('runtimeLoader');
        const loaderMessage = document.getElementById('runtimeLoaderMessage');
        const lockedForms = new Set();
        const nativeSubmit = HTMLFormElement.prototype.submit;
        let runtimeLocked = false;
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

        const hideLoader = () => {
            runtimeLocked = false;
            document.body.classList.remove('runtime-locked');
            document.documentElement.removeAttribute('aria-busy');
            if (loader) loader.hidden = true;
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
            showLoader(form.dataset.loadingMessage || 'Processing request…');

            queueMicrotask(() => {
                if (!event.defaultPrevented) return;
                unlockForm(form);
                hideLoader();
            });

        });

        // The overlay absorbs pointer input. Capture keyboard-triggered actions too,
        // so users cannot start another operation while a request is in flight.
        document.addEventListener('click', event => {
            if (!runtimeLocked || loader?.contains(event.target)) return;
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

        window.addEventListener('beforeunload', () => showLoader('Loading page…'));
        window.addEventListener('pageshow', () => {
            lockedForms.forEach(unlockForm);
            hideLoader();
        });
    })();
</script>
