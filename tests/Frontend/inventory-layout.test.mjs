import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import vm from "node:vm";

const page = readFileSync(
    new URL("../../resources/views/inventory/index.blade.php", import.meta.url),
    "utf8",
);
const source = page.slice(
    page.indexOf("function inventoryWorkspace("),
    page.indexOf("function confirmDelete("),
);

function workspace({ saved = null, blocked = false } = {}) {
    const values = new Map([["htc-inventory-layout", saved]]);
    const localStorage = {
        getItem(key) {
            if (blocked) throw Error("Blocked");
            return values.get(key);
        },
        setItem(key, value) {
            if (blocked) throw Error("Blocked");
            values.set(key, value);
        },
    };
    const create = vm.runInNewContext(`(${source})`, {
        window: { location: { href: "https://example.test/inventory" } },
        URL,
        localStorage,
    });
    const engine = create([
        { id: 1, tier: "minor", text: "paper", status: "low" },
        { id: 2, tier: "major", text: "printer", status: "healthy" },
    ]);
    engine.init();
    return { engine, values };
}

test("inventory restores its layout and handles unavailable browser storage", () => {
    assert.equal(workspace().engine.catalogLayout, "list");
    assert.equal(workspace({ saved: "tiles" }).engine.catalogLayout, "tiles");
    assert.equal(workspace({ saved: "invalid" }).engine.catalogLayout, "list");
    const { engine } = workspace({ blocked: true });
    engine.setCatalogLayout("tiles");
    assert.equal(engine.catalogLayout, "tiles");
});

test("select all covers the unified catalog and layout changes preserve selection and filters", () => {
    const { engine, values } = workspace();
    engine.toggleMatching();
    assert.deepEqual(Array.from(engine.selected), [1, 2]);
    assert.equal(engine.allMatchingSelected(), true);
    engine.query = "paper";
    engine.stockFilter = "low";
    engine.setCatalogLayout("tiles");
    assert.equal(values.get("htc-inventory-layout"), "tiles");
    assert.equal(engine.query, "paper");
    assert.equal(engine.stockFilter, "low");
    assert.deepEqual(Array.from(engine.selected), [1, 2]);
    engine.toggleMatching();
    assert.deepEqual(Array.from(engine.selected), [2]);
    engine.query = "";
    engine.stockFilter = "all";
    assert.equal(engine.someMatchingSelected(), true);
});

test("dynamic filter updates preserve layout and offscreen selections", () => {
    const { engine } = workspace({ saved: "tiles" });
    engine.selected = [2];
    engine.$el = {
        querySelector: () => ({
            dataset: {
                filterCatalog: JSON.stringify([
                    { id: 1, text: "paper", status: "low" },
                ]),
            },
        }),
    };
    engine.syncFilters("https://example.test/inventory?search=paper&stock=low");
    assert.deepEqual(Array.from(engine.matchingIds()), [1]);
    assert.deepEqual(Array.from(engine.selected), [2]);
    assert.equal(engine.catalogLayout, "tiles");
    engine.toggleMatching();
    assert.deepEqual(Array.from(engine.selected), [2, 1]);
});

test("product dropdown changes the active stock record and falls back to a matching variant", () => {
    const { engine } = workspace();
    const rowSource = page.slice(page.indexOf('function inventoryProductRow('), page.indexOf('function inventoryWorkspace('));
    const createRow = vm.runInNewContext(`(${rowSource})`);
    const variants = [
        { id: 11, text: 'paper legal double a', status: 'low', quantity: 2, price: 210, edit_url: '/inventory/11/edit', delete_url: '/inventory/11' },
        { id: 12, text: 'paper short hard copy', status: 'healthy', quantity: 12, price: 190, edit_url: '/inventory/12/edit', delete_url: '/inventory/12' },
    ];
    const row = createRow(variants);
    row.matches = (text, status) => engine.matches(text, status);
    assert.equal(row.activeVariant.id, 11);
    row.chosenId = 12;
    assert.equal(row.activeVariant.price, 190);
    assert.equal(row.activeVariant.quantity, 12);
    assert.equal(row.activeVariant.edit_url, '/inventory/12/edit');
    assert.equal(row.activeVariant.delete_url, '/inventory/12');
    engine.stockFilter = 'low';
    assert.equal(row.activeVariant.id, 11);
    assert.deepEqual(Array.from(row.variantIds), [11]);
    engine.query = 'unknown';
    assert.equal(row.matchingVariants.length, 0);
    assert.equal(row.activeVariant.id, 11, 'Keep hidden row bindings safe while results refresh.');
});

test("group selection targets matching variant IDs and preserves other product selections", () => {
    const { engine } = workspace();
    engine.selected = [99, 11];
    assert.equal(engine.someSelected([11, 12]), true);
    assert.equal(engine.allSelected([11, 12]), false);
    engine.toggleIds([11, 12]);
    assert.deepEqual(Array.from(engine.selected), [99, 11, 12]);
    engine.toggleIds([11, 12]);
    assert.deepEqual(Array.from(engine.selected), [99]);
    assert.equal(engine.allSelected([]), false);
});
