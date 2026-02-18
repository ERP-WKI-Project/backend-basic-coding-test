# Business Flow Documentation: User Assignment to Machine & Shift

## 📋 Table of Contents
1. [Overview](#overview)
2. [Architecture Patterns](#architecture-patterns)
3. [Real-World Use Cases](#real-world-use-cases)
4. [Complete Flow Examples](#complete-flow-examples)
5. [API Request Examples](#api-request-examples)

---

## Overview

Sistem ini mengelola assignment karyawan (user) ke mesin produksi pada shift tertentu dengan fitur:
- ✅ Clock-in/out untuk kehadiran
- ✅ Transfer antar mesin
- ✅ Pelaporan kerusakan mesin
- ✅ Manajemen waktu istirahat
- ✅ Laporan aktivitas lengkap

---

## Architecture Patterns

### 1. **Layering Pattern**

```
┌─────────────────────────────────────────┐
│  HTTP Request (from Client/Postman)    │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Controller Layer                       │
│  - Validate Request (FormRequest)       │
│  - Transform to DTO                     │
│  - Call Service                         │
│  - Return Resource                      │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Service Layer                          │
│  - Business Logic                       │
│  - Validation Rules                     │
│  - Database Transactions                │
│  - Call Models                          │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Model Layer (Eloquent ORM)             │
│  - Database Queries                     │
│  - Relationships                        │
│  - Accessors/Mutators                   │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Database (PostgreSQL)                  │
└─────────────────────────────────────────┘
```

### 2. **DTO Pattern Usage**

**Purpose:** Transfer data between layers with type safety

```php
// ✅ Good: Using DTO
public function createUser(UserDto $dto): User
{
    $data = $dto->toArray();
    return User::create($data);
}

// ❌ Bad: Using raw array
public function createUser(array $data): User
{
    return User::create($data); // No type safety
}
```

**Available DTOs:**
- `UserDto` - User management
- `MachineDto` - Machine management
- `ShiftDto` - Shift template
- `UserShiftDto` - User shift assignment
- `MachineLogDto` - Machine logging
- `AuthCredentialDto` - Authentication
- `AuthDto` - Authentication result

### 3. **Service Instantiation Pattern**

```php
// ✅ Good: Instance-based (Dependency Injection)
class UserShiftController extends Controller
{
    public function __construct(
        private readonly UserShiftService $userShiftService
    ) {}
    
    public function store(Request $request)
    {
        return $this->userShiftService->assignUserToShift($dto);
    }
}

// ❌ Bad: Static methods (hard to test)
class UserShiftController extends Controller
{
    public function store(Request $request)
    {
        return UserShiftService::assignUserToShift($dto);
    }
}
```

### 4. **Resource Structure**

```
app/Http/Resources/
├── BackOffice/              ← Resources for BackOffice namespace
│   ├── UserResource.php
│   ├── MachineResource.php
│   ├── ShiftResource.php
│   └── UserShiftResource.php
├── Machine/                 ← Resources for Machine namespace
│   └── ProfileResource.php
└── MachineLogResource.php   ← Shared resource (used by both)
```

---

## Real-World Use Cases

### 🏭 **Scenario: PT. Manufaktur Indonesia**

**Setup:**
- **Pabrik:** Produksi komponen elektronik
- **Shift:** 3 shift (Pagi, Siang, Malam)
- **Mesin:** 10 mesin produksi
- **Karyawan:** 30 operator mesin

---

## Complete Flow Examples

### **Case 1: Setup Awal Pabrik**

#### Step 1: Buat Shift Templates
```json
// POST /api/backoffice/v1/shift
// Shift Pagi (Monday)
{
  "name": "Shift Pagi - Senin",
  "day_of_week": 1,
  "start_time": "07:00:00",
  "end_time": "15:00:00"
}

// Shift Siang (Monday)
{
  "name": "Shift Siang - Senin",
  "day_of_week": 1,
  "start_time": "15:00:00",
  "end_time": "23:00:00"
}

// Shift Malam (Monday)
{
  "name": "Shift Malam - Senin",
  "day_of_week": 1,
  "start_time": "23:00:00",
  "end_time": "07:00:00"
}
```

#### Step 2: Daftarkan Mesin
```json
// POST /api/backoffice/v1/machine
{
  "machine_code": "MCH-001",
  "name": "Mesin CNC Milling",
  "description": "Mesin milling untuk produksi presisi tinggi"
}

{
  "machine_code": "MCH-002",
  "name": "Mesin Injection Molding",
  "description": "Mesin untuk cetakan plastik"
}

{
  "machine_code": "MCH-003",
  "name": "Mesin Packaging",
  "description": "Mesin otomatis untuk packaging produk"
}
```

#### Step 3: Daftarkan Karyawan
```json
// POST /api/backoffice/v1/user
{
  "name": "Budi Santoso",
  "employee_number": "EMP-001",
  "email": "budi.santoso@manufaktur.com",
  "password": "password123"
}

{
  "name": "Siti Nurhaliza",
  "employee_number": "EMP-002",
  "email": "siti.nurhaliza@manufaktur.com",
  "password": "password123"
}

{
  "name": "Ahmad Hidayat",
  "employee_number": "EMP-003",
  "email": "ahmad.hidayat@manufaktur.com",
  "password": "password123"
}
```

---

### **Case 2: Assign Karyawan ke Shift & Mesin**

#### Senin, 17 Februari 2026

**Shift Pagi (07:00 - 15:00)**
```json
// POST /api/backoffice/v1/user-shift

// Budi operate MCH-001
{
  "user_id": 1,
  "shift_id": 1,
  "shift_date": "2026-02-17",
  "machine_code": "MCH-001",
  "notes": "Assignment shift pagi untuk Budi di Mesin CNC"
}

// Siti operate MCH-002
{
  "user_id": 2,
  "shift_id": 1,
  "shift_date": "2026-02-17",
  "machine_code": "MCH-002",
  "notes": "Assignment shift pagi untuk Siti di Mesin Injection"
}
```

**Shift Siang (15:00 - 23:00)**
```json
// Ahmad operate MCH-001 (replace Budi)
{
  "user_id": 3,
  "shift_id": 2,
  "shift_date": "2026-02-17",
  "machine_code": "MCH-001",
  "notes": "Assignment shift siang untuk Ahmad di Mesin CNC"
}
```

**Validation:**
- ✅ `shift_date` harus sesuai `day_of_week` (17 Feb 2026 = Monday = 1)
- ✅ Budi & Ahmad bisa di mesin sama tapi beda shift (no overlap)
- ✅ Budi & Siti shift sama tapi mesin berbeda (OK)
- ❌ TIDAK BOLEH: 2 user di mesin sama pada waktu overlap

---

### **Case 3: Aktivitas Harian Karyawan**

#### **Pagi: Budi Clock-In (07:05)**
```json
// POST /api/backoffice/v1/user-shift/1/clock-in
{}

// Response:
{
  "message": "Clock-in berhasil",
  "data": {
    "id": 15,
    "user": { "name": "Budi Santoso" },
    "machine": { "machine_code": "MCH-001" },
    "event": "CLOCK_IN",
    "log_message": "User clocked in to shift",
    "created_at": "2026-02-17T07:05:23+07:00"
  }
}
```

**System creates MachineLog:**
- Event: `CLOCK_IN`
- User: Budi Santoso
- Machine: MCH-001
- Timestamp: 07:05:23

---

#### **Pagi: Budi Menemukan Kerusakan Mesin (09:30)**
```json
// POST /api/backoffice/v1/user-shift/1/report-failure
{
  "severity": "HIGH",
  "description": "Mesin CNC berbunyi tidak normal dan berhenti mendadak",
  "metadata": {
    "temperature": "85°C",
    "vibration": "high",
    "estimated_downtime": "2 hours"
  }
}

// Response:
{
  "message": "Kerusakan mesin berhasil dilaporkan",
  "data": {
    "event": "MACHINE_FAILURE",
    "severity": "high",
    "log_message": "Mesin CNC berbunyi tidak normal...",
    "metadata": {
      "temperature": "85°C",
      "vibration": "high",
      "estimated_downtime": "2 hours"
    }
  }
}
```

**System creates MachineLog:**
- Event: `MACHINE_FAILURE`
- Severity: `HIGH`
- Metadata: JSON detail

---

#### **Pagi: Budi Pindah ke MCH-003 (10:00)**
```json
// POST /api/backoffice/v1/user-shift/1/transfer-machine
{
  "new_machine_code": "MCH-003",
  "reason": "MCH-001 rusak, dipindahkan sementara ke Mesin Packaging"
}

// Response:
{
  "message": "Transfer mesin berhasil",
  "data": {
    "id": 1,
    "user": { "name": "Budi Santoso" },
    "machine": { "machine_code": "MCH-003", "name": "Mesin Packaging" },
    "shift_date": "2026-02-17"
  }
}
```

**System:**
1. Creates MachineLog: `MACHINE_TRANSFER`
2. Updates UserShift: `machine_code` → MCH-003
3. Validates: MCH-003 available (no other user)

---

#### **Siang: Budi Istirahat Makan (12:00 - 12:30)**
```json
// POST /api/backoffice/v1/user-shift/1/start-break
{
  "break_reason": "Istirahat makan siang"
}

// Response: MachineLog created with event "BREAK_START"
```

**30 menit kemudian:**
```json
// POST /api/backoffice/v1/user-shift/1/end-break
{}

// Response: MachineLog created with event "BREAK_END"
```

**System tracks:**
- Break start: 12:00:00
- Break end: 12:30:00
- Duration: 30 minutes

---

#### **Sore: Budi Clock-Out (15:00)**
```json
// POST /api/backoffice/v1/user-shift/1/clock-out
{
  "is_early": false
}

// Response:
{
  "message": "Clock-out berhasil",
  "data": {
    "event": "CLOCK_OUT",
    "log_message": "User clocked out from shift",
    "created_at": "2026-02-17T15:00:45+07:00"
  }
}
```

**System:**
- Event: `CLOCK_OUT` (normal)
- Total work: ~8 hours
- Break deducted: 30 minutes
- Actual work: 7.5 hours

---

### **Case 4: Generate Activity Report**

#### **Manager Request Report (End of Week)**
```json
// GET /api/backoffice/v1/report/user-machine-activity
{
  "start_date": "2026-02-17",
  "end_date": "2026-02-21",
  "user_id": 1  // Optional: specific user (Budi)
}

// Response:
{
  "message": "User machine activity report generated successfully",
  "data": {
    "period": {
      "start_date": "2026-02-17",
      "end_date": "2026-02-21",
      "total_days": 5
    },
    "summary": {
      "total_users": 1,
      "total_shifts": 5,
      "completed_shifts": 5,
      "in_progress_shifts": 0,
      "not_started_shifts": 0,
      "total_work_hours": 37.5,
      "total_break_hours": 2.5,
      "total_incidents": 3,
      "average_work_hours_per_shift": 7.5
    },
    "activities": [
      {
        "user": {
          "id": 1,
          "name": "Budi Santoso",
          "employee_number": "EMP-001"
        },
        "daily_activities": [
          {
            "date": "2026-02-17",
            "shifts": [
              {
                "shift": {
                  "id": 1,
                  "name": "Shift Pagi - Senin",
                  "start_time": "07:00:00",
                  "end_time": "15:00:00"
                },
                "machine": {
                  "machine_code": "MCH-003",
                  "name": "Mesin Packaging"
                },
                "attendance": {
                  "clock_in": "07:05:23",
                  "clock_out": "15:00:45",
                  "status": "completed"
                },
                "work_summary": {
                  "total_minutes": 475,
                  "break_minutes": 30,
                  "actual_work_minutes": 445,
                  "breaks": [
                    {
                      "start": "12:00:00",
                      "end": "12:30:00",
                      "duration_minutes": 30
                    }
                  ]
                },
                "incidents": {
                  "machine_failures": [
                    {
                      "timestamp": "09:30:15",
                      "severity": "high",
                      "description": "Mesin CNC berbunyi tidak normal...",
                      "metadata": {
                        "temperature": "85°C",
                        "vibration": "high",
                        "estimated_downtime": "2 hours"
                      }
                    }
                  ],
                  "machine_transfers": [
                    {
                      "timestamp": "10:00:00",
                      "from_machine": "MCH-001",
                      "to_machine": "MCH-003",
                      "reason": "MCH-001 rusak..."
                    }
                  ]
                },
                "notes": "Assignment shift pagi untuk Budi di Mesin CNC"
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

## API Request Examples

### **Authentication**
```bash
# BackOffice Login
POST /api/backoffice/v1/auth/login
{
  "employee_number": "EMP-001",
  "password": "password123"
}

# Machine Login (for operators)
POST /api/machine/v1/auth/login
{
  "employee_number": "EMP-001",
  "password": "password123",
  "machine_code": "MCH-001"
}
```

### **CRUD Operations**

#### Users
```bash
GET    /api/backoffice/v1/user          # List all users
POST   /api/backoffice/v1/user          # Create user
GET    /api/backoffice/v1/user/{id}     # Show user
PUT    /api/backoffice/v1/user/{id}     # Update user
DELETE /api/backoffice/v1/user/{id}     # Delete user
```

#### Machines
```bash
GET    /api/backoffice/v1/machine          # List all machines
POST   /api/backoffice/v1/machine          # Create machine
GET    /api/backoffice/v1/machine/{id}     # Show machine
PUT    /api/backoffice/v1/machine/{id}     # Update machine
DELETE /api/backoffice/v1/machine/{id}     # Delete machine
```

#### Shifts
```bash
GET    /api/backoffice/v1/shift          # List all shifts
POST   /api/backoffice/v1/shift          # Create shift
GET    /api/backoffice/v1/shift/{id}     # Show shift
PUT    /api/backoffice/v1/shift/{id}     # Update shift
DELETE /api/backoffice/v1/shift/{id}     # Delete shift
```

#### User Shift Assignments
```bash
GET    /api/backoffice/v1/user-shift                    # List assignments
POST   /api/backoffice/v1/user-shift                    # Assign user
GET    /api/backoffice/v1/user-shift/{id}               # Show assignment
PUT    /api/backoffice/v1/user-shift/{id}               # Update assignment
DELETE /api/backoffice/v1/user-shift/{id}               # Cancel assignment

# Operations
POST   /api/backoffice/v1/user-shift/{id}/clock-in      # Clock in
POST   /api/backoffice/v1/user-shift/{id}/clock-out     # Clock out
POST   /api/backoffice/v1/user-shift/{id}/transfer-machine   # Transfer
POST   /api/backoffice/v1/user-shift/{id}/report-failure     # Report failure
POST   /api/backoffice/v1/user-shift/{id}/start-break        # Start break
POST   /api/backoffice/v1/user-shift/{id}/end-break          # End break

# Query
GET    /api/backoffice/v1/user-shift/schedule/user      # User schedule
```

#### Reports
```bash
GET /api/backoffice/v1/report/user-machine-activity
  ?start_date=2026-02-17
  &end_date=2026-02-21
  &user_id=1              # Optional
  &machine_code=MCH-001   # Optional
```

---

## Key Business Rules

### ✅ **Allowed**
1. Multiple users on different machines (same time)
2. Same user on same machine (different shifts, no overlap)
3. Same user on different machines (same day, different shifts)
4. Historical assignments (past dates)
5. Future assignments (scheduled)

### ❌ **Not Allowed**
1. Double-booking user (overlap shifts same day)
2. Double-booking machine (2+ users same time)
3. Clock-in without assignment
4. Clock-out without clock-in
5. Transfer machine before clock-in
6. Delete shift with existing assignments
7. Delete user with active shifts

---

## Data Flow Summary

```
1. SETUP
   ├─ Create Shifts (templates by day_of_week)
   ├─ Register Machines
   └─ Register Users

2. PLANNING
   ├─ Assign Users to Shifts + Machines
   ├─ Validate: date, availability, conflicts
   └─ Save UserShift records

3. EXECUTION
   ├─ Clock-in (start shift)
   ├─ Work Activities
   │  ├─ Machine failures
   │  ├─ Machine transfers
   │  └─ Break times
   └─ Clock-out (end shift)

4. MONITORING & REPORTING
   ├─ Real-time logs (MachineLog)
   ├─ Activity reports
   └─ Performance analytics
```

---

## Technology Stack

- **Backend:** Laravel 11 (PHP 8.2+)
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum (token-based)
- **Architecture:** Service Layer + Repository Pattern
- **Data Transfer:** DTOs (Data Transfer Objects)
- **API Response:** JSON Resources
- **Testing:** Pest PHP

---

## Testing Recommendations

```php
// Test user assignment validation
it('cannot assign user to overlapping shifts', function () {
    // Create shift 1: 07:00 - 15:00
    // Assign user to shift 1
    // Create shift 2: 12:00 - 20:00 (overlap!)
    // Attempt assign same user to shift 2
    // Expect: ValidationException
});

// Test machine availability
it('cannot assign same machine to multiple users at same time', function () {
    // Assign user A to MCH-001 (07:00 - 15:00)
    // Attempt assign user B to MCH-001 (07:00 - 15:00)
    // Expect: ValidationException
});

// Test clock-in/out flow
it('can complete full shift cycle', function () {
    // 1. Assign user to shift
    // 2. Clock-in
    // 3. Start break
    // 4. End break
    // 5. Clock-out
    // Assert: All logs created correctly
});
```

---

**Last Updated:** February 16, 2026  
**Version:** 1.0  
**Author:** Backend Team - PT. Manufaktur Indonesia
