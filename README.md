# DM Kuliner POS — Sistem Kasir FoodCourt Multi-Vendor

Kasir terpusat untuk foodcourt multi-vendor. Pelanggan bayar di satu kasir,
sistem otomatis menghitung jatah tiap vendor (`harga_dasar`) dan margin owner
(`margin`) per transaksi — tidak perlu lagi hitung manual misah-misah nota tiap sore.

> **Status: Phase 1 (MVP) + Phase 2 (Operasional) selesai.**
>
> - **Phase 1** — Kasir input pesanan multi-vendor, pembayaran cash/QRIS, rekap
>   settlement harian otomatis per vendor.
> - **Phase 2** — Tiket dapur per vendor, toggle sold-out realtime, portal vendor
>   read-only, aturan diskon (margin/vendor/bagi dua), tutup shift + rekonsiliasi
>   kas vs QRIS.

## Model Harga

| Istilah        | Arti                          | Contoh    |
|----------------|-------------------------------|-----------|
| `harga_dasar`  | jatah vendor                  | Rp 10.000 |
| `margin`       | jatah owner                   | Rp 2.000  |
| `harga_jual`   | dibayar pelanggan (auto)      | Rp 12.000 |

`harga_jual = harga_dasar + margin` (dihitung otomatis di model & form).

## Tech Stack

- **Laravel 13** (PHP 8.3+) — `pdo_pgsql` wajib aktif
- **PostgreSQL** (database `dmkuliner_pos`)
- **Filament 4** — panel admin / back-office (master data, laporan settlement)
- **Livewire 3 + Alpine.js** — layar kasir reaktif (cart live, sentuh-cepat)
- **Tailwind CSS v4 + Vite** — styling clean-white (referensi Square/Toast/Loyverse)
- **spatie/laravel-permission** — role `owner`, `manager`, `cashier`, `vendor`

Pembagian: **Filament** = back-office (owner/manajer). **Livewire custom** = layar kasir.

## Setup Lokal

Prasyarat: PHP 8.3+ (`pdo_pgsql`), Composer, Node 18+, PostgreSQL 14+.

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Buat database PostgreSQL (sekali saja)
#    psql:  CREATE DATABASE dmkuliner_pos;
#           CREATE USER dmpos WITH PASSWORD 'dmpos_secret';
#           GRANT ALL PRIVILEGES ON DATABASE dmkuliner_pos TO dmpos;
#    lalu sesuaikan DB_* di .env

# 4. Migrasi + seed (membuat lokasi, 8 vendor, ~38 menu, akun demo)
php artisan migrate --seed
#    atau jika sudah migrate: php artisan import:menu

# 5. Build asset + jalankan
npm run dev          # development (hot reload)
#    atau: npm run build   # production
php artisan serve
```

Buka:
- **Layar kasir:** http://localhost:8000/kasir (atau `/` → login)
- **Panel admin:** http://localhost:8000/admin (owner/manager)
- **Portal vendor:** http://localhost:8000/vendor (role vendor)

### Akun Demo

| Email                    | Password   | Role    | Akses                          |
|--------------------------|------------|---------|--------------------------------|
| owner@dmkuliner.test     | `password` | owner   | Kasir + Admin (semua)          |
| manager@dmkuliner.test   | `password` | manager | Kasir + Admin (semua)          |
| kasir@dmkuliner.test     | `password` | cashier | Hanya layar kasir              |
| vendor@dmkuliner.test    | `password` | vendor  | Portal vendor (`/vendor`)      |

## Import Menu (CSV)

`php artisan import:menu` membaca `database/data/menu-dmkuliner-seed.csv`.

Kolom: `vendor_kode,vendor_nama,kategori,menu,harga_jual,harga_dasar,margin,catatan`

Aturan parsing:
- Baris diawali `#` diabaikan (komentar).
- `harga_dasar` kosong tapi `margin` terisi → `harga_dasar = harga_jual - margin`.
- Keduanya kosong → `harga_dasar = harga_jual`, `margin = 0` (diedit owner via UI).

Pakai CSV lain: `php artisan import:menu /path/ke/file.csv`.

## Logika Settlement

Untuk tiap vendor, pada lokasi & tanggal tertentu, **hanya order `status = paid`**
(void/refund otomatis tidak dihitung):

```
totalBaseOwed = SUM(base_price_snapshot   * qty)   // dibayar ke vendor
totalMargin   = SUM(margin_snapshot       * qty)   // keuntungan owner
totalGross    = SUM(selling_price_snapshot * qty)   // total uang masuk
```

⚠️ **Snapshot harga.** `order_items` menyimpan `base_price_snapshot`,
`margin_snapshot`, `selling_price_snapshot` saat transaksi. Kalau harga menu
diubah besok, laporan settlement kemarin **tetap akurat** (sudah ada test-nya).

Lihat di admin: **Laporan → Tutup Hari / Settlement** (filter lokasi + tanggal,
ringkasan margin harian, tabel per vendor, **Export CSV** daftar pembayaran).

## Struktur Penting

```
app/
  Console/Commands/ImportMenu.php        # artisan import:menu
  Livewire/CashierScreen.php             # layar kasir (POS): cart, diskon, shift
  Livewire/Auth/LoginForm.php            # login
  Services/SettlementService.php         # logika rekap settlement
  Services/DiscountAllocator.php         # alokasi diskon ke base/margin per item
  Filament/Resources/                    # admin: Location/Vendor/MenuItem/Order/Shift
  Filament/Pages/TutupHari.php           # halaman settlement + export CSV
  Filament/Vendor/                       # portal vendor (panel terpisah /vendor)
  Providers/Filament/                    # AdminPanelProvider, VendorPanelProvider
  Models/                                # Location, Vendor, MenuItem, Order, OrderItem, Settlement, Shift
database/
  data/menu-dmkuliner-seed.csv           # seed menu DM Kuliner
  migrations/                            # skema (dengan snapshot harga)
resources/views/livewire/cashier-screen.blade.php   # UI kasir
tests/Feature/                           # CashierSettlementTest, AdminPanelTest
```

Jalankan test: `php artisan test`

## Deployment (dm.digisolve.id)

App ini berdiri sendiri (codebase terpisah). Host di subdomain
`dm.digisolve.id` dengan database PostgreSQL terpisah (`dmkuliner_pos`).

### 1. Server (PHP 8.3+ dengan pdo_pgsql)

```bash
sudo apt install php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml \
     php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
php -m | grep pdo_pgsql   # pastikan aktif
```

### 2. Deploy kode & build

```bash
git clone <repo> /var/www/dm-pos && cd /var/www/dm-pos
composer install --no-dev --optimize-autoloader
npm install && npm run build
cp .env.example .env && php artisan key:generate
```

### 3. `.env` produksi

```dotenv
APP_NAME="DM Kuliner POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dm.digisolve.id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=dmkuliner_pos
DB_USERNAME=dmpos
DB_PASSWORD=********
```

```bash
php artisan migrate --force --seed   # seed sekali saja di awal
php artisan config:cache route:cache view:cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Nginx virtual host

```nginx
server {
    listen 80;
    server_name dm.digisolve.id;
    root /var/www/dm-pos/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

### 5. SSL (Let's Encrypt)

```bash
sudo certbot --nginx -d dm.digisolve.id
```

Certbot otomatis menambahkan blok `listen 443 ssl` + redirect HTTP→HTTPS.

> Boleh 1 server PostgreSQL bersama app lain, **asal database-nya terpisah**
> (`dmkuliner_pos`).

## Fitur Phase 2 (operasional)

- **Tiket dapur (KOT)** — di modal struk, tombol *Tiket Dapur* mencetak satu
  tiket per vendor (tiap vendor hanya lihat item miliknya).
- **Toggle sold-out realtime** — owner/manager dari admin, atau vendor dari
  portalnya, bisa matikan menu habis; layar kasir auto-refresh (15 dtk).
- **Portal vendor** (`/vendor`) — panel terpisah read-only: vendor lihat
  penjualan & jatahnya sendiri (per tanggal, rincian per menu) + toggle menu.
- **Aturan diskon** — diskon per transaksi, ditanggung oleh **margin saya**,
  **vendor**, atau **bagi dua**; alokasi disnapshot per item, settlement
  menyesuaikan otomatis.
- **Tutup shift + rekonsiliasi** — buka shift (kas awal), tutup shift hitung
  kas seharusnya (kas awal + penjualan tunai) vs kas fisik → selisih; total
  QRIS terpisah. Riwayat di admin (**Laporan → Shift Kasir**).

## Roadmap

- **Phase 3** — Dashboard multi-lokasi, analitik (jam ramai, menu terlaris),
  pajak PB1, mode offline, audit log.
