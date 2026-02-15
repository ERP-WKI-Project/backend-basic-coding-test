### Cara Menjalankan Test
1. Buat database `basic_coding_test_testing` (atau sesuai preferensi) di PostgreSQL lokal.
2. Salin `.env.testing.example` menjadi `.env.testing`.
3. Sesuaikan konfigurasi DB di `.env.testing`.
4. Jalankan `php artisan test`.

### Setelah Selesai
- Pastikan deskripsi PR menjelaskan ringkas perubahan utama, asumsi, serta cara tes yang sudah dijalankan.

### Dokumentasi & Catatan Teknis
**Pengujian (Testing):**
Dikarenakan penggunaan fitur spesifik PostgreSQL (seperti `ilike` untuk pencarian *case-insensitive*), pengujian **wajib** menggunakan database PostgreSQL.
- Database SQLite (`:memory:`) tidak didukung untuk *Feature Test* yang melibatkan query pencarian.
- Silakan ikuti instruksi pada bagian "Cara Menjalankan Test" di atas untuk konfigurasi `.env.testing`.

## API Documentation (Postman)

Collection Postman tersedia di: `postman_collection.json`. Anda dapat mengimpor file ini ke Postman untuk menguji endpoint API (Backoffice & Machine).
