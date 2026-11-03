<script>
    function inventoryProductEditor(initialRows, previousRows) {
        const restored = Array.isArray(previousRows) && previousRows.length > 0;
        const source = restored ? previousRows : initialRows;
        return {
            nextKey: source.length,
            rows: source.map((row, index) => ({
                ...row, key: index,
                options: (row.options || []).map(option => ({...option})),
                locked: initialRows.find(current => Number(current.id) === Number(row.id))?.locked || false,
                detailsOpen: restored,
            })),
            addVariant() {
                if (this.rows.length >= 100) return;
                const first = this.rows[0] || {};
                this.rows.push({key: this.nextKey++, id: null, version: '', locked: false, detailsOpen: true,
                    size: '', supplier: first.supplier || '', unit: first.unit || 'Piece(s)', unit_price: '',
                    quantity: 0, min_stock_level: 5, specifications: '', model_number: '', options: [{name: 'Brand', values: ''}],
                });
            },
            copyVariant(index) {
                if (this.rows.length >= 100) return;
                this.rows.push({...this.rows[index], key: this.nextKey++, id: null, version: '', locked: false, detailsOpen: true,
                    quantity: 0, options: this.rows[index].options.map(option => ({...option})),
                });
            },
            removeNewVariant(index) { if (!this.rows[index].id) this.rows.splice(index, 1); },
            showInvalidField(event) {
                const key = event.target.closest('[data-product-variant]')?.dataset.variantKey;
                const row = this.rows.find(row => String(row.key) === key);
                if (row) {
                    row.detailsOpen = true;
                    this.$nextTick(() => event.target.focus());
                }
            },
            variantLabel(row) {
                return [...row.options.map(option => `${option.name}: ${option.values}`), row.size, row.supplier].filter(Boolean).join(' · ') || 'Standard';
            },
        };
    }
</script>
