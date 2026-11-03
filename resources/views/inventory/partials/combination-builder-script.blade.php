<script>
    function inventoryVariantBuilder(names, initialRows) {
        return {
            enabled: initialRows.length > 0,
            columns: names,
            combinations: initialRows.map((row, index) => ({...row, values: [...row.values], key: index})),
            nextKey: initialRows.length,
            init() { if (!this.combinations.length) this.addCombination(); },
            addCombination() {
                if (this.combinations.length >= 100) return;
                this.combinations.push({key: this.nextKey++, values: this.columns.map(() => ''), size: '', supplier: '', unit_price: '', quantity: 0, min_stock_level: 5});
            },
            copyCombination(index) {
                if (this.combinations.length >= 100) return;
                const row = this.combinations[index];
                this.combinations.push({...row, key: this.nextKey++, values: [...row.values]});
            },
            addColumn() {
                if (this.columns.length >= 5) return;
                this.columns.push('');
                this.combinations.forEach(row => row.values.push(''));
            },
            removeColumn(index) {
                if (index === 0) return;
                this.columns.splice(index, 1);
                this.combinations.forEach(row => row.values.splice(index, 1));
            },
        };
    }
</script>
