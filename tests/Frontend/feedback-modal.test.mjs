import assert from 'node:assert/strict';
import test from 'node:test';
import {initializeFeedback} from '../../resources/js/feedback.js';

function fixture(items = []) {
    const timers = new Map();
    let timerId = 0;
    const sources = [];
    class Element extends EventTarget {
        dataset = {};
        textContent = '';
        isConnected = true;
        children = [];
        setAttribute() {}
        replaceChildren(...children) { this.children = children; }
        focus() { doc.activeElement = this; this.dispatchEvent(new Event('focusin')); }
        remove() { this.isConnected = false; }
    }
    const children = new Map(['h2', 'ul', '.feedback-modal__icon', '.feedback-modal__dismiss', '.feedback-modal__close']
        .map(selector => [selector, new Element()]));
    const dialog = Object.assign(new Element(), {
        open: false,
        querySelector: selector => children.get(selector),
        showModal() { this.open = true; },
        close() { this.open = false; this.dispatchEvent(new Event('close')); },
    });
    const doc = Object.assign(new EventTarget(), {
        hidden: false,
        activeElement: new Element(),
        body: {append() {}},
        createElement: tag => tag === 'dialog' ? dialog : new Element(),
        querySelectorAll: () => sources.filter(source => source.isConnected),
    });
    const originalFocus = doc.activeElement;
    const addPayload = payload => {
        const source = new Element();
        source.textContent = JSON.stringify(payload);
        sources.push(source);
        return source;
    };
    addPayload(items);
    const browser = Object.assign(new EventTarget(), {
        setTimeout(callback, delay) { const id = ++timerId; timers.set(id, {callback, delay}); return id; },
        clearTimeout: id => timers.delete(id),
    });
    const feedback = initializeFeedback(browser, doc);
    const expire = () => {
        assert.equal(timers.size, 1);
        const [id, timer] = [...timers][0];
        timers.delete(id);
        timer.callback();
    };
    return {browser, doc, dialog, children, timers, feedback, expire, originalFocus, addPayload};
}

test('success opens a modal, expires automatically, and restores focus', () => {
    const b = fixture([{tone: 'success', message: 'Item has been removed from inventory.'}]);
    assert.equal(b.dialog.open, true);
    assert.equal(b.children.get('ul').children[0].textContent, 'Item has been removed from inventory.');
    assert.equal([...b.timers.values()][0].delay, 3500);
    b.expire();
    assert.equal(b.dialog.open, false);
    assert.equal(b.doc.activeElement, b.originalFocus);
});

test('messages queue in one modal and error feedback has more reading time', () => {
    const b = fixture([{tone: 'error', messages: ['Name is required.', 'Choose a department.']}, {tone: 'success', message: 'Saved.'}]);
    assert.equal(b.dialog.dataset.tone, 'error');
    assert.equal(b.children.get('ul').children.length, 2);
    assert.equal([...b.timers.values()][0].delay, 6000);
    b.expire();
    assert.equal(b.dialog.open, true);
    assert.equal(b.dialog.dataset.tone, 'success');
    b.expire();
    assert.equal(b.dialog.open, false);
});

test('Escape and the close button dismiss feedback and clear its timer', () => {
    const b = fixture([{message: 'Notice.'}]);
    b.dialog.dispatchEvent(new Event('cancel', {cancelable: true}));
    assert.equal(b.dialog.open, false);
    assert.equal(b.timers.size, 0);
    b.feedback.show({message: 'Another notice.'});
    b.children.get('.feedback-modal__close').dispatchEvent(new Event('click'));
    assert.equal(b.dialog.open, false);
    assert.equal(b.timers.size, 0);
});

test('hovering or hiding the tab pauses expiry and returning restarts it', () => {
    const b = fixture([{message: 'Please read.'}]);
    b.dialog.dispatchEvent(new Event('mouseenter'));
    assert.equal(b.timers.size, 0);
    b.dialog.dispatchEvent(new Event('mouseleave'));
    assert.equal(b.timers.size, 1);
    b.doc.hidden = true;
    b.doc.dispatchEvent(new Event('visibilitychange'));
    assert.equal(b.timers.size, 0);
    b.doc.hidden = false;
    b.doc.dispatchEvent(new Event('visibilitychange'));
    b.expire();
    assert.equal(b.dialog.open, false);
});

test('consumed feedback is not replayed, HTML stays text, and initialization is idempotent', () => {
    const b = fixture();
    assert.equal(b.dialog.open, false);
    b.addPayload([{tone: 'success', message: '<img src=x onerror=alert(1)>'}]);
    b.feedback.consume();
    assert.equal(b.children.get('ul').children[0].textContent, '<img src=x onerror=alert(1)>');
    assert.equal(initializeFeedback(b.browser, b.doc), b.feedback);
    b.feedback.consume();
    b.expire();
    assert.equal(b.dialog.open, false);
});
