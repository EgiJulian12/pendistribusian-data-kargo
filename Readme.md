# Sistem Manajemen Logistik dan Distribusi Kargo Nasional

Tugas UAS Pemrosesan Data Terdistribusi — sistem basis data terdistribusi untuk logistik kargo nasional. Mengimplementasikan fragmentasi horizontal, replikasi master-slave, Two-Phase Commit (2PC), federasi FDW, caching Redis, dan reverse proxy Nginx.

---

## Arsitektur Singkat

| Komponen | Peran |
|---|---|
| 4× PostgreSQL | 1 Peladen Pusat (master data) + 3 Peladen Regional (Barat/Tengah/Timur, data kargo terfragmentasi) |
| postgres_fdw | Federasi query lintas region (full mesh) |
| Logical Replication | Replikasi otomatis data master dari Pusat ke 3 region |
| Redis | Caching pelacakan resi + antrean notifikasi |
| Nginx | Reverse proxy di depan aplikasi Laravel |
| Laravel | Aplikasi web dengan login berbasis role |

---

## Struktur Repository

```
.
├── docker-compose.yml
├── nginx/
│   └── nginx.conf
├── web-app/                     ← aplikasi Laravel
│   ├── app/
│   ├── resources/views/
│   ├── routes/web.php
│   ├── config/database.php
│   └── ...
└── README.md
```

---

## Prasyarat

- Docker Desktop
- PHP 8.2+ dan Composer
- Git

---

# BAGIAN A — QUICK START (dari Clone Repository)

## Langkah 1 — Clone Repository

```powershell
git clone <url-repository-anda> sistem-logistik-kargo
cd sistem-logistik-kargo
```

## Langkah 2 — Jalankan Infrastruktur Docker

```powershell
docker compose up -d
docker compose ps
```

Pastikan 6 container aktif: `pg-pusat`, `pg-barat`, `pg-tengah`, `pg-timur`, `redis-barat`, `nginx-main`.

## Langkah 3 — Inisialisasi Database (Manual via psql)

Jalankan urutan berikut **satu kali saja** setelah container Docker aktif.

### 3.1 Aktifkan Two-Phase Commit di 3 region

```powershell
docker exec pg-barat psql -U postgres -d logistik_barat -c "ALTER SYSTEM SET max_prepared_transactions = 10;"
docker exec pg-tengah psql -U postgres -d logistik_tengah -c "ALTER SYSTEM SET max_prepared_transactions = 10;"
docker exec pg-timur psql -U postgres -d logistik_timur -c "ALTER SYSTEM SET max_prepared_transactions = 10;"
```

### 3.2 Aktifkan replikasi logis di Peladen Pusat

```powershell
docker exec pg-pusat psql -U postgres -d logistik_pusat -c "ALTER SYSTEM SET wal_level = logical;"
```

### 3.3 Restart 4 container agar konfigurasi baru aktif

```powershell
docker restart pg-pusat pg-barat pg-tengah pg-timur
```

Tunggu ±5 detik, lalu pastikan semua kembali "Up": `docker compose ps`

### 3.4 Masuk ke Peladen Pusat, buat skema & data master

```powershell
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

CREATE TABLE transaksi_log (
    id SERIAL PRIMARY KEY,
    gid VARCHAR(100) NOT NULL,
    nomor_resi VARCHAR(30) NOT NULL,
    region_asal VARCHAR(50) NOT NULL,
    region_tujuan VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    pesan TEXT,
    waktu TIMESTAMP NOT NULL DEFAULT NOW()
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

### 3.5 Masuk ke Region Barat, buat skema regional + subscription

```powershell
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

-- Tabel kosong penampung hasil replikasi dari Peladen Pusat
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

INSERT INTO kargo (nomor_resi, asal_pengiriman, tujuan_pengiriman, berat, status, region_fragment) VALUES
('RESIBRT001', 'Bandung', 'Semarang', 2.5, 'Diproses', 'Barat'),
('RESIBRT002', 'Jakarta', 'Surabaya', 1.2, 'Diproses', 'Barat');

CREATE SUBSCRIPTION sub_master_barat
CONNECTION 'host=pg-pusat port=5432 dbname=logistik_pusat user=postgres password=pusat123'
PUBLICATION pub_master_data;
```

Keluar: `\q`

### 3.6 Masuk ke Region Tengah, ulangi dengan penyesuaian

```powershell
docker exec -it pg-tengah psql -U postgres -d logistik_tengah
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
    region_fragment VARCHAR(10) NOT NULL DEFAULT 'Tengah'
);

CREATE TABLE riwayat_pengiriman (
    id_riwayat UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_kargo UUID NOT NULL REFERENCES kargo(id_kargo),
    id_gudang UUID,
    waktu_update TIMESTAMP NOT NULL DEFAULT NOW(),
    status VARCHAR(30) NOT NULL,
    keterangan TEXT
);

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

INSERT INTO kargo (nomor_resi, asal_pengiriman, tujuan_pengiriman, berat, status, region_fragment) VALUES
('RESITGH001', 'Semarang', 'Bandung', 3.0, 'Diproses', 'Tengah'),
('RESITGH002', 'Yogyakarta', 'Surabaya', 1.8, 'Diproses', 'Tengah');

CREATE SUBSCRIPTION sub_master_tengah
CONNECTION 'host=pg-pusat port=5432 dbname=logistik_pusat user=postgres password=pusat123'
PUBLICATION pub_master_data;
```

Keluar: `\q`

### 3.7 Masuk ke Region Timur, ulangi dengan penyesuaian

```powershell
docker exec -it pg-timur psql -U postgres -d logistik_timur
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
    region_fragment VARCHAR(10) NOT NULL DEFAULT 'Timur'
);

CREATE TABLE riwayat_pengiriman (
    id_riwayat UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_kargo UUID NOT NULL REFERENCES kargo(id_kargo),
    id_gudang UUID,
    waktu_update TIMESTAMP NOT NULL DEFAULT NOW(),
    status VARCHAR(30) NOT NULL,
    keterangan TEXT
);

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

INSERT INTO kargo (nomor_resi, asal_pengiriman, tujuan_pengiriman, berat, status, region_fragment) VALUES
('RESITMR001', 'Surabaya', 'Bandung', 2.2, 'Diproses', 'Timur'),
('RESITMR002', 'Denpasar', 'Semarang', 1.5, 'Diproses', 'Timur');

CREATE SUBSCRIPTION sub_master_timur
CONNECTION 'host=pg-pusat port=5432 dbname=logistik_pusat user=postgres password=pusat123'
PUBLICATION pub_master_data;
```

Keluar: `\q`

### 3.8 Setup FDW mesh penuh — dari Region Barat

```powershell
docker exec -it pg-barat psql -U postgres -d logistik_barat
```

```sql
CREATE EXTENSION IF NOT EXISTS postgres_fdw;

CREATE SERVER server_tengah FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-tengah', port '5432', dbname 'logistik_tengah');
CREATE USER MAPPING FOR postgres SERVER server_tengah OPTIONS (user 'postgres', password 'tengah123');
CREATE FOREIGN TABLE kargo_tengah (
    id_kargo UUID, nomor_resi VARCHAR(30), id_pelanggan UUID,
    asal_pengiriman VARCHAR(50), tujuan_pengiriman VARCHAR(50),
    tanggal_kirim DATE, berat NUMERIC(10,2), status VARCHAR(30), region_fragment VARCHAR(10)
) SERVER server_tengah OPTIONS (schema_name 'public', table_name 'kargo');

CREATE SERVER server_timur FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-timur', port '5432', dbname 'logistik_timur');
CREATE USER MAPPING FOR postgres SERVER server_timur OPTIONS (user 'postgres', password 'timur123');
CREATE FOREIGN TABLE kargo_timur (
    id_kargo UUID, nomor_resi VARCHAR(30), id_pelanggan UUID,
    asal_pengiriman VARCHAR(50), tujuan_pengiriman VARCHAR(50),
    tanggal_kirim DATE, berat NUMERIC(10,2), status VARCHAR(30), region_fragment VARCHAR(10)
) SERVER server_timur OPTIONS (schema_name 'public', table_name 'kargo');
```

### 3.9 Setup FDW — dari Region Tengah

```powershell
docker exec -it pg-tengah psql -U postgres -d logistik_tengah
```

```sql
CREATE EXTENSION IF NOT EXISTS postgres_fdw;

CREATE SERVER server_barat FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-barat', port '5432', dbname 'logistik_barat');
CREATE USER MAPPING FOR postgres SERVER server_barat OPTIONS (user 'postgres', password 'barat123');
CREATE FOREIGN TABLE kargo_barat (
    id_kargo UUID, nomor_resi VARCHAR(30), id_pelanggan UUID,
    asal_pengiriman VARCHAR(50), tujuan_pengiriman VARCHAR(50),
    tanggal_kirim DATE, berat NUMERIC(10,2), status VARCHAR(30), region_fragment VARCHAR(10)
) SERVER server_barat OPTIONS (schema_name 'public', table_name 'kargo');

CREATE SERVER server_timur FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-timur', port '5432', dbname 'logistik_timur');
CREATE USER MAPPING FOR postgres SERVER server_timur OPTIONS (user 'postgres', password 'timur123');
CREATE FOREIGN TABLE kargo_timur (
    id_kargo UUID, nomor_resi VARCHAR(30), id_pelanggan UUID,
    asal_pengiriman VARCHAR(50), tujuan_pengiriman VARCHAR(50),
    tanggal_kirim DATE, berat NUMERIC(10,2), status VARCHAR(30), region_fragment VARCHAR(10)
) SERVER server_timur OPTIONS (schema_name 'public', table_name 'kargo');
```

### 3.10 Setup FDW — dari Region Timur

```powershell
docker exec -it pg-timur psql -U postgres -d logistik_timur
```

```sql
CREATE EXTENSION IF NOT EXISTS postgres_fdw;

CREATE SERVER server_barat FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-barat', port '5432', dbname 'logistik_barat');
CREATE USER MAPPING FOR postgres SERVER server_barat OPTIONS (user 'postgres', password 'barat123');
CREATE FOREIGN TABLE kargo_barat (
    id_kargo UUID, nomor_resi VARCHAR(30), id_pelanggan UUID,
    asal_pengiriman VARCHAR(50), tujuan_pengiriman VARCHAR(50),
    tanggal_kirim DATE, berat NUMERIC(10,2), status VARCHAR(30), region_fragment VARCHAR(10)
) SERVER server_barat OPTIONS (schema_name 'public', table_name 'kargo');

CREATE SERVER server_tengah FOREIGN DATA WRAPPER postgres_fdw
OPTIONS (host 'pg-tengah', port '5432', dbname 'logistik_tengah');
CREATE USER MAPPING FOR postgres SERVER server_tengah OPTIONS (user 'postgres', password 'tengah123');
CREATE FOREIGN TABLE kargo_tengah (
    id_kargo UUID, nomor_resi VARCHAR(30), id_pelanggan UUID,
    asal_pengiriman VARCHAR(50), tujuan_pengiriman VARCHAR(50),
    tanggal_kirim DATE, berat NUMERIC(10,2), status VARCHAR(30), region_fragment VARCHAR(10)
) SERVER server_tengah OPTIONS (schema_name 'public', table_name 'kargo');
```

### 3.11 Verifikasi replikasi & FDW berjalan

```powershell
docker exec -it pg-barat psql -U postgres -d logistik_barat -c "SELECT * FROM gudang;"
docker exec -it pg-barat psql -U postgres -d logistik_barat -c "SELECT * FROM kargo_tengah;"
```

Kalau muncul data di kedua query itu, replikasi dan FDW sudah berjalan dengan benar.

## Langkah 4 — Setup Aplikasi Laravel

Karena `web-app/` sudah ikut ter-*clone* (bukan project kosong), **tidak perlu** `composer create-project` lagi — cukup install dependency dari `composer.json` yang sudah ada:

```powershell
cd web-app
composer install
copy .env.example .env
php artisan key:generate
```

### Isi/pastikan `.env` berisi konfigurasi berikut:

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

> `.env` biasanya di-*gitignore* sehingga tidak ikut ter-*clone* — salin dari `.env.example` lalu sesuaikan seperti di atas. Konfigurasi 4 koneksi database custom (`pgsql_barat`, dst) di `config/database.php` **sudah tersedia** di repo (lihat Bagian B.3 untuk isinya sebagai referensi bila perlu diperbaiki).

### Buat akun demo

```powershell
php artisan db:seed --class=DemoUsersSeeder
```

Otomatis membuat 4 akun (satu per role) dengan password di-*hash* langsung oleh Laravel — tidak perlu generate hash manual.

## Langkah 5 — Jalankan Laravel

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

**Biarkan terminal ini tetap terbuka.** Flag `--host=0.0.0.0` wajib, karena Nginx (berjalan di dalam Docker) mengakses Laravel dari luar `127.0.0.1`.

## Langkah 6 — Akses Aplikasi

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

## Daftar Fitur per Role

- **Pelanggan** — Lacak Kargo, Riwayat Pengiriman
- **Petugas Gudang** — Lacak Kargo, Tambah Kargo, Riwayat, Pindah Kargo (2PC), Kapasitas Gudang, Notifikasi
- **Administrator Pusat** — Dashboard Monitoring Replikasi, Kelola Tarif, Log Audit Transaksi
- **Eksekutif** — Dashboard Agregat Nasional (grafik)

---

## Uji Coba Fitur Inti (untuk demo/verifikasi)

**1. Distributed Tracking Query** — buka `/tracking`, cari `RESIBRT001` atau `RESITMR001`. Cari resi yang sama dua kali untuk melihat label berubah dari "Database" ke "Cache Redis".

**2. Two-Phase Commit** — login sebagai Petugas, buka `/pindah-kargo`, pindahkan `RESITGH002` ke region lain. Verifikasi langsung di dua database:
```powershell
docker exec -it pg-tengah psql -U postgres -d logistik_tengah -c "SELECT nomor_resi, status FROM kargo WHERE nomor_resi = 'RESITGH002';"
docker exec -it pg-barat psql -U postgres -d logistik_barat -c "SELECT nomor_resi, status FROM kargo WHERE nomor_resi = 'RESITGH002';"
```

**3. Replikasi Live** — login sebagai Admin, buka `/kelola-tarif`, ubah salah satu tarif. Cek otomatis tersinkron:
```powershell
docker exec -it pg-barat psql -U postgres -d logistik_barat -c "SELECT * FROM tarif_pengiriman;"
```

**4. Log Audit** — buka `/log-transaksi` sebagai Admin untuk melihat histori seluruh transaksi 2PC (sukses maupun rollback).

---

# BAGIAN B — REFERENSI KONFIGURASI LENGKAP

Bagian ini berisi isi lengkap file-file konfigurasi utama. Berguna sebagai rujukan bila perlu setup manual dari nol, memperbaiki konfigurasi yang rusak, atau memahami detail teknis di baliknya.

## B.1 — `docker-compose.yml`

```yaml
networks:
  logistik-net:
    driver: bridge

services:
  pg-pusat:
    image: postgres:16
    container_name: pg-pusat
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: pusat123
      POSTGRES_DB: logistik_pusat
    ports:
      - "5440:5432"
    networks:
      - logistik-net
    volumes:
      - pg-pusat-data:/var/lib/postgresql/data

  pg-barat:
    image: postgres:16
    container_name: pg-barat
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: barat123
      POSTGRES_DB: logistik_barat
    ports:
      - "5441:5432"
    networks:
      - logistik-net
    volumes:
      - pg-barat-data:/var/lib/postgresql/data

  pg-tengah:
    image: postgres:16
    container_name: pg-tengah
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: tengah123
      POSTGRES_DB: logistik_tengah
    ports:
      - "5442:5432"
    networks:
      - logistik-net
    volumes:
      - pg-tengah-data:/var/lib/postgresql/data

  pg-timur:
    image: postgres:16
    container_name: pg-timur
    environment:
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: timur123
      POSTGRES_DB: logistik_timur
    ports:
      - "5443:5432"
    networks:
      - logistik-net
    volumes:
      - pg-timur-data:/var/lib/postgresql/data

  redis-barat:
    image: redis:7
    container_name: redis-barat
    ports:
      - "6379:6379"
    networks:
      - logistik-net

  nginx-main:
    image: nginx:alpine
    container_name: nginx-main
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/conf.d/default.conf:ro
    ports:
      - "8080:80"
    networks:
      - logistik-net

volumes:
  pg-pusat-data:
  pg-barat-data:
  pg-tengah-data:
  pg-timur-data:
```

## B.2 — `nginx/nginx.conf`

```nginx
server {
    listen 80;
    location / {
        proxy_pass http://host.docker.internal:8000;
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $http_host;
        proxy_redirect off;
    }
}
```

> **Penting**: gunakan `$http_host`, bukan `$host` — `$host` membuang informasi port, menyebabkan Laravel salah generate URL (kehilangan port `:8080` saat submit form).

## B.3 — Tambahan di `web-app/config/database.php`

Empat blok berikut harus ada di dalam array `'connections' => [ ... ]`, sejajar dengan koneksi `pgsql` bawaan Laravel:

```php
'pgsql_barat' => [
    'driver' => 'pgsql',
    'host' => env('DB_BARAT_HOST', '127.0.0.1'),
    'port' => env('DB_BARAT_PORT', '5441'),
    'database' => env('DB_BARAT_DATABASE', 'logistik_barat'),
    'username' => env('DB_BARAT_USERNAME', 'postgres'),
    'password' => env('DB_BARAT_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
'pgsql_tengah' => [
    'driver' => 'pgsql',
    'host' => env('DB_TENGAH_HOST', '127.0.0.1'),
    'port' => env('DB_TENGAH_PORT', '5442'),
    'database' => env('DB_TENGAH_DATABASE', 'logistik_tengah'),
    'username' => env('DB_TENGAH_USERNAME', 'postgres'),
    'password' => env('DB_TENGAH_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
'pgsql_timur' => [
    'driver' => 'pgsql',
    'host' => env('DB_TIMUR_HOST', '127.0.0.1'),
    'port' => env('DB_TIMUR_PORT', '5443'),
    'database' => env('DB_TIMUR_DATABASE', 'logistik_timur'),
    'username' => env('DB_TIMUR_USERNAME', 'postgres'),
    'password' => env('DB_TIMUR_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
'pgsql_pusat' => [
    'driver' => 'pgsql',
    'host' => env('DB_PUSAT_HOST', '127.0.0.1'),
    'port' => env('DB_PUSAT_PORT', '5440'),
    'database' => env('DB_PUSAT_DATABASE', 'logistik_pusat'),
    'username' => env('DB_PUSAT_USERNAME', 'postgres'),
    'password' => env('DB_PUSAT_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'search_path' => 'public',
    'sslmode' => 'prefer',
],
```

## B.4 — Isi lengkap `web-app/routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\LogController;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/beranda', function () {
    return view('beranda');
})->middleware('auth')->name('beranda');

Route::get('/tracking', [TrackingController::class, 'index'])->middleware('auth')->name('tracking.index');
Route::post('/tracking', [TrackingController::class, 'search'])->middleware('auth')->name('tracking.search');

Route::get('/pindah-kargo', [TrackingController::class, 'pindahForm'])->middleware('role:petugas')->name('pindah.form');
Route::post('/pindah-kargo', [TrackingController::class, 'pindahProses'])->middleware('role:petugas')->name('pindah.proses');

Route::get('/tambah-kargo', [CargoController::class, 'tambahForm'])->middleware('role:petugas')->name('kargo.tambah.form');
Route::post('/tambah-kargo', [CargoController::class, 'tambahProses'])->middleware('role:petugas')->name('kargo.tambah.proses');

Route::get('/riwayat-kargo', [CargoController::class, 'riwayatForm'])->middleware('auth')->name('kargo.riwayat.form');
Route::post('/riwayat-kargo', [CargoController::class, 'riwayatProses'])->middleware('auth')->name('kargo.riwayat.proses');

Route::get('/kapasitas-gudang', [DashboardController::class, 'kapasitasGudang'])->middleware('role:petugas')->name('kapasitas.gudang');

Route::get('/dashboard-eksekutif', [DashboardController::class, 'eksekutif'])->middleware('role:eksekutif')->name('dashboard.eksekutif');
Route::get('/dashboard-admin', [DashboardController::class, 'adminPusat'])->middleware('role:admin')->name('dashboard.admin');

Route::get('/kelola-tarif', [MasterDataController::class, 'index'])->middleware('role:admin')->name('master.tarif.index');
Route::post('/kelola-tarif/{id}/update', [MasterDataController::class, 'update'])->middleware('role:admin')->name('master.tarif.update');

Route::get('/log-transaksi', [LogController::class, 'transaksi'])->middleware('role:admin')->name('log.transaksi');
Route::get('/notifikasi-terbaru', [LogController::class, 'notifikasiTerbaru'])->middleware('auth')->name('notifikasi.terbaru');
```

## B.5 — Middleware Role

Di `web-app/bootstrap/app.php`, dalam `->withMiddleware()`:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\CheckRole::class,
]);
```

## B.6 — `AppServiceProvider` (Wajib untuk Reverse Proxy)

Di `web-app/app/Providers/AppServiceProvider.php`, method `boot()`:
```php
public function boot(): void
{
    if (config('app.url')) {
        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
    }
}
```

Tanpa ini, form login/aksi lain akan mengarah ke URL tanpa port saat diakses lewat Nginx (`http://localhost/login` alih-alih `http://localhost:8080/login`).

---

# BAGIAN C — TROUBLESHOOTING

| Gejala | Kemungkinan Penyebab | Solusi |
|---|---|---|
| `Connection refused` ke port 5440–5443 | Container Postgres belum jalan / port mapping hilang setelah container di-recreate | `docker compose down && docker compose up -d`, cek dengan `docker port pg-barat` |
| `password authentication failed` | Password di `.env` tidak sesuai dengan `docker-compose.yml` | Cocokkan kembali kedua file |
| `RuntimeException: This password does not use the Bcrypt algorithm` | Hash password dibuat di luar Laravel (mis. Python bcrypt `$2b$`) | Gunakan `DemoUsersSeeder` (`php artisan db:seed --class=DemoUsersSeeder`), atau generate manual via `php artisan tinker` → `Hash::make('password123')` |
| `Target class [App\Http\Controllers\XXX] does not exist` | Nama file controller salah ketik, atau Composer autoload belum refresh | `composer dump-autoload`, `php artisan config:clear` |
| Folder `nginx.conf` ternyata jadi folder kosong, bukan file | Docker otomatis membuat folder saat file volume yang dirujuk belum ada | Hapus foldernya, taruh file `nginx.conf` yang benar (lihat B.2), baru `docker compose up -d` |
| `502 Bad Gateway` di Nginx | `php artisan serve` tidak berjalan, atau tidak pakai `--host=0.0.0.0` | Jalankan ulang: `php artisan serve --host=0.0.0.0 --port=8000`, biarkan terminal tetap terbuka |
| `docker exec nginx-main wget http://host.docker.internal:8000` gagal, padahal `ping` berhasil | Laravel `serve` hanya listen di `127.0.0.1` | Wajib pakai flag `--host=0.0.0.0` |
| Form login mengarah ke URL tanpa port | (a) Nginx memakai `$host` bukan `$http_host`; (b) `AppServiceProvider` belum memanggil `forceRootUrl()` | Terapkan B.2 dan B.6 |
| `Class "Redis" not found` | Laravel memakai driver Redis native, bukan predis | Tambahkan `REDIS_CLIENT=predis` di `.env`, `composer require predis/predis` |
| 2PC gagal dengan error prepared transaction | `max_prepared_transactions` masih `0` | Ulangi Langkah 3.1–3.3 (aktifkan lalu restart container) |
| Setelah edit `.env`, perubahan tidak berpengaruh | Ada cache lama di `bootstrap/cache/config.php` | Hapus manual, lalu `php artisan config:clear` |
| PHP versi salah terpanggil | Beberapa instalasi PHP di komputer (mis. XAMPP + manual) | Cek `Get-Command php -All`, atur urutan PATH |
| Windows Firewall memblokir akses dari Docker | `php.exe` belum diizinkan untuk jaringan Private/Public | Windows Security → Firewall & network protection → Allow an app → izinkan `php.exe` |
| `\dt` menampilkan tabel `users`, `sessions`, `cache` yang tidak diharapkan di database region | Migration bawaan Laravel sempat dijalankan ke koneksi region | Aman dihapus (kecuali tabel `migrations`) |

---

## Catatan Tambahan untuk Windows

- Selalu jalankan `php artisan serve --host=0.0.0.0 --port=8000` — Docker Desktop mengakses Laravel dari "luar" `127.0.0.1` melalui `host.docker.internal`.
- Simpan `php artisan serve` di jendela PowerShell terpisah yang **tidak** dipakai untuk perintah lain.
- Jika `docker-compose.yml` diedit (tambah/hapus service), jalankan `docker compose down` lalu `docker compose up -d`, bukan sekadar `docker restart`.

---

## Catatan Arsitektur & Keterbatasan

- Data kargo (`kargo`, `riwayat_pengiriman`) **difragmentasi** per region — tidak direplikasi penuh, sesuai prinsip alokasi data pada SRS.
- Data master (`gudang`, `tarif_pengiriman`, `kode_pos`) **direplikasi penuh** via Logical Replication, dengan Peladen Pusat sebagai satu-satunya sumber tulis (topologi master-slave).
- Failover otomatis PostgreSQL regional belum diimplementasikan secara fisik — dicatat sebagai keterbatasan versi saat ini.
- Notifikasi menggunakan Redis List + polling JavaScript (bukan Pub/Sub blocking asli), karena model request-response Laravel tidak cocok untuk koneksi Redis yang bersifat *listening* terus-menerus.

Dokumentasi arsitektur lengkap (SRS, ERD, DFD Konteks, Use Case Diagram) tersedia terpisah dari repository ini.
