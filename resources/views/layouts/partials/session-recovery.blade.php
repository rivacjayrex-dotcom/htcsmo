<span id="sessionRecovery" hidden
        data-check-url="{{ route('session.check') }}" data-recovery-url="{{ route('session.recover') }}" data-login-url="{{ route('login') }}"
        data-account="{{ Auth::id() }}" data-token="{{ csrf_token() }}"></span>
<script>
    (() => {
        if (window.htcSession) return;
        const config = document.getElementById('sessionRecovery').dataset;
        const nativeFetch = window.fetch.bind(window);
        const nativeSubmit = HTMLFormElement.prototype.submit;
        const pending = new Map();
        let expired = false;
        let refreshing = false;
        let checking = false;

        const expiryError = () => Object.assign(new Error('Your session expired.'), {name: 'SessionExpiredError'});
        const releaseForm = form => {
            const state = pending.get(form);
            if (!state) return;
            state.buttons.forEach(button => button.disabled = false);
            state.submitterInput?.remove();
            for (const [name, value] of state.attributes) {
                if (value === null) form.removeAttribute(name);
                else form.setAttribute(name, value);
            }
            pending.delete(form);
        };
        window.addEventListener('pageshow', () => {
            [...pending.keys()].forEach(form => {
                releaseForm(form);
                const status = form.querySelector('[data-session-status]');
                if (status) {
                    status.textContent = '';
                    status.hidden = true;
                }
            });
        });
        const refreshExpiredPage = () => {
            if (!expired || refreshing || document.hidden) return;
            refreshing = true;
            const recovery = new URL(config.recoveryUrl, window.location.href);
            // The server validates this again against the application screen routes.
            recovery.searchParams.set('return', window.location.pathname + window.location.search);
            // Navigate with GET so the browser cannot resubmit a previous write.
            // Guest forms need a fresh token on their current screen.
            window.location.replace(config.account ? recovery.href : window.location.href);
        };
        const show = () => {
            if (expired) return;
            expired = true;
            document.dispatchEvent(new CustomEvent('htc:session-expired'));
            refreshExpiredPage();
        };
        const inspect = response => {
            const redirectedToLogin = response.redirected && response.url
                && new URL(response.url).pathname === new URL(config.loginUrl).pathname;
            if (response.status === 401 || response.status === 419 || redirectedToLogin) show();
            if (expired) throw expiryError();
            return response;
        };
        window.htcSession = {get expired() { return expired; }, show, inspect};

        // Every first-party fetch uses one expiry contract, including HTML/PDF reads.
        window.fetch = async (input, options) => {
            const url = new URL(typeof input === 'string' || input instanceof URL ? input : input.url, window.location.href);
            if (url.origin !== window.location.origin) return nativeFetch(input, options);
            if (expired) throw expiryError();
            try {
                return inspect(await nativeFetch(input, options));
            } catch (error) {
                if (expired) throw expiryError();
                throw error;
            }
        };
        const checkOnReturn = async () => {
            if (document.hidden) return;
            if (expired) { refreshExpiredPage(); return; }
            if (checking) return;
            checking = true;
            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 10000);
            try {
                const body = new URLSearchParams({_token: config.token});
                if (config.account) body.set('account', config.account);
                await window.fetch(config.checkUrl, {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store',
                    headers: {Accept: 'application/json'}, body, signal: controller.signal,
                });
            } catch (error) {
                // Offline or temporary server errors do not mean the session expired.
                // A later return or submission can check again.
            } finally {
                window.clearTimeout(timeout);
                checking = false;
            }
        };
        document.addEventListener('visibilitychange', checkOnReturn);
        window.addEventListener('focus', checkOnReturn);
        window.addEventListener('pageshow', event => {
            if (event.persisted) checkOnReturn();
        });
        document.addEventListener('submit', event => {
            if (!expired) return;
            event.preventDefault();
            event.stopImmediatePropagation();
        }, true);

        // Check the existing token before a native write. Run after target
        // handlers/confirmations; never redispatch them or replay a write.
        document.addEventListener('submit', async event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
            const submitter = event.submitter;
            const method = (submitter?.getAttribute('formmethod') || form.method || 'get').toLowerCase();
            const action = submitter?.getAttribute('formaction') || form.action || window.location.href;
            if (method === 'get' || method === 'dialog' || new URL(action, window.location.href).origin !== window.location.origin) return;
            event.preventDefault();
            if (expired) { show(); return; }
            if (pending.has(form)) return;
            const state = {buttons: [], attributes: ['action', 'method', 'enctype', 'target', 'aria-busy'].map(name => [name, form.getAttribute(name)])};
            pending.set(form, state);
            form.setAttribute('aria-busy', 'true');
            let status = form.querySelector('[data-session-status]');
            if (!status) {
                status = document.createElement('p');
                status.dataset.sessionStatus = '';
                status.setAttribute('role', 'status');
                form.appendChild(status);
            }
            status.textContent = '';
            status.hidden = true;
            const buttons = [...form.querySelectorAll('button[type="submit"], input[type="submit"]')].filter(button => !button.disabled);
            state.buttons = buttons;
            buttons.forEach(button => button.disabled = true);
            const controller = new AbortController();
            const timeout = window.setTimeout(() => controller.abort(), 10000);
            try {
                const body = new URLSearchParams({_token: form.querySelector('[name="_token"]')?.value || ''});
                if (config.account) body.set('account', config.account);
                const response = await window.fetch(config.checkUrl, {
                    method: 'POST', credentials: 'same-origin', headers: {Accept: 'application/json'}, body, signal: controller.signal,
                });
                if (response.status !== 204) throw new Error('Session check failed.');
                if (expired || !form.isConnected) return;
                // Native submission already passed validation and explicit confirmation.
                // Retain the clicked action value and any button-level form overrides.
                if (submitter?.name) {
                    const value = document.createElement('input');
                    value.type = 'hidden'; value.name = submitter.name; value.value = submitter.value;
                    form.appendChild(value);
                    state.submitterInput = value;
                }
                for (const attribute of ['action', 'method', 'enctype', 'target']) {
                    if (submitter?.hasAttribute('form' + attribute)) form.setAttribute(attribute, submitter.getAttribute('form' + attribute));
                }
                document.dispatchEvent(new CustomEvent('htc:session-submit', {detail: {form, submitter}}));
                nativeSubmit.call(form);
                // Keep the form locked until navigation completes; never retry a write.
            } catch (error) {
                status.textContent = error.name === 'SessionExpiredError'
                    ? 'Your session expired. Refreshing...'
                    : 'We could not check your session. This form was not submitted. Keep your work here and try again when connected.';
                status.hidden = false;
                releaseForm(form);
                form.dispatchEvent(new CustomEvent('htc:session-check-failed', {bubbles: true}));
            } finally {
                window.clearTimeout(timeout);
            }
        });
    })();
</script>
