import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/inventory/partials/product-editor-script.blade.php', import.meta.url), 'utf8').replace(/<\/?script>/g, '');
const createEditor = vm.runInNewContext(`${source}; inventoryProductEditor`);
const initial = () => [
    { id: 1, version: 'version-1', locked: true, size: 'A4', supplier: 'Supplier A', unit: 'Ream(s)', unit_price: '190.00', quantity: 12, min_stock_level: 5, options: [{ name: 'Brand', values: 'Hard Copy' }] },
    { id: 2, version: 'version-2', locked: false, size: 'Legal', supplier: 'Supplier A', unit: 'Ream(s)', unit_price: '210.00', quantity: 8, min_stock_level: 5, options: [{ name: 'Brand', values: 'Double A' }] },
];

test('one product form holds independent prices and stock for all existing variants', () => {
    const originals = initial();
    const editor = createEditor(originals, []);
    editor.rows[0].unit_price = '200.00';
    editor.rows[1].quantity = 10;
    assert.equal(editor.rows.length, 2);
    assert.equal(editor.rows[1].unit_price, '210.00');
    assert.equal(editor.rows[0].quantity, 12);
    assert.equal(originals[0].unit_price, '190.00');
    assert.equal(editor.rows[0].version, 'version-1');
    editor.removeNewVariant(0);
    assert.equal(editor.rows.length, 2, 'Existing variants cannot disappear from a product save.');
});

test('copying creates a separate new variant without copying the stock quantity or request link', () => {
    const editor = createEditor(initial(), []);
    editor.copyVariant(0);
    const copy = editor.rows[2];
    assert.equal(copy.id, null);
    assert.equal(copy.version, '');
    assert.equal(copy.locked, false);
    assert.equal(copy.quantity, 0);
    assert.equal(copy.unit_price, '190.00');
    copy.options[0].values = 'PaperOne';
    assert.equal(editor.rows[0].options[0].values, 'Hard Copy');
    editor.removeNewVariant(2);
    editor.addVariant();
    assert.notEqual(editor.rows[2].key, copy.key);
    assert.equal(editor.rows[2].unit, 'Ream(s)');
});

test('a validation return restores entered values and keeps authoritative identity locks', () => {
    const previous = initial();
    previous[0].unit_price = '205.50';
    previous[0].locked = false;
    const editor = createEditor(initial(), previous);
    assert.equal(editor.rows[0].unit_price, '205.50');
    assert.equal(editor.rows[0].locked, true);
    assert.equal(editor.rows[0].detailsOpen, true);
});
