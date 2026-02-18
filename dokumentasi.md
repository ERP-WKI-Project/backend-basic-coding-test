# Asumsi & Keputusan Teknis

Dokumen ini memisahkan antara **Asumsi Bisnis** (kondisi yang diterima apa adanya) dan **Keputusan Teknis** (solusi yang dipilih oleh developer).

---

## I. Asumsi Bisnis & Lingkungan (Assumptions)
*Kondisi, batasan, atau aturan bisnis yang diasumsikan "benar" dari requirement.*

### 1. `employee_number` sebagai Identifier Publik
*   **Asumsi**: `employee_number` diperlakukan sebagai identifier umum (seperti Username/NIK), bukan data rahasia (seperti PIN ATM).
*   **Implikasi**:
    *   Aman diekspos di API response.
    *   Digunakan sebagai route key di URL (`/users/{employee_number}`).
    *   Disimpan *plain* (tidak di-hash) di database.

### 2. `email` Bersifat Opsional
*   **Asumsi**: Tidak semua user (terutama pekerja lapangan/pabrik) memiliki email korporat.
*   **Implikasi**:
    *   Field `email` di database `nullable`.
    *   Login BackOffice tidak bisa mengandalkan email, sehingga menggunakan `employee_number` + `password`.

### 3. Model Autentikasi BackOffice
*   **Asumsi**: Login menggunakan `employee_number` sebagai pengganti Username.
*   **Konteks**:
    *   **Machine Auth**: PIN (`employee_number`) + Mesin Fisik (`machine_code`).
    *   **BackOffice Auth**: Username (`employee_number`) + Password (`password`).

---

## II. Keputusan Teknis & Arsitektur (Decisions)
*Pilihan desain, pola, dan teknologi yang diambil untuk memenuhi requirement.*

### 1. Arsitektur Layered (Service Pattern)
*   **Keputusan**: Memisahkan business logic dari Controller ke Service (`UserService`, dll).
*   **Alasan**: Agar Controller tetap tipis (*skinny*) dan logic bisa di-reuse atau di-test secara terisolasi.

### 2. Data Transfer Object (DTO)
*   **Keputusan**: Menggunakan `readonly class` DTO untuk transfer data.
*   **Alasan**: Menjamin *type safety* dan struktur data yang jelas antar layer, menghindari *magic array*.

### 3. Strategi Validasi & Integritas Data
*   **Validasi Shift**: `UserShift` divalidasi sangat ketat (hari harus cocok dengan shift pattern, mesin harus aktif).
*   **Partial Update**: Menggunakan `array_filter` di DTO untuk menangani update parsial tanpa menimpa data existing dengan `null`.
*   **Validasi Unik**:
    *   **Master Data**: Cek unik menghitung deleted row (mencegah ID reuse).
*   **UserShift**: Cek unik mengabaikan deleted row (slot waktu bisa dipakai ulang).
*   **Proteksi Historis**:
    *   **Delete**: DILARANG untuk jadwal masa lalu (menjaga konsistensi laporan).
    *   **Update**: DIIZINKAN untuk keperluan koreksi data (human error).

### 4. Struktur Data Machine
*   **Keputusan**: Tetap membuat tabel `machines` sebagai Master Data, meskipun `machine_code` sudah ada di `machine_logs`.
*   **Alasan**:
    *   **Identifier**: Menggunakan `code` (string) sebagai route key publik (anti-enumeration).
    *   **Relasi**: Tidak memaksa FK ke tabel log lama untuk menghindari breaking changes.
    *   **Status**: Membutuhkan kolom `status` (`active`/`inactive`) untuk kontrol operasional.

### 5. Strategi Database & Penghapusan
*   **Soft Deletes**:
    *   Diterapkan pada: `User`, `Machine`, `Shift`, `UserShift`.
    *   Alasan: Audit trail dan pemulihan data tidak sengaja.
*   **Immutable (Hard Delete)**:
    *   Diterapkan pada: `MachineLog`.
    *   Alasan: Data log bervolume tinggi dan bersifat *append-only*.
*   **Indexing**:
    *   `MachineLog` di-index pada `created_at` (dan compound index) untuk performa Reporting yang berat di filter tanggal.

### 6. Manajemen Transaksi
*   **Keputusan**: Tidak menggunakan block `DB::transaction()` manual di Service saat ini.
*   **Alasan (YAGNI)**: Mayoritas operasi masih *Single-Entity CRUD* yang atomic. Transaksi akan ditambahkan nanti jika ada operasi *Multi-Write* kompleks.

### 7. Sistem Logging Fleksibel
*   **Keputusan**: Menyimpan `event` log sebagai `string`, bukan Enum.
*   **Alasan**: Agar mesin IoT bisa mengirim tipe event baru (misal error code khusus) tanpa perlu menambahkan Enum.

### 8. Keamanan Autentikasi
*   **Keputusan**: Menggunakan `Hash::check()` manual untuk login API.
*   **Alasan**: Menghindari pembuatan session stateful (`Auth::attempt`) karena API bersifat stateless (Sanctum token).

### 9. Lokalisasi (i18n)
*   **Keputusan**: Menggunakan `__('messages.key')` untuk output pesan.
*   **Alasan**: Memisahkan teks dari logika code, memudahkan support multi-bahasa.

### 10. Standardisasi Response & Pencarian
*   **Format**: JSON konsisten (`success`, `message`, `data`) dan tanggal ISO 8601.
*   **Nested Objects**: Resource mengembalikan objek relasi penuh (User, Shift, Machine) bukan hanya ID.
*   **Deep Search**: Pencarian mencakup kolom relasi (misal: nama user, nama shift, nama mesin) via `whereHas`.
*   **Alasan**: Memudahkan konsumsi data frontend dan mengurangi round-trip request.

### 11. Strategi Testing
*   **Database**: Menggunakan **PostgreSQL** untuk testing (bukan SQLite) untuk paritas production.
*   **Dynamic Time**: Test case shift menggunakan waktu relatif (`now()`) bukan seeder statis, untuk menghindari *flaky test* di jam berbeda.

---

### 12. Cakupan Tes (Test Coverage)
*Detail jumlah dan skenario tes yang telah diimplementasikan.*

*   **User Shift Management** (24 test, 297 assertions): Coverage untuk Create, Update, Delete, Validation, Integrity Check.
*   **User Management** (28 test, 137 assertions): CRUD User, Auth, Password Hashing.
*   **BackOffice Auth** (7 test, 23 assertions): Login, Logout, Guard protection.
*   **Machine Management** (19 test, 135 assertions): CRUD Machine, Status Active/Inactive.
*   **Machine Log Entry** (10 test, 53 assertions): Logging IoT, Flexible event type.
*   **Reporting** (6 test, 42 assertions): Filter tanggal, performa query.

---

## III. Tools & Konvensi

| Tool | Fungsi |
|---|---|
| **Pest PHP** | Testing framework |
| **Laravel Pint** | Code formatting (PSR-12/Laravel) |
| **PostgreSQL** | Database (dev + test) |
| **Sanctum** | API token authentication |

### Commit Convention
Menggunakan **Conventional Commits** (Bahasa Indonesia):
`feat`, `test`, `fix`, `style`, `chore`.
