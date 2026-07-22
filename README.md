# Mini Project Management API (SaaS Multi-Tenant)

Backend RESTful API untuk mini Project Management berbasis SaaS Multi-Tenant (versi ringkas dari Asana/Trello) yang dibangun menggunakan **Laravel 11** dan **PostgreSQL**.

---

## 🛠️ Tech Stack & Technical Decisions

- **Framework:** Laravel 11 (PHP 8.2+)
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum (API Token)
- **Queue Driver:** Database Queue (Async Background Jobs)

### Alasan Pemilihan Stack
Laravel dipilih karena menyediakan ekosistem terintegrasi yang sangat matang untuk aplikasi SaaS:
1. **Security & Eloquent ORM:** Kemudahan implementasi *Global Scopes* untuk isolasi tenant yang aman dan konsisten.
2. **Built-in Queue System:** Pengelolaan background job tanpa memerlukan library pihak ketiga.
3. **Robust Testing Tools:** Dukungan bawaan PHPUnit/Pest yang memudahkan pembuatan tes *Feature/Integration* untuk menguji isolasi tenant dan RBAC.

---

## 🏗️ Multi-Tenancy Strategy & Trade-Offs

Strategi multi-tenancy yang dipilih adalah **Row-Level Scoping / Single-Database Multi-Tenancy**. Setiap tabel (*users*, *projects*, *tasks*, *audit_logs*) memiliki kolom `company_id` sebagai *foreign key*.

### Alasan Pemilihan:
- Paling efisien dari segi penggunaan server dan database, cocok untuk SaaS tahap awal hingga menengah.
- Proses skema update dan rollback dapat dilakukan dalam satu kali eksekusi migration tanpa perlu *looping* ke ratusan database/skema tenant.

### Trade-Offs & Mitigasi:
- **Risiko Data Leak (Human Error):** Jika developer lupa menambahkan kondisi `WHERE company_id = ?` pada query, data antar-tenant bisa terakses.
  - *Mitigasi:* Implementasi **Global Scope / Trait `BelongsToCompany`** secara otomatis pada Model, serta penambahan unit test isolasi tenant yang ketat di `TenantIsolationTest`.
- **Performa pada Skala Masif (High Data Volume):** Tabel bisa menjadi sangat besar seiring bertambahnya tenant.
  - *Mitigasi:* Menambahkan *Database Indexing* pada setiap kolom `company_id` dan foreign key relasional.

---

## 🚀 Cara Menjalankan Aplikasi

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
