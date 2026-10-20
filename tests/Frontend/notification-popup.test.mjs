import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const layout = readFileSync(new URL('../../resources/views/layouts/app.blade.php', import.meta.url), 'utf8');
const centerSource = layout.slice(layout.indexOf('function notificationCenter()'), layout.indexOf('// Cart Engine'))
    .replaceAll('{{ Auth::id() }}', '7')
    .replace('{{ (int) Auth::id() }}', '7')
    .replace('{{ (int) ($layoutLatestNotificationId ?? 0) }}', '42')
    .replace("@json(route('notifications'))", "'/notifications'");
const runtimeSource = layout.slice(layout.indexOf('// Keep every signed-in workspace'), layout.indexOf('function countUp('))
    .replace("@json(route('api.runtime-state'))", "'/api/runtime-state'")
    .replaceAll('{{ Auth::id() }}', '7')
    .replace('{{ (int) Auth::id() }}', '7');

function browser({ cursor, bufferedState, storageDisabled = false, audioContextClass } = {}) {
    const values = new Map();
    if (cursor !== undefined) values.set('htc-presented-notification-7', String(cursor));
    const storage = {
        getItem: key => {
            if (storageDisabled) throw new Error('Storage disabled');
            return values.get(key) ?? null;
        },
        setItem: (key, value) => {
            if (storageDisabled) throw new Error('Storage disabled');
            values.set(key, String(value));
        },
    };
    const timers = new Map();
    let nextTimer = 0;
    const badge = {};
    const document = Object.assign(new EventTarget(), {
        hidden: false,
        activeElement: null,
        getElementById: id => id === 'notificationUnreadCount' ? badge : {},
        querySelector: () => null,
    });
    const window = Object.assign(new EventTarget(), {
        setTimeout(callback, delay) {
            const id = ++nextTimer;
            timers.set(id, { callback, delay });
            return id;
        },
        clearTimeout: id => timers.delete(id),
        location: { reload() { window.reloads++; } },
        reloads: 0,
        htcRuntimeState: bufferedState,
        AudioContext: audioContextClass,
    });
    const context = vm.createContext({
        window, document, localStorage: storage, sessionStorage: storage,
        CustomEvent, AbortController, Date, console,
        lucide: { createIcons() {} },
    });
    window.lucide = context.lucide;
    const center = vm.runInContext(`(${centerSource})()`, context);
    center.$nextTick = callback => callback();
    center.init();
    const payload = (...ids) => ({
        account_id: 7, unread_count: ids.length,
        notifications: ids.map(id => ({ id, title: `Notice ${id}`, message: 'Deadline reminder', type: 'warning' })),
    });
    async function runTimer(delay) {
        const entry = [...timers].find(([, timer]) => timer.delay === delay);
        assert.ok(entry, `Expected a timer for ${delay}ms`);
        timers.delete(entry[0]);
        await entry[1].callback();
    }
    return { center, context, document, window, storage, payload, runTimer, timers };
}

test('an unread reminder appears when the recipient first opens a page', () => {
    const { center, payload } = browser();
    center.consumeRuntimeState(payload(42));
    assert.equal(center.show, true);
    assert.equal(center.toasts[0].title, 'Notice 42');
});

test('navigating before the next poll does not skip a new reminder', () => {
    const { center, payload } = browser({ cursor: 41 });
    center.consumeRuntimeState(payload(42));
    assert.equal(center.show, true);
    assert.equal(center.lastId, 42);
});

test('queued reminders remain eligible until their popup is displayed', () => {
    const { center, payload } = browser();
    center.consumeRuntimeState(payload(43, 44, 45, 46, 47));
    assert.equal(center.toasts.length, 3);
    assert.equal(center.toasts[0].title, 'Notice 43');
    assert.equal(center.lastId, 45);
    assert.equal(center.queue[0].id, 46);
});

test('a hidden tab does not consume popups meant for a visible tab', () => {
    const { center, document, payload } = browser();
    document.hidden = true;
    center.consumeRuntimeState(payload(43));
    assert.equal(center.show, false);
    assert.ok(center.lastId < 43);
    document.hidden = false;
    center.consumeRuntimeState(payload(43));
    assert.equal(center.show, true);
});

test('duplicate polls do not enqueue the same reminder twice', () => {
    const { center, payload } = browser();
    center.consumeRuntimeState(payload(43, 44, 45, 46));
    center.consumeRuntimeState(payload(43, 44, 45, 46));
    assert.equal(center.toasts.length, 3);
    assert.equal(center.queue.length, 1);
    assert.equal(center.queue[0].id, 46);
});

test('the next queued reminder appears after dismissal', async () => {
    const { center, payload, runTimer } = browser();
    center.consumeRuntimeState(payload(43, 44, 45, 46));
    center.dismiss(44);
    assert.equal(center.toasts.find(toast => toast.id === 43).visible, true);
    assert.equal(center.toasts.length, 3);
    await runTimer(300);
    assert.equal(center.show, true);
    assert.equal(center.toasts.length, 3);
    assert.equal(center.toasts[2].title, 'Notice 46');
    assert.equal(center.lastId, 46);
    assert.equal(center.queue.length, 0);
});

test('a runtime response received before Alpine initializes is still displayed', () => {
    const { center } = browser({ bufferedState: {
        account_id: 7, unread_count: 1, notifications: [{ id: 42, title: 'Early reminder' }],
    } });
    assert.equal(center.show, true);
    assert.equal(center.toasts[0].title, 'Early reminder');
});

test('popups still work with browser storage disabled and icons unavailable', () => {
    const { center, window, payload } = browser({ storageDisabled: true });
    delete window.lucide;
    center.consumeRuntimeState(payload(42));
    assert.equal(center.show, true);
    assert.equal(center.toasts[0].title, 'Notice 42');
});

test('a reminder already displayed is not replayed after navigation', () => {
    const { center, payload } = browser({ cursor: 42 });
    center.consumeRuntimeState(payload(42));
    assert.equal(center.show, false);
});

test('notifications from another account are ignored', () => {
    const { center, payload } = browser();
    center.consumeRuntimeState({ ...payload(43), account_id: 8 });
    assert.equal(center.show, false);
});

test('automatic refresh waits until the entire popup stack is dismissed', async () => {
    const fixture = browser();
    const { center, context, document, window, payload, runTimer } = fixture;
    let version = 'before';
    context.fetch = async () => ({ ok: true, json: async () => ({ ...payload(...(version === 'before' ? [] : [43, 44, 45])), version }) });
    vm.runInContext(runtimeSource, context);
    await runTimer(0);
    version = 'after';
    await runTimer(15000);
    assert.equal(center.show, true);
    assert.equal([...fixture.timers.values()].some(timer => timer.delay === 500), false);
    center.dismiss(43);
    await runTimer(300);
    assert.equal([...fixture.timers.values()].some(timer => timer.delay === 500), false);
    center.dismiss(44);
    await runTimer(300);
    assert.equal([...fixture.timers.values()].some(timer => timer.delay === 500), false);
    center.dismiss(45);
    await runTimer(300);
    await runTimer(500);
    assert.equal(window.reloads, 1);
});

test('each popup expires independently and frees a slot for the queue', async () => {
    const { center, payload, runTimer } = browser();
    center.consumeRuntimeState(payload(43, 44, 45, 46));
    await runTimer(9000);
    assert.equal(center.toasts[0].visible, false);
    assert.equal(center.toasts[1].visible, true);
    await runTimer(300);
    assert.equal(center.toasts.length, 3);
    assert.equal(center.toasts[2].id, 46);
});

function audioFixture({ suspended = false } = {}) {
    const notes = [];
    const pendingResumes = [];
    let audioAllowed = !suspended;
    class AudioContext {
        state = suspended ? 'suspended' : 'running';
        currentTime = 0;
        destination = {};
        resume() {
            return new Promise(resolve => {
                const resume = () => { this.state = 'running'; resolve(); };
                if (audioAllowed) resume();
                else pendingResumes.push(resume);
            });
        }
        createOscillator() {
            const oscillator = {
                frequency: {},
                connect: gain => gain,
                start: () => notes.push(oscillator.frequency.value),
                stop() {},
            };
            return oscillator;
        }
        createGain() {
            return {
                gain: { setValueAtTime() {}, exponentialRampToValueAtTime() {} },
                connect() {},
            };
        }
    }
    return {
        ...browser({ audioContextClass: AudioContext }), notes,
        allowAudio() {
            audioAllowed = true;
            pendingResumes.splice(0).forEach(resume => resume());
        },
    };
}

test('new stacked popups play one audible chime per burst', async () => {
    const { center, payload, notes } = audioFixture();
    center.consumeRuntimeState(payload(43, 44, 45));
    await new Promise(setImmediate);
    assert.equal(notes.length, 2);
    assert.ok(notes.every(frequency => frequency > 0));
});

test('the sound toggle mutes arrivals and remembers the preference', async () => {
    const { center, payload, storage, notes } = audioFixture();
    center.toggleSound();
    center.consumeRuntimeState(payload(43));
    await new Promise(setImmediate);
    assert.equal(notes.length, 0);
    assert.equal(storage.getItem('htc-notification-sound'), 'off');
    center.toggleSound();
    await new Promise(setImmediate);
    assert.equal(notes.length, 2);
    assert.equal(storage.getItem('htc-notification-sound'), 'on');
});

test('a browser-blocked chime plays once after the user interacts', async () => {
    const { center, payload, notes, document, allowAudio } = audioFixture({ suspended: true });
    center.consumeRuntimeState(payload(43, 44, 45));
    await new Promise(setImmediate);
    assert.equal(notes.length, 0);
    allowAudio();
    document.dispatchEvent(new Event('pointerdown'));
    await new Promise(setImmediate);
    assert.equal(notes.length, 2);
});

test('dismissing a popup before audio is unlocked cancels its delayed chime', async () => {
    const { center, payload, notes, document, allowAudio } = audioFixture({ suspended: true });
    center.consumeRuntimeState(payload(43));
    center.dismiss(43);
    allowAudio();
    document.dispatchEvent(new Event('pointerdown'));
    await new Promise(setImmediate);
    assert.equal(notes.length, 0);
});
