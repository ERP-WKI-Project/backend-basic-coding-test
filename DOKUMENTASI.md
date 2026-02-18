### Dokumentasi & Catatan Teknis

**Pengujian (Testing):**
Dikarenakan penggunaan fitur spesifik PostgreSQL (seperti `ilike` untuk pencarian _case-insensitive_), pengujian **wajib** menggunakan database PostgreSQL.

- Database SQLite (`:memory:`) tidak didukung untuk _Feature Test_ yang melibatkan query pencarian.
- Silakan ikuti instruksi pada bagian "Cara Menjalankan Test" di atas untuk konfigurasi `.env.testing`.

**Asumsi / Keputusan Desain:**

1. Menambahkan migrasi untuk tabel `machines` (yang tidak disertakan dalam kerangka kode awal) agar aplikasi dapat berjalan dan menyimpan data mesin sesuai kebutuhan soal.
2. Menambahkan kolom `ulid` pada tabel `users` sebagai identifier tambahan.
3. Mengimplementasikan _hybrid login_ untuk autentikasi Backoffice yang memungkinkan login menggunakan `email` atau `employee_number`. Pendekatan ini dipilih karena terdapat kondisi di mana `email` user bernilai `NULL`.
4. Menambahkan kolom `ulid`, `machine_id`, `created_by`, dan `soft_deletes` pada tabel `user_shifts`:
    - `ulid`: Sebagai identifier publik yang aman (non-sequential) untuk keperluan API.
    - `machine_id`: Untuk menghubungkan shift dengan mesin yang digunakan.
    - `created_by`: Untuk _audit trail_ (mengetahui siapa yang membuat data shift).
    - `soft_deletes`: Agar data shift yang dihapus tidak hilang permanen (bisa di-restore jika diperlukan).
5. Menempatkan logika validasi bisnis (_Business Logic_) pada `ShiftService` untuk menjaga integritas data user shift, meliputi:
    - Validasi kesesuaian hari (`validateDayMatching`): Memastikan `shift_date` sesuai dengan hari operasional shift.
    - Validasi ketersediaan user (`validateUserAvailability`): Mencegah user memiliki lebih dari satu shift pada tanggal yang sama.
    - Validasi ketersediaan mesin (`validateMachineAvailability`): Mencegah _double booking_ mesin pada shift dan tanggal yang sama.
6. Pada proses autentikasi mesin (`authenticate machine`), menggunakan **Custom Job Class** tersendiri (bukan _dispatch_ closure/inline) untuk menangani proses _logging_ atau aksi lanjutan. Hal ini dilakukan agar logika antrian lebih terstruktur, mudah di-maintain, dan mendukung penggunaan _custom tags_ untuk monitoring job.
7. Untuk fitur _Manual Entry Log_, `machine_id` dan `user_id` diambil secara otomatis dari data **User Shift** yang sedang aktif, bukan dari _input form_. Hal ini meminimalisir kesalahan input dan memastikan log tercatat sesuai dengan penugasan shift yang valid.
8. Menambahkan validasi ekstra pada `MachineLogService` untuk memastikan integritas data log:
    - **Validasi Sesi Aktif (`validateActiveSession`):** Memastikan user sudah melakukan login pada mesin sebelum mencatat aktivitas lain.
    - **Validasi Shift (`validateShift`):** Memastikan user benar-benar ditugaskan pada mesin tersebut untuk hari ini sebelum diizinkan melakukan operasi apa pun.
9. Untuk endpoint laporan (`ReportController`), menggunakan **Form Request** terpisah (`UserMachineActivityRequest`) alih-alih validasi _inline_. Hal ini dilakukan karena filter laporan cenderung kompleks dan banyak (tanggal, user, mesin, shift), sehingga pemisahan ini menjaga _controller_ tetap bersih dan mudah dibaca.
10. Menambahkan kolom `user_shift_id` pada tabel `machine_logs`. Hal ini bertujuan untuk menghubungkan setiap aktivitas mesin (_log_) secara langsung dengan sesi _shift_ user yang sedang berlangsung, sehingga mempermudah pelacakan dan pelaporan aktivitas per _shift_.

### Cara Menjalankan Test

1. Buat database `basic_coding_test_testing` (atau sesuai preferensi) di PostgreSQL lokal.
2. Salin `.env.testing.example` menjadi `.env.testing`.
3. Sesuaikan konfigurasi DB di `.env.testing`.
4. Jalankan `php artisan test`.

## API Documentation (Postman)

Collection Postman tersedia di: `postman_collection.json`. Anda dapat mengimpor file ini ke Postman untuk menguji endpoint API (Backoffice & Machine).
