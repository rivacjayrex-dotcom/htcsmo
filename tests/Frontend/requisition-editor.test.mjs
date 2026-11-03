import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import vm from "node:vm";

const view = readFileSync(
    new URL("../../resources/views/requests/edit.blade.php", import.meta.url),
    "utf8",
);
const grouping = readFileSync(
    new URL(
        "../../resources/views/layouts/partials/requisition-products-script.blade.php",
        import.meta.url,
    ),
    "utf8",
);
const source = view
    .slice(
        view.indexOf("function requisitionEditor("),
        view.indexOf("</script>"),
    )
    .replace("@js($requisition->request_type)", "'minor'");
const catalog = [
    {
        id: 1,
        name: "Bond Paper",
        category: "Office",
        size: "A4",
        supplier: "A",
        price: 150,
        stock: 10,
        unit: "Ream(s)",
        attributes: { Brand: "Hard Copy" },
        options: { Brand: ["Hard Copy"] },
    },
    {
        id: 2,
        name: "Bond Paper",
        category: "Office",
        size: "Legal",
        supplier: "B",
        price: 190,
        stock: 8,
        unit: "Ream(s)",
        attributes: { Brand: "Double A" },
        options: { Brand: ["Double A"] },
    },
    {
        id: 3,
        name: "Pen",
        category: "Office",
        price: 20,
        stock: 50,
        unit: "Piece(s)",
        options: {},
    },
];

function editor(items = []) {
    const engine = vm.runInNewContext(
        `${grouping}\n(${source})(catalog, items, [])`,
        {
            catalog,
            items,
            window: { setTimeout() {}, scrollTo() {} },
        },
    );
    engine.init();
    return engine;
}

test("editing a requisition shows one catalog entry per product and keeps all variants selectable after search", () => {
    const engine = editor();
    assert.equal(engine.filteredProducts.length, 2);
    engine.search = "legal";
    assert.equal(engine.filteredProducts.length, 1);
    assert.equal(engine.filteredProducts[0].item.id, 2);
    engine.configureItem(engine.filteredProducts[0].item);
    assert.deepEqual(
        Array.from(engine.productVariants, (item) => item.id),
        [1, 2],
    );
    assert.deepEqual(Object.keys(engine.customizationOptions), []);
    engine.chooseVariant("Brand", "Hard Copy");
    engine.saveConfiguredItem();
    assert.equal(engine.cart.length, 1);
    assert.equal(engine.cart[0].id, 1);
    assert.equal(engine.cart[0].selected_options.Brand, "Hard Copy");
    assert.equal(engine.grandTotal, 150);
});

test("changing a variant on an existing line updates that line and retains its quantity", () => {
    const engine = editor([
        { supply_id: 1, qty: 2, options: { Brand: "Hard Copy" } },
    ]);
    engine.configureCartItem(0);
    engine.chooseVariant("Brand", "Double A");
    assert.equal(engine.editingIndex, 0);
    assert.equal(engine.selectedQty, 2);
    assert.equal(engine.selectedItem.price, 190);
    assert.equal(engine.selectedItem.supplier, "B");
    engine.saveConfiguredItem();
    assert.equal(engine.cart.length, 1);
    assert.equal(engine.cart[0].id, 2);
    assert.equal(engine.cart[0].qty, 2);
    assert.equal(engine.cart[0].selected_options.Brand, "Double A");
    assert.equal(engine.grandTotal, 380);
});

test("the selected variant stock limit still prevents saving excessive quantities", () => {
    const engine = editor();
    engine.configureItem(engine.filteredProducts[0].item);
    engine.selectedQty = 9;
    engine.chooseVariant("Brand", "Double A");
    assert.equal(engine.canSaveConfigured, false);
    engine.saveConfiguredItem();
    assert.equal(engine.cart.length, 0);
    engine.selectedQty = 8;
    engine.saveConfiguredItem();
    assert.equal(engine.cart[0].id, 2);
    assert.equal(engine.cart[0].qty, 8);
});
