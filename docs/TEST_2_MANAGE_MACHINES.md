# TEST 2: MANAGE MACHINES

Dokumentasi lengkap untuk implementasi Machine Management CRUD API.

## Overview

Test 2 mengimplementasikan sistem manajemen mesin (Machine Management) dengan operasi CRUD lengkap. Sistem ini memungkinkan pengelolaan data mesin yang digunakan dalam operasi produksi.

## Endpoints

Base URL: `/api/backoffice/v1/machine`

### 1. List All Machines

**Endpoint:** `GET /api/backoffice/v1/machine`

**Authentication:** Required (Sanctum - BACKOFFICE_SYSTEM ability)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "machine_code": "FILLING-MACHINE-001",
      "name": "Filling Machine Unit 1",
      "description": "High-speed filling machine for beverages",
      "is_active": true,
      "created_at": "2026-02-14T10:00:00.000000Z",
      "updated_at": "2026-02-14T10:00:00.000000Z",
      "deleted_at": null
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/backoffice/v1/machine" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 2. Create Machine

**Endpoint:** `POST /api/backoffice/v1/machine`

**Authentication:** Required (Sanctum - BACKOFFICE_SYSTEM ability)

**Request Body:**
```json
{
  "machine_code": "PACKING-MACHINE-001",
  "name": "Packing Machine Unit 1",
  "description": "Automated packing system",
  "is_active": true
}
```

**Response:** `201 Created`
```json
{
  "data": {
    "id": 2,
    "machine_code": "PACKING-MACHINE-001",
    "name": "Packing Machine Unit 1",
    "description": "Automated packing system",
    "is_active": true,
    "created_at": "2026-02-14T10:05:00.000000Z",
    "updated_at": "2026-02-14T10:05:00.000000Z",
    "deleted_at": null
  }
}
```

**cURL Example:**
```bash
curl -X POST "http://localhost:8000/api/backoffice/v1/machine" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "machine_code": "PACKING-MACHINE-001",
    "name": "Packing Machine Unit 1",
    "description": "Automated packing system",
    "is_active": true
  }'
```

---

### 3. Show Machine Details

**Endpoint:** `GET /api/backoffice/v1/machine/{id}`

**Authentication:** Required (Sanctum - BACKOFFICE_SYSTEM ability)

**Response:**
```json
{
  "data": {
    "id": 1,
    "machine_code": "FILLING-MACHINE-001",
    "name": "Filling Machine Unit 1",
    "description": "High-speed filling machine for beverages",
    "is_active": true,
    "created_at": "2026-02-14T10:00:00.000000Z",
    "updated_at": "2026-02-14T10:00:00.000000Z",
    "deleted_at": null
  }
}
```

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/backoffice/v1/machine/1" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

### 4. Update Machine

**Endpoint:** `PUT /api/backoffice/v1/machine/{id}`

**Authentication:** Required (Sanctum - BACKOFFICE_SYSTEM ability)

**Request Body:**
```json
{
  "name": "Filling Machine Unit 1 (Updated)",
  "description": "Updated description",
  "is_active": false
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "machine_code": "FILLING-MACHINE-001",
    "name": "Filling Machine Unit 1 (Updated)",
    "description": "Updated description",
    "is_active": false,
    "created_at": "2026-02-14T10:00:00.000000Z",
    "updated_at": "2026-02-14T10:10:00.000000Z",
    "deleted_at": null
  }
}
```

**cURL Example:**
```bash
curl -X PUT "http://localhost:8000/api/backoffice/v1/machine/1" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Filling Machine Unit 1 (Updated)",
    "is_active": false
  }'
```

---

### 5. Delete Machine

**Endpoint:** `DELETE /api/backoffice/v1/machine/{id}`

**Authentication:** Required (Sanctum - BACKOFFICE_SYSTEM ability)

**Response:** `200 OK`
```json
{
  "message": "Mesin berhasil dihapus."
}
```

**Error Response (Machine has active shifts):** `422 Unprocessable Entity`
```json
{
  "message": "Tidak dapat menghapus mesin yang masih memiliki shift aktif."
}
```

**cURL Example:**
```bash
curl -X DELETE "http://localhost:8000/api/backoffice/v1/machine/1" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

---

## Validation Rules

### Create Machine (POST)

| Field | Type | Rules | Description |
|-------|------|-------|-------------|
| `machine_code` | string | required, max:50, unique, regex:/^[A-Z0-9\-]+$/ | Kode mesin (huruf kapital, angka, strip) |
| `name` | string | required, max:255 | Nama mesin |
| `description` | text | nullable, max:1000 | Deskripsi mesin |
| `is_active` | boolean | nullable | Status aktif (default: true) |

### Update Machine (PUT/PATCH)

| Field | Type | Rules | Description |
|-------|------|-------|-------------|
| `machine_code` | string | sometimes, required, max:50, unique (ignore current), regex:/^[A-Z0-9\-]+$/ | Kode mesin |
| `name` | string | sometimes, required, max:255 | Nama mesin |
| `description` | text | nullable, max:1000 | Deskripsi mesin |
| `is_active` | boolean | nullable | Status aktif |

**Catatan:** Semua field di update adalah opsional (menggunakan `sometimes`), hanya field yang dikirim yang akan divalidasi dan diupdate.

---

## Business Rules

1. **Machine Code Uniqueness**
   - Setiap machine_code harus unik di sistem
   - Format: Huruf kapital, angka, dan strip (-) saja
   - Contoh valid: `FILLING-MACHINE-001`, `PACK-01`, `TEST-MACHINE-A1`

2. **Soft Delete**
   - Machine menggunakan soft delete (timestamp `deleted_at`)
   - Machine yang dihapus tidak benar-benar terhapus dari database

3. **Delete Protection**
   - Machine yang memiliki shift aktif (shift_date >= hari ini) tidak dapat dihapus
   - Validasi dilakukan di level Service untuk mencegah data inconsistency
   - Machine dengan shift di masa lalu dapat dihapus

4. **Active Status**
   - Default value `is_active = true` untuk machine baru
   - Status dapat diubah kapan saja melalui update endpoint
   - Machine non-aktif tetap dapat dilihat dan dikelola

---

## Database Schema

**Table:** `machines`

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| `id` | bigint | primary key, auto increment | ID unik machine |
| `machine_code` | varchar(50) | unique, indexed | Kode mesin |
| `name` | varchar(255) | not null | Nama mesin |
| `description` | text | nullable | Deskripsi mesin |
| `is_active` | boolean | default true | Status aktif |
| `created_at` | timestamp | not null | Waktu dibuat |
| `updated_at` | timestamp | not null | Waktu diupdate |
| `deleted_at` | timestamp | nullable | Waktu dihapus (soft delete) |

**Indexes:**
- PRIMARY KEY: `id`
- UNIQUE KEY: `machine_code`
- INDEX: `machine_code`

---

## Architecture

### Layered Architecture

```
Controller (MachineController)
    ↓
Service (MachineService)
    ↓
Model (Machine)
```

### Components

1. **Controller: `App\Http\Controllers\BackOffice\MachineController`**
   - Handle HTTP requests dan responses
   - Dependency injection untuk MachineService
   - Return MachineResource untuk formatting response

2. **Service: `App\Services\MachineService`**
   - Business logic untuk machine operations
   - Transaction management untuk data consistency
   - Validasi business rules (e.g., active shift check)

3. **Model: `App\Models\Machine`**
   - Eloquent ORM model
   - Soft delete support
   - Default attributes untuk `is_active`

4. **Requests:**
   - `App\Http\Requests\BackOffice\StoreMachineRequest` - Validasi create
   - `App\Http\Requests\BackOffice\UpdateMachineRequest` - Validasi update

5. **Resource: `App\Http\Resources\BackOffice\MachineResource`**
   - Transform model data untuk API response
   - Consistent JSON structure

---

## Testing

### Test Coverage

**Feature Tests** (`tests/Feature/BackOffice/MachineManagementTest.php`):
- 21 tests covering all endpoints
- Data providers untuk test validation scenarios
- Authentication dan authorization testing

**Unit Tests** (`tests/Unit/MachineServiceTest.php`):
- 15 tests untuk MachineService methods
- Transaction testing
- Business logic validation
- Edge cases handling

### Run Tests

```bash
# Run all machine tests
php artisan test --filter=Machine

# Run feature tests only
php artisan test --filter=MachineManagementTest

# Run unit tests only
php artisan test --filter=MachineServiceTest
```

**Test Results:**
- Total Tests: 40
- Feature Tests: 21
- Unit Tests: 15
- Assertions: 142+
- Status: ✅ All Passing

---

## Error Messages

Error messages menggunakan Bahasa Indonesia untuk user-friendliness:

| Error | Message |
|-------|---------|
| Machine code required | "Kode mesin wajib diisi." |
| Machine code invalid format | "Kode mesin hanya boleh mengandung huruf kapital, angka, dan tanda strip (-)." |
| Machine code duplicate | "Kode mesin sudah terdaftar." |
| Name required | "Nama mesin wajib diisi." |
| Cannot delete with active shifts | "Tidak dapat menghapus mesin yang masih memiliki shift aktif." |
| Delete success | "Mesin berhasil dihapus." |

---

## Security

1. **Authentication**
   - Semua endpoints memerlukan Sanctum authentication
   - Token harus memiliki ability `BACKOFFICE_SYSTEM`

2. **Authorization**
   - Laravel Sanctum ability-based authorization
   - Middleware `auth:sanctum` dan `ability:BACKOFFICE_SYSTEM`

3. **Validation**
   - Form Request validation untuk semua input
   - Custom error messages dalam Bahasa Indonesia
   - Regex validation untuk machine_code format

4. **Data Integrity**
   - Database transaction untuk semua write operations
   - Foreign key validation untuk relationships
   - Soft delete untuk data recovery

---

## Design Decisions

### 1. Machine Code Format
**Keputusan:** Menggunakan format uppercase dengan dash separator (e.g., `FILLING-MACHINE-001`)

**Alasan:**
- Standar industri untuk kode identifikasi mesin
- Mudah dibaca dan dikenali
- Konsisten dengan existing code (`FILLING-MACHINE-001` di test files)

### 2. Soft Delete Implementation
**Keputusan:** Menggunakan soft delete untuk machine

**Alasan:**
- Preserves historical data untuk audit trail
- Machine code di user_shifts tetap valid setelah machine dihapus
- Memungkinkan restore jika diperlukan

### 3. Active Shift Protection
**Keputusan:** Prevent deletion jika machine memiliki shift aktif (hari ini atau masa depan)

**Alasan:**
- Mencegah data inconsistency
- Protect ongoing operations
- Machine dengan shift masa lalu bisa dihapus (historical data)

### 4. Default Active Status
**Keputusan:** `is_active = true` by default

**Alasan:**
- Asumsi: machine baru langsung siap digunakan
- Dapat diubah jika diperlukan maintenance
- Consistent dengan business flow

---

## Sample Data

```json
[
  {
    "machine_code": "FILLING-MACHINE-001",
    "name": "Filling Machine Unit 1",
    "description": "High-speed filling machine for beverages",
    "is_active": true
  },
  {
    "machine_code": "PACKING-MACHINE-001",
    "name": "Packing Machine Unit 1",
    "description": "Automated packing system for finished products",
    "is_active": true
  },
  {
    "machine_code": "LABELING-MACHINE-001",
    "name": "Labeling Machine Unit 1",
    "description": "Automatic labeling system",
    "is_active": true
  }
]
```

---

## Future Enhancements

Possible improvements (not implemented in basic version):

1. **Machine Maintenance Schedule**
   - Track maintenance history
   - Schedule preventive maintenance

2. **Machine Performance Metrics**
   - Uptime/downtime tracking
   - Performance analytics

3. **Machine Categories**
   - Group machines by type/category
   - Filter by category in listing

4. **Search and Filtering**
   - Search by machine_code or name
   - Filter by is_active status
   - Pagination support

5. **Audit Log**
   - Track who created/updated/deleted machines
   - Full audit trail for compliance

---

## Files Summary

**Created/Modified Files:**

```
database/
  migrations/2026_02_14_174647_create_machines_table.php
  factories/MachineFactory.php

app/
  Models/Machine.php
  Services/MachineService.php
  Http/
    Controllers/BackOffice/MachineController.php
    Requests/BackOffice/
      StoreMachineRequest.php
      UpdateMachineRequest.php
    Resources/BackOffice/MachineResource.php

routes/
  backoffice/backoffice_v1.php (enabled machine route)

tests/
  Feature/BackOffice/MachineManagementTest.php
  Unit/MachineServiceTest.php

docs/
  TEST_2_MANAGE_MACHINES.md
```

---

**Last Updated:** February 14, 2026
