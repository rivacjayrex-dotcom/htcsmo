import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/inventory/partials/combination-builder-script.blade.php', import.meta.url), 'utf8').replace(/<\/?script>/g, '');
const builder = () => {
    const engine = vm.runInNewContext(`${source}; inventoryVariantBuilder(['Brand', 'Color'], [])`);
    engine.init();
    return engine;
};

test('combination copies keep their own prices, quantities and attribute values', () => {
    const engine = builder();
    assert.equal(engine.enabled, false);
    engine.combinations[0].values = ['HTC', 'Black'];
    engine.combinations[0].unit_price = 450;
    engine.combinations[0].quantity = 20;
    engine.copyCombination(0);
    engine.combinations[1].values[1] = 'Vintage';
    engine.combinations[1].unit_price = 650;
    engine.combinations[1].quantity = 10;
    assert.equal(engine.combinations[0].values[1], 'Black');
    assert.equal(engine.combinations[0].unit_price, 450);
    assert.equal(engine.combinations[0].quantity, 20);
    assert.notEqual(engine.combinations[0].key, engine.combinations[1].key);
});

test('attribute column changes keep every combination aligned and preserve Brand', () => {
    const engine = builder();
    engine.combinations[0].values = ['HTC', 'Black'];
    engine.copyCombination(0);
    engine.addColumn();
    engine.columns[2] = 'Quality';
    engine.combinations[0].values[2] = 'Standard';
    engine.combinations[1].values[2] = 'Premium';
    engine.removeColumn(1);
    engine.removeColumn(0);
    assert.deepEqual(Array.from(engine.columns), ['Brand', 'Quality']);
    assert.deepEqual(Array.from(engine.combinations[0].values), ['HTC', 'Standard']);
    assert.deepEqual(Array.from(engine.combinations[1].values), ['HTC', 'Premium']);
    engine.combinations.splice(0, 1);
    engine.addCombination();
    assert.notEqual(engine.combinations[0].key, engine.combinations[1].key);
    assert.equal(engine.combinations[1].values.length, 2);
});
