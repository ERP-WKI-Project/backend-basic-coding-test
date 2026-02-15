## ERP WKI – Basic Coding Test

Panduan singkat untuk mengerjakan tugas **Test 1** – **Test 5** pada repository ini.

### Alur Pengerjaan
1. Fork repository ini ke akun GitHub pribadi Anda.
2. Buat branch baru dari fork dengan nama `test-result-{nick_name}` (ganti `{nick_name}` sesuai inisial/panggilan Anda).
3. Kerjakan seluruh **Test 1** – **Test 5** sesuai instruksi yang ada di project.
4. Pastikan perubahan sudah dipush ke branch tersebut di fork Anda.
5. Buat Pull Request dari branch `test-result-{nick_name}` di fork menuju repository sumber (upstream) ini.

### Kriteria Penilaian
- Arsitektur & Struktur: **30%**
- Business Logic: **30%**
- Database Design: **20%**
- Security & Validation: **10%**
- Kerapian & Dokumentasi: **10%**

### Saran Teknis
- Jaga konsistensi arsitektur (layering, service separation, DTO/Resource) dan struktur folder.
- Lengkapi validasi request dan sanitasi input; perhatikan otorisasi setiap endpoint/aksi.
- Pastikan skema database mendukung relasi dan integritas yang diperlukan oleh requirement.
- Tambahkan test minimal untuk bagian kritis (business logic dan endpoint utama) bila waktu memungkinkan.
- Sertakan catatan asumsi/keputusan desain di bagian Dokumentasi bila ada hal yang tidak eksplisit di soal.
- Pisahkan setiap konteks pengerjaan pada setiap commit

### Software / Tools yang Dibutuhkan
- PHP 8.2
- Composer 2.8
- PostgreSQL 17
- Redis 6.2

### Cara Menjalankan
1. `composer install`
2. Salin `.env.example` menjadi `.env`, isi kredensial DB, lalu `php artisan key:generate`
3. `php artisan migrate --seed`
4. `php artisan serve`

### Setelah Selesai
- Periksa kembali lint/error, formatting, dan logika sebelum membuat Pull Request.
- Pastikan deskripsi PR menjelaskan ringkas perubahan utama, asumsi, serta cara tes yang sudah dijalankan.
