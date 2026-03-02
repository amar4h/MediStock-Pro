# MediStock Pro - Architecture Design Document

---

## STEP 1: BACKEND STACK RECOMMENDATION

### Verdict: PHP 8.2 + Laravel 11 + MySQL 8

| Layer            | Choice                        | Rationale                                                    |
|------------------|-------------------------------|--------------------------------------------------------------|
| **Language**     | PHP 8.2+                      | Native on every Hostinger shared plan, zero config needed    |
| **Framework**    | Laravel 11                    | MVC, Eloquent ORM, migrations, queues, built-in auth        |
| **Database**     | MySQL 8.0                     | Provided by Hostinger, mature, great indexing                |
| **Auth**         | Laravel Sanctum (session)     | Cookie/session auth, no daemon needed (unlike Passport/JWT)  |
| **Frontend**     | Blade + Alpine.js + Tailwind  | Server-rendered, mobile-first, no Node runtime on server     |
| **Build (dev)**  | Vite (local dev only)         | Compiles CSS/JS locally, deploy compiled assets              |
| **Cache**        | File cache (→ Redis on VPS)   | File driver works on shared; swap driver config on VPS       |
| **Queue**        | Database driver (→ Redis on VPS) | No daemon needed; cron-based `queue:work` via Hostinger cron |
| **Search**       | MySQL FULLTEXT + LIKE         | No Elasticsearch needed; sufficient for 10k items            |
| **PDF**          | DomPDF (pure PHP)             | Invoice generation, no wkhtmltopdf binary needed             |
| **Barcode**      | picqer/php-barcode-generator  | Pure PHP, no system dependencies                             |

### Why NOT Node.js?
- Hostinger shared hosting does not provide persistent Node.js processes
- No `pm2`, no `forever`, no process supervisor
- PHP runs natively via Apache mod_php — zero friction

### VPS Migration Path
- Swap `CACHE_DRIVER=file` → `redis`, `QUEUE_CONNECTION=database` → `redis`
- Add Horizon for queue monitoring
- Add nginx + PHP-FPM for better performance
- Optionally add Meilisearch for full-text search
- No code changes required — only `.env` config changes

---

## STEP 2: COMPLETE DATABASE SCHEMA

### Naming Conventions
- All tables: `snake_case`, plural
- All columns: `snake_case`
- Every business table has `tenant_id` (foreign key)
- Soft deletes on critical tables (`deleted_at`)
- Timestamps on all tables (`created_at`, `updated_at`)

---

### 2.1 TENANT & AUTH TABLES

```sql
-- ============================================
-- TENANTS
-- ============================================
CREATE TABLE tenants (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,              -- Store name
    slug                VARCHAR(100) NOT NULL UNIQUE,       -- URL identifier
    owner_name          VARCHAR(255) NOT NULL,
    email               VARCHAR(255) NOT NULL UNIQUE,
    phone               VARCHAR(20) NOT NULL,
    drug_license_no     VARCHAR(100) NULL,
    gstin               VARCHAR(20) NULL,
    address_line1       VARCHAR(255) NULL,
    address_line2       VARCHAR(255) NULL,
    city                VARCHAR(100) NULL,
    state               VARCHAR(100) DEFAULT 'Maharashtra',
    pincode             VARCHAR(10) NULL,
    subscription_status ENUM('active','inactive','trial','expired') DEFAULT 'trial',
    subscription_plan   VARCHAR(50) NULL,
    trial_ends_at       TIMESTAMP NULL,
    subscription_ends_at TIMESTAMP NULL,
    settings            JSON NULL,                          -- Tenant-level config
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    INDEX idx_tenants_slug (slug),
    INDEX idx_tenants_subscription (subscription_status)
);

-- ============================================
-- ROLES (seeded per tenant)
-- ============================================
CREATE TABLE roles (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(50) NOT NULL,                       -- owner, store_manager, pharmacist, cashier
    guard_name  VARCHAR(50) DEFAULT 'web',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_roles_tenant_name (tenant_id, name)
);

-- ============================================
-- PERMISSIONS
-- ============================================
CREATE TABLE permissions (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,               -- e.g. items.create, sales.void
    module      VARCHAR(50) NOT NULL,                       -- e.g. items, sales, purchases
    guard_name  VARCHAR(50) DEFAULT 'web',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- ROLE ↔ PERMISSION PIVOT
-- ============================================
CREATE TABLE role_permissions (
    role_id       BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================
-- USERS
-- ============================================
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    role_id         BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NULL,
    password        VARCHAR(255) NOT NULL,                  -- bcrypt hash
    is_active       TINYINT(1) DEFAULT 1,
    last_login_at   TIMESTAMP NULL,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    UNIQUE KEY uq_users_tenant_email (tenant_id, email),
    INDEX idx_users_tenant (tenant_id)
);
```

---

### 2.2 ITEM MASTER & CATEGORIES

```sql
-- ============================================
-- CATEGORIES
-- ============================================
CREATE TABLE categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(255) NOT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_categories_tenant_name (tenant_id, name)
);

-- ============================================
-- MANUFACTURERS
-- ============================================
CREATE TABLE manufacturers (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(255) NOT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_manufacturers_tenant_name (tenant_id, name)
);

-- ============================================
-- ITEMS (ITEM MASTER)
-- ============================================
CREATE TABLE items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    category_id     BIGINT UNSIGNED NULL,
    manufacturer_id BIGINT UNSIGNED NULL,
    name            VARCHAR(255) NOT NULL,
    composition     VARCHAR(500) NULL,                      -- Salt composition
    hsn_code        VARCHAR(20) NULL,
    gst_percent     DECIMAL(5,2) DEFAULT 0.00,             -- 5, 12, 18, 28
    default_margin  DECIMAL(5,2) DEFAULT 0.00,             -- Default margin %
    barcode         VARCHAR(100) NULL,
    unit            VARCHAR(50) DEFAULT 'strip',            -- strip, bottle, tube, etc.
    schedule        VARCHAR(10) NULL,                       -- H, H1, X, etc.
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE SET NULL,
    INDEX idx_items_tenant (tenant_id),
    INDEX idx_items_barcode (tenant_id, barcode),
    INDEX idx_items_name (tenant_id, name),
    FULLTEXT INDEX ft_items_search (name, composition)
);
```

---

### 2.3 BATCH MANAGEMENT

```sql
-- ============================================
-- BATCHES
-- ============================================
CREATE TABLE batches (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    batch_number    VARCHAR(100) NOT NULL,
    expiry_date     DATE NOT NULL,
    mrp             DECIMAL(10,2) NOT NULL,
    purchase_price  DECIMAL(10,2) NOT NULL,
    selling_price   DECIMAL(10,2) NOT NULL,
    stock_quantity  INT NOT NULL DEFAULT 0,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    INDEX idx_batches_item (tenant_id, item_id),
    INDEX idx_batches_expiry (tenant_id, expiry_date),
    INDEX idx_batches_fifo (tenant_id, item_id, expiry_date, stock_quantity),
    UNIQUE KEY uq_batches_tenant_item_batch (tenant_id, item_id, batch_number)
);
```

---

### 2.4 SUPPLIERS & PURCHASE MODULE

```sql
-- ============================================
-- SUPPLIERS
-- ============================================
CREATE TABLE suppliers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(255) NULL,
    gstin           VARCHAR(20) NULL,
    drug_license_no VARCHAR(100) NULL,
    address         TEXT NULL,
    opening_balance DECIMAL(12,2) DEFAULT 0.00,            -- Opening credit balance
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_suppliers_tenant (tenant_id)
);

-- ============================================
-- PURCHASES
-- ============================================
CREATE TABLE purchases (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    supplier_id         BIGINT UNSIGNED NOT NULL,
    invoice_number      VARCHAR(100) NOT NULL,              -- Supplier invoice #
    invoice_date        DATE NOT NULL,
    subtotal            DECIMAL(12,2) NOT NULL DEFAULT 0,
    gst_amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_amount     DECIMAL(12,2) DEFAULT 0,
    total_amount        DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_mode        ENUM('cash','credit','partial') DEFAULT 'cash',
    paid_amount         DECIMAL(12,2) DEFAULT 0,
    balance_amount      DECIMAL(12,2) DEFAULT 0,
    notes               TEXT NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY uq_purchases_tenant_invoice (tenant_id, supplier_id, invoice_number),
    INDEX idx_purchases_tenant_date (tenant_id, invoice_date)
);

-- ============================================
-- PURCHASE ITEMS
-- ============================================
CREATE TABLE purchase_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id     BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    batch_id        BIGINT UNSIGNED NULL,                   -- Links to batch created/updated
    batch_number    VARCHAR(100) NOT NULL,
    expiry_date     DATE NOT NULL,
    quantity        INT NOT NULL,
    free_quantity   INT DEFAULT 0,                          -- Bonus/free items
    mrp             DECIMAL(10,2) NOT NULL,
    purchase_price  DECIMAL(10,2) NOT NULL,
    selling_price   DECIMAL(10,2) NOT NULL,
    gst_percent     DECIMAL(5,2) DEFAULT 0,
    gst_amount      DECIMAL(10,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    INDEX idx_purchase_items_purchase (purchase_id)
);

-- ============================================
-- PURCHASE RETURNS
-- ============================================
CREATE TABLE purchase_returns (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    purchase_id     BIGINT UNSIGNED NOT NULL,
    return_number   VARCHAR(50) NOT NULL,
    return_date     DATE NOT NULL,
    total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    reason          TEXT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_purchase_returns_tenant (tenant_id, return_date)
);

-- ============================================
-- PURCHASE RETURN ITEMS
-- ============================================
CREATE TABLE purchase_return_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_return_id  BIGINT UNSIGNED NOT NULL,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    item_id             BIGINT UNSIGNED NOT NULL,
    batch_id            BIGINT UNSIGNED NOT NULL,
    quantity            INT NOT NULL,
    purchase_price      DECIMAL(10,2) NOT NULL,
    total_amount        DECIMAL(10,2) NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
);
```

---

### 2.5 CUSTOMERS & SALES MODULE

```sql
-- ============================================
-- CUSTOMERS
-- ============================================
CREATE TABLE customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(255) NULL,
    address         TEXT NULL,
    opening_balance DECIMAL(12,2) DEFAULT 0.00,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_customers_tenant (tenant_id),
    INDEX idx_customers_phone (tenant_id, phone)
);

-- ============================================
-- SALES (INVOICES)
-- ============================================
CREATE TABLE sales (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    customer_id         BIGINT UNSIGNED NULL,               -- NULL = walk-in customer
    invoice_number      VARCHAR(50) NOT NULL,               -- Auto-generated
    invoice_date        DATE NOT NULL,
    subtotal            DECIMAL(12,2) NOT NULL DEFAULT 0,
    gst_amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
    item_discount_total DECIMAL(12,2) DEFAULT 0,            -- Sum of item-level discounts
    invoice_discount    DECIMAL(12,2) DEFAULT 0,            -- Invoice-level discount
    roundoff            DECIMAL(5,2) DEFAULT 0,
    total_amount        DECIMAL(12,2) NOT NULL DEFAULT 0,   -- Final payable
    payment_mode        ENUM('cash','credit','partial','upi') DEFAULT 'cash',
    paid_amount         DECIMAL(12,2) DEFAULT 0,
    balance_amount      DECIMAL(12,2) DEFAULT 0,
    status              ENUM('completed','returned','partial_return') DEFAULT 'completed',
    doctor_name         VARCHAR(255) NULL,
    patient_name        VARCHAR(255) NULL,
    notes               TEXT NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY uq_sales_tenant_invoice (tenant_id, invoice_number),
    INDEX idx_sales_tenant_date (tenant_id, invoice_date),
    INDEX idx_sales_customer (tenant_id, customer_id)
);

-- ============================================
-- SALE ITEMS
-- ============================================
CREATE TABLE sale_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id         BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    batch_id        BIGINT UNSIGNED NOT NULL,
    quantity        INT NOT NULL,
    mrp             DECIMAL(10,2) NOT NULL,
    selling_price   DECIMAL(10,2) NOT NULL,
    purchase_price  DECIMAL(10,2) NOT NULL,                 -- Snapshot for profit calc
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    gst_percent     DECIMAL(5,2) DEFAULT 0,
    gst_amount      DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    INDEX idx_sale_items_sale (sale_id),
    INDEX idx_sale_items_profit (tenant_id, item_id, created_at)
);

-- ============================================
-- SALE RETURNS
-- ============================================
CREATE TABLE sale_returns (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    sale_id         BIGINT UNSIGNED NOT NULL,
    return_number   VARCHAR(50) NOT NULL,
    return_date     DATE NOT NULL,
    total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    return_type     ENUM('full','partial') DEFAULT 'partial',
    reason          TEXT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_sale_returns_tenant (tenant_id, return_date)
);

-- ============================================
-- SALE RETURN ITEMS
-- ============================================
CREATE TABLE sale_return_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_return_id  BIGINT UNSIGNED NOT NULL,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    batch_id        BIGINT UNSIGNED NOT NULL,
    quantity        INT NOT NULL,
    selling_price   DECIMAL(10,2) NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
);
```

---

### 2.6 PAYMENTS & FINANCIAL MODULE

```sql
-- ============================================
-- CUSTOMER PAYMENTS (Receivables)
-- ============================================
CREATE TABLE customer_payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    customer_id     BIGINT UNSIGNED NOT NULL,
    sale_id         BIGINT UNSIGNED NULL,                   -- Optional link to invoice
    amount          DECIMAL(12,2) NOT NULL,
    payment_mode    ENUM('cash','upi','bank_transfer','cheque') DEFAULT 'cash',
    payment_date    DATE NOT NULL,
    reference_no    VARCHAR(100) NULL,
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_customer_payments_tenant (tenant_id, payment_date),
    INDEX idx_customer_payments_customer (tenant_id, customer_id)
);

-- ============================================
-- SUPPLIER PAYMENTS (Payables)
-- ============================================
CREATE TABLE supplier_payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    supplier_id     BIGINT UNSIGNED NOT NULL,
    purchase_id     BIGINT UNSIGNED NULL,
    amount          DECIMAL(12,2) NOT NULL,
    payment_mode    ENUM('cash','upi','bank_transfer','cheque') DEFAULT 'cash',
    payment_date    DATE NOT NULL,
    reference_no    VARCHAR(100) NULL,
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_supplier_payments_tenant (tenant_id, payment_date),
    INDEX idx_supplier_payments_supplier (tenant_id, supplier_id)
);

-- ============================================
-- EXPENSE CATEGORIES
-- ============================================
CREATE TABLE expense_categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(255) NOT NULL,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_expense_cat_tenant_name (tenant_id, name)
);

-- ============================================
-- EXPENSES
-- ============================================
CREATE TABLE expenses (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id           BIGINT UNSIGNED NOT NULL,
    expense_category_id BIGINT UNSIGNED NOT NULL,
    amount              DECIMAL(12,2) NOT NULL,
    expense_date        DATE NOT NULL,
    description         TEXT NULL,
    payment_mode        ENUM('cash','upi','bank_transfer','cheque') DEFAULT 'cash',
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_expenses_tenant_date (tenant_id, expense_date)
);
```

---

### 2.7 INVENTORY & AUDIT

```sql
-- ============================================
-- STOCK MOVEMENTS (Ledger for every stock change)
-- ============================================
CREATE TABLE stock_movements (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    batch_id        BIGINT UNSIGNED NOT NULL,
    movement_type   ENUM('purchase','purchase_return','sale','sale_return','discard','adjustment') NOT NULL,
    reference_type  VARCHAR(50) NOT NULL,                   -- e.g. 'purchase', 'sale', 'discard'
    reference_id    BIGINT UNSIGNED NOT NULL,               -- ID of the source record
    quantity         INT NOT NULL,                           -- +ve for in, -ve for out
    stock_before    INT NOT NULL,
    stock_after     INT NOT NULL,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    INDEX idx_stock_movements_item (tenant_id, item_id, created_at),
    INDEX idx_stock_movements_batch (tenant_id, batch_id)
);

-- ============================================
-- STOCK DISCARDS
-- ============================================
CREATE TABLE stock_discards (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    item_id         BIGINT UNSIGNED NOT NULL,
    batch_id        BIGINT UNSIGNED NOT NULL,
    quantity        INT NOT NULL,
    reason          ENUM('expired','damaged','lost','other') NOT NULL,
    notes           TEXT NULL,
    created_by      BIGINT UNSIGNED NOT NULL,
    discard_date    DATE NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_stock_discards_tenant (tenant_id, discard_date)
);

-- ============================================
-- AUDIT LOGS
-- ============================================
CREATE TABLE audit_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NULL,
    action          VARCHAR(50) NOT NULL,                   -- created, updated, deleted, voided
    auditable_type  VARCHAR(100) NOT NULL,                  -- Model class name
    auditable_id    BIGINT UNSIGNED NOT NULL,               -- Record ID
    old_values      JSON NULL,
    new_values      JSON NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(500) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_audit_tenant_date (tenant_id, created_at),
    INDEX idx_audit_auditable (auditable_type, auditable_id)
);

-- ============================================
-- INVOICE NUMBER SEQUENCES
-- ============================================
CREATE TABLE sequences (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   BIGINT UNSIGNED NOT NULL,
    type        VARCHAR(50) NOT NULL,                       -- 'sale', 'purchase_return', 'sale_return'
    prefix      VARCHAR(20) NOT NULL,                       -- 'INV-', 'SR-', 'PR-'
    next_number BIGINT UNSIGNED NOT NULL DEFAULT 1,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_sequences_tenant_type (tenant_id, type)
);
```

---

### 2.8 INVOICE SCAN (OCR)

```sql
-- ============================================
-- INVOICE SCANS (OCR scan history + audit)
-- ============================================
CREATE TABLE invoice_scans (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       BIGINT UNSIGNED NOT NULL,
    user_id         BIGINT UNSIGNED NOT NULL,
    purchase_id     BIGINT UNSIGNED NULL,               -- Links to purchase if created from this scan
    image_path      VARCHAR(500) NOT NULL,               -- Relative storage path
    status          ENUM('processing','completed','partial','failed') DEFAULT 'processing',
    raw_ocr_text    LONGTEXT NULL,                       -- Full OCR output for debugging
    ocr_confidence  DECIMAL(5,4) NULL,                   -- 0.0000 to 1.0000
    extracted_data  JSON NULL,                            -- Parsed structured data
    warnings        JSON NULL,                            -- Array of warning messages
    error_message   TEXT NULL,                            -- Error details if failed
    processing_ms   INT UNSIGNED NULL,                    -- Processing time in milliseconds
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
    INDEX idx_invoice_scans_tenant (tenant_id, created_at),
    INDEX idx_invoice_scans_status (tenant_id, status)
);
```

---

## STEP 3: ER RELATIONSHIP EXPLANATION

### Entity Relationship Map

```
tenants (1) ──────< (M) users
tenants (1) ──────< (M) roles
roles   (1) ──────< (M) users
roles   (M) >─────< (M) permissions        [via role_permissions]

tenants (1) ──────< (M) categories
tenants (1) ──────< (M) manufacturers
tenants (1) ──────< (M) items
categories    (1) ──────< (M) items
manufacturers (1) ──────< (M) items

items   (1) ──────< (M) batches             ← CORE: each item has many batches

tenants (1) ──────< (M) suppliers
suppliers (1) ────< (M) purchases
purchases (1) ────< (M) purchase_items
purchase_items ────> (1) items
purchase_items ────> (1) batches             ← purchase creates/updates batch

purchases (1) ────< (M) purchase_returns
purchase_returns (1) < (M) purchase_return_items

tenants (1) ──────< (M) customers
customers (1) ────< (M) sales               ← NULL customer = walk-in
sales     (1) ────< (M) sale_items
sale_items ────────> (1) items
sale_items ────────> (1) batches             ← sale deducts from specific batch

sales (1) ────────< (M) sale_returns
sale_returns (1) ──< (M) sale_return_items

customers (1) ────< (M) customer_payments
suppliers (1) ────< (M) supplier_payments

tenants (1) ──────< (M) expense_categories
expense_categories (1) < (M) expenses

batches (1) ──────< (M) stock_movements      ← every stock change is logged
items   (1) ──────< (M) stock_discards

tenants (1) ──────< (M) audit_logs
tenants (1) ──────< (M) sequences
tenants (1) ──────< (M) invoice_scans
invoice_scans ────> (1) purchases             ← optional link after purchase created from scan
```

### Key Relationship Rules

| Rule | Explanation |
|------|-------------|
| **Tenant Isolation** | Every business table has `tenant_id`. All queries scoped via Laravel Global Scope. |
| **Batch ↔ Purchase** | Each `purchase_item` creates or updates a `batch`. Batch is the source of truth for stock. |
| **Batch ↔ Sale** | Each `sale_item` must reference a `batch_id`. Stock deducted at batch level. |
| **FIFO by Expiry** | During sale, system selects batch with `earliest expiry_date WHERE stock_quantity > 0 AND expiry_date > NOW()`. |
| **Stock Movement** | Every stock change (purchase, sale, return, discard) creates a `stock_movements` record. |
| **Profit Tracking** | `sale_items.purchase_price` is a snapshot from batch at time of sale — ensures accurate profit even if batch price changes later. |
| **Ledger Pattern** | Customer/Supplier outstanding = `opening_balance + credits - payments`. Calculated, not stored (avoids sync issues). |
| **Soft Deletes** | Items, users, customers, suppliers, sales, purchases use soft deletes — never lose audit trail. |

---

## STEP 4: REST API ENDPOINTS

### Convention
- Base: `/api/v1/`
- All endpoints require authentication (except login/register)
- Tenant resolved from authenticated user's `tenant_id`
- Response format: `{ success: bool, data: {}, message: string }`

---

### 4.1 Auth & Tenant

```
POST   /api/v1/auth/register              Register new tenant + owner
POST   /api/v1/auth/login                 Login → returns session/token
POST   /api/v1/auth/logout                Logout
GET    /api/v1/auth/me                    Current user + tenant info
PUT    /api/v1/auth/password              Change password

GET    /api/v1/tenant/settings            Get tenant settings
PUT    /api/v1/tenant/settings            Update tenant settings
```

### 4.2 User Management

```
GET    /api/v1/users                      List users (owner/manager only)
POST   /api/v1/users                      Create user
GET    /api/v1/users/{id}                 Get user
PUT    /api/v1/users/{id}                 Update user
DELETE /api/v1/users/{id}                 Deactivate user
GET    /api/v1/roles                      List roles
```

### 4.3 Item Master

```
GET    /api/v1/items                      List items (paginated, searchable)
POST   /api/v1/items                      Create item
GET    /api/v1/items/{id}                 Get item + batches
PUT    /api/v1/items/{id}                 Update item
DELETE /api/v1/items/{id}                 Soft delete item
GET    /api/v1/items/search?q=            Search by name/composition/barcode
GET    /api/v1/items/barcode/{code}       Lookup by barcode

GET    /api/v1/categories                 List categories
POST   /api/v1/categories                 Create category
PUT    /api/v1/categories/{id}            Update category
DELETE /api/v1/categories/{id}            Delete category

GET    /api/v1/manufacturers              List manufacturers
POST   /api/v1/manufacturers              Create manufacturer
PUT    /api/v1/manufacturers/{id}         Update manufacturer
DELETE /api/v1/manufacturers/{id}         Delete manufacturer
```

### 4.4 Batch Management

```
GET    /api/v1/items/{itemId}/batches     List batches for item
GET    /api/v1/batches/near-expiry        Near expiry batches (30/60/90 days)
GET    /api/v1/batches/expired            Expired batches
```

### 4.5 Suppliers & Purchases

```
GET    /api/v1/suppliers                  List suppliers
POST   /api/v1/suppliers                  Create supplier
GET    /api/v1/suppliers/{id}             Get supplier + ledger summary
PUT    /api/v1/suppliers/{id}             Update supplier
DELETE /api/v1/suppliers/{id}             Soft delete supplier

GET    /api/v1/purchases                  List purchases (paginated)
POST   /api/v1/purchases                  Create purchase + items + batches
GET    /api/v1/purchases/{id}             Get purchase detail
PUT    /api/v1/purchases/{id}             Update purchase (if unpaid)

POST   /api/v1/purchases/{id}/returns     Create purchase return
GET    /api/v1/purchase-returns            List purchase returns

GET    /api/v1/suppliers/{id}/ledger       Supplier ledger (payments + purchases)
POST   /api/v1/suppliers/{id}/payments     Record supplier payment
```

### 4.6 Customers & Sales

```
GET    /api/v1/customers                  List customers
POST   /api/v1/customers                  Create customer
GET    /api/v1/customers/{id}             Get customer + ledger summary
PUT    /api/v1/customers/{id}             Update customer
DELETE /api/v1/customers/{id}             Soft delete customer

GET    /api/v1/sales                      List sales (paginated)
POST   /api/v1/sales                      Create sale (POS) + batch deduction
GET    /api/v1/sales/{id}                 Get sale detail
GET    /api/v1/sales/{id}/invoice         Get printable invoice data

POST   /api/v1/sales/{id}/returns         Create sale return
GET    /api/v1/sale-returns               List sale returns

GET    /api/v1/customers/{id}/ledger       Customer ledger
POST   /api/v1/customers/{id}/payments     Record customer payment
```

### 4.7 Inventory

```
GET    /api/v1/inventory/stock             Current stock summary (paginated)
GET    /api/v1/inventory/low-stock         Low stock items
GET    /api/v1/inventory/near-expiry       Near expiry (with day filter)
GET    /api/v1/inventory/expired           Expired stock
GET    /api/v1/inventory/dead-stock        Dead stock (no movement in X days)
GET    /api/v1/inventory/movement-analysis Fast/Slow moving items

POST   /api/v1/inventory/discard           Discard stock
GET    /api/v1/inventory/discards          List discards

GET    /api/v1/inventory/movements         Stock movement history
GET    /api/v1/items/{id}/movements        Movements for specific item
```

### 4.8 Reports & Dashboard

```
GET    /api/v1/dashboard                   Owner dashboard data

GET    /api/v1/reports/sales               Sales report (daily/weekly/monthly/annual)
GET    /api/v1/reports/profit              Profit report (daily/monthly)
GET    /api/v1/reports/net-profit          Net profit (sales profit - expenses)
GET    /api/v1/reports/expenses            Expense report

GET    /api/v1/reports/gst-summary         GST summary report
GET    /api/v1/reports/item-wise-profit    Per-item profit breakdown
```

### 4.9 Expenses

```
GET    /api/v1/expense-categories          List expense categories
POST   /api/v1/expense-categories          Create expense category
PUT    /api/v1/expense-categories/{id}     Update expense category

GET    /api/v1/expenses                    List expenses (date filtered)
POST   /api/v1/expenses                    Create expense
PUT    /api/v1/expenses/{id}               Update expense
DELETE /api/v1/expenses/{id}               Delete expense
```

### 4.10 Invoice Scan (OCR Purchase Entry)

```
POST   /api/v1/invoice-scans              Upload invoice image → OCR → parse → return structured data
GET    /api/v1/invoice-scans/{id}          Get scan result
GET    /api/v1/invoice-scans               List scan history (paginated)
DELETE /api/v1/invoice-scans/{id}          Soft-delete scan record
```

---

## STEP 5: FOLDER STRUCTURE (Laravel 11)

```
medistock-pro/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── PurgeExpiredTrials.php          # Cron: deactivate expired trials
│   │       └── NearExpiryNotification.php      # Cron: daily expiry alerts
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── V1/
│   │   │   │       ├── AuthController.php
│   │   │   │       ├── TenantController.php
│   │   │   │       ├── UserController.php
│   │   │   │       ├── ItemController.php
│   │   │   │       ├── CategoryController.php
│   │   │   │       ├── ManufacturerController.php
│   │   │   │       ├── BatchController.php
│   │   │   │       ├── SupplierController.php
│   │   │   │       ├── PurchaseController.php
│   │   │   │       ├── PurchaseReturnController.php
│   │   │   │       ├── CustomerController.php
│   │   │   │       ├── SaleController.php
│   │   │   │       ├── SaleReturnController.php
│   │   │   │       ├── CustomerPaymentController.php
│   │   │   │       ├── SupplierPaymentController.php
│   │   │   │       ├── InventoryController.php
│   │   │   │       ├── ExpenseController.php
│   │   │   │       ├── ExpenseCategoryController.php
│   │   │   │       ├── DashboardController.php
│   │   │   │       ├── ReportController.php
│   │   │   │       └── InvoiceScanController.php
│   │   │   └── Web/
│   │   │       └── PageController.php           # Serves Blade SPA shell
│   │   │
│   │   ├── Middleware/
│   │   │   ├── EnsureTenantActive.php           # Block if subscription expired
│   │   │   ├── SetTenantScope.php               # Apply tenant global scope
│   │   │   └── CheckPermission.php              # Role-based permission check
│   │   │
│   │   ├── Requests/                            # Form Request validation
│   │   │   ├── StorePurchaseRequest.php
│   │   │   ├── StoreSaleRequest.php
│   │   │   ├── StoreItemRequest.php
│   │   │   └── ...
│   │   │
│   │   └── Resources/                           # API Resource transformers
│   │       ├── ItemResource.php
│   │       ├── SaleResource.php
│   │       ├── BatchResource.php
│   │       └── ...
│   │
│   ├── Models/
│   │   ├── Traits/
│   │   │   ├── BelongsToTenant.php              # Auto tenant_id scope trait
│   │   │   └── Auditable.php                    # Auto audit log trait
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   ├── Item.php
│   │   ├── Category.php
│   │   ├── Manufacturer.php
│   │   ├── Batch.php
│   │   ├── Supplier.php
│   │   ├── Purchase.php
│   │   ├── PurchaseItem.php
│   │   ├── PurchaseReturn.php
│   │   ├── PurchaseReturnItem.php
│   │   ├── Customer.php
│   │   ├── Sale.php
│   │   ├── SaleItem.php
│   │   ├── SaleReturn.php
│   │   ├── SaleReturnItem.php
│   │   ├── CustomerPayment.php
│   │   ├── SupplierPayment.php
│   │   ├── ExpenseCategory.php
│   │   ├── Expense.php
│   │   ├── StockMovement.php
│   │   ├── StockDiscard.php
│   │   ├── AuditLog.php
│   │   ├── Sequence.php
│   │   └── InvoiceScan.php
│   │
│   ├── Services/                                # Business logic layer
│   │   ├── TenantService.php
│   │   ├── BatchService.php                     # FIFO logic, stock management
│   │   ├── SaleService.php                      # Sale creation, batch deduction
│   │   ├── PurchaseService.php                  # Purchase + batch creation
│   │   ├── StockService.php                     # Movement tracking, alerts
│   │   ├── InvoiceService.php                   # Number generation, PDF
│   │   ├── InvoiceScanService.php               # Orchestrator: OCR → parse → match
│   │   ├── ItemMatchingService.php              # Fuzzy item matching for OCR
│   │   ├── OCR/
│   │   │   ├── OCRServiceInterface.php          # Swappable OCR provider interface
│   │   │   ├── GoogleVisionOCRService.php       # Google Cloud Vision REST API
│   │   │   └── OCRResult.php                    # OCR response DTO
│   │   ├── InvoiceParser/
│   │   │   ├── InvoiceParserInterface.php       # Parser interface
│   │   │   ├── LLMInvoiceParser.php             # Claude Haiku parser (primary)
│   │   │   ├── RegexInvoiceParser.php           # Regex fallback parser
│   │   │   ├── FallbackInvoiceParser.php        # Primary→Fallback wrapper
│   │   │   ├── ParsedInvoiceDTO.php             # Structured invoice DTO
│   │   │   └── ParsedInvoiceItemDTO.php         # Item-level DTO
│   │   ├── LedgerService.php                    # Customer/Supplier balance calc
│   │   ├── ReportService.php                    # All report queries
│   │   └── DashboardService.php                 # Dashboard aggregations
│   │
│   ├── Observers/                               # Model event hooks
│   │   ├── SaleObserver.php
│   │   ├── PurchaseObserver.php
│   │   └── StockDiscardObserver.php
│   │
│   └── Scopes/
│       └── TenantScope.php                      # Global scope: WHERE tenant_id = ?
│
├── database/
│   ├── migrations/                              # All table migrations (ordered)
│   │   ├── 0001_create_tenants_table.php
│   │   ├── 0002_create_roles_table.php
│   │   ├── 0003_create_permissions_table.php
│   │   ├── ...
│   │   └── 0020_create_audit_logs_table.php
│   │
│   └── seeders/
│       ├── PermissionSeeder.php                 # All permissions
│       ├── DefaultRoleSeeder.php                # Default roles with permissions
│       └── DemoTenantSeeder.php                 # Test data
│
├── routes/
│   ├── api.php                                  # All /api/v1/ routes
│   └── web.php                                  # SPA shell + auth pages
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php                    # Main layout
│   │   ├── components/                          # Blade components
│   │   ├── pages/                               # Page-level Blade views
│   │   │   ├── dashboard.blade.php
│   │   │   ├── items/
│   │   │   ├── sales/
│   │   │   ├── purchases/
│   │   │   ├── inventory/
│   │   │   ├── reports/
│   │   │   └── settings/
│   │   └── pdf/
│   │       └── invoice.blade.php                # Invoice PDF template
│   │
│   ├── css/
│   │   └── app.css                              # Tailwind entry
│   └── js/
│       └── app.js                               # Alpine.js entry
│
├── public/
│   ├── index.php                                # Laravel entry point
│   ├── build/                                   # Compiled assets (Vite)
│   └── .htaccess                                # Apache rewrite rules
│
├── config/
│   └── medistock.php                            # App-specific config
│
├── .env                                         # Environment config
├── composer.json
├── vite.config.js
└── package.json                                 # Dev-only (Tailwind, Alpine, Vite)
```

### Key Architectural Decisions

| Decision | Rationale |
|----------|-----------|
| **Services layer** | Business logic separated from controllers. Controllers stay thin. Easy to test and migrate. |
| **BelongsToTenant trait** | Auto-applies `TenantScope` and auto-sets `tenant_id` on create. Prevents cross-tenant leaks. |
| **Blade + Alpine.js** | Server-rendered pages, Alpine for interactivity (modals, dropdowns, live search). No SPA framework needed. |
| **API controllers** | REST JSON endpoints for AJAX calls from Alpine. Same endpoints usable by future mobile app. |
| **Form Requests** | Validation logic separated from controllers. Reusable, testable. |
| **Observers** | Automatic side effects (stock movements, audit logs) on model events. |

---

## STEP 6: MVP PLAN (Phased Rollout)

### Phase 1: Foundation (Week 1-2)
> Goal: Working auth, tenant setup, item master

| # | Task | Priority |
|---|------|----------|
| 1 | Laravel project setup, Hostinger deployment pipeline | Critical |
| 2 | Database migrations (tenants, users, roles, permissions) | Critical |
| 3 | Tenant registration + login/logout | Critical |
| 4 | Role & permission seeding | Critical |
| 5 | Tenant middleware (scope, active check) | Critical |
| 6 | User management CRUD | High |
| 7 | Category + Manufacturer CRUD | High |
| 8 | Item Master CRUD with search | Critical |
| 9 | Mobile-first layout (Tailwind + Alpine) | Critical |
| 10 | Basic dashboard shell | Medium |

**Deliverable**: Tenant can register, login, manage items.

---

### Phase 2: Purchase & Inventory (Week 3-4)
> Goal: Stock can enter the system

| # | Task | Priority |
|---|------|----------|
| 1 | Supplier CRUD | Critical |
| 2 | Purchase entry (multi-item, batch creation) | Critical |
| 3 | Batch management (auto-create on purchase) | Critical |
| 4 | Stock movement logging | Critical |
| 5 | Current stock view | High |
| 6 | Near-expiry / expired views | High |
| 7 | Purchase return | Medium |
| 8 | Supplier ledger view | Medium |

**Deliverable**: Purchases create batches, stock is tracked.

---

### Phase 3: Sales & Billing (Week 5-6)
> Goal: POS billing is functional

| # | Task | Priority |
|---|------|----------|
| 1 | Customer CRUD | High |
| 2 | Sale/POS interface (mobile-optimized) | Critical |
| 3 | Barcode scan integration | Critical |
| 4 | FIFO batch selection (nearest expiry) | Critical |
| 5 | Item & invoice level discounts | High |
| 6 | GST calculation | Critical |
| 7 | Cash / Credit / Partial payment | High |
| 8 | Invoice generation (PDF-ready) | High |
| 9 | Sale return (full + partial) | Medium |
| 10 | Stock deduction on sale | Critical |

**Deliverable**: Complete billing cycle works end-to-end.

---

### Phase 4: Financial & Reports (Week 7-8)
> Goal: Business insights available

| # | Task | Priority |
|---|------|----------|
| 1 | Customer ledger + payment recording | High |
| 2 | Supplier ledger + payment recording | High |
| 3 | Expense categories + entry | Medium |
| 4 | Profit reports (daily/monthly) | High |
| 5 | Sales reports (daily/weekly/monthly/annual) | High |
| 6 | Net profit (sales - expenses) | Medium |
| 7 | Owner dashboard (all KPIs) | Critical |
| 8 | Low stock alerts | High |
| 9 | Dead stock / movement analysis | Medium |

**Deliverable**: Full reporting suite, dashboard live.

---

### Phase 5: Polish & Production (Week 9-10)
> Goal: Production-ready, hardened

| # | Task | Priority |
|---|------|----------|
| 1 | Audit logs (returns, discards, price changes) | High |
| 2 | Stock discard module | Medium |
| 3 | Invoice print layout optimization | High |
| 4 | Performance optimization (query indexing, eager loading) | Critical |
| 5 | Security audit (CSRF, XSS, SQL injection, tenant isolation) | Critical |
| 6 | Hostinger cron setup (expiry alerts, trial cleanup) | High |
| 7 | Error logging and monitoring | High |
| 8 | User acceptance testing | Critical |
| 9 | Backup strategy documentation | Medium |
| 10 | Production deployment | Critical |

**Deliverable**: MediStock Pro v1.0 live on Hostinger.

---

### Hostinger Deployment Notes

| Concern | Solution |
|---------|----------|
| **Document root** | Point to `/public` directory via Hostinger file manager |
| **PHP version** | Set to 8.2+ via Hostinger PHP config |
| **Composer** | Run locally, upload `vendor/` OR use Hostinger SSH (available on Business plan) |
| **Migrations** | Run via SSH: `php artisan migrate` |
| **Cron jobs** | Hostinger cron panel: `php /home/user/public_html/artisan schedule:run` every minute |
| **Assets** | Compile locally with `npm run build`, upload `public/build/` |
| **SSL** | Free SSL from Hostinger, force HTTPS in `.env` |
| **File permissions** | `storage/` and `bootstrap/cache/` must be writable (775) |
| **Session driver** | `file` (default) — works perfectly on shared hosting |
| **Mail** | Hostinger SMTP or use Laravel's mail driver with external service |

---

### Table Count Summary

| Group | Tables | Count |
|-------|--------|-------|
| Auth & Tenant | tenants, users, roles, permissions, role_permissions | 5 |
| Item Master | categories, manufacturers, items | 3 |
| Batch | batches | 1 |
| Purchase | suppliers, purchases, purchase_items, purchase_returns, purchase_return_items | 5 |
| Sales | customers, sales, sale_items, sale_returns, sale_return_items | 5 |
| Financial | customer_payments, supplier_payments, expense_categories, expenses | 4 |
| Inventory & Audit | stock_movements, stock_discards, audit_logs, sequences | 4 |
| Invoice Scan | invoice_scans | 1 |
| **Total** | | **28 tables** |
