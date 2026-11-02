import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';
import {initializeDynamicSearch} from '../../resources/js/dynamic-search.js';

const read = path => readFileSync(new URL('../../' + path, import.meta.url), 'utf8');
const template = read('resources/views/layouts/partials/session-recovery.blade.php');
const source = template.slice(template.indexOf('<script>') + 8, template.lastIndexOf('</script>'));
const flush = () => new Promise(resolve => setImmediate(resolve));

function browser(reply = {ok: true, status: 204}) {
    class Element extends EventTarget {
        dataset = {}; children = []; attributes = new Map(); hidden = true; disabled = false; isConnected = true;
        textContent = ''; name = ''; value = '';
        setAttribute(key, value) { this.attributes.set(key, value); }
        getAttribute(key) { return this.attributes.get(key) ?? null; }
        hasAttribute(key) { return this.attributes.has(key); }
        removeAttribute(key) { this.attributes.delete(key); }
        appendChild(child) { this.children.push(child); child.parent = this; }
        remove() { this.parent.children = this.parent.children.filter(child => child !== this); }
        querySelector(selector) {
            if (selector === '[name="_token"]') return {value: 'old-token'};
            if (selector === '[data-session-status]') return this.children.find(child => 'sessionStatus' in child.dataset);
        }
        querySelectorAll() { return this.children.filter(child => child === button); }
    }
    class Form extends Element {
        method = 'post'; action = 'https://app.test/requisitions'; purpose = 'Unsaved work'; writes = 0;
        submit() { this.writes++; }
    }
    const button = new Element(); button.name = 'status'; button.value = 'approved';
    const config = new Element();
    config.dataset = {checkUrl: '/session/check', recoveryUrl: '/session/recover', loginUrl: 'https://app.test/login', account: '7', token: 'old-token'};
    const document = Object.assign(new EventTarget(), {
        hidden: false, getElementById: () => config, createElement: () => new Element(),
    });
    const calls = [];
    const navigations = [];
    const window = Object.assign(new EventTarget(), {location: {href: 'https://app.test/requests?status=pending', origin: 'https://app.test', pathname: '/requests', search: '?status=pending', replace: url => navigations.push(url)},
        fetch: async (...args) => { calls.push(args); if (reply instanceof Error) throw reply; return typeof reply === 'function' ? reply(...args) : reply; },
        setTimeout: () => 1, clearTimeout() {},
    });
    vm.runInNewContext(source, {window, document, HTMLFormElement: Form, URL, URLSearchParams, CustomEvent, AbortController});
    const form = new Form(); form.children = [button];
    const submit = () => {
        const event = new Event('submit', {cancelable: true});
        Object.defineProperty(event, 'target', {value: form});
        event.submitter = button;
        document.dispatchEvent(event);
        return event;
    };
    return {window, document, form, button, submit, calls, navigations, config};
}

for (const status of [401, 419]) {
    test(`${status} during a native form check navigates once without sending or replaying the write`, async () => {
        const b = browser({status, ok: false});
        assert.equal(b.submit().defaultPrevented, true);
        await flush();
        assert.equal(b.calls.length, 1);
        assert.equal(b.calls[0][0], '/session/check');
        assert.equal(b.calls[0][1].body.get('_token'), 'old-token');
        assert.equal(b.form.writes, 0);
        assert.equal(b.form.purpose, 'Unsaved work');
        assert.equal(b.form.hasAttribute('aria-busy'), false);
        assert.equal(b.window.htcSession.expired, true);
        assert.equal(b.navigations.length, 1);
        assert.equal(new URL(b.navigations[0]).pathname, '/session/recover');
        assert.equal(new URL(b.navigations[0]).searchParams.get('return'), '/requests?status=pending');
        b.submit();
        b.window.dispatchEvent(new Event('focus'));
        await assert.rejects(b.window.fetch('/notifications'), {name: 'SessionExpiredError'});
        assert.equal(b.navigations.length, 1);
        assert.equal(b.calls.length, 1);
        assert.equal(b.form.writes, 0);
    });
}

test('a valid form check submits exactly once with the confirmed action value', async () => {
    const b = browser();
    let submitted;
    b.document.addEventListener('htc:session-submit', event => submitted = event.detail);
    b.submit();
    assert.equal(b.form.querySelector('[data-session-status]').hidden, true);
    assert.equal(b.form.querySelector('[data-session-status]').textContent, '');
    b.submit();
    await flush();
    assert.equal(b.calls.length, 1);
    assert.equal(b.calls[0][1].body.get('account'), '7');
    assert.equal(b.form.writes, 1);
    assert.equal(b.form.children.find(child => child.type === 'hidden').value, 'approved');
    assert.equal(submitted.form, b.form);
    assert.equal(submitted.submitter, b.button);
    assert.equal(b.window.htcSession.expired, false);
    assert.equal(b.form.querySelector('[data-session-status]').hidden, true);
    assert.equal(b.form.querySelector('[data-session-status]').textContent, '');
});

test('browser Back restores submit controls without leaving duplicate action fields', async () => {
    const b = browser();
    b.submit(); await flush();
    assert.equal(b.button.disabled, true);
    b.window.dispatchEvent(new Event('pageshow'));
    assert.equal(b.button.disabled, false);
    assert.equal(b.form.children.filter(child => child.type === 'hidden').length, 0);
    b.submit(); await flush();
    assert.equal(b.form.writes, 2);
    assert.equal(b.form.children.filter(child => child.type === 'hidden').length, 1);
});

test('a network failure checking the session sends no write and allows an explicit retry', async () => {
    const b = browser(new Error('Offline'));
    b.submit(); await flush();
    assert.equal(b.form.writes, 0);
    assert.equal(b.button.disabled, false);
    assert.match(b.form.children.at(-1).textContent, /not submitted/);
    assert.equal(b.form.children.at(-1).hidden, false);
    b.submit();
    assert.equal(b.form.children.at(-1).hidden, true);
    await flush();
    assert.equal(b.calls.length, 2);
    assert.equal(b.form.writes, 0);
});

test('a followed login redirect triggers recovery before HTML or PDF content is consumed', async () => {
    const b = browser({status: 200, ok: true, redirected: true, url: 'https://app.test/login'});
    await assert.rejects(b.window.fetch('/admin/reports/generate'), {name: 'SessionExpiredError'});
    assert.equal(b.navigations.length, 1);
});

test('first-party expiry handling does not intercept external fetches', async () => {
    const b = browser({status: 401});
    assert.equal((await b.window.fetch('https://external.test/data')).status, 401);
    assert.equal(b.window.htcSession.expired, false);
});

for (const trigger of ['focus', 'visibilitychange', 'pageshow']) {
    test(`returning via ${trigger} checks the session and recovers an expired page`, async () => {
        const b = browser({status: 419});
        const event = new Event(trigger);
        if (trigger === 'pageshow') event.persisted = true;
        (trigger === 'visibilitychange' ? b.document : b.window).dispatchEvent(event);
        await flush();
        assert.equal(b.calls.length, 1);
        assert.equal(b.calls[0][0], '/session/check');
        assert.equal(b.calls[0][1].body.get('_token'), 'old-token');
        assert.equal(b.calls[0][1].body.get('account'), '7');
        assert.equal(b.calls[0][1].cache, 'no-store');
        assert.equal(b.navigations.length, 1);
        assert.equal(b.form.writes, 0);
    });
}

test('an ordinary page load and hiding the tab do not check or refresh the session', async () => {
    const b = browser();
    b.window.dispatchEvent(new Event('pageshow'));
    b.document.hidden = true;
    b.document.dispatchEvent(new Event('visibilitychange'));
    b.window.dispatchEvent(new Event('focus'));
    await flush();
    assert.equal(b.calls.length, 0);
    assert.equal(b.navigations.length, 0);
});

test('overlapping return events share one check and leave an active session on the page', async () => {
    let finish;
    const b = browser(new Promise(resolve => finish = resolve));
    b.document.dispatchEvent(new Event('visibilitychange'));
    b.window.dispatchEvent(new Event('focus'));
    assert.equal(b.calls.length, 1);
    finish({status: 204});
    await flush();
    assert.equal(b.navigations.length, 0);
    assert.equal(b.window.htcSession.expired, false);
    assert.equal(b.form.writes, 0);
});

test('expiry detected in a hidden tab refreshes only once when the user returns', async () => {
    const b = browser({status: 401});
    b.document.hidden = true;
    await assert.rejects(b.window.fetch('/notifications'), {name: 'SessionExpiredError'});
    assert.equal(b.navigations.length, 0);
    b.document.hidden = false;
    b.document.dispatchEvent(new Event('visibilitychange'));
    b.window.dispatchEvent(new Event('focus'));
    assert.equal(b.navigations.length, 1);
    assert.equal(b.calls.length, 1);
});

test('guest forms refresh their current page to obtain a fresh token', async () => {
    const b = browser({status: 419});
    b.config.dataset.account = '';
    b.window.location.href = 'https://app.test/login';
    b.window.dispatchEvent(new Event('focus'));
    await flush();
    assert.equal(b.calls[0][1].body.has('account'), false);
    assert.deepEqual(b.navigations, ['https://app.test/login']);
    assert.equal(b.form.writes, 0);
});

for (const reply of [new Error('Offline'), {status: 500}, {status: 429}]) {
    test(`a return check with ${reply.message || reply.status} allows a later retry without refreshing`, async () => {
        let attempts = 0;
        const b = browser(() => {
            if (++attempts > 1) return {status: 419};
            if (reply instanceof Error) throw reply;
            return reply;
        });
        b.window.dispatchEvent(new Event('focus'));
        await flush();
        assert.equal(b.navigations.length, 0);
        assert.equal(b.window.htcSession.expired, false);
        b.window.dispatchEvent(new Event('focus'));
        await flush();
        assert.equal(b.calls.length, 2);
        assert.equal(b.navigations.length, 1);
    });
}

test('an in-flight success cannot replace work after another request detects expiry', async () => {
    let finish;
    const b = browser(new Promise(resolve => finish = resolve));
    const response = b.window.fetch('/dashboard');
    b.window.htcSession.show();
    finish({status: 200, ok: true});
    await assert.rejects(response, {name: 'SessionExpiredError'});
    assert.equal(b.form.purpose, 'Unsaved work');
});

test('an unexpected successful response from the check endpoint cannot submit a write', async () => {
    const b = browser({status: 200, ok: true});
    b.submit(); await flush();
    assert.equal(b.form.writes, 0);
    assert.match(b.form.children.at(-1).textContent, /not submitted/);
});

test('expiry cancels dynamic search without its navigation fallback', async () => {
    const navigations = [];
    const result = {id: 'results', setAttribute() {}, removeAttribute() {}};
    const status = {};
    const root = {dataset: {}, querySelector: selector => selector === '[data-filter-status]' ? status : result, querySelectorAll: () => []};
    const document = {querySelectorAll: () => [], addEventListener() {}};
    const window = {location: {href: 'https://app.test/requests', origin: 'https://app.test', assign: url => navigations.push(url)},
        addEventListener() {}, clearTimeout, setTimeout,
        fetch: async () => {throw Object.assign(new Error('Expired'), {name: 'SessionExpiredError'});},
    };
    await initializeDynamicSearch(window, document).refresh(root, window.location.href);
    assert.deepEqual(navigations, []);
});

for (const name of ['SessionExpiredError', 'TypeError']) {
    test(`notification write failure (${name}) never falls back to submitting the same form`, async () => {
        const template = read('resources/views/notifications.blade.php');
        const start = template.indexOf("center.addEventListener('submit', ") + "center.addEventListener('submit', ".length;
        const callback = template.slice(start, template.indexOf("window.addEventListener('storage'", start)).replace(/\);\s*$/, '');
        let writes = 0; let alerts = 0;
        const button = {disabled: false, isConnected: true};
        const form = {dataset: {}, action: '/notifications/1/read', submit: () => writes++, querySelector: selector => selector.startsWith('button') ? button : {value: 'old-token'}};
        const center = {querySelector: () => null, prepend: () => alerts++};
        const handle = vm.runInNewContext(`(${callback})`, {center, document: {createElement: () => ({dataset: {}, setAttribute() {}})},
            fetch: async () => {throw Object.assign(new Error('Failed'), {name});},
        });
        await handle({target: {closest: selector => selector === '[data-notification-read-form]' ? form : null}, preventDefault() {}});
        assert.equal(writes, 0);
        assert.equal(alerts, name === 'SessionExpiredError' ? 0 : 1);
        assert.equal(button.disabled, false);
    });
}
