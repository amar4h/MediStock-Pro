/**
 * POS Billing Alpine Component
 *
 * Handles the sales / billing interface:
 *  - Barcode scanning input
 *  - Item search and addition
 *  - Live total calculations (GST, discounts)
 *  - Payment mode handling
 *  - Sale submission
 */
export function posBilling() {
    return {
        // ------ State ------
        items: [],
        barcodeInput: '',
        searchQuery: '',
        searchResults: [],
        searchOpen: false,

        // Customer / Prescription
        customerName: '',
        customerPhone: '',
        doctorName: '',
        patientName: '',
        customerId: null,

        // Payment
        paymentMode: 'cash',    // cash | credit | upi | partial
        paidAmount: 0,
        invoiceDiscount: 0,     // Invoice-level discount percentage
        invoiceDiscountAmount: 0, // Invoice-level discount flat amount
        roundOff: 0,

        // Submission
        submitting: false,
        saleCompleted: false,
        saleInvoiceNumber: '',
        saleId: null,

        // Barcode buffer for keyboard wedge scanners
        _barcodeBuffer: '',
        _barcodeTimer: null,

        // ------ Lifecycle ------
        init() {
            // Listen for barcode keyboard input (scanner sends rapid keystrokes)
            document.addEventListener('keydown', (e) => this._handleBarcodeKeydown(e));

            // Calculate round-off whenever totals change
            this.$watch('items', () => {
                this._calculateRoundOff();
            }, { deep: true });

            this.$watch('invoiceDiscount', () => this._calculateRoundOff());
            this.$watch('invoiceDiscountAmount', () => this._calculateRoundOff());
        },

        // ------ Computed Properties ------

        get subtotal() {
            return this.items.reduce((sum, item) => sum + this._itemAmount(item), 0);
        },

        get totalItemDiscount() {
            return this.items.reduce((sum, item) => {
                const base = item.qty * item.price;
                const disc = base * (item.discount / 100);
                return sum + disc;
            }, 0);
        },

        get totalGst() {
            return this.items.reduce((sum, item) => {
                const amountAfterDisc = this._itemAmount(item);
                const gstAmount = amountAfterDisc * (item.gstPercent / (100 + item.gstPercent));
                return sum + gstAmount;
            }, 0);
        },

        get invoiceDiscountTotal() {
            if (this.invoiceDiscountAmount > 0) {
                return this.invoiceDiscountAmount;
            }
            return this.subtotal * (this.invoiceDiscount / 100);
        },

        get grandTotal() {
            const raw = this.subtotal - this.invoiceDiscountTotal + this.roundOff;
            return Math.max(0, raw);
        },

        get balanceAmount() {
            if (this.paymentMode === 'credit') return this.grandTotal;
            return Math.max(0, this.grandTotal - (parseFloat(this.paidAmount) || 0));
        },

        get changeAmount() {
            if (this.paymentMode === 'credit') return 0;
            const paid = parseFloat(this.paidAmount) || 0;
            return Math.max(0, paid - this.grandTotal);
        },

        get canSubmit() {
            if (this.items.length === 0) return false;
            if (this.submitting) return false;
            if (this.paymentMode === 'cash' || this.paymentMode === 'upi') {
                return (parseFloat(this.paidAmount) || 0) >= this.grandTotal;
            }
            return true; // credit and partial allow any paid amount
        },

        get itemCount() {
            return this.items.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
        },

        // ------ Item Management ------

        addItem(itemData) {
            // Check if item already exists (same item_id and batch_id)
            const existing = this.items.find(
                i => i.item_id === itemData.item_id && i.batch_id === itemData.batch_id
            );

            if (existing) {
                existing.qty += 1;
                Alpine.store('notification').info(`${existing.name} quantity updated to ${existing.qty}`);
                return;
            }

            this.items.push({
                item_id: itemData.item_id || null,
                batch_id: itemData.batch_id || null,
                name: itemData.name || '',
                batchNumber: itemData.batch_number || '',
                expiry: itemData.expiry || '',
                hsnCode: itemData.hsn_code || '',
                qty: 1,
                maxQty: itemData.available_qty || 999,
                price: parseFloat(itemData.selling_price) || 0,
                mrp: parseFloat(itemData.mrp) || 0,
                discount: 0,
                gstPercent: parseFloat(itemData.gst_percent) || 0,
                schedule: itemData.schedule || ''
            });

            this.searchQuery = '';
            this.searchResults = [];
            this.searchOpen = false;

            Alpine.store('notification').success(`${itemData.name} added`);
        },

        removeItem(index) {
            const name = this.items[index]?.name || 'Item';
            this.items.splice(index, 1);
            Alpine.store('notification').info(`${name} removed`);
        },

        updateItemQty(index, qty) {
            const item = this.items[index];
            if (!item) return;

            const parsedQty = parseInt(qty) || 0;
            if (parsedQty > item.maxQty) {
                Alpine.store('notification').warning(`Only ${item.maxQty} units available for ${item.name}`);
                item.qty = item.maxQty;
            } else if (parsedQty < 1) {
                item.qty = 1;
            } else {
                item.qty = parsedQty;
            }
        },

        // ------ Search ------

        async searchItems() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                this.searchOpen = false;
                return;
            }

            try {
                const response = await fetch(`/api/v1/items/search?q=${encodeURIComponent(this.searchQuery)}&with_batches=1`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();
                this.searchResults = data.data || [];
                this.searchOpen = this.searchResults.length > 0;
            } catch (error) {
                console.error('Search error:', error);
            }
        },

        // ------ Barcode Handling ------

        async handleBarcodeSubmit() {
            const barcode = this.barcodeInput.trim();
            if (!barcode) return;

            try {
                const response = await fetch(`/api/v1/items/barcode/${encodeURIComponent(barcode)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.data) {
                        this.addItem(data.data);
                    } else {
                        Alpine.store('notification').warning('Item not found for this barcode.');
                    }
                } else {
                    Alpine.store('notification').error('Item not found for this barcode.');
                }
            } catch (error) {
                console.error('Barcode lookup error:', error);
                Alpine.store('notification').error('Failed to look up barcode.');
            }

            this.barcodeInput = '';
        },

        // ------ Payment ------

        setPaymentMode(mode) {
            this.paymentMode = mode;
            if (mode === 'cash' || mode === 'upi') {
                this.paidAmount = this.grandTotal;
            } else if (mode === 'credit') {
                this.paidAmount = 0;
            }
        },

        // ------ Submission ------

        async submitSale() {
            if (!this.canSubmit) return;

            this.submitting = true;

            const payload = {
                customer_id: this.customerId,
                customer_name: this.customerName,
                customer_phone: this.customerPhone,
                doctor_name: this.doctorName,
                patient_name: this.patientName,
                payment_mode: this.paymentMode,
                paid_amount: parseFloat(this.paidAmount) || 0,
                invoice_discount: this.invoiceDiscount,
                invoice_discount_amount: this.invoiceDiscountAmount,
                round_off: this.roundOff,
                items: this.items.map(item => ({
                    item_id: item.item_id,
                    batch_id: item.batch_id,
                    qty: item.qty,
                    price: item.price,
                    discount: item.discount,
                    gst_percent: item.gstPercent
                }))
            };

            try {
                const response = await fetch('/api/v1/sales', {
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
                    this.saleCompleted = true;
                    this.saleInvoiceNumber = data.data.invoice_number;
                    this.saleId = data.data.id;
                    Alpine.store('notification').success(`Sale completed! Invoice: ${this.saleInvoiceNumber}`);
                } else {
                    const errors = data.errors ? Object.values(data.errors).flat().join(', ') : data.message;
                    Alpine.store('notification').error(errors || 'Failed to complete sale.');
                }
            } catch (error) {
                console.error('Sale submission error:', error);
                Alpine.store('notification').error('Network error. Please try again.');
            } finally {
                this.submitting = false;
            }
        },

        printInvoice() {
            if (this.saleId) {
                window.open(`/sales/${this.saleId}/invoice`, '_blank');
            }
        },

        newSale() {
            this.items = [];
            this.barcodeInput = '';
            this.searchQuery = '';
            this.customerName = '';
            this.customerPhone = '';
            this.doctorName = '';
            this.patientName = '';
            this.customerId = null;
            this.paymentMode = 'cash';
            this.paidAmount = 0;
            this.invoiceDiscount = 0;
            this.invoiceDiscountAmount = 0;
            this.roundOff = 0;
            this.saleCompleted = false;
            this.saleInvoiceNumber = '';
            this.saleId = null;
        },

        // ------ Private Helpers ------

        _itemAmount(item) {
            const base = item.qty * item.price;
            return base - (base * (item.discount / 100));
        },

        _calculateRoundOff() {
            const rawTotal = this.subtotal - this.invoiceDiscountTotal;
            this.roundOff = Math.round(rawTotal) - rawTotal;
        },

        _handleBarcodeKeydown(e) {
            // Barcode scanners type rapidly; buffer chars and detect Enter
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                return; // Don't interfere with focused inputs
            }

            if (e.key === 'Enter' && this._barcodeBuffer.length > 3) {
                this.barcodeInput = this._barcodeBuffer;
                this._barcodeBuffer = '';
                clearTimeout(this._barcodeTimer);
                this.handleBarcodeSubmit();
                return;
            }

            if (e.key.length === 1) {
                this._barcodeBuffer += e.key;
                clearTimeout(this._barcodeTimer);
                this._barcodeTimer = setTimeout(() => {
                    this._barcodeBuffer = '';
                }, 100);
            }
        },

        // ------ Formatting Helpers ------

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
