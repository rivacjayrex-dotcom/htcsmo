import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const template = readFileSync(new URL('../../resources/views/notifications.blade.php', import.meta.url), 'utf8');
const source = template.slice(template.indexOf('<script>') + 8, template.lastIndexOf('</script>'));
const flush = async () => { for (let n = 0; n < 6; n++) await Promise.resolve(); };

function browser() {
    let feed = {scrollTop: 325};
    let tabs = {scrollLeft: 130};
    const unread = {textContent: '3'};
    const markAll = {hidden: false};
    const center = Object.assign(new EventTarget(), {
        dataset: {notificationAccount: '7'},
        querySelectorAll: () => [unread],
        querySelector: selector => ({
            '[data-notification-feed]': feed,
            '.notification-category-nav': tabs,
            '[data-mark-all-read-form]': markAll,
        })[selector] || null,
    });
    const document = Object.assign(new EventTarget(), {querySelector: () => center, getElementById: () => null});
    const refreshes = [];
    const window = Object.assign(new EventTarget(), {
        location: {href: 'https://supply.test/notifications?category=inventory'},
        htcFilters: {async refresh(root, url, options) {
            refreshes.push({root, url, options});
            feed = {scrollTop: 0}; tabs = {scrollLeft: 0};
        }},
    });
    vm.runInNewContext(source, {document, window, CustomEvent});
    return {
        document, window, refreshes, unread, markAll,
        get feed() { return feed; }, get tabs() { return tabs; },
        runtime(attributes = {}) {
            const event = new Event('htc:runtime-state');
            event.detail = {account_id: 7, latest_id: 5, unread_count: 3, notifications: [], versions: {dashboard: 'v1'}, ...attributes};
            document.dispatchEvent(event);
        },
    };
}

test('current warning changes refresh the feed without new notification records and preserve both scroll positions', async () => {
    const b = browser();
    b.runtime();
    assert.equal(b.refreshes.length, 0);
    b.runtime({versions: {dashboard: 'v2'}});
    await flush();
    assert.equal(b.refreshes.length, 1);
    assert.equal(b.feed.scrollTop, 325);
    assert.equal(b.tabs.scrollLeft, 130);
    assert.equal(b.refreshes[0].url, b.window.location.href);
    assert.equal(b.refreshes[0].options.history, 'none');
    assert.equal(b.refreshes[0].options.orient, false);
    b.runtime({versions: {dashboard: 'v2'}});
    assert.equal(b.refreshes.length, 1);
});

test('other accounts cannot trigger a feed refresh or change unread state', () => {
    const b = browser();
    b.runtime({account_id: 8, unread_count: 99, notifications: [{id: 6}]});
    assert.equal(b.refreshes.length, 0);
    assert.equal(b.unread.textContent, '3');
});

test('read-state changes refresh the current category even when no unread payload is returned', async () => {
    const b = browser();
    b.runtime();
    b.runtime({unread_count: 0});
    await flush();
    assert.equal(b.refreshes.length, 1);
    assert.equal(b.markAll.hidden, true);
    assert.equal(b.unread.textContent, 0);
    assert.equal(b.feed.scrollTop, 325);
});

function feedBrowser() {
    class Element extends EventTarget {
        dataset = {}; attributes = new Map(); textContent = ''; innerHTML = ''; scrollTop = 0;
        hidden = false; removed = false;
        classes = new Set();
        classList = {
            toggle: (name, active) => active ? this.classes.add(name) : this.classes.delete(name),
            contains: name => this.classes.has(name),
        };
        setAttribute(name, value) { this.attributes.set(name, value); }
        removeAttribute(name) { this.attributes.delete(name); }
        remove() { this.removed = true; }
    }
    const feed = Object.assign(new Element(), {innerHTML: 'Original feed', scrollTop: 325});
    const nav = {scrollLeft: 120};
    const title = {textContent: 'All activity'};
    const unread = {textContent: '3'};
    const markAll = {hidden: false};
    const tabs = ['all', 'approvals', 'deadlines', 'accounts', 'inventory'].map(category => {
        const tab = new Element();
        tab.dataset = {notificationCategory: category, categoryLabel: category};
        tab.count = {textContent: '1'};
        tab.querySelector = () => tab.count;
        tab.classList.toggle('is-active', category === 'all');
        return tab;
    });
    const center = Object.assign(new Element(), {dataset: {notificationAccount: '7'}});
    const errors = [];
    center.prepend = element => errors.push(element);
    center.querySelectorAll = selector => selector === '[data-notification-category]' ? tabs : [unread];
    center.querySelector = selector => ({
        '[data-notification-feed]': feed,
        '.notification-category-nav': nav,
        '[data-current-category]': title,
        '[data-mark-all-read-form]': markAll,
        '.notification-category-link.is-active': tabs.find(tab => tab.classList.contains('is-active')),
        '[data-notification-feed-error]': errors.find(error => !error.removed),
    })[selector] || null;
    const document = Object.assign(new EventTarget(), {
        querySelector: () => center, getElementById: () => null, createElement: () => new Element(),
    });
    const requests = [];
    const pushes = [];
    const window = Object.assign(new EventTarget(), {
        location: {href: 'https://supply.test/notifications'},
        history: {pushState(state, label, url) { pushes.push(url); window.location.href = url; }},
        htcSession: {expired: false}, setTimeout: () => 1, clearTimeout() {},
        fetch: (url, options) => new Promise(resolve => requests.push({url, options, resolve})),
        htcFilters: {refresh: (root, url, options) => root.refreshNotifications(url, options)},
    });
    vm.runInNewContext(source, {document, window, URL, AbortController, CustomEvent});
    return {
        center, document, window, feed, nav, title, tabs, requests, pushes, errors,
        refresh: (category, options) => center.refreshNotifications(`https://supply.test/notifications?category=${category}`, options),
        respond(index, category, html = category) {
            requests[index].resolve({ok: true, json: async () => ({
                html, category, unread_count: 3,
                category_counts: {all: 10, approvals: 4, deadlines: 3, accounts: 2, inventory: 1},
            })});
        },
        runtime(version) {
            const event = new Event('htc:runtime-state');
            event.detail = {account_id: 7, latest_id: 5, unread_count: 3, notifications: [], versions: {dashboard: version}};
            document.dispatchEvent(event);
        },
    };
}

test('notification tabs select immediately and update only the feed using JSON', async () => {
    const b = feedBrowser();
    const pending = b.refresh('approvals');
    assert.equal(b.tabs[1].classList.contains('is-active'), true);
    assert.equal(b.title.textContent, 'approvals');
    assert.equal(b.feed.innerHTML, 'Original feed');
    assert.equal(b.center.attributes.has('aria-busy'), false);
    assert.equal(b.requests[0].options.headers.Accept, 'application/json');
    b.respond(0, 'approvals');
    assert.equal(await pending, true);
    assert.equal(b.feed.innerHTML, 'approvals');
    assert.equal(b.feed.scrollTop, 0);
    assert.equal(b.nav.scrollLeft, 120);
    assert.equal(b.tabs[1].count.textContent, 4);
    assert.equal(b.feed.attributes.has('aria-busy'), false);
    assert.equal(b.pushes.length, 1);
});

test('recently visited categories switch from memory without another request', async () => {
    const b = feedBrowser();
    const approvals = b.refresh('approvals'); b.respond(0, 'approvals'); await approvals;
    const accounts = b.refresh('accounts'); b.respond(1, 'accounts'); await accounts;
    const cached = b.refresh('approvals');
    assert.equal(b.feed.innerHTML, 'approvals');
    assert.equal(b.requests.length, 2);
    assert.equal(await cached, true);
});

test('a slower response cannot overwrite the most recently selected category', async () => {
    const b = feedBrowser();
    const first = b.refresh('approvals');
    const latest = b.refresh('deadlines');
    assert.equal(b.requests[0].options.signal.aborted, true);
    b.respond(1, 'deadlines'); await latest;
    b.respond(0, 'approvals'); await first;
    assert.equal(b.feed.innerHTML, 'deadlines');
    assert.equal(b.tabs[2].classList.contains('is-active'), true);
    assert.equal(b.pushes.length, 1);
});

test('live updates queued during a tab switch refresh that category and invalidate older cached feeds', async () => {
    const b = feedBrowser();
    const first = b.refresh('approvals'); b.respond(0, 'approvals'); await first;
    b.runtime('v1');
    const next = b.refresh('accounts');
    b.runtime('v2');
    assert.equal(b.requests.length, 2);
    b.respond(1, 'accounts'); await next;
    assert.equal(b.requests.length, 3);
    assert.equal(b.requests[2].url.searchParams.get('category'), 'accounts');
    b.feed.scrollTop = 210;
    b.respond(2, 'accounts', 'Live accounts'); await flush();
    assert.equal(b.feed.innerHTML, 'Live accounts');
    assert.equal(b.feed.scrollTop, 210);
    const revisit = b.refresh('approvals');
    assert.equal(b.requests.length, 4);
    b.respond(3, 'approvals', 'Fresh approvals'); await revisit;
    assert.equal(b.feed.innerHTML, 'Fresh approvals');
});

test('failed tab requests retain the previous feed and restore its selected tab', async () => {
    const b = feedBrowser();
    const pending = b.refresh('approvals');
    b.requests[0].resolve({ok: false, status: 500});
    assert.equal(await pending, false);
    assert.equal(b.feed.innerHTML, 'Original feed');
    assert.equal(b.tabs[0].classList.contains('is-active'), true);
    assert.match(b.errors[0].textContent, /could not be updated/);
});

test('session expiry cancels a tab request without replacing the current feed', async () => {
    const b = feedBrowser();
    const pending = b.refresh('approvals');
    b.window.htcSession.expired = true;
    b.document.dispatchEvent(new Event('htc:session-expired'));
    assert.equal(b.requests[0].options.signal.aborted, true);
    b.respond(0, 'approvals');
    assert.equal(await pending, false);
    assert.equal(b.feed.innerHTML, 'Original feed');
    assert.equal(b.feed.attributes.has('aria-busy'), false);
});
