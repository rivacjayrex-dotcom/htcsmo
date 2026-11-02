<style>
    .auth-progress-dialog { width:min(360px, calc(100vw - 2rem)); margin:auto; padding:var(--space-24); border:1px solid #dce4de; border-radius:16px; color:#173b27; background:#fff; box-shadow:0 22px 60px rgb(8 34 19 / 24%); }
    .auth-progress-dialog::backdrop { background:rgb(10 29 18 / 46%); backdrop-filter:blur(2px); }
    .auth-progress-dialog__content { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:var(--space-16); text-align:center; }
    .auth-progress-dialog h2 { margin:0; font-size:var(--text-heading-md); font-weight:700; line-height:var(--line-body); }
    .auth-progress-dialog p { margin:var(--space-4) 0 0; color:#66756c; font-size:var(--text-body); line-height:var(--line-body); }
    .auth-progress-spinner { display:block; width:2.75rem; height:2.75rem; flex:0 0 auto; border:4px solid #dbe9df; border-top-color:#198754; border-radius:50%; animation:auth-progress-spin .8s linear infinite; will-change:transform; }
    body:has(.auth-progress-dialog[open]) { overflow:hidden; }
    @keyframes auth-progress-spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
    @keyframes auth-progress-pulse { 0%,100% { opacity:.45; } 50% { opacity:1; } }
    @media(prefers-reduced-motion:reduce) { .auth-progress-spinner { animation:auth-progress-pulse 1.6s ease-in-out infinite; will-change:opacity; } }
</style>
<dialog id="authProgressDialog" class="auth-progress-dialog" aria-labelledby="authProgressTitle" aria-describedby="authProgressMessage" tabindex="-1">
    <div class="auth-progress-dialog__content">
        <span class="auth-progress-spinner" aria-hidden="true"></span>
        <div role="status" aria-live="polite" aria-atomic="true">
            <h2 id="authProgressTitle">Please wait</h2>
            <p id="authProgressMessage">Preparing your request.</p>
        </div>
    </div>
</dialog>
<script>
    (() => {
        const dialog = document.getElementById('authProgressDialog');
        const title = document.getElementById('authProgressTitle');
        const message = document.getElementById('authProgressMessage');
        let activeForm = null;
        let previousFocus = null;
        const copy = {
            login: ['Signing you in…', 'Please wait while we securely sign you in.'],
            register: ['Creating your account…', 'Please wait while we submit your account for review.'],
        };

        const show = form => {
            const text = copy[form?.dataset.authProgress];
            if (!text || window.htcSession?.expired || typeof dialog.showModal !== 'function') return;
            title.textContent = text[0];
            message.textContent = text[1];
            activeForm = form;
            if (dialog.open) return;
            previousFocus = document.activeElement;
            dialog.showModal();
            dialog.focus({preventScroll: true});
        };
        const hide = (restoreFocus = true) => {
            if (dialog.open) dialog.close();
            if (restoreFocus && previousFocus?.isConnected) previousFocus.focus({preventScroll: true});
            activeForm = null;
            previousFocus = null;
        };

        // The session guard runs first and marks pending forms busy. A canceled
        // submission must not show a modal unless that check is actually running.
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            const checking = form.getAttribute('aria-busy') === 'true';
            if (event.defaultPrevented && !checking) return;
            show(form);
        });
        document.addEventListener('htc:session-submit', event => show(event.detail.form));
        document.addEventListener('htc:session-check-failed', event => {
            if (event.target === activeForm) hide(!window.htcSession?.expired);
        });
        document.addEventListener('htc:session-expired', () => hide(false));
        dialog.addEventListener('cancel', event => event.preventDefault());
        window.addEventListener('pagehide', () => hide(false));
        window.addEventListener('pageshow', () => hide(false));
    })();
</script>
