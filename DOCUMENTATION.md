# Dokumentasi API Backend Basic Coding Test

## Struktur Proyek

Proyek ini menggunakan Laravel 11 dengan arsitektur API RESTful. Berikut struktur direktori utama:

```
app/
├── DTOs/                    # Data Transfer Objects
├── Enums/                   # Enum definitions
│   └── MachineLog/          # Machine log related enums
├── Http/
│   ├── Controllers/
│   │   ├── BackOffice/      # Backoffice API controllers
│   │   │   ├── AuthController.php
│   │   │   ├── Machine/
│   │   │   ├── Report/
│   │   │   ├── Shift/
│   │   │   └── UserController.php
│   │   └── Machine/         # Machine API controllers
│   ├── Requests/            # Form Request validation
│   └── Resources/           # API Resource transformers
├── Models/                  # Eloquent Models
├── Services/                # Business Logic Layer
│   └── BackOffice/          # Backoffice-specific services
└── Traits/                  # Reusable traits
```

## Endpoint API

### Backoffice API (`/api/backoffice/v1/`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `auth/login` | Login backoffice |
| POST | `auth/logout` | Logout backoffice |
| GET | `user` | List semua user (pagination) |
| POST | `user` | Buat user baru |
| GET | `user/{id}` | Detail user |
| PUT | `user/{id}` | Update user |
| DELETE | `user/{id}` | Hapus user |
| POST | `user/{id}/restore` | Restore user yang dihapus |
| GET | `machine` | List semua mesin (pagination, search) |
| POST | `machine` | Buat mesin baru |
| GET | `machine/{id}` | Detail mesin |
| PUT | `machine/{id}` | Update mesin |
| DELETE | `machine/{id}` | Hapus mesin |
| GET | `shift` | List semua shift (pagination) |
| POST | `shift` | Buat shift baru |
| GET | `shift/{id}` | Detail shift |
| PUT | `shift/{id}` | Update shift |
| DELETE | `shift/{id}` | Hapus shift |
| GET | `user-shift` | List semua penugasan shift |
| POST | `user-shift` | Assign user ke shift |
| GET | `user-shift/{id}` | Detail user shift |
| PUT | `user-shift/{id}` | Update user shift |
| DELETE | `user-shift/{id}` | Hapus user shift |
| GET | `report/user-machine-activity` | Laporan aktivitas user di mesin |

### Machine API (`/api/machine/v1/`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `auth/login` | Login mesin (dengan PIN + machine_code) |
| POST | `auth/logout` | Logout mesin |
| GET | `profile` | Get profile user yang login |
| GET | `log-entry` | List log entries mesin |
| POST | `log-entry` | Buat log entry baru |

## Database Schema

### Tabel `users`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| employee_number | char(6) | Nomor karyawan (unik, indexed) |
| name | varchar | Nama user |
| email | varchar | Email (nullable, unik) |
| password | varchar | Password hashed |
| email_verified_at | timestamp | Verifikasi email |
| created_at, updated_at | timestamp | Timestamp |
| deleted_at | timestamp | Soft delete |

### Tabel `machines`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| ulid | char(26) | ULID identifier |
| code | varchar | Kode mesin (unik) |
| name | varchar | Nama mesin |
| location | varchar | Lokasi mesin |
| status | varchar | Status mesin (active/inactive) |
| created_at, updated_at | timestamp | Timestamp |
| deleted_at | timestamp | Soft delete |

### Tabel `shifts`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| ulid | char(26) | ULID identifier |
| name | varchar | Nama shift |
| day_of_week | tinyint | Hari kerja (0=Minggu, 6=Sabtu) |
| start_time | time | Jam mulai shift |
| end_time | time | Jam selesai shift |
| created_at, updated_at | timestamp | Timestamp |

### Tabel `user_shifts`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| user_id | bigint | FK ke users |
| shift_id | bigint | FK ke shifts |
| shift_date | date | Tanggal shift |
| machine_code | varchar | Kode mesin (nullable) |
| created_at, updated_at | timestamp | Timestamp |
| deleted_at | timestamp | Soft delete |

### Tabel `machine_logs`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| ulid | char(26) | ULID identifier |
| user_id | bigint | FK ke users |
| machine_code | varchar | Kode mesin |
| event | varchar | Jenis event |
| log_message | text | Isi log |
| created_at, updated_at | timestamp | Timestamp |

## Fitur Utama

### 1. Autentikasi Hybrid

Login backoffice menggunakan email/password. Login mesin menggunakan PIN (employee_number) dan kode mesin.

**Login Backoffice:**
```json
POST /api/backoffice/v1/auth/login
{
    "email": "user@example.com",
    "password": "password"
}
```

**Login Mesin:**
```json
POST /api/machine/v1/auth/login
{
    "pin": "123456",
    "machine_code": "MCH001"
}
```

### 2. Shift Assignment

User ditugaskan ke shift tertentu dengan tanggal dan mesin. Validasi dilakukan untuk memastikan:

- User tidak memiliki shift lebih dari satu pada tanggal yang sama
- Tidak ada double booking mesin pada shift dan tanggal yang sama
- Tanggal shift sesuai dengan hari operasional shift

### 3. Machine Authentication

Login mesin memvalidasi:
- User memiliki shift pada tanggal tersebut
- User ditugaskan pada mesin yang dimaksud
- Waktu login berada dalam jam kerja shift

### 4. Log Entry Management

User dapat mencatat aktivitas di mesin:
- `in`: Check-in ke mesin
- `out`: Check-out dari mesin
- `maintenance`: Log maintenance
- `issue`: Laporan issue

### 5. Laporan Aktivitas

Endpoint `/report/user-machine-activity` menyediakan laporan aktivitas user di mesin dengan filter:
- Tanggal mulai dan selesai
- User ID (optional)
- Kode mesin (optional)
- Pencarian (search)
- Limit pagination

## Enum

### EventEnum
```php
case LOGIN_SUCCESS = 'login_success';
case LOGIN_FAILED = 'login_failed';
```

### LogEntryTypeEnum
```php
case IN = 'in';
case OUT = 'out';
case MAINTENANCE = 'maintenance';
case ISSUE = 'issue';
```

### SystemAbility
```php
case BACKOFFICE = 'backoffice';
case MACHINE = 'machine';
```

## Konfigurasi Testing

Pengujian memerlukan PostgreSQL karena penggunaan fitur `ilike` untuk pencarian case-insensitive.

1. Buat database PostgreSQL:
```bash
createdb basic_coding_test_testing
```

2. Salin file konfigurasi:
```bash
cp .env.testing.example .env.testing
```

3. Update konfigurasi database di `.env.testing` sesuai environment lokal.

4. Jalankan test:
```bash
php artisan test
```

## Catatan Teknis

1. **ULID Identifier**: Menggunakan ULID sebagai identifier publik untuk keamanan (non-sequential).

2. **API Key Abilities**: Sanctum ability digunakan untuk memisahkan akses backoffice dan mesin.

3. **Soft Deletes**: Model User, Machine, Shift, dan UserShift menggunakan soft delete untuk recovery data.

4. **PostgreSQL Required**: Fitur `ilike` hanya tersedia di PostgreSQL, tidak didukung SQLite untuk testing.
