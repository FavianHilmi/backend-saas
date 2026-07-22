# Mini Project Management API (SaaS Multi-Tenant)

Backend RESTful API untuk mini Project Management berbasis SaaS Multi-Tenant (versi ringkas dari Asana/Trello) yang dibangun menggunakan **Laravel 11** dan **PostgreSQL**.

---

## Tech Stack & Technical Decisions

- **Framework:** Laravel 11 (PHP 8.2+)
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum (API Token)
- **Queue Driver:** Database Queue (Async Background Jobs)

### Alasan Pemilihan Stack

Memutuskan untuk menggunakan Laravel karena lebih familiar dan Laravel sudah menyediakan ekosistem yang matang dan lengkap untuk kebutuhan SaaS:
1. **Security & Eloquent ORM:** Memudahkan implementasi *Global Scopes* untuk isolasi tenant yang aman dan konsisten.
2. **Built-in Queue System:** Pengelolaan background job (seperti pengiriman notifikasi task) tanpa memerlukan library pihak ketiga.
3. **Robust Testing Tools:** Dukungan bawaan PHPUnit/Pest yang memudahkan pembuatan tes *Feature/Integration* untuk menguji isolasi tenant dan RBAC.

---

## Multi-Tenancy Strategy & Trade-Offs

Pendekatan multi-tenancy yang diterapkan di proyek ini adalah Row-Level Scoping (Single-Database Multi-Tenancy). Setiap tabel (*users*, *projects*, *tasks*, *audit_logs*) memiliki kolom `company_id` sebagai *foreign key*.

### Alasan Pemilihan:
- Paling efisien dari segi penggunaan server dan database, cocok untuk SaaS tahap awal sampai menengah.
- Proses skema update dan rollback bisa dilakukan dalam 1x eksekusi migration tanpa perlu *looping* ke ratusan database/skema tenant.

### Trade-Offs & Mitigasi:
- **Risiko Data Leak (Human Error):** Jika developer lupa menambahkan kondisi `WHERE company_id = ?` pada query, data antar-tenant bisa terakses.
  - *Mitigasi:* Implementasi **Global Scope / Trait `BelongsToCompany`** secara otomatis pada Model, serta penambahan unit test isolasi tenant yang ketat di `TenantIsolationTest`.
- **Performa pada Skala Masif (High Data Volume):** Tabel bisa menjadi sangat besar seiring bertambahnya tenant.
  - *Mitigasi:* Menambahkan *Database Indexing* pada setiap kolom `company_id` dan foreign key relasional.

---

## Cara Running Aplikasi

### 1. Prasyarat System
- PHP >= 8.2
- Composer
- PostgreSQL

### 2. Instalasi & Setup Environment
```bash
# Clone repository
git clone <repository-url>
cd backend-saas

# Install dependensi PHP
composer install

# Copy file .env
cp .env.example .env

# Generate Application Key
php artisan key:generate
```

## Konfigurasi Environment
```env
APP_NAME=Laravel
APP_ENV=local
APP_KEY= # Dihasilkan dari command `php artisan key:generate`
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=saas_db
DB_USERNAME=postgres
DB_PASSWORD=postgres

QUEUE_CONNECTION=database
MAIL_MAILER=log
```

## Migration & Seeding
```bash
php artisan migrate --seed
```

## Running Server & Queue Worker
Terminal 1 (Aplikasi):
```bash
php artisan serve
```
Terminal 2 (Queue Worker):
```bash
php artisan queue:work
```

## Menjalankan Automated Tests
```bash
php artisan test
```

## Kredensial Data Dummy (Seeder)

### Tenant 1: Nexus Company
- **Admin**: `admin@nexus.com` / `admin#123`
- **Member**: `member@nexus.com` / `member#123`

### Tenant 2: Vertex Company
- **Admin**: `admin@vertex.com` / `admin#123`

---

## Postman Collection

Untuk mempermudah pengujian seluruh REST API (termasuk skenario multi-tenancy dan RBAC), berkas **Postman Collection** telah disediakan di dalam folder `postman/`:

- `postman/SaaS Backend (Take Home Test).postman_collection.json`

### Cara Penggunaan:

1. **Import ke Postman:**
   - Buka aplikasi Postman.
   - Klik tombol **Import** di pojok kiri atas.
   - Pilih dan unggah file `SaaS Backend (Take Home Test).postman_collection.json` dari folder `postman/`.
2. **Atur Variable (Jika Perlu):**
   - Atur `base_url` ke `http://127.0.0.1:8000/api/v1`.
3. **Autentikasi & Testing:**
   - Jalankan request **`POST /login`** menggunakan salah satu kredensial dari tabel *Seeder*.
   - Gunakan Bearer Token yang didapatkan untuk mengautentikasi request berikutnya (*Projects* & *Tasks*).

---

## Ringkasan API Endpoints

Prefix URL: `/api/v1`

### Authentication
- `POST /register` - Pendaftaran Perusahaan/Tenant baru + Admin.
- `POST /registerMember` - *(Admin Only)* Mendaftarkan member baru di perusahaannya.
- `POST /login` - Login dan dapatkan Bearer Token.
- `POST /logout` - Logout / hapus token.

### Projects
- `GET /projects` - List project milik perusahaan user (Sudah eager loading, bebas N+1).
- `POST /projects` - *(Admin Only)* Buat project baru.
- `GET /projects/{project}` - Detail project.
- `PUT/PATCH /projects/{project}` - Update project.
- `DELETE /projects/{project}` - Hapus project.

### Tasks
- `GET /projects/{project}/tasks` - List task di dalam project tertentu.
- `POST /projects/{project}/tasks` - Buat task baru di satu project.
- `GET /projects/{project}/tasks/{task}` - Detail task.
- `PUT/PATCH /projects/{project}/tasks/{task}` - Update task (Member hanya bisa mengedit task yang di-assign kepadanya).
- `DELETE /projects/{project}/tasks/{task}` - *(Admin Only)* Hapus task.

---

## Fitur Tambahan & Implementasi

- **Audit Trail:** Setiap ada *update* dan *delete* pada Task dicatat ke tabel `audit_logs` dan otomatis melalui *Eloquent Model Events* (`Task::booted`), mencatat perubahan data (before & after).
- **Pencegahan N+1 Query:** Menggunakan *Eager Loading* (`with(['project', 'assignee'])`) di Controller untuk mengoptimalkan performa dan agar kueri tetap efisien pada response list API.
- **Reversible Migrations:** Semua file migration terdapat method `down()` yang bersih untuk proses *rollback* skema database jika dibutuhkan.
- **Penanganan Race Condition & Data Integrity:**
  - Penggunaan `DB::transaction()` di alur eksekusi data yang bersifat kritis.
  - Pemanfaatan *Scoped Route Model Binding* (`->scoped()`) bawaan Laravel untuk memvalidasi antara parent resource Project dan child resource Task secara otomatis di tingkat routing.

---

## Keputusan Teknis & Rencana Pengembangan

### Hal yang Disesuaikan Karena Keterbatasan Waktu:
- Pengiriman notifikasi penugasan task (*task assignment*) tidak dihubungkan ke SMTP eksternal, hanya di-queue secara *asynchronous* dan dicatat ke dalam log (`storage/logs/laravel.log`) menggunakan *Database Queue Driver*.
- Endpoint `GET` saat ini masih membaca langsung dari database tanpa lapisan Redis Cache.

### Rencana Jika Ada Waktu Lebih:
- Penerapa Redis Caching untuk endpoint list data, dan mekanisme *cache invalidation* otomatis saat ada perubahan data (*create/update/delete*).
- Mengubah strategi multi-tenancy dari *Row-Level Scoping* ke Schema-per-Tenant jika skala bisnis bertambah hingga ribuan tenant untuk isolasi data yang lebih rapat.
- Menyiapkan workflow CI/CD Pipeline sederhana menggunakan GitHub Actions untuk memproses *linting* (Laravel Pint) dan *automated testing* otomatis setiap ada Pull Request.
