# DOKUMENTASI BACKEND REST API (UNTUK APLIKASI ANDROID)
**Aplikasi:** Sistem POS Sparepart & Manajemen Bengkel Multi-Cabang  
**Versi API:** v1  
**Base URL:** `http://localhost:8000/api/v1` (atau IP server jaringan lokal)  
**Header Standar:**
```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token_sanctum}
```

---

## 1. Autentikasi (Sanctum Token)

### 1.1 Login Mobile
* **Method:** `POST`
* **Endpoint:** `/auth/login`
* **Request Body:**
```json
{
  "email": "kasir.sudirman@posbengkel.com",
  "password": "password",
  "device_name": "Xiaomi-Redmi-Note-12"
}
```
* **Response (200 OK):**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
      "id": 3,
      "name": "Siti (Kasir)",
      "email": "kasir.sudirman@posbengkel.com",
      "role": "cashier",
      "role_label": "Kasir / Front Desk",
      "phone": "081234567892",
      "branch": {
        "id": 2,
        "code": "CB01",
        "name": "Bengkel Cabang Sudirman",
        "is_warehouse_central": false
      }
    }
  }
}
```

### 1.2 Profil Pengguna Aktif
* **Method:** `GET`
* **Endpoint:** `/auth/me`

### 1.3 Logout Perangkat
* **Method:** `POST`
* **Endpoint:** `/auth/logout`

---

## 2. Modul Inventori & Gudang (Warehouse Mobile)

### 2.1 Cek Stok Cepat via Barcode (Kamera Scanner HP)
* **Method:** `GET`
* **Endpoint:** `/inventory/quick-check?barcode=8991234560012`
* **Response (200 OK):**
```json
{
  "success": true,
  "message": "Data produk berhasil ditemukan.",
  "data": {
    "product_id": 1,
    "name": "Oli AHM MPX2 Matic 0.8L",
    "sku": "AHM-MPX2-08L",
    "barcode": "8991234560012",
    "unit": "Botol",
    "quality_type": "Original / OEM",
    "category": "Oli & Pelumas Mesin",
    "brand": "Honda (AHM)",
    "bin_location": "RAK-OLI-01",
    "current_stock": 50,
    "min_stock": 10,
    "is_low_stock": false,
    "prices": [
      { "tier_code": "RETAIL", "tier_name": "Harga Eceran (Default)", "price": 56000, "min_qty": 1 },
      { "tier_code": "WHOLESALE", "tier_name": "Harga Grosir", "price": 51000, "min_qty": 6 },
      { "tier_code": "SPECIAL_WHOLESALE", "tier_name": "Harga Spesial Grosir", "price": 48500, "min_qty": 24 }
    ]
  }
}
```

### 2.2 Scan Barcode Stock Opname (Fisik)
* **Method:** `POST`
* **Endpoint:** `/inventory/opname/scan`
* **Request Body:**
```json
{
  "opname_id": 1,
  "barcode": "8991234560012",
  "qty": 1,
  "replace": false
}
```

### 2.3 Riwayat Kartu Stok
* **Method:** `GET`
* **Endpoint:** `/inventory/stock-card?product_id=1&page=1`

---

## 3. Modul Bengkel & PKB (Service Advisor & Mekanik Mobile)

### 3.1 Cari Motor & Riwayat Servis Lintas Cabang
* **Method:** `GET`
* **Endpoint:** `/workshop/vehicles/search?plate_number=B 1234 ABC`
* **Response (200 OK):**
```json
{
  "success": true,
  "message": "Data kendaraan & riwayat servis ditemukan.",
  "data": {
    "vehicle": {
      "id": 1,
      "plate_number": "B 1234 ABC",
      "last_km": 15200,
      "next_service_date": "2026-12-15",
      "motor_model": { "brand": "Honda", "name": "Vario 125 eSP" },
      "customer": { "name": "Budi Santoso", "phone": "08123456789" }
    },
    "history": [
      {
        "id": 10,
        "wo_number": "WO-20261115-A1B2",
        "branch": { "name": "Bengkel Cabang Sudirman" },
        "current_km": 15000,
        "mechanic": { "name": "Budi Santoso" },
        "items": [...]
      }
    ]
  }
}
```

### 3.2 Registrasi Motor & Pemilik Baru
* **Method:** `POST`
* **Endpoint:** `/workshop/vehicles`
* **Request Body:**
```json
{
  "plate_number": "B 9999 XYZ",
  "motor_model_id": 1,
  "customer_name": "Joko Susilo",
  "customer_phone": "081311223344",
  "current_km": 10500
}
```

### 3.3 Buat Tiket PKB Baru
* **Method:** `POST`
* **Endpoint:** `/workshop/work-orders`
* **Request Body:**
```json
{
  "vehicle_id": 1,
  "mechanic_id": 6,
  "current_km": 15500,
  "complaints": "Ganti oli mesin dan tarikan awal berat",
  "diagnosis": "Roller aus, filter udara kotor"
}
```

### 3.4 Tambah Part / Jasa ke PKB
* **Method:** `POST`
* **Endpoint:** `/workshop/work-orders/{id}/items`
* **Request Body (Jasa):**
```json
{
  "item_type": "service",
  "item_id": 1,
  "qty": 1
}
```

### 3.5 Update Status PKB (Mulai / Selesai)
* **Method:** `PATCH`
* **Endpoint:** `/workshop/work-orders/{id}/status`
* **Request Body:**
```json
{
  "status": "in_progress" // atau "ready_for_payment"
}
```

### 3.6 Rekap Komisi Mekanik
* **Method:** `GET`
* **Endpoint:** `/workshop/mechanics/my-commissions`

---

## 4. Modul Kasir Mobile (POS Android)

### 4.1 Buka Shift Kasir
* **Method:** `POST`
* **Endpoint:** `/pos/register/open`
* **Request Body:**
```json
{
  "opening_balance": 200000,
  "notes": "Modal awal shift pagi"
}
```

### 4.2 Checkout Transaksi Lunas (Split Payment)
* **Method:** `POST`
* **Endpoint:** `/pos/checkout`
* **Request Body:**
```json
{
  "cash_register_id": 1,
  "customer_id": 1,
  "price_tier_id": 1,
  "total_gross": 106000,
  "discount_amount": 6000,
  "grand_total": 100000,
  "payments": [
    { "payment_method": "cash", "amount": 60000 },
    { "payment_method": "qris", "amount": 40000, "reference_number": "QRIS-998877" }
  ],
  "items": [
    { "item_type": "product", "item_id": 1, "qty": 1, "unit_price": 56000, "subtotal": 56000 },
    { "item_type": "service", "item_id": 1, "qty": 1, "unit_price": 50000, "subtotal": 50000, "mechanic_id": 6, "commission_amount": 20000 }
  ]
}
```

### 4.3 Tutup Shift Kasir (Rekonsiliasi)
* **Method:** `POST`
* **Endpoint:** `/pos/register/close`
* **Request Body:**
```json
{
  "cash_register_id": 1,
  "physical_balance": 260000
}
```

---

## 5. Modul Keuangan & Pembelian (Owner / Manager Mobile)

### 5.1 Laporan Laba Rugi Real-Time
* **Method:** `GET`
* **Endpoint:** `/finance/profit-loss?start_date=2026-11-01&end_date=2026-11-30`
* **Response (200 OK):**
```json
{
  "success": true,
  "message": "Ringkasan Laba Rugi berhasil dihitung.",
  "data": {
    "revenue": { "parts_revenue": 56000, "services_revenue": 50000, "total_revenue": 106000 },
    "cogs": { "total_cogs": 45000 },
    "gross_profit": { "amount": 61000, "margin_percent": 57.55 },
    "expenses": {
      "mechanic_commissions": 20000,
      "branch_expenses": 150000,
      "opname_loss": 0,
      "total_expenses": 170000
    },
    "net_profit": { "amount": -109000, "margin_percent": -102.83 }
  }
}
```

### 5.2 Konfirmasi Terima Barang PO di Gudang
* **Method:** `POST`
* **Endpoint:** `/finance/purchases/{id}/receive`
