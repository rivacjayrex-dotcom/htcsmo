import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const layout = readFileSync(new URL('../../resources/views/layouts/app.blade.php', import.meta.url), 'utf8');
const source = layout.slice(layout.indexOf('function cartSystem()'), layout.indexOf('const requisitionModal ='))
    .replace(/const previousRequest = [\s\S]*?;\r?\n/, 'const previousRequest = {};\n')
    .replace("@json(route('api.requisition-catalog'))", "'/api/requisition-catalog'");

function cart({ saved, blocked = false, confirm = async () => false } = {}) {
    const values = new Map(saved ? [['htc-requisition-layout', saved]] : []);
    const storage = {
        getItem(key) { if (blocked) throw new Error('Storage unavailable'); return values.get(key) ?? null; },
        setItem(key, value) { if (blocked) throw new Error('Storage unavailable'); values.set(key, value); },
    };
    const decisions = [];
    let hides = 0;
    const modal = {};
    const window = {
        clearTimeout() {},
        setTimeout() { return 1; },
        matchMedia() { return { matches: false }; },
        async htcConfirm(options) { decisions.push(options); return confirm(options); },
        bootstrap: { Modal: { getOrCreateInstance(element) {
            assert.equal(element, modal);
            return { hide() { hides++; } };
        } } },
    };
    const engine = vm.runInNewContext(`(${source})()`, { localStorage: storage, window });
    engine.$refs = {};
    engine.$nextTick = callback => callback();
    engine.init();
    const closeEvent = () => ({
        currentTarget: modal,
        defaultPrevented: false,
        preventDefault() { this.defaultPrevented = true; },
    });
    return { engine, values, decisions, closeEvent, get hides() { return hides; } };
}

test('catalog starts in grid mode and restores a saved list preference', () => {
    assert.equal(cart().engine.catalogLayout, 'grid');
    assert.equal(cart({ saved: 'list' }).engine.catalogLayout, 'list');
    assert.equal(cart({ saved: 'invalid' }).engine.catalogLayout, 'grid');
});

test('size and supplier selection uses separate prices, quantities and cart lines', () => {
    const { engine } = cart();
    engine.refreshIcons = () => {};
    engine.catalog = [
        { id: 1, name: 'Bond Paper', size: 'A4', supplier: 'Supplier A', category: 'Office', price: 200, stock: 3, options: {} },
        { id: 2, name: 'Bond Paper', size: 'Letter', supplier: 'Supplier B', category: 'Office', price: 180, stock: 5, options: {} },
    ];
    engine.openProduct(engine.catalog[0]);
    assert.equal(engine.productVariants.length, 2);
    engine.productQty = 2;
    engine.addConfiguredItem();
    engine.openProduct(engine.catalog[1]);
    engine.addConfiguredItem();
    assert.equal(engine.cart.length, 2);
    assert.equal(engine.grandTotal, 580);
    assert.deepEqual(Array.from(engine.cart, item => [item.id, item.size, item.supplier, item.qty]), [
        [1, 'A4', 'Supplier A', 2], [2, 'Letter', 'Supplier B', 1],
    ]);
    engine.search = 'letter';
    assert.deepEqual(Array.from(engine.filteredItems, item => item.id), [2]);
    engine.search = 'supplier a';
    assert.deepEqual(Array.from(engine.filteredItems, item => item.id), [1]);
    engine.openProduct(engine.catalog[0]);
    assert.equal(engine.remainingStock(engine.selectedItem), 1);
    engine.productQty = 2;
    assert.equal(engine.canAddProduct, false);
    engine.addConfiguredItem();
    assert.equal(engine.cart[0].qty, 2);
    engine.productQty = 1;
    engine.addConfiguredItem();
    assert.equal(engine.cart[0].qty, 3);
    engine.openProduct(engine.catalog[0]);
    assert.equal(engine.canAddProduct, false);
});

test('different options share the stock limit within one size and supplier', () => {
    const { engine } = cart();
    engine.refreshIcons = () => {};
    const item = { id: 1, name: 'Paper', size: 'A4', supplier: 'Supplier A', stock: 3, options: { Brand: ['One', 'Two'] } };
    engine.openProduct(item);
    engine.selectedOptions = { Brand: 'One' };
    engine.productQty = 2;
    engine.addConfiguredItem();
    engine.openProduct(item);
    engine.selectedOptions = { Brand: 'Two' };
    engine.productQty = 2;
    assert.equal(engine.canAddProduct, false);
    engine.productQty = 1.5;
    assert.equal(engine.canAddProduct, false);
    engine.productQty = 1;
    assert.equal(engine.canAddProduct, true);
    engine.addConfiguredItem();
    assert.equal(engine.remainingStock(item), 0);
});

test('style and size buttons resolve real combinations and update price, attributes and stock together', () => {
    const { engine } = cart();
    engine.refreshIcons = () => {};
    engine.catalog = [
        { id: 1, name: 'Pants', category: 'Clothing', size: 'Large', supplier: 'A', price: 450, stock: 20, attributes: { Brand: 'HTC', Style: 'Black', Quality: 'Standard' }, options: { Brand: ['HTC'], Style: ['Black'], Quality: ['Standard'] } },
        { id: 2, name: 'Pants', category: 'Clothing', size: 'Large', supplier: 'A', price: 650, stock: 10, attributes: { Brand: 'HTC', Style: 'Vintage', Quality: 'Premium' }, options: { Brand: ['HTC'], Style: ['Vintage'], Quality: ['Premium'] } },
        { id: 3, name: 'Pants', category: 'Clothing', size: 'Medium', supplier: 'A', price: 600, stock: 15, attributes: { Brand: 'HTC', Style: 'Vintage', Quality: 'Premium' }, options: { Brand: ['HTC'], Style: ['Vintage'], Quality: ['Premium'] } },
    ];
    engine.openProduct(engine.catalog[0]);
    engine.chooseVariant('Style', 'Vintage');
    assert.equal(engine.selectedItem.id, 2);
    assert.equal(engine.selectedItem.price, 650);
    assert.equal(engine.selectedOptions.Quality, 'Premium');
    assert.deepEqual(Array.from(engine.variantDimensions.find(dimension => dimension.name === 'Size').values), ['Large', 'Medium']);
    assert.deepEqual(Object.keys(engine.customizationOptions), []);
    engine.addConfiguredItem();
    engine.openProduct(engine.catalog[1]);
    engine.chooseVariant('Size', 'Medium');
    assert.equal(engine.selectedItem.id, 3);
    assert.equal(engine.selectedItem.price, 600);
    engine.chooseVariant('Style', 'Black');
    assert.equal(engine.selectedItem.id, 1);
    assert.equal(engine.selectedItem.size, 'Large');
    assert.equal(engine.selectedItem.price, 450);
    assert.deepEqual(Array.from(engine.variantDimensions.find(dimension => dimension.name === 'Size').values), ['Large']);
    engine.chooseVariant('Size', 'Missing');
    assert.equal(engine.selectedItem.id, 1);
    engine.addConfiguredItem();
    assert.equal(engine.cart.length, 2);
    assert.equal(engine.grandTotal, 1100);
    assert.equal(engine.cart[0].attributes.Style, 'Vintage');
    assert.equal(engine.cart[1].attributes.Style, 'Black');
    engine.search = 'premium';
    assert.deepEqual(Array.from(engine.filteredItems, item => item.id), [2, 3]);
});

test('both tiers show the complete catalog, categories, counts, and search results', () => {
    const { engine } = cart();
    engine.refreshIcons = () => {};
    engine.catalog = [
        { id: 1, tier: 'minor', name: 'Paper', category: 'Office Supplies' },
        { id: 2, tier: 'major', name: 'Projector', category: 'Equipment' },
    ];

    for (const tier of ['minor', 'major']) {
        engine.setTier(tier);
        assert.equal(engine.catalogCount(), 2);
        assert.deepEqual(Array.from(engine.filteredItems, item => item.id), [1, 2]);
        assert.deepEqual(Array.from(engine.categories), ['Equipment', 'Office Supplies']);
        engine.category = 'Equipment';
        assert.deepEqual(Array.from(engine.filteredItems, item => item.id), [2]);
        engine.category = 'All';
        engine.search = 'paper';
        assert.deepEqual(Array.from(engine.filteredItems, item => item.id), [1]);
    }
});

test('restoring a cart keeps items regardless of their inventory tier', () => {
    const { engine } = cart();
    engine.refreshIcons = () => {};
    engine.catalog = [
        { id: 1, tier: 'minor', stock: 10 },
        { id: 2, tier: 'major', stock: 10 },
    ];

    for (const tier of ['minor', 'major']) {
        engine.tier = tier;
        engine.restorePreviousRequest({ tier, items: [
            { supply_id: 1, qty: 2 }, { supply_id: 2, qty: 3 }, { supply_id: 99, qty: 1 },
        ] });
        assert.deepEqual(Array.from(engine.cart, item => [item.id, item.qty]), [[1, 2], [2, 3]]);
    }
});

test('switching layouts preserves the cart, search, category, and purpose', () => {
    const { engine, values } = cart();
    engine.cart = [{ id: 12, qty: 2, price: 50 }];
    engine.search = 'paper';
    engine.category = 'Office Supplies';
    engine.purpose = 'For classroom assessment materials.';
    const items = engine.cart;
    engine.setCatalogLayout('list');
    assert.equal(engine.cart, items);
    assert.equal(engine.search, 'paper');
    assert.equal(engine.category, 'Office Supplies');
    assert.equal(engine.purpose, 'For classroom assessment materials.');
    assert.equal(values.get('htc-requisition-layout'), 'list');
    engine.resetModal();
    assert.equal(engine.catalogLayout, 'list');
});

test('layout toggle works when browser storage is blocked', () => {
    const { engine } = cart({ blocked: true });
    engine.setCatalogLayout('list');
    assert.equal(engine.catalogLayout, 'list');
    engine.setCatalogLayout('grid');
    assert.equal(engine.catalogLayout, 'grid');
});

test('invalid layout values leave the current preference unchanged', () => {
    const { engine, values } = cart({ saved: 'list' });
    engine.setCatalogLayout('tiles');
    assert.equal(engine.catalogLayout, 'list');
    assert.equal(values.get('htc-requisition-layout'), 'list');
});

test('reviewing the cart starts at the items and preserves the catalog and draft', () => {
    const { engine } = cart();
    const focus = [];
    engine.$nextTick = callback => callback();
    engine.$refs.cartBody = { scrollTop: 400, focus(options) { focus.push(['cart', options]); } };
    engine.$refs.catalogPane = { focus(options) { focus.push(['catalog', options]); } };
    engine.cart = [{ id: 12, qty: 2, price: 50 }];
    engine.search = 'paper';
    engine.category = 'Office Supplies';
    engine.purpose = 'For classroom assessment materials.';
    engine.view = 'catalog';
    const items = engine.cart;

    engine.reviewCart();
    assert.equal(engine.cartOpen, true);
    assert.equal(engine.$refs.cartBody.scrollTop, 0);
    assert.equal(focus[0][0], 'cart');
    assert.equal(focus[0][1].preventScroll, true);
    engine.continueBrowsing();
    assert.equal(engine.cartOpen, false);
    assert.equal(focus[1][0], 'catalog');
    assert.equal(engine.cart, items);
    assert.equal(engine.search, 'paper');
    assert.equal(engine.category, 'Office Supplies');
    assert.equal(engine.purpose, 'For classroom assessment materials.');
    assert.equal(engine.view, 'catalog');
});

test('the reason shortcut opens the cart and reveals the required field', () => {
    const { engine } = cart();
    const calls = [];
    engine.$nextTick = callback => callback();
    engine.$refs.purpose = {
        focus(options) { calls.push(['focus', options]); },
        scrollIntoView(options) { calls.push(['scroll', options]); },
    };
    engine.completeReason();
    assert.equal(engine.cartOpen, true);
    assert.equal(calls[0][0], 'focus');
    assert.equal(calls[0][1].preventScroll, true);
    assert.equal(calls[1][0], 'scroll');
    assert.equal(calls[1][1].block, 'nearest');
    engine.lastAddedKey = '12';
    engine.resetModal();
    assert.equal(engine.cartOpen, false);
    assert.equal(engine.lastAddedKey, null);
});

test('keeping a requisition prevents closing and preserves all draft data', async () => {
    const fixture = cart();
    const { engine } = fixture;
    engine.tier = 'minor';
    engine.cart = [{ id: 12, qty: 2, price: 50 }];
    engine.purpose = 'For classroom assessment materials.';
    const items = engine.cart;
    const event = fixture.closeEvent();
    await engine.confirmRequisitionClose(event);
    assert.equal(event.defaultPrevented, true);
    assert.equal(fixture.decisions[0].cancel, 'Keep editing');
    assert.equal(engine.cart, items);
    assert.equal(engine.purpose, 'For classroom assessment materials.');
    assert.equal(fixture.hides, 0);
    assert.equal(engine.closeConfirmationPending, false);
});

test('discarding closes the builder once and clears the draft only after hidden', async () => {
    const fixture = cart({ confirm: async () => true });
    const { engine } = fixture;
    engine.tier = 'minor';
    engine.cart = [{ id: 12, qty: 2, price: 50 }];
    engine.purpose = 'For classroom assessment materials.';
    await engine.confirmRequisitionClose(fixture.closeEvent());
    assert.equal(fixture.hides, 1);
    assert.equal(engine.cart.length, 1);
    const approvedClose = fixture.closeEvent();
    await engine.confirmRequisitionClose(approvedClose);
    assert.equal(approvedClose.defaultPrevented, false);
    assert.equal(fixture.decisions.length, 1);
    engine.resetModal();
    assert.equal(engine.cart.length, 0);
    assert.equal(engine.purpose, '');
    assert.equal(engine.discardConfirmed, false);
});

test('closing an untouched builder still asks for confirmation', async () => {
    const fixture = cart();
    const event = fixture.closeEvent();
    await fixture.engine.confirmRequisitionClose(event);
    assert.equal(event.defaultPrevented, true);
    assert.equal(fixture.decisions.length, 1);
    assert.equal(fixture.hides, 0);
});

test('an untouched builder closes only after confirming cancellation', async () => {
    const fixture = cart({ confirm: async () => true });
    const event = fixture.closeEvent();
    await fixture.engine.confirmRequisitionClose(event);
    assert.equal(event.defaultPrevented, true);
    assert.equal(fixture.decisions.length, 1);
    assert.equal(fixture.hides, 1);
    const confirmed = fixture.closeEvent();
    await fixture.engine.confirmRequisitionClose(confirmed);
    assert.equal(confirmed.defaultPrevented, false);
    assert.equal(fixture.decisions.length, 1);
});

test('choosing a tier or entering a reason counts as a draft', async () => {
    const tier = cart();
    tier.engine.tier = 'minor';
    await tier.engine.confirmRequisitionClose(tier.closeEvent());
    assert.equal(tier.decisions.length, 1);
    const reason = cart();
    reason.engine.purpose = 'Draft reason';
    await reason.engine.confirmRequisitionClose(reason.closeEvent());
    assert.equal(reason.decisions.length, 1);
});

test('repeated close requests share one outstanding discard decision', async () => {
    let resolve;
    const fixture = cart({ confirm: () => new Promise(callback => { resolve = callback; }) });
    fixture.engine.tier = 'minor';
    const first = fixture.engine.confirmRequisitionClose(fixture.closeEvent());
    const second = fixture.closeEvent();
    await fixture.engine.confirmRequisitionClose(second);
    assert.equal(second.defaultPrevented, true);
    assert.equal(fixture.decisions.length, 1);
    resolve(false);
    await first;
    assert.equal(fixture.engine.closeConfirmationPending, false);
});

test('a submission in progress cannot be closed or discarded', async () => {
    const fixture = cart();
    fixture.engine.submitting = true;
    const event = fixture.closeEvent();
    await fixture.engine.confirmRequisitionClose(event);
    assert.equal(event.defaultPrevented, true);
    assert.equal(fixture.decisions.length, 0);
    assert.equal(fixture.hides, 0);
});
