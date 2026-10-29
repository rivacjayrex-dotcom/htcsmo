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
    vm.runInNewContext(source, {document, window});
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
