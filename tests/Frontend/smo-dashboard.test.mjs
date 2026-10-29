import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const template = readFileSync(new URL('../../resources/views/dashboard/partials/smo-tabs.blade.php', import.meta.url), 'utf8');
const source = template.slice(template.indexOf('<script>') + 8, template.lastIndexOf('</script>'));

function browser() {
    const document = new EventTarget();
    const window = Object.assign(new EventTarget(), {
        location: {href: 'https://supply.test/dashboard'},
        history: {state: {workspace: true}, replaceState(state, _, href) { this.state = state; window.location.href = href; }},
    });
    let tabs, panels;
    const workspace = {querySelectorAll: () => tabs};
    function render(selected = 'recent') {
        panels = new Map();
        tabs = ['recent', 'deadlines', 'inventory'].map(key => {
            panels.set(`smo-panel-${key}`, {hidden: key !== selected});
            return {
                dataset: {smoTab: key}, href: `https://supply.test/dashboard?tab=${key}`, tabIndex: key === selected ? 0 : -1,
                attributes: {'aria-controls': `smo-panel-${key}`, 'aria-selected': String(key === selected)},
                getAttribute(name) { return this.attributes[name]; },
                setAttribute(name, value) { this.attributes[name] = value; },
                closest: () => workspace,
                focus() { document.activeElement = this; },
            };
        });
    }
    render();
    document.getElementById = id => panels.get(id);
    document.querySelectorAll = () => tabs;
    vm.runInNewContext(source, {window, document, URL});
    return {
        window, document, get tabs() { return tabs; }, get panels() { return panels; },
        event(type, index, properties = {}) {
            const event = new Event(type, {cancelable: true});
            Object.assign(event, {button: 0, ...properties});
            Object.defineProperty(event, 'target', {value: {closest: () => tabs[index]}});
            document.dispatchEvent(event);
            return event;
        },
        refresh() { render(); document.dispatchEvent(new Event('htc:workspace-updated')); },
    };
}

test('clicking a card switches exactly one panel and persists its URL without navigation', () => {
    const b = browser();
    assert.equal(b.event('click', 1).defaultPrevented, true);
    assert.equal(b.window.location.href, 'https://supply.test/dashboard?tab=deadlines');
    assert.deepEqual(b.tabs.map(tab => tab.attributes['aria-selected']), ['false', 'true', 'false']);
    assert.deepEqual([...b.panels.values()].map(panel => panel.hidden), [true, false, true]);
    assert.deepEqual(b.tabs.map(tab => tab.tabIndex), [-1, 0, -1]);
    assert.equal(b.window.history.state.workspace, true);
});

test('arrow keys wrap and Home, End, and Space select the focused card', () => {
    const b = browser();
    b.event('keydown', 0, {key: 'ArrowLeft'});
    assert.equal(b.document.activeElement, b.tabs[2]);
    b.event('keydown', 2, {key: 'ArrowRight'});
    assert.equal(b.document.activeElement, b.tabs[0]);
    b.event('keydown', 0, {key: 'End'});
    assert.equal(b.document.activeElement, b.tabs[2]);
    b.event('keydown', 2, {key: 'Home'});
    assert.equal(b.document.activeElement, b.tabs[0]);
    assert.equal(b.event('keydown', 1, {key: ' '}).defaultPrevented, true);
    assert.equal(b.tabs[1].attributes['aria-selected'], 'true');
});

test('live refresh restores the selected tab on newly rendered elements', () => {
    const b = browser();
    b.event('click', 2);
    const previous = b.tabs[2];
    b.refresh();
    assert.notEqual(b.tabs[2], previous);
    assert.equal(b.tabs[2].attributes['aria-selected'], 'true');
    assert.equal(b.panels.get('smo-panel-inventory').hidden, false);
});

test('modified clicks keep normal link behavior and invalid history falls back to recent', () => {
    const b = browser();
    assert.equal(b.event('click', 1, {ctrlKey: true}).defaultPrevented, false);
    assert.equal(b.event('click', 1, {button: 1}).defaultPrevented, false);
    b.window.location.href = 'https://supply.test/dashboard?tab=unknown';
    b.window.dispatchEvent(new Event('popstate'));
    assert.equal(b.tabs[0].attributes['aria-selected'], 'true');
    assert.equal(b.panels.get('smo-panel-recent').hidden, false);
});
