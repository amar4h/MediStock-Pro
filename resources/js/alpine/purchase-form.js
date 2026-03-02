/**
 * Purchase Form Alpine Component
 *
 * Handles the purchase entry form:
 *  - Dynamic item rows
 *  - Listens for 'invoice-scanned' events to prefill
 *  - Confidence-based field highlighting
 *  - GST and total calculations
 *  - Form submission
 */
export function purchaseForm() {
    return {
        // ------ State ------
        supplierId: '',
        supplierName: '',
        supplierGstin: '',
        invoiceNumber: '',
        invoiceDate: '',
        items: [],
        paymentMode: 'credit',    // cash | credit | partial
        paidAmount: 0,
        notes: '',

        // OCR confidence data
        confidence: {},
        overallConfidence: 0,
        scanPrefilled: false,

        // Item search
        searchQuery: '',
        searchResults: [],
        searchOpen: false,
        activeRowIndex: -1,

        // Submission
        submitting: false,

        // ------ Lifecycle ------
        init() {
            // Add one empty row by default
            this.addRow();

            // Set default date to today
            this.invoiceDate = new Date().toISOString().split('T')[0];

            // Listen for invoice scanner events
            window.addEventListener('invoice-scanned', (e) => {
                this.populateFromScan(e.detail);
            });
        },

        // ------ Computed Properties ------

        get subtotal() {
            return this.items.reduce((sum, item) => sum + this._rowAmount(item), 0);
        },

        get totalGst() {
            return this.items.reduce((sum, item) => {
                const amount = this._rowAmount(item);
                return sum + (amount * (parseFloat(item.gstPercent) || 0) / 100);
            }, 0);
        },

        get totalDiscount() {
            return this.items.reduce((sum, item) => {
                const base = (parseFloat(item.qty) || 0) * (parseFloat(item.purchasePrice) || 0);
                return sum + (base * (parseFloat(item.discountPercent) || 0) / 100);
            }, 0);
        },

        get grandTotal() {
            return this.subtotal + this.totalGst;
        },

        get balanceAmount() {
            if (this.paymentMode === 'cash') return 0;
            return Math.max(0, this.grandTotal - (parseFloat(this.paidAmount) || 0));
        },

        get canSubmit() {
            if (this.submitting) return false;
            if (!this.supplierId && !this.supplierName) return false;
            if (!this.invoiceNumber) return false;
            return this.items.some(item => item.itemId && (parseFloat(item.qty) || 0) > 0);
        },

        // ------ Row Management ------

        addRow() {
            this.items.push({
                itemId: '',
                itemName: '',
                batchNumber: '',
                expiry: '',
                qty: '',
                freeQty: '',
                mrp: '',
                purchasePrice: '',
                sellingPrice: '',
                gstPercent: '12',
                discountPercent: '',
                hsnCode: '',
                amount: 0,
                // Confidence tracking per field
                _confidence: {}
            });
        },

        removeRow(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            } else {
                // Reset the single row instead of removing
                this.items[0] = this._emptyRow();
            }
        },

        duplicateRow(index) {
            const source = { ...this.items[index], _confidence: {} };
            source.batchNumber = '';
            source.expiry = '';
            source.qty = '';
            source.freeQty = '';
            this.items.splice(index + 1, 0, source);
        },

        // ------ Item Search ------

        async searchItemsForRow(index, query) {
            this.activeRowIndex = index;
            this.items[index].itemName = query;

            if (query.length < 2) {
                this.searchResults = [];
                this.searchOpen = false;
                return;
            }

            try {
                const response = await fetch(`/api/v1/items/search?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();
                this.searchResults = data.data || [];
                this.searchOpen = this.searchResults.length > 0;
            } catch (error) {
                console.error('Item search error:', error);
            }
        },

        selectItemForRow(item) {
            const row = this.items[this.activeRowIndex];
            if (!row) return;

            row.itemId = item.id;
            row.itemName = item.name;
            row.hsnCode = item.hsn_code || '';
            row.gstPercent = item.gst_percent || '12';

            this.searchOpen = false;
            this.searchResults = [];
        },

        // ------ OCR Prefill ------

        populateFromScan(data) {
            if (!data) return;

            this.scanPrefilled = true;
            this.confidence = data.confidence || {};
            this.overallConfidence = data.overall_confidence || 0;

            // Set header fields
            if (data.supplier_name) this.supplierName = data.supplier_name;
            if (data.supplier_gstin) this.supplierGstin = data.supplier_gstin;
            if (data.invoice_number) this.invoiceNumber = data.invoice_number;
            if (data.invoice_date) this.invoiceDate = data.invoice_date;

            // Set items
            if (data.items && data.items.length > 0) {
                this.items = data.items.map(scannedItem => ({
                    itemId: scannedItem.item_id || '',
                    itemName: scannedItem.name || '',
                    batchNumber: scannedItem.batch_number || '',
                    expiry: scannedItem.expiry || '',
                    qty: scannedItem.qty || '',
                    freeQty: scannedItem.free_qty || '',
                    mrp: scannedItem.mrp || '',
                    purchasePrice: scannedItem.purchase_price || '',
                    sellingPrice: scannedItem.selling_price || '',
                    gstPercent: scannedItem.gst_percent || '12',
                    discountPercent: scannedItem.discount_percent || '',
                    hsnCode: scannedItem.hsn_code || '',
                    amount: 0,
                    _confidence: scannedItem.confidence || {}
                }));
            }

            // Set totals if provided
            if (data.totals) {
                // totals could include paid_amount, etc.
                if (data.totals.paid_amount) {
                    this.paidAmount = data.totals.paid_amount;
                }
            }

            Alpine.store('notification').info('Invoice data prefilled from scan. Please verify all fields.');
        },

        // ------ Confidence Helpers ------

        fieldConfidenceClass(fieldName) {
            const score = this.confidence[fieldName] ?? -1;
            if (score >= 0.8) return 'confidence-high-border';
            if (score >= 0.5) return 'confidence-medium-border';
            if (score >= 0) return 'confidence-low-border';
            return '';
        },

        rowFieldConfidenceClass(rowIndex, fieldName) {
            const row = this.items[rowIndex];
            if (!row?._confidence) return '';
            const score = row._confidence[fieldName] ?? -1;
            if (score >= 0.8) return 'confidence-high-border';
            if (score >= 0.5) return 'confidence-medium-border';
            if (score >= 0) return 'confidence-low-border';
            return '';
        },

        // ------ Calculate Row Amount ------

        calculateRowAmount(index) {
            const row = this.items[index];
            if (!row) return 0;
            return this._rowAmount(row);
        },

        // ------ Payment ------

        setPaymentMode(mode) {
            this.paymentMode = mode;
            if (mode === 'cash') {
                this.paidAmount = this.grandTotal;
            } else if (mode === 'credit') {
                this.paidAmount = 0;
            }
        },

        // ------ Submission ------

        async submitPurchase() {
            if (!this.canSubmit) return;

            this.submitting = true;

            const payload = {
                supplier_id: this.supplierId || null,
                supplier_name: this.supplierName,
                supplier_gstin: this.supplierGstin,
                invoice_number: this.invoiceNumber,
                invoice_date: this.invoiceDate,
                payment_mode: this.paymentMode,
                paid_amount: parseFloat(this.paidAmount) || 0,
                notes: this.notes,
                items: this.items
                    .filter(item => item.itemName && (parseFloat(item.qty) || 0) > 0)
                    .map(item => ({
                        item_id: item.itemId || null,
                        item_name: item.itemName,
                        batch_number: item.batchNumber,
                        expiry: item.expiry,
                        qty: parseInt(item.qty) || 0,
                        free_qty: parseInt(item.freeQty) || 0,
                        mrp: parseFloat(item.mrp) || 0,
                        purchase_price: parseFloat(item.purchasePrice) || 0,
                        selling_price: parseFloat(item.sellingPrice) || 0,
                        gst_percent: parseFloat(item.gstPercent) || 0,
                        discount_percent: parseFloat(item.discountPercent) || 0,
                        hsn_code: item.hsnCode
                    }))
            };

            try {
                const response = await fetch('/api/v1/purchases', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    Alpine.store('notification').success('Purchase saved successfully!');
                    // Redirect to purchase list
                    window.location.href = '/purchases';
                } else {
                    const errors = data.errors
                        ? Object.values(data.errors).flat().join(', ')
                        : data.message;
                    Alpine.store('notification').error(errors || 'Failed to save purchase.');
                }
            } catch (error) {
                console.error('Purchase submission error:', error);
                Alpine.store('notification').error('Network error. Please try again.');
            } finally {
                this.submitting = false;
            }
        },

        // ------ Private Helpers ------

        _rowAmount(row) {
            const qty = parseFloat(row.qty) || 0;
            const price = parseFloat(row.purchasePrice) || 0;
            const disc = parseFloat(row.discountPercent) || 0;
            const base = qty * price;
            return base - (base * disc / 100);
        },

        _emptyRow() {
            return {
                itemId: '',
                itemName: '',
                batchNumber: '',
                expiry: '',
                qty: '',
                freeQty: '',
                mrp: '',
                purchasePrice: '',
                sellingPrice: '',
                gstPercent: '12',
                discountPercent: '',
                hsnCode: '',
                amount: 0,
                _confidence: {}
            };
        },

        // ------ Formatting ------

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                minimumFractionDigits: 2
            }).format(amount);
        },

        formatNumber(amount) {
            return new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }
    };
}
