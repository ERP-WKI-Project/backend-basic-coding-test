# Asumsi & Keputusan Teknis

Dokumen ini berisi asumsi-asumsi yang diambil selama pengerjaan coding test, beserta alasan di balik setiap keputusan teknis.

---

## Asumsi Bisnis

### 1. `employee_number` sebagai Identifier Publik

`employee_number` diperlakukan sebagai **identifier publik** (bukan data rahasia), sehingga:

- Diekspos di API response (`UserResource`)
- Digunakan sebagai route key (`/api/backoffice/v1/users/{employee_number}`)
- Tidak di-hash di database

> **Catatan:** Jika `employee_number` bersifat sensitif (seperti PIN ATM), maka perlu:
> - Di-hash di database
> - Diganti route key-nya ke UUID/ULID
> - Tidak diekspos di API response

### 2. `email` Bersifat Opsional

User dapat dibuat tanpa email (`nullable`). Konsekuensinya:

- Login BackOffice menggunakan `employee_number` + `password` (bukan email)
- Konsisten dengan Machine login yang juga menggunakan `employee_number` (PIN)

### 3. Autentikasi BackOffice

Login BackOffice menggunakan `employee_number` + `password`. Meskipun `employee_number` disebut sebagai PIN di modul Machine, dalam konteks ini PIN berfungsi sebagai **identifier** (seperti username/badge number), bukan sebagai secret. Alasannya:

| Modul | Credential | Keterangan |
|---|---|---|
| **Machine** | PIN + `machine_code` | PIN = identifier, mesin fisik = verifikasi |
| **BackOffice** | PIN + `password` | PIN = identifier, password = secret |

- Semua user pasti memiliki `employee_number` (required), sedangkan `email` bersifat nullable
- Pola ini sama dengan **username + password** pada umumnya — PIN hanya pengganti username

### 4. Verifikasi Password via `Hash::check()`

BackOffice auth menggunakan `Hash::check()` secara manual di Service, bukan `Auth::attempt()`. Alasan:

- `Auth::attempt()` membuat session (stateful), tidak cocok untuk **stateless API** berbasis token
- `Hash::check()` hanya memverifikasi password tanpa side-effect session
- Konsisten dengan arsitektur Sanctum token-based

### 5. Struktur Data Machine

Meskipun tabel `user_shifts` dan `machine_logs` sudah menggunakan `machine_code` sebagai identifier (string), tetap membuat tabel `machines` terpisah sebagai **Master Data**.

- **Identifier**: Menggunakan `code` (string) sebagai route key API publik untuk mencegah data enumeration, sementara ID auto-increment murni untuk relasi internal database.
- **Relasi**: Tidak menambahkan Foreign Key (FK) constraint ke tabel existing (`user_shifts`) untuk menghindari breaking changes pada data lama. Integritas data dijaga di level aplikasi.
- **Status**: Menambahkan kolom `status` (`active`/`inactive`) untuk kontrol operasional sederhana (soft delete digunakan untuk arsip).

---

### 6. Validasi Jadwal Shift (User Shift)

Validasi jadwal shift sangat ketat untuk menjaga integritas data operasional:

- **Konsistensi Hari**: `shift_date` wajib sesuai dengan `day_of_week` dari `shift_id` yang dipilih.
    - Senin (1) harus dipasangkan dengan Shift Pagi/Siang yang aktif di hari Senin.
    - Validasi ini berjalan saat `store` maupun `update` (bahkan saat partial update tanggal/shift saja).
- **Unique Constraint**: 1 user hanya boleh memiliki 1 shift per tanggal.
- **Active Machine Only**: `machine_code` hanya boleh diisi dengan mesin yang statusnya `ACTIVE`. Mesin `INACTIVE` akan ditolak (422).
- **Integrity Check**: `user_id`, `shift_id`, dan `machine_code` divalidasi keberadaannya di database (Foreign Key check).

### 7. Flexible Event Logging
- **Keputusan**: Kolom `event` di tabel `machine_logs` disimpan sebagai `string` (varchar), bukan terbatas pada Enum.
- **Alasan**: Memberikan fleksibilitas penuh bagi mesin IoT untuk mengirimkan tipe event baru (misal: `machine_overheat`, `emergency_stop`, `sensor_fault`) tanpa perlu update/deploy backend setiap kali ada jenis event baru dari vendor mesin.
- **Implementasi**: DTO `MachineLogDto` menerima `string` untuk properti event. `EventEnum` hanya digunakan untuk standarisasi event *internal* system (seperti `login_success`).

---

## Keputusan Arsitektur

### 1. Service Layer Pattern

Business logic dipisahkan dari Controller ke Service (`UserService`). Controller hanya bertanggung jawab untuk:

- Menerima dan memvalidasi request (via Form Request)
- Memanggil Service
- Mengembalikan response (via API Resource)

### 2. Data Transfer Object (DTO)

Menggunakan `readonly class` dengan typed properties untuk transfer data antar layer:

- `CreateUserDto` — data untuk membuat user baru
- `UpdateUserDto` — data untuk memperbarui user, dengan flag `hasEmail` untuk membedakan field yang tidak dikirim vs dikirim sebagai `null`

### 3. Partial Update yang Aman

`UpdateUserDto::toArray()` menggunakan `array_filter` untuk hanya mengirim field yang eksplisit dikirim dalam request. Ini mencegah:

- Field yang tidak dikirim ter-overwrite menjadi `null`
- Hanya `email` yang boleh di-set ke `null` secara eksplisit (via flag `hasEmail`)

### 4. Mass Assignment Protection

Meskipun `BaseAuthenticatable` menggunakan `$guarded = []`, model `User` secara eksplisit mendefinisikan `$fillable` untuk membatasi field yang dapat di-mass-assign:

```php
protected $fillable = ['employee_number', 'name', 'email', 'password'];
```

### 5. Environment Parity untuk Testing

Database testing menggunakan **PostgreSQL** (bukan SQLite in-memory) agar environment test identik dengan production. Konfigurasi:

- `phpunit.xml` → `DB_CONNECTION=pgsql`, `DB_DATABASE=basic_coding_test_testing`
- `.env.testing` → konfigurasi lengkap environment testing

### 6. Standardized API Response

Semua response menggunakan format konsisten via `ApiResponse` trait:

```json
{
    "success": true,
    "message": "Pesan operasi",
    "data": { ... },
    "errors": null
}
```

---

## Test Coverage

### User Shift Management (19 test, 126 assertions)

| Kategori | Jumlah | Skenario |
|---|---|---|
| **Index** | 4 | List semua, filter by user_id, filter by shift_id, filter by shift_date |
| **Store** | 4 | Berhasil, validasi hari mismatch, double booking tanggal, foreign key tidak ada |
| **Show** | 2 | Detail jadwal, 404 not found |
| **Update** | 6 | Success update, mismatch hari/shift, conflict tanggal lain, self-update tanggal sama (ok), mesin inactive (gagal) |
| **Destroy** | 2 | Berhasil hapus, 404 not found |
| **Auth** | 1 | Unauthenticated (401) |

### User Management (28 test, 137 assertions)

| Kategori | Jumlah | Skenario |
|---|---|---|
| **Index** | 4 | List semua, search by name, search by employee_number, search kosong |
| **Store** | 8 | Berhasil, tanpa email, password hashing, validasi kosong/duplikat/format/password |
| **Show** | 2 | Detail user, 404 not found |
| **Update** | 10 | Full update, partial update, self-update email/NIP, set null, validasi duplikat/format/password, 404 |
| **Destroy** | 2 | Soft delete, 404 not found |
| **Auth** | 2 | Unauthenticated (401) |

### BackOffice Auth (7 test, 23 assertions)

| Kategori | Jumlah | Skenario |
|---|---|---|
| **Login** | 5 | Berhasil, password salah (401), user not found (404), validasi kosong (422), format salah (422) |
| **Logout** | 2 | Berhasil, tanpa token (401) |

### Machine Management (19 test, 135 assertions)

| Kategori | Jumlah | Skenario |
|---|---|---|
| **Index** | 4 | List semua, search by name/code, search kosong, paginasi valid |
| **Store** | 5 | Berhasil, default active, validasi kosong/duplikat/format |
| **Show** | 2 | Detail mesin, 404 not found |
| **Update** | 5 | Full update, partial update, self-update code, validasi duplikat, 404 |
| **Destroy** | 2 | Soft delete, 404 not found |
| **Auth** | 1 | Unauthenticated (401) |

### Machine Log Entry (8 test, 37 assertions)

| Kategori | Jumlah | Skenario |
|---|---|---|
| **Index** | 3 | List log (pagination), Filter by machine_code, Filter by search keyword (event & message) |
| **Store** | 5 | Berhasil (std event), Berhasil (custom/flexible event), Gagal (invalid machine), Gagal (empty event), Gagal (machine inactive) |

---

## Tools & Konvensi

| Tool | Fungsi |
|---|---|
| **Pest PHP** | Testing framework |
| **Laravel Pint** | Code formatting (PSR-12/Laravel) |
| **PostgreSQL** | Database (dev + test) |
| **Sanctum** | API token authentication |

### Commit Convention

Menggunakan **Conventional Commits** dalam bahasa Indonesia:

```
feat: deskripsi fitur baru
test: deskripsi test baru
fix: deskripsi perbaikan bug
style: formatting/linting
chore: maintenance
```
