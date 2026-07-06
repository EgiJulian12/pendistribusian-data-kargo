# Sistem Manajemen Logistik dan Distribusi Kargo Nasional

Tugas UAS Pemrosesan Data Terdistribusi — implementasi sistem basis data terdistribusi untuk logistik kargo nasional, mencakup fragmentasi horizontal, replikasi master-slave, Two-Phase Commit (2PC), caching Redis, dan reverse proxy Nginx.

## Arsitektur Singkat

- **4 instance PostgreSQL**: 1 Peladen Pusat (data master) + 3 Peladen Regional (Barat, Tengah, Timur) yang menyimpan data kargo terfragmentasi.
- **postgres_fdw**: federasi antar peladen regional untuk query lintas region.
- **Replikasi Logis (Logical Replication)**: data master (gudang, tarif, kode pos) direplikasi otomatis dari Pusat ke 3 region.
- **Redis**: caching hasil pelacakan resi + Pub/Sub notifikasi.
- **Nginx**: reverse proxy di depan aplikasi Laravel.
- **Laravel**: aplikasi web dengan sistem login berbasis role (Pelanggan, Petugas Gudang, Administrator Pusat, Eksekutif).

---

## Prasyarat

- Docker Desktop (Windows/Mac/Linux)
- PHP 8.2 atau lebih baru
- Composer

---

## 1. Setup Infrastruktur (Docker)

### 1.1 Jalankan seluruh container

Dari folder project (tempat `docker-compose.yml` berada):

```bash
docker compose up -d
```

Ini akan menjalankan:
- `pg-pusat` (port 5440)
- `pg-barat` (port 5441)
- `pg-tengah` (port 5442)
- `pg-timur` (port 5443)
- `redis-barat` (port 6379)
- `nginx-main` (port 8080)

Cek semua container aktif:
```bash
docker compose ps
```

### 1.2 Aktifkan fitur Two-Phase Commit

```bash
docker exec -it pg-barat psql -U postgres -d logistik_barat -c "ALTER SYSTEM SET max_prepared_transactions = 10;"
docker exec -it pg-tengah psql -U postgres -d logistik_tengah -c "ALTER SYSTEM SET max_prepared_transactions = 10;"
docker exec -it pg-timur psql -U postgres -d logistik_timur -c "ALTER SYSTEM SET max_prepared_transactions = 10;"
docker restart pg-barat pg-tengah pg-timur
```

### 1.3 Aktifkan replikasi logis di Peladen Pusat

```bash
docker exec -it pg-pusat psql -U postgres -d logistik_pusat -c "ALTER SYSTEM SET wal_level = logical;"
docker restart pg-pusat
```

---

## 2. Setup Skema & Data

### 2.1 Buat tabel di Peladen Pusat

```bash
docker exec -it pg-pusat psql -U postgres -d logistik_pusat
```

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE gudang (
    id_gudang UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nama_gudang VARCHAR(100) NOT NULL,
    wilayah VARCHAR(50) NOT NULL,
    alamat TEXT,
    kapasitas INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE tarif_pengiriman (
    id_tarif UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wilayah_asal VARCHAR(50) NOT NULL,
    wilayah_tujuan VARCHAR(50) NOT NULL,
    berat_min NUMERIC(10,2) NOT NULL,
    berat_max NUMERIC(10,2) NOT NULL,
    tarif NUMERIC(12,2) NOT NULL
);

CREATE TABLE kode_pos (
    kode_pos VARCHAR(10) PRIMARY KEY,
    wilayah VARCHAR(50) NOT NULL,
    provinsi VARCHAR(50) NOT NULL,
    kota VARCHAR(50) NOT NULL,
    kecamatan VARCHAR(50) NOT NULL
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

INSERT INTO gudang (nama_gudang, wilayah, alamat, kapasitas) VALUES
('Gudang Transit Jakarta', 'Region Barat', 'Jl. Cakung Raya No. 1, Jakarta Timur', 5000),
('Gudang Transit Semarang', 'Region Tengah', 'Jl. Kaligawe No. 10, Semarang', 3000),
('Gudang Transit Surabaya', 'Region Timur', 'Jl. Rungkut Industri No. 5, Surabaya', 4000);

INSERT INTO tarif_pengiriman (wilayah_asal, wilayah_tujuan, berat_min, berat_max, tarif) VALUES
('Barat', 'Tengah', 0, 5, 25000),
('Barat', 'Timur', 0, 5, 40000),
('Tengah', 'Timur', 0, 5, 30000);

INSERT INTO kode_pos (kode_pos, wilayah, provinsi, kota, kecamatan) VALUES
('40123', 'Barat', 'Jawa Barat', 'Bandung', 'Coblong'),
('50123', 'Tengah', 'Jawa Tengah', 'Semarang', 'Semarang Tengah'),
('60123', 'Timur', 'Jawa Timur', 'Surabaya', 'Gubeng');

CREATE PUBLICATION pub_master_data FOR TABLE gudang, tarif_pengiriman, kode_pos;
```

Keluar: `\q`

### 2.2 Buat tabel di tiap region (Barat, Tengah, Timur)

Ulangi untuk `pg-barat`, `pg-tengah`, `pg-timur` (ganti nama database & kode wilayah sesuai region):

```bash
docker exec -it pg-barat psql -U postgres -d logistik_barat
```

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE kargo (
    id_kargo UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_resi VARCHAR(30) UNIQUE NOT NULL,
    id_pelanggan UUID,
    asal_pengiriman VARCHAR(50) NOT NULL,
    tujuan_pengiriman VARCHAR(50) NOT NULL,
    tanggal_kirim DATE NOT NULL DEFAULT CURRENT_DATE,
    berat NUMERIC(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Diproses',
    region_fragment VARCHAR(10) NOT NULL DEFAULT 'Barat'
);

CREATE TABLE riwayat_pengiriman (
    id_riwayat UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_kargo UUID NOT NULL REFERENCES kargo(id_kargo),
    id_gudang UUID,
    waktu_update TIMESTAMP NOT NULL DEFAULT NOW(),
    status VARCHAR(30) NOT NULL,
    keterangan TEXT
);

-- Tabel kosong untuk menampung hasil replikasi (struktur harus sama persis dengan Pusat)
CREATE TABLE gudang (
    id_gudang UUID PRIMARY KEY,
    nama_gudang VARCHAR(100) NOT NULL,
    wilayah VARCHAR(50) NOT NULL,
    alamat TEXT,
    kapasitas INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE tarif_pengiriman (
    id_tarif UUID PRIMARY KEY,
    wilayah_asal VARCHAR(50) NOT NULL,
    wilayah_tujuan VARCHAR(50) NOT NULL,
    berat_min NUMERIC(10,2) NOT NULL,
    berat_max NUMERIC(10,2) NOT NULL,
    tarif NUMERIC(12,2) NOT NULL
);

CREATE TABLE kode_pos (
    kode_pos VARCHAR(10) PRIMARY KEY,
    wilayah VARCHAR(50) NOT NULL,
    provinsi VARCHAR(50) NOT NULL,
    kota VARCHAR(50) NOT NULL,
    kecamatan VARCHAR(50) NOT NULL
);

CREATE SUBSCRIPTION sub_master_barat
CONNECTION 'host=pg-pusat port=5432 dbname=logistik_pusat user=postgres password=pusat123'
PUBLICATION pub_master_data;
```

> Untuk `pg-tengah` dan `pg-timur`, ganti nama subscription (`sub_master_tengah` / `sub_master_timur`) dan `CONNECTION` tetap mengarah ke `pg-pusat`.

### 2.3 Setup Foreign Data Wrapper (full mesh antar region)

Di tiap region, buat koneksi FDW ke 2 region lainnya. Contoh dari `pg-barat`:

```sql
CREATE EXTENSION IF NOT EXISTS postgres_fdw;

CREATE SERVER server_tengah FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-tengah', port '5432', dbname 'logistik_tengah');
CREATE USER MAPPING FOR postgres SERVER server_tengah OPTIONS (user 'postgres', password 'tengah123');
CREATE FOREIGN TABLE kargo_tengah (/* kolom sama seperti tabel kargo */) SERVER server_tengah OPTIONS (schema_name 'public', table_name 'kargo');

-- Ulangi untuk server_timur
```

Lakukan pola yang sama di `pg-tengah` dan `pg-timur` agar terbentuk mesh penuh.

### 2.4 Buat akun demo (di Peladen Pusat)

```bash
docker exec -it pg-pusat psql -U postgres -d logistik_pusat
```

```sql
INSERT INTO users (name, email, password, role) VALUES
('Pelanggan Demo', 'pelanggan@demo.com', '<hash_bcrypt>', 'pelanggan'),
('Petugas Gudang Demo', 'petugas@demo.com', '<hash_bcrypt>', 'petugas'),
('Administrator Pusat Demo', 'admin@demo.com', '<hash_bcrypt>', 'admin'),
('Eksekutif Demo', 'eksekutif@demo.com', '<hash_bcrypt>', 'eksekutif');
```

> Generate hash bcrypt yang valid lewat `php artisan tinker` → `echo Hash::make('password123');` — jangan pakai hash dari luar Laravel karena format bcrypt PHP (`$2y$`) berbeda dari library lain.

---

## 3. Setup Aplikasi Laravel

### 3.1 Install project

```bash
composer create-project laravel/laravel web-app
cd web-app
```

Salin seluruh file Controller, Model, View, Middleware, dan Service yang ada di project ini ke lokasi yang sesuai:

| File | Lokasi |
|---|---|
| `TrackingController.php` | `app/Http/Controllers/` |
| `CargoController.php` | `app/Http/Controllers/` |
| `DashboardController.php` | `app/Http/Controllers/` |
| `AuthController.php` | `app/Http/Controllers/` |
| `MasterDataController.php` | `app/Http/Controllers/` |
| `RegionResolver.php` | `app/Services/` |
| `User.php` | `app/Models/` |
| `CheckRole.php` | `app/Http/Middleware/` |
| `AppServiceProvider.php` | `app/Providers/` |
| `app.blade.php` (layout) | `resources/views/layouts/` |
| `login.blade.php` | `resources/views/auth/` |
| Semua `*.blade.php` lainnya | `resources/views/` |

### 3.2 Konfigurasi `.env`

```env
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql_barat

DB_BARAT_HOST=127.0.0.1
DB_BARAT_PORT=5441
DB_BARAT_DATABASE=logistik_barat
DB_BARAT_USERNAME=postgres
DB_BARAT_PASSWORD=barat123

DB_TENGAH_HOST=127.0.0.1
DB_TENGAH_PORT=5442
DB_TENGAH_DATABASE=logistik_tengah
DB_TENGAH_USERNAME=postgres
DB_TENGAH_PASSWORD=tengah123

DB_TIMUR_HOST=127.0.0.1
DB_TIMUR_PORT=5443
DB_TIMUR_DATABASE=logistik_timur
DB_TIMUR_USERNAME=postgres
DB_TIMUR_PASSWORD=timur123

DB_PUSAT_HOST=127.0.0.1
DB_PUSAT_PORT=5440
DB_PUSAT_DATABASE=logistik_pusat
DB_PUSAT_USERNAME=postgres
DB_PUSAT_PASSWORD=pusat123

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=database
```

Tambahkan 4 koneksi custom (`pgsql_barat`, `pgsql_tengah`, `pgsql_timur`, `pgsql_pusat`) di `config/database.php` mengikuti pola koneksi `pgsql` bawaan Laravel.

### 3.3 Install dependency tambahan

```bash
composer require predis/predis
```

### 3.4 Daftarkan middleware role

Di `bootstrap/app.php`, dalam `->withMiddleware()`:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\CheckRole::class,
]);
```

### 3.5 Definisikan seluruh route

Lihat isi `routes/web.php` pada project untuk daftar lengkap route beserta middleware `auth`/`role:...` yang sesuai untuk masing-masing fitur.

### 3.6 Jalankan Laravel

Jalankan dengan `--host=0.0.0.0` agar dapat diakses dari container Nginx:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Biarkan terminal ini tetap terbuka.**

---

## 4. Akses Aplikasi

Buka browser ke:
```
http://localhost:8080/login
```

### Akun Demo (password: `password123`)

| Role | Email |
|---|---|
| Pelanggan | pelanggan@demo.com |
| Petugas Gudang | petugas@demo.com |
| Administrator Pusat | admin@demo.com |
| Eksekutif | eksekutif@demo.com |

---

## 5. Daftar Fitur per Role

- **Pelanggan**: Lacak Kargo, Riwayat Pengiriman
- **Petugas Gudang**: Lacak Kargo, Tambah Kargo, Riwayat, Pindah Kargo (2PC), Kapasitas Gudang
- **Administrator Pusat**: Dashboard Monitoring Replikasi, Kelola Tarif
- **Eksekutif**: Dashboard Agregat Nasional (grafik)

---

## 6. Troubleshooting Umum

| Gejala | Kemungkinan Penyebab | Solusi |
|---|---|---|
| `Connection refused` ke port 5440-5443 | Container Postgres belum jalan / port mapping hilang | `docker compose down && docker compose up -d` |
| `password authentication failed` | Password di `.env` tidak sesuai dengan `docker-compose.yml` | Cocokkan kembali kedua file |
| Form login mengarah ke URL tanpa port (`localhost/login`) | Laravel tidak memaksa root URL saat di balik reverse proxy | Pastikan `AppServiceProvider.php` memanggil `URL::forceRootUrl()` |
| `502 Bad Gateway` di Nginx | `php artisan serve` tidak berjalan / tidak pakai `--host=0.0.0.0` | Jalankan ulang dengan flag tersebut |
| `Class "Redis" not found` | Laravel memakai driver Redis native, bukan predis | Tambahkan `REDIS_CLIENT=predis` di `.env` |
| 2PC gagal dengan error prepared transaction | `max_prepared_transactions` belum diaktifkan | Jalankan ulang langkah 1.2 |

---

## 7. Struktur Diagram

Dokumentasi arsitektur lengkap (ERD, DFD Konteks, Use Case Diagram) tersedia dalam dokumen SRS terpisah.