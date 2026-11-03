function requisitionProductKey(item) {
    return JSON.stringify([item.name, item.category].map(value => String(value || '').trim().toLowerCase()));
}

function groupRequisitionProducts(catalog, matchingItems = catalog) {
    const matches = new Set(matchingItems.map(item => item.id));
    const groups = new Map();
    for (const item of catalog) {
        const key = requisitionProductKey(item);
        if (!groups.has(key)) groups.set(key, {key, name: String(item.name || '').trim(), category: item.category, variants: []});
        groups.get(key).variants.push(item);
    }

    return Array.from(groups.values()).flatMap(product => {
        const matchingVariants = product.variants.filter(item => matches.has(item.id));
        if (!matchingVariants.length) return [];
        const prices = product.variants.map(item => Number(item.price || 0));
        const units = new Set(product.variants.map(item => item.unit));
        const stock = product.variants.reduce((sum, item) => sum + Number(item.stock || 0), 0);
        return [{
            ...product,
            item: matchingVariants.find(item => item.stock > 0) || matchingVariants[0],
            price: Math.min(...prices),
            hasPriceRange: Math.min(...prices) !== Math.max(...prices),
            stock,
            stockSummary: units.size === 1 ? `${stock} ${product.variants[0].unit} available` : 'Stock varies by option',
        }];
    });
}
