import assert from 'node:assert/strict';
import {readFileSync, readdirSync} from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import test from 'node:test';
import vm from 'node:vm';
import * as lucide from 'lucide';

const root = new URL('../../', import.meta.url);
const source = readFileSync(new URL('resources/js/icons.js', root), 'utf8')
    .replace(/import\s*\{[\s\S]*?\}\s*from 'lucide';/, '');
const views = directory => readdirSync(directory, {withFileTypes: true}).flatMap(entry =>
    entry.isDirectory() ? views(path.join(directory, entry.name)) : [path.join(directory, entry.name)]);

test('local icons render page placeholders and icons introduced by live updates', () => {
    const names = new Set(['clock-3', 'message-circle-question', 'circle-minus', 'user-cog', 'check-circle', 'alert-triangle']);
    for (const file of views(fileURLToPath(new URL('resources/views', root)))) {
        if (!file.endsWith('.blade.php')) continue;
        for (const [, name] of readFileSync(file, 'utf8').matchAll(/data-lucide="([a-z0-9-]+)"/g)) names.add(name);
    }

    const element = (tag, attrs = {}) => ({
        tag,
        attributes: Object.entries(attrs).map(([name, value]) => ({name, value})),
        children: [],
        getAttribute(name) { return this.attributes.find(attr => attr.name === name)?.value ?? null; },
        setAttribute(name, value) {
            const attr = this.attributes.find(attr => attr.name === name);
            if (attr) attr.value = value;
            else this.attributes.push({name, value});
        },
        appendChild(child) { this.children.push(child); },
    });
    let placeholders = [];
    const rendered = [];
    const addPlaceholder = name => {
        const placeholder = element('i', {'data-lucide': name, class: 'me-2', 'aria-label': 'Request action'});
        placeholder.parentNode = {replaceChild(svg) { rendered.push(svg); }};
        return placeholder;
    };
    const document = {
        createElementNS: (namespace, tag) => element(tag),
        querySelectorAll: selector => selector === '[data-lucide]' ? placeholders : [],
    };
    const previousDocument = globalThis.document;
    globalThis.document = document;

    try {
        placeholders = [...names].map(addPlaceholder);
        const window = {};
        vm.runInNewContext(source, {...lucide, window, document});
        assert.equal(rendered.length, names.size, 'Every static page icon must be in the local bundle');

        // Existing Alpine and workspace code calls the same API after replacement.
        placeholders = [addPlaceholder('bell-ring')];
        window.lucide.createIcons();
        assert.equal(rendered.length, names.size + 1);
        for (const svg of rendered) {
            assert.equal(svg.tag, 'svg');
            assert.ok(svg.children.length > 0);
            assert.equal(svg.getAttribute('aria-label'), 'Request action');
            assert.match(svg.getAttribute('class'), /me-2/);
        }
    } finally {
        if (previousDocument === undefined) delete globalThis.document;
        else globalThis.document = previousDocument;
    }
});
