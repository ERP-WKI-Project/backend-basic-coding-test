# ERP Backend - Feature Documentation

## Overview

Backend ERP system dengan clean architecture pattern. Semua logic bisnis tersentralisasi di business layer, controller hanya menangani HTTP request/response, dan model hanya menangani query database.

## Arsitektur

```
Clean Architecture Pattern:
Request -> Controller → Services → Model → Database
            ↓
            DTO (Data Transfer Object)
            ↓
        JSON Response
```

## layer

1. **Controller Layer**: Hanya parsing request dan return response
2. **Service Layer**: Semua business logic, validation, dan data transformation
3. **Model Layer**: Query database dengan method static, tidak ada Eloquent method di service/controller
4. **DTO Layer**: Data transformation dan relationship embedding untuk JSON response
4. **Request Layer**: Parsing request dan validasi semua request yang masuk kedalam controller

---

## API Contract - Postman Collection

### Import Collection

1. **Download Collection File**
   - Link: [ERP Backend API Collection](https://drive.google.com/file/d/1C-NhBeAWhzz9ljWN4H8Nj6WLGl_u1EPG/view?usp=sharing)

2. **Import ke Postman**
   - Buka Postman
   - Click **Import** (top left)
   - Pilih **Link** atau upload **File**
   - Paste link atau upload file collection
   - Collection akan ter-import dengan semua endpoints, headers, dan contoh requests

3. **Setup Environment**
   - Klik **Environments** (kiri)
   - Import environment file atau create manual:
     - `{{base_url}}` = `http://localhost:8000`
     - `{{token}}` = (dari login response)

4. **Test Endpoints**
   - Semua endpoints sudah tersedia dengan full request/response examples
   - Query parameters, headers, dan body sudah ter-setup
   - Tinggal run dan test

### API Endpoints Overview

#### Authentication
- `POST /api/backoffice/v1/auth/login` - Login user
- `POST /api/backoffice/v1/auth/logout` - Logout user

#### User Management
- `POST /api/backoffice/v1/user` - Create user
- `GET /api/backoffice/v1/user` - List users
- `GET /api/backoffice/v1/user/{nik}` - Get user detail
- `PUT /api/backoffice/v1/user/{nik}` - Update user
- `DELETE /api/backoffice/v1/user/{nik}` - Delete user

#### Machine Management
- `POST /api/backoffice/v1/machine` - Create machine
- `GET /api/backoffice/v1/machine` - List machines
- `GET /api/backoffice/v1/machine/{machine_code}` - Get machine detail
- `PUT /api/backoffice/v1/machine/{machine_code}` - Update machine
- `DELETE /api/backoffice/v1/machine/{machine_code}` - Delete machine

#### Shift Management
- `POST /api/backoffice/v1/shift` - Create shift
- `GET /api/backoffice/v1/shift` - List shifts
- `GET /api/backoffice/v1/shift/{id}` - Get shift detail
- `PUT /api/backoffice/v1/shift/{id}` - Update shift
- `DELETE /api/backoffice/v1/shift/{id}` - Delete shift

#### User Shift Assignment (Test 3)
- `POST /api/backoffice/v1/user-shift` - Assign user to shift
- `GET /api/backoffice/v1/user-shift` - List user shifts (with filters)
- `GET /api/backoffice/v1/user-shift/{id}` - Get user shift detail
- `PUT /api/backoffice/v1/user-shift/{id}` - Update user shift
- `DELETE /api/backoffice/v1/user-shift/{id}` - Delete user shift

#### Machine Log Management
- `POST /api/machine/v1/log-entry` - Create machine log
- `GET /api/machine/v1/log-entry` - List machine logs (with filters)

#### Reports (Test 5)
- `GET /api/backoffice/v1/report/user-machine-activity` - User machine activity report with date range

### Filter Examples

**User Shift - Filter by User & Date Range**
```
GET /api/backoffice/v1/user-shift?user_id=1&start_date=2026-02-01&end_date=2026-02-28&per_page=20
```

**Machine Log - Filter by Machine & Event**
```
GET /api/machine/v1/log-entry?machine_code=M001&event=shift_start&per_page=15
```

**Activity Report - Date Range with Machine Filter**
```
GET /api/backoffice/v1/report/user-machine-activity?start_date=2026-02-01&end_date=2026-02-28&machine_code=M001
```

---

## Features Documentation

### 1. User Management
- Create, read, update, delete users
- Search by employee number (NIK)
- Soft delete untuk audit trail

### 2. Machine Management
- Create, read, update, delete machines
- Manage machine status
- Organize by location

### 3. Shift Management
- Create, read, update, delete shifts
- Configure shift times (start/end)
- Organize by day of week

### 4. User Shift Assignment (Test 3)
- Assign users to shifts and machines
- Track shift dates
- Support date range queries
- Embedded relationships (user, shift, machine data)

### 5. Machine Log Management
- Log machine events (start, stop, errors)
- Track user operations
- Event types: login_success, login_failed, logout, shift_start, shift_end

### 6. User Machine Activity Report (Test 5)
- Comprehensive activity tracking
- Date range filtering
- Join multiple tables (users, machines, machine_logs, user_shifts, shifts)
- Machine-to-shift mapping
- User activity audit trail

---



```
app/
├── DTOs/                          # Data Transfer Objects
│   ├── BaseResponseDto.php        # Standard response format
│   ├── UserDto.php
│   ├── MachineDto.php
│   ├── ShiftDto.php
│   ├── UserShiftDto.php
│   ├── MachineLogDto.php
│   └── UserMachineActivityReportDto.php
│
├── Enums/
│   └── MachineLog/
│       └── EventEnum.php          # Machine event types
│
├── Http/
│   ├── Business/                  # Service layer (business logic)
│   │   ├── BackOfficeAuth/
│   │   │   └── AuthService.php
│   │   ├── User/
│   │   │   └── UserService.php
│   │   ├── Machine/
│   │   │   └── MachineService.php
│   │   ├── Shift/
│   │   │   └── ShiftService.php
│   │   ├── UserShift/
│   │   │   └── UserShiftService.php
│   │   ├── MachineLog/
│   │   │   └── MachineLogService.php
│   │   └── Report/
│   │       └── ReportService.php
│   │
│   ├── Controllers/
│   │   ├── BackOffice/
│   │   │   ├── AuthController.php
│   │   │   ├── UserController.php
│   │   │   ├── MachineController.php
│   │   │   ├── ShiftController.php
│   │   │   ├── UserShiftController.php
│   │   │   └── ReportController.php
│   │   └── Machine/
│   │       └── LogEntryController.php
│   │
│   └── Requests/                  # Form requests (optional)
│
├── Models/
│   ├── BaseModel.php
│   ├── BaseAuthenticatable.php
│   ├── User.php
│   ├── Machine.php
│   ├── Shift.php
│   ├── UserShift.php
│   ├── MachineLog.php
│   └── PersonalAccessToken.php
│
├── Traits/
│   └── HasUlidColumn.php
│
└── Providers/
    └── AppServiceProvider.php

database/
├── migrations/
├── factories/
└── seeders/

routes/
├── api.php
├── backoffice/
│   ├── backoffice_index.php
│   └── backoffice_v1.php
└── machine/
    ├── machine_index.php
    └── machine_v1.php
```

---

## Authentication

### Login
```bash
POST /api/backoffice/v1/auth/login
{
  "employee_number": "12345",
  "password": "password123"
}
```

**Response**
```json
{
  "status": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "employee_number": "12345",
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "token_string_here"
  }
}
```

### Using Token
```bash
Authorization: Bearer {token}
```

### Abilities

- `ABILITY_BACKOFFICE_SYSTEM` - BackOffice endpoints
- `ABILITY_MACHINE_SYSTEM` - Machine endpoints

---

## Error Handling

### Standard Error Response
```json
{
  "status": false,
  "message": "User not found",
  "errors": {},
  "data": null
}
```

### Validation Error
```json
{
  "status": false,
  "message": "Validation failed",
  "errors": {
    "employee_number": ["The employee number field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

### HTTP Status Codes
| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request / Validation Error |
| 401 | Unauthorized |
| 404 | Not Found |
| 422 | Validation Failed |
| 500 | Server Error |

---

## Key Points

### Clean Architecture Benefits
✅ Separation of Concerns - Controller, Service, Model punya tanggung jawab jelas  
✅ Reusability - Service dapat digunakan dari berbagai controller  
✅ Testability - Service logic mudah ditest tanpa HTTP layer  
✅ Maintainability - Perubahan logic hanya di service, tidak perlu touch controller  
✅ Consistency - Semua endpoint mengikuti pattern yang sama  

### Query Building
- Model methods support optional parameters untuk flexible filtering
- Pagination metadata included untuk frontend pagination
- Relationship eager loading dengan `with()` menghindari N+1 query problem
- Join logic di model method untuk report kompleks

### DTO Pattern
- Data transformation dari Model ke DTO
- Relationship embedding untuk richer JSON response
- ISO8601 date formatting untuk consistency
- Service method `dtoToArray()` untuk serialization

---

## Setup & Running

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 4. Run Server
```bash
php artisan serve
```

### 5. Run Worker
```bash
php artisan queue:work
```

### 5. Test API
```bash
# Login first
curl -X POST http://localhost:8000/api/backoffice/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"nik":"user_nik","password":"password"}'

# Use token from response
curl -X GET http://localhost:8000/api/backoffice/v1/user-shift \
  -H "Authorization: Bearer {token}"
```


## Notes
* create table machine -> sebagai master data dari machine yang akan berelasi ke machine logs
* create atau improve models jika tidak ada bawaan dan untuk menambahkan method ke eloquent
* semua method eloquent berada di models tidak ada logic yang langsung ke eloquent method
* penambahan event di enum
* penambahan endpoint reset password untuk menggunakan table password reset token yang telah disediakan