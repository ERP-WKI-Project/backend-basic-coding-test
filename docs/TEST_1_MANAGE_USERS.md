# Test 1: Manage Users

## Implementasi

Implementasi lengkap untuk manajemen user (CRUD) dengan mengikuti arsitektur dan standar yang sudah ada di project.

### Struktur File yang Dibuat

1. **Controller**: `app/Http/Controllers/BackOffice/UserController.php`
   - Handle HTTP requests
   - Dependency injection UserService
   - Return JSON response dengan format standar

2. **Request Validation**:
   - `app/Http/Requests/BackOffice/StoreUserRequest.php` - Validasi create user
   - `app/Http/Requests/BackOffice/UpdateUserRequest.php` - Validasi update user

3. **Service**: `app/Services/UserService.php`
   - Business logic untuk create, update, delete user
   - Transaction handling
   - Password hashing
   - Validasi business rules (contoh: cek shift aktif sebelum delete)

4. **Resource**: `app/Http/Resources/BackOffice/UserResource.php`
   - Format response JSON yang konsisten
   - Include relasi jika dimuat

5. **Routes**: `routes/backoffice/backoffice_v1.php`
   - RESTful API endpoints dengan protection auth:sanctum

---

## Endpoints

Base URL: `/backoffice/v1/user`

### 1. List Users (GET)
```
GET /backoffice/v1/user
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "employee_number": "123456",
      "name": "John Doe",
      "email": "john@example.com",
      "email_verified_at": null,
      "created_at": "2026-02-14 10:00:00",
      "updated_at": "2026-02-14 10:00:00"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 2. Create User (POST)
```
POST /backoffice/v1/user
```

**Request Body:**
```json
{
  "employee_number": "123456",
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!"
}
```

**Validation Rules:**
- `employee_number`: required, 6 digits, numeric, unique
- `name`: required, max 255 characters
- `email`: required, valid email format, unique
- `password`: required, min 8 chars, mixed case, numbers, symbols

**Response (201):**
```json
{
  "message": "User berhasil dibuat",
  "data": {
    "id": 1,
    "employee_number": "123456",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-02-14 10:00:00",
    "updated_at": "2026-02-14 10:00:00"
  }
}
```

### 3. Show User (GET)
```
GET /backoffice/v1/user/{employee_number}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "employee_number": "123456",
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-02-14 10:00:00",
    "updated_at": "2026-02-14 10:00:00"
  }
}
```

### 4. Update User (PUT/PATCH)
```
PUT /backoffice/v1/user/{employee_number}
PATCH /backoffice/v1/user/{employee_number}
```

**Request Body (semua field optional):**
```json
{
  "employee_number": "654321",
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "NewSecurePass123!"
}
```

**Response:**
```json
{
  "message": "User berhasil diupdate",
  "data": {
    "id": 1,
    "employee_number": "654321",
    "name": "Jane Doe",
    "email": "jane@example.com",
    "email_verified_at": null,
    "created_at": "2026-02-14 10:00:00",
    "updated_at": "2026-02-14 10:15:00"
  }
}
```

### 5. Delete User (DELETE)
```
DELETE /backoffice/v1/user/{employee_number}
```

**Response:**
```json
{
  "message": "User berhasil dihapus"
}
```

**Business Logic:**
- User tidak bisa dihapus jika memiliki shift aktif (shift_date >= hari ini)
- Menggunakan soft delete (data tidak benar-benar terhapus)

---

## Keamanan & Validasi

### Authentication & Authorization
- Semua endpoint dilindungi dengan middleware `auth:sanctum`
- Memerlukan ability `ABILITY_BACKOFFICE_SYSTEM`
- User harus login terlebih dahulu untuk mengakses

### Input Validation
- Request validation menggunakan FormRequest classes
- Custom error messages dalam Bahasa Indonesia
- Unique validation untuk employee_number dan email
- Password hashing otomatis saat create/update

### Business Rules
- Employee_number harus 6 digit angka
- Email wajib diisi dan harus unique (untuk keperluan notifikasi)
- Password minimal 8 karakter dengan kombinasi huruf besar, kecil, angka, dan simbol
- User dengan shift aktif tidak bisa dihapus

---

## Testing Endpoints

### 1. Login sebagai BackOffice User
```bash
curl -X POST http://localhost:8000/backoffice/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "employee_number": "000001",
    "password": "password"
  }'
```

Simpan token yang didapat untuk request selanjutnya.

### 2. Create User
```bash
curl -X POST http://localhost:8000/backoffice/v1/user \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}" \
  -d '{
    "employee_number": "123456",
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecurePass123!"
  }'
```

### 3. List Users
```bash
curl -X GET http://localhost:8000/backoffice/v1/user \
  -H "Authorization: Bearer {your_token}"
```

### 4. Show User
```bash
curl -X GET http://localhost:8000/backoffice/v1/user/123456 \
  -H "Authorization: Bearer {your_token}"
```

### 5. Update User
```bash
curl -X PUT http://localhost:8000/backoffice/v1/user/123456 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {your_token}" \
  -d '{
    "name": "Jane Doe Updated"
  }'
```

### 6. Delete User
```bash
curl -X DELETE http://localhost:8000/backoffice/v1/user/123456 \
  -H "Authorization: Bearer {your_token}"
```

---

## Arsitektur & Best Practices

### Layered Architecture
```
Request → Validation (FormRequest) → Controller → Service → Model → Database
                                         ↓
                                    Response (Resource)
```

### Alasan Desain

1. **Controller Tipis**: Controller hanya menerima request dan return response, logic ada di Service
2. **Service Layer**: Semua business logic di Service layer untuk reusability dan testability
3. **DTO Pattern**: UserResource untuk consistent response format
4. **Transaction**: Semua operasi write (create/update/delete) dibungkus dalam DB transaction
5. **Soft Delete**: User tidak benar-benar terhapus, hanya di-mark sebagai deleted
6. **Validation**: Terpisah di FormRequest dengan custom messages
7. **Security**: Password di-hash dengan bcrypt, tidak pernah return password di response

### Ekstensibilitas

Service layer sudah disiapkan untuk menambahkan logic tambahan:
- Send notification email saat user dibuat
- Log audit trail untuk setiap perubahan
- Integration dengan sistem lain
- Restore soft deleted users (method sudah tersedia)

---

## Catatan

- Route key menggunakan `employee_number` bukan `id` (sudah di-set di User model)
- Pagination default 15 items per page
- Timestamps otomatis di-format ke `Y-m-d H:i:s`
- Password tidak di-return dalam response (hidden in model)
