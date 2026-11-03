// Transient action feedback is shared by the signed-in and guest layouts.
export function initializeFeedback(browser = window, doc = document) {
    if (browser.htcFeedback) return browser.htcFeedback;

    const dialog = doc.createElement('dialog');
    dialog.className = 'feedback-modal';
    dialog.setAttribute('aria-labelledby', 'feedbackTitle');
    dialog.setAttribute('aria-describedby', 'feedbackMessages feedbackExpiry');
    dialog.innerHTML = `
        <button type="button" class="feedback-modal__close" aria-label="Close message">&times;</button>
        <span class="feedback-modal__icon" aria-hidden="true"></span>
        <h2 id="feedbackTitle"></h2>
        <ul id="feedbackMessages"></ul>
        <p id="feedbackExpiry" class="feedback-modal__expiry">This message closes automatically.</p>
        <button type="button" class="feedback-modal__dismiss" autofocus>Dismiss</button>`;
    doc.body.append(dialog);

    const title = dialog.querySelector('h2');
    const messages = dialog.querySelector('ul');
    const icon = dialog.querySelector('.feedback-modal__icon');
    const dismiss = dialog.querySelector('.feedback-modal__dismiss');
    const queue = [];
    let timer = null;
    let duration = 3500;
    let focus = null;
    const tones = {
        success: ['Success', '\u2713'],
        error: ['Unable to complete the action', '!'],
        warning: ['Please note', '!'],
        info: ['Information', 'i'],
    };
    const clearTimer = () => { browser.clearTimeout(timer); timer = null; };
    const schedule = () => {
        clearTimer();
        if (dialog.open && !doc.hidden) timer = browser.setTimeout(close, duration);
    };
    const showNext = () => {
        if (dialog.open || !queue.length) return;
        const item = queue.shift();
        const tone = Object.hasOwn(tones, item.tone) ? item.tone : 'info';
        const lines = (Array.isArray(item.messages) ? item.messages : [item.message])
            .filter(message => typeof message === 'string' && message.trim());
        if (!lines.length) { showNext(); return; }
        dialog.dataset.tone = tone;
        title.textContent = item.title || tones[tone][0];
        icon.textContent = tones[tone][1];
        messages.replaceChildren(...lines.map(message => {
            const line = doc.createElement('li');
            line.textContent = message;
            return line;
        }));
        // Give longer messages time to be read; hovering or focusing pauses expiry.
        const words = lines.join(' ').split(/\s+/).length;
        duration = Math.min(20000, Math.max(tone === 'success' ? 3500 : 6000, words * 250));
        focus = doc.activeElement;
        dialog.showModal();
        dismiss.focus({preventScroll: true});
        schedule();
    };
    const close = () => {
        clearTimer();
        if (dialog.open) dialog.close();
    };
    dialog.addEventListener('close', () => {
        clearTimer();
        if (focus?.isConnected) focus.focus({preventScroll: true});
        showNext();
    });
    dialog.addEventListener('cancel', event => { event.preventDefault(); close(); });
    dialog.querySelector('.feedback-modal__close').addEventListener('click', close);
    dismiss.addEventListener('click', close);
    dialog.addEventListener('mouseenter', clearTimer);
    dialog.addEventListener('mouseleave', schedule);
    dialog.addEventListener('focusin', event => {
        // Allow feedback over Bootstrap's requisition/edit dialogs.
        event.stopPropagation();
        clearTimer();
    });
    dialog.addEventListener('focusout', schedule);
    doc.addEventListener('visibilitychange', () => doc.hidden ? clearTimer() : schedule());
    browser.addEventListener('pagehide', () => { queue.length = 0; close(); });

    const show = item => {
        if (!item || typeof item !== 'object') return;
        queue.push(item);
        showNext();
    };
    const consume = (root = doc) => {
        root.querySelectorAll('[data-feedback-messages]').forEach(source => {
            try {
                const items = JSON.parse(source.textContent);
                if (Array.isArray(items)) items.forEach(show);
            } finally {
                source.remove();
            }
        });
    };
    browser.htcFeedback = {show, consume, close};
    consume();
    return browser.htcFeedback;
}
