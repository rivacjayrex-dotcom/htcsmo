<style>
    .runtime-loader {
        position: fixed;
        inset: 0;
        z-index: 20000;
        display: grid;
        place-items: end center;
        padding: 1.25rem;
        pointer-events: none;
        background: rgba(248, 250, 249, .12);
    }

    .runtime-loader[hidden] { display: none !important; }
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

<div id="runtimeLoader" class="runtime-loader" role="status" aria-live="polite" aria-atomic="true" hidden>
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
            loaderMessage.textContent = message.replace('&hellip;', '…');
            loader.hidden = false;
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
                if (loader) loader.hidden = true;
            });

            // Restore controls if another script cancels navigation or the network fails.
            window.setTimeout(() => {
                if (document.visibilityState === 'visible') {
                    unlockForm(form);
                    if (loader) loader.hidden = true;
                }
            }, 45000);
        });

        document.addEventListener('click', event => {
            const link = event.target.closest('a[href]');
            if (!link || event.defaultPrevented || link.dataset.noLoader !== undefined || link.target === '_blank' || link.hasAttribute('download')) return;
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin || destination.href === window.location.href || link.dataset.bsToggle) return;
            showLoader('Opening page…');
        });

        window.addEventListener('pageshow', () => {
            lockedForms.forEach(unlockForm);
            if (loader) loader.hidden = true;
        });
    })();
</script>
