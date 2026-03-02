/**
 * Invoice Scanner Alpine Component
 *
 * Handles camera capture / file upload of purchase invoices,
 * compresses the image, uploads to the OCR endpoint, and
 * dispatches the extracted data so the purchase form can prefill.
 */
export function invoiceScanner() {
    return {
        // ------ State ------
        scanning: false,       // Upload / OCR in progress
        scanned: false,        // Successfully scanned
        previewUrl: null,      // Data URL of the selected image
        originalUrl: null,     // Original (uncompressed) data URL
        progress: 0,           // Upload progress percentage
        scanResult: null,      // Parsed response from API
        warnings: [],          // Array of warning strings
        errorMessage: '',      // Error message on failure
        showOriginal: false,   // Toggle to view original image

        // ------ Configuration ------
        maxImageDimension: 1920,
        compressionQuality: 0.8,
        apiEndpoint: '/api/v1/invoice-scans',

        // ------ Lifecycle ------
        init() {
            // Reset state on component init
        },

        // ------ Methods ------

        /**
         * Handle image selection from file input or camera capture
         */
        async handleImageSelect(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            // Validate file type
            if (!file.type.startsWith('image/')) {
                this.errorMessage = 'Please select a valid image file.';
                Alpine.store('notification').error('Invalid file type. Please select an image.');
                return;
            }

            // Validate file size (max 15MB raw)
            if (file.size > 15 * 1024 * 1024) {
                this.errorMessage = 'Image is too large. Maximum 15MB allowed.';
                Alpine.store('notification').error('Image too large. Maximum 15MB.');
                return;
            }

            this.errorMessage = '';
            this.warnings = [];
            this.scanning = true;
            this.scanned = false;
            this.progress = 5;

            try {
                // Read original for preview
                this.originalUrl = await this._readAsDataURL(file);
                this.progress = 10;

                // Compress image
                const compressedBlob = await this.compressImage(
                    file,
                    this.maxImageDimension,
                    this.compressionQuality
                );
                this.previewUrl = URL.createObjectURL(compressedBlob);
                this.progress = 25;

                // Upload to OCR endpoint
                const formData = new FormData();
                formData.append('invoice_image', compressedBlob, 'invoice.jpg');

                const response = await this._uploadWithProgress(formData);
                this.progress = 100;

                if (response.success) {
                    this.scanResult = response.data;
                    this.warnings = response.data.warnings || [];
                    this.scanned = true;

                    // Show confidence-based notification
                    const confidence = response.data.overall_confidence || 0;
                    if (confidence >= 0.8) {
                        Alpine.store('notification').success('Invoice scanned successfully! Please verify the prefilled data.');
                    } else if (confidence >= 0.5) {
                        Alpine.store('notification').warning('Invoice partially scanned. Some fields may need manual entry.');
                    } else {
                        Alpine.store('notification').warning('Low scan confidence. Please verify all fields carefully.');
                    }

                    // Dispatch scanned data to the purchase form
                    this.prefillForm(response.data.extracted);
                } else {
                    this.errorMessage = response.message || 'Scan failed. Please try again or enter manually.';
                    Alpine.store('notification').error(this.errorMessage);
                }
            } catch (error) {
                console.error('Invoice scan error:', error);
                this.errorMessage = 'Network error. Please check your connection and try again.';
                Alpine.store('notification').error('Scan failed. Please try again.');
            } finally {
                this.scanning = false;
            }
        },

        /**
         * Compress image using Canvas API
         */
        compressImage(file, maxDim = 1920, quality = 0.8) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const reader = new FileReader();

                reader.onload = (e) => {
                    img.onload = () => {
                        let { width, height } = img;

                        // Scale down if larger than maxDim
                        if (width > maxDim || height > maxDim) {
                            const ratio = Math.min(maxDim / width, maxDim / height);
                            width = Math.round(width * ratio);
                            height = Math.round(height * ratio);
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = true;
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob(
                            (blob) => {
                                if (blob) {
                                    resolve(blob);
                                } else {
                                    reject(new Error('Canvas compression failed.'));
                                }
                            },
                            'image/jpeg',
                            quality
                        );
                    };

                    img.onerror = () => reject(new Error('Failed to load image.'));
                    img.src = e.target.result;
                };

                reader.onerror = () => reject(new Error('Failed to read file.'));
                reader.readAsDataURL(file);
            });
        },

        /**
         * Dispatch extracted data as a custom event for the purchase form
         */
        prefillForm(extracted) {
            if (!extracted) return;

            window.dispatchEvent(new CustomEvent('invoice-scanned', {
                detail: {
                    supplier_name: extracted.supplier_name || '',
                    supplier_gstin: extracted.supplier_gstin || '',
                    invoice_number: extracted.invoice_number || '',
                    invoice_date: extracted.invoice_date || '',
                    items: extracted.items || [],
                    totals: extracted.totals || {},
                    confidence: extracted.confidence || {},
                    overall_confidence: extracted.overall_confidence || 0
                }
            }));
        },

        /**
         * Retake / re-scan: reset state and trigger file input
         */
        retake() {
            this.scanning = false;
            this.scanned = false;
            this.previewUrl = null;
            this.originalUrl = null;
            this.progress = 0;
            this.scanResult = null;
            this.warnings = [];
            this.errorMessage = '';
            this.showOriginal = false;

            // Trigger file input
            this.$nextTick(() => {
                this.$refs.fileInput?.click();
            });
        },

        /**
         * Cancel scan and reset
         */
        cancelScan() {
            this.scanning = false;
            this.scanned = false;
            this.previewUrl = null;
            this.originalUrl = null;
            this.progress = 0;
            this.scanResult = null;
            this.warnings = [];
            this.errorMessage = '';
            this.showOriginal = false;
        },

        /**
         * Toggle viewing original vs compressed image
         */
        viewOriginal() {
            this.showOriginal = !this.showOriginal;
        },

        /**
         * Get confidence CSS class for a field
         */
        confidenceClass(fieldName) {
            if (!this.scanResult?.confidence) return '';
            const score = this.scanResult.confidence[fieldName] ?? -1;
            if (score >= 0.8) return 'confidence-high-border';
            if (score >= 0.5) return 'confidence-medium-border';
            if (score >= 0) return 'confidence-low-border';
            return '';
        },

        /**
         * Get confidence label for a field
         */
        confidenceLabel(fieldName) {
            if (!this.scanResult?.confidence) return '';
            const score = this.scanResult.confidence[fieldName] ?? -1;
            if (score >= 0.8) return 'High';
            if (score >= 0.5) return 'Medium';
            if (score >= 0) return 'Low';
            return '';
        },

        // ------ Private Helpers ------

        _readAsDataURL(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = () => reject(new Error('Failed to read file.'));
                reader.readAsDataURL(file);
            });
        },

        async _uploadWithProgress(formData) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        // Map upload progress to 25-80% of our progress bar
                        this.progress = 25 + Math.round((e.loaded / e.total) * 55);
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            this.progress = 90;
                            resolve(JSON.parse(xhr.responseText));
                        } catch {
                            reject(new Error('Invalid response from server.'));
                        }
                    } else {
                        try {
                            const err = JSON.parse(xhr.responseText);
                            resolve({ success: false, message: err.message || 'Server error.' });
                        } catch {
                            reject(new Error(`Server returned status ${xhr.status}`));
                        }
                    }
                });

                xhr.addEventListener('error', () => reject(new Error('Network error.')));
                xhr.addEventListener('abort', () => reject(new Error('Upload cancelled.')));

                xhr.open('POST', this.apiEndpoint);
                if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.send(formData);
            });
        }
    };
}
