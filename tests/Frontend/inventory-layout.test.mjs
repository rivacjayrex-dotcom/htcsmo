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
