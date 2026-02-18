# Architecture Patterns Documentation

## 📚 Table of Contents
1. [DTO Pattern](#dto-pattern)
2. [Service Instantiation Pattern](#service-instantiation-pattern)
3. [Resource Organization](#resource-organization)
4. [Best Practices](#best-practices)

---

## DTO Pattern

### What is DTO?

**Data Transfer Object (DTO)** adalah object yang digunakan untuk transfer data antar layers dalam aplikasi, dengan type-safety dan validation built-in.

### Why Use DTOs?

#### ✅ Benefits:
1. **Type Safety**: PHP 8+ typed properties mencegah type errors
2. **Immutability**: `readonly` properties mencegah accidental modification
3. **Single Responsibility**: Satu DTO untuk satu purpose
4. **Testability**: Mudah di-test dan di-mock
5. **Clarity**: Intent jelas dari method signature
6. **Refactoring**: Lebih mudah refactor karena strong typing

#### ❌ Without DTO (Problems):
```php
// Controller
public function store(Request $request)
{
    $this->service->createUser($request->all()); // What data?
}

// Service
public function createUser(array $data) // What's inside array?
{
    // No type hints, must check manually
    $name = $data['name'] ?? null;
    $email = $data['email'] ?? null;
    // ... prone to errors
}
```

#### ✅ With DTO (Better):
```php
// Controller
public function store(StoreUserRequest $request)
{
    $dto = UserDto::fromRequest($request->validated());
    $this->service->createUser($dto); // Clear intent!
}

// Service
public function createUser(UserDto $dto): User // Type-safe!
{
    // Properties are guaranteed to exist
    $user = User::create([
        'name' => $dto->name,           // IDE autocomplete works!
        'email' => $dto->email,         // No null checks needed
        'employee_number' => $dto->employeeNumber,
    ]);
}
```

---

### DTO Structure

#### Basic DTO Template
```php
<?php

namespace App\DTOs;

class UserDto
{
    public function __construct(
        public readonly string $name,           // Required
        public readonly string $email,          // Required
        public readonly string $employeeNumber, // Required
        public readonly ?string $password = null, // Optional
    ) {}

    /**
     * Create DTO from validated request data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            employeeNumber: $data['employee_number'],
            password: $data['password'] ?? null,
        );
    }

    /**
     * Convert DTO to array for database operations
     */
    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employeeNumber,
        ];

        // Only include password if provided
        if ($this->password !== null) {
            $result['password'] = $this->password;
        }

        return $result;
    }
}
```

#### Key Components:

1. **Constructor Parameters**
   - Use `public readonly` for immutability
   - Type-hint everything
   - Required params first, optional params with defaults last
   - Use camelCase for property names

2. **fromRequest() Method**
   - Static factory method
   - Accepts validated array from FormRequest
   - Maps snake_case (API) → camelCase (DTO)
   - Handles optional fields with null coalescing

3. **toArray() Method**
   - Converts DTO back to array
   - Maps camelCase → snake_case for database
   - Handles optional fields appropriately

---

### Available DTOs in Project

| DTO | Purpose | Required Fields | Optional Fields |
|-----|---------|----------------|-----------------|
| `UserDto` | User CRUD | name, email, employee_number | password |
| `MachineDto` | Machine CRUD | machine_code, name | description |
| `ShiftDto` | Shift template | name, day_of_week, start_time, end_time | - |
| `UserShiftDto` | Shift assignment | user_id, shift_id, shift_date, machine_code | notes |
| `MachineLogDto` | Logging | user, machine_code, event, log_message | severity, metadata |
| `AuthCredentialDto` | Authentication | user, machine_code | - |
| `AuthDto` | Auth result | is_successful, message | token |

---

### DTO Usage Examples

#### Example 1: Create User
```php
// In Controller
public function store(StoreUserRequest $request): JsonResponse
{
    // Transform validated request to DTO
    $dto = UserDto::fromRequest($request->validated());
    
    // Pass DTO to service
    $user = $this->userService->createUser($dto);
    
    return response()->json([
        'message' => 'User created',
        'data' => new UserResource($user)
    ], 201);
}

// In Service
public function createUser(UserDto $dto): User
{
    return DB::transaction(function () use ($dto) {
        $data = $dto->toArray();
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    });
}
```

#### Example 2: Assign User to Shift
```php
// In Controller
public function store(AssignUserShiftRequest $request): UserShiftResource
{
    $dto = UserShiftDto::fromRequest($request->validated());
    $assignment = $this->userShiftService->assignUserToShift($dto);
    return new UserShiftResource($assignment);
}

// In Service
public function assignUserToShift(UserShiftDto $dto): UserShift
{
    return DB::transaction(function () use ($dto) {
        // Validate shift exists
        $shift = Shift::findOrFail($dto->shiftId);
        
        // Validate user exists
        User::findOrFail($dto->userId);
        
        // Validate machine exists
        Machine::where('machine_code', $dto->machineCode)->firstOrFail();
        
        // Business validations
        $this->validateShiftDateMatchesDayOfWeek($dto->shiftDate, $shift->day_of_week);
        $this->checkUserAvailability($dto->userId, $dto->shiftDate, $shift);
        $this->checkMachineAvailability($dto->machineCode, $dto->shiftDate, $shift);
        
        // Create assignment
        return UserShift::create($dto->toArray());
    });
}
```

#### Example 3: Complex DTO with Nested Data
```php
class MachineLogDto
{
    public function __construct(
        public readonly User $user,                    // Model instance
        public readonly string $machineCode,
        public readonly MachineLogEventEnum $event,    // Enum
        public readonly string $logMessage,
        public readonly ?SeverityEnum $severity = null, // Optional enum
        public readonly ?array $metadata = null,        // Optional JSON
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user->id,
            'machine_code' => $this->machineCode,
            'event' => $this->event->value,           // Enum to string
            'log_message' => $this->logMessage,
            'severity' => $this->severity?->value,    // Null-safe enum
            'metadata' => $this->metadata,
        ];
    }
}
```

---

## Service Instantiation Pattern

### Instance-based Services (Recommended ✅)

#### Why Instance-based?

1. **Dependency Injection**: Automatic by Laravel container
2. **Testability**: Easy to mock and test
3. **State Management**: Can hold instance state if needed
4. **Interface Implementation**: Can implement contracts
5. **Best Practice**: Industry standard

#### Implementation:

```php
// Service Class
namespace App\Services;

class UserShiftService
{
    // Optional: Inject dependencies in constructor
    public function __construct(
        private readonly UserService $userService,
        private readonly ShiftService $shiftService,
    ) {}
    
    public function assignUserToShift(UserShiftDto $dto): UserShift
    {
        // Instance method - can access $this
        return $this->performAssignment($dto);
    }
    
    private function performAssignment(UserShiftDto $dto): UserShift
    {
        // Helper method accessible via $this
        // ...
    }
}
```

```php
// Controller
namespace App\Http\Controllers\BackOffice;

class UserShiftController extends Controller
{
    public function __construct(
        private readonly UserShiftService $userShiftService
    ) {}
    
    public function store(AssignUserShiftRequest $request): UserShiftResource
    {
        $dto = UserShiftDto::fromRequest($request->validated());
        
        // Call instance method
        $assignment = $this->userShiftService->assignUserToShift($dto);
        
        return new UserShiftResource($assignment);
    }
}
```

#### Testing Instance-based Services:

```php
use App\Services\UserShiftService;
use Mockery;

it('can assign user to shift', function () {
    // Mock the service
    $mockService = Mockery::mock(UserShiftService::class);
    
    // Define behavior
    $mockService->shouldReceive('assignUserToShift')
        ->once()
        ->with(Mockery::type(UserShiftDto::class))
        ->andReturn(UserShift::factory()->make());
    
    // Bind mock to container
    $this->app->instance(UserShiftService::class, $mockService);
    
    // Test controller
    $response = $this->postJson('/api/backoffice/v1/user-shift', [...]);
    
    $response->assertStatus(201);
});
```

---

### Static Methods (Legacy Pattern ❌)

#### Problems with Static:

1. **Hard to Test**: Cannot mock static methods easily
2. **Tight Coupling**: Direct class dependencies
3. **No Polymorphism**: Cannot implement interfaces
4. **No DI**: Cannot inject dependencies
5. **Global State**: All state is global

#### Example (Avoid this):
```php
// ❌ Bad: Static service
class AuthService
{
    public static function authenticate(AuthCredentialDto $dto): AuthDto
    {
        // Cannot inject dependencies
        // Hard to test
        // Cannot be mocked
    }
}

// Controller using static
public function login(Request $request)
{
    $dto = AuthCredentialDto::fromRequest($request->all());
    
    // Tight coupling, cannot be mocked
    $result = AuthService::authenticate($dto);
    
    return response()->json($result);
}
```

#### Refactoring to Instance-based:
```php
// ✅ Good: Instance-based service
class AuthService
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly UserRepository $userRepository,
    ) {}
    
    public function authenticate(AuthCredentialDto $dto): AuthDto
    {
        // Can use injected dependencies
        $user = $this->userRepository->findByCredentials($dto);
        
        if ($user) {
            $token = $this->tokenService->generateToken($user);
            return AuthDto::success($token);
        }
        
        return AuthDto::failure('Invalid credentials');
    }
}

// Controller
public function __construct(
    private readonly AuthService $authService
) {}

public function login(Request $request)
{
    $dto = AuthCredentialDto::fromRequest($request->all());
    $result = $this->authService->authenticate($dto);
    return response()->json($result);
}
```

---

## Resource Organization

### Current Structure

```
app/Http/Resources/
├── BackOffice/              ← Resources for BackOffice controllers
│   ├── UserResource.php
│   ├── MachineResource.php
│   ├── ShiftResource.php
│   └── UserShiftResource.php
├── Machine/                 ← Resources for Machine controllers
│   └── ProfileResource.php
└── MachineLogResource.php   ← Shared resource (used by both namespaces)
```

### Naming Convention

**Pattern:** `{Domain}/{ModelName}Resource.php`

#### BackOffice Resources:
- `BackOffice\UserResource` → transforms User model for BackOffice API
- `BackOffice\MachineResource` → transforms Machine model for BackOffice API
- `BackOffice\ShiftResource` → transforms Shift model for BackOffice API
- `BackOffice\UserShiftResource` → transforms UserShift model for BackOffice API

#### Machine Resources:
- `Machine\ProfileResource` → transforms User model for Machine API (different structure!)

#### Shared Resources:
- `MachineLogResource` → used by both namespaces (same structure)

### Resource Template

```php
<?php

namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            
            // Relationships (conditionally loaded)
            'user_shifts' => UserShiftResource::collection($this->whenLoaded('userShifts')),
            
            // Computed attributes
            'is_active' => $this->isActive(),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

### Resource Collections

```php
// In Controller
public function index(): AnonymousResourceCollection
{
    $users = User::with('userShifts')->paginate(15);
    
    // Automatically wraps in collection
    return UserResource::collection($users);
}

// Response structure:
{
  "data": [
    { "id": 1, "name": "..." },
    { "id": 2, "name": "..." }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "total": 50, ... }
}
```

### Conditional Attributes

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        
        // Only include if authenticated user is admin
        'salary' => $this->when($request->user()->isAdmin(), $this->salary),
        
        // Only include if relationship loaded
        'shifts' => ShiftResource::collection($this->whenLoaded('userShifts')),
        
        // Merge additional data conditionally
        $this->mergeWhen($this->isActive(), [
            'last_login' => $this->last_login_at,
            'login_count' => $this->login_count,
        ]),
    ];
}
```

---

## Best Practices

### 1. Controller Responsibilities

**Controllers should be THIN:**
- ✅ Validate request (via FormRequest)
- ✅ Transform to DTO
- ✅ Call service method
- ✅ Transform result to Resource
- ❌ NO business logic
- ❌ NO database queries (except simple finds)
- ❌ NO complex calculations

```php
// ✅ Good Controller
public function store(StoreUserRequest $request): JsonResponse
{
    $dto = UserDto::fromRequest($request->validated());
    $user = $this->userService->createUser($dto);
    
    return response()->json([
        'message' => 'User created successfully',
        'data' => new UserResource($user)
    ], 201);
}

// ❌ Bad Controller
public function store(Request $request)
{
    // Too much logic in controller!
    $validated = $request->validate([...]);
    
    if (User::where('email', $validated['email'])->exists()) {
        return response()->json(['error' => 'Email exists'], 422);
    }
    
    $validated['password'] = Hash::make($validated['password']);
    
    $user = User::create($validated);
    
    event(new UserCreated($user));
    
    Mail::to($user)->send(new WelcomeEmail($user));
    
    return response()->json($user, 201);
}
```

### 2. Service Responsibilities

**Services contain business logic:**
- ✅ Complex validations
- ✅ Business rules
- ✅ Database transactions
- ✅ Event dispatching
- ✅ Calculations
- ❌ NO HTTP concerns (request/response)
- ❌ NO view logic

```php
// ✅ Good Service
public function createUser(UserDto $dto): User
{
    return DB::transaction(function () use ($dto) {
        // Business validation
        if ($this->emailExists($dto->email)) {
            throw ValidationException::withMessages([
                'email' => 'Email already exists'
            ]);
        }
        
        // Data transformation
        $data = $dto->toArray();
        $data['password'] = Hash::make($data['password']);
        
        // Create user
        $user = User::create($data);
        
        // Side effects
        event(new UserCreated($user));
        
        return $user;
    });
}
```

### 3. DTO Best Practices

```php
// ✅ Good DTO practices:
class UserShiftDto
{
    public function __construct(
        // 1. Type everything
        public readonly int $userId,
        public readonly int $shiftId,
        
        // 2. Use camelCase for properties
        public readonly string $shiftDate,
        public readonly string $machineCode,
        
        // 3. Optional params last with defaults
        public readonly ?string $notes = null,
    ) {
        // 4. Validation in constructor (optional)
        if (empty($machineCode)) {
            throw new \InvalidArgumentException('Machine code cannot be empty');
        }
    }
    
    // 5. Static factory from various sources
    public static function fromRequest(array $data): self { }
    public static function fromModel(UserShift $model): self { }
    public static function fromArray(array $data): self { }
    
    // 6. Transform back to array
    public function toArray(): array { }
    
    // 7. Additional helpers if needed
    public function hasNotes(): bool
    {
        return $this->notes !== null;
    }
}
```

### 4. Testing Pattern

```php
use App\DTOs\UserDto;
use App\Services\UserService;
use App\Models\User;

describe('UserService', function () {
    it('can create user with DTO', function () {
        $service = new UserService();
        
        $dto = new UserDto(
            name: 'John Doe',
            email: 'john@example.com',
            employeeNumber: 'EMP-001',
            password: 'password123'
        );
        
        $user = $service->createUser($dto);
        
        expect($user)->toBeInstanceOf(User::class);
        expect($user->name)->toBe('John Doe');
        expect($user->email)->toBe('john@example.com');
    });
    
    it('validates email uniqueness', function () {
        User::factory()->create(['email' => 'existing@example.com']);
        
        $service = new UserService();
        $dto = new UserDto(
            name: 'John',
            email: 'existing@example.com',
            employeeNumber: 'EMP-002',
            password: 'password'
        );
        
        expect(fn() => $service->createUser($dto))
            ->toThrow(ValidationException::class);
    });
});
```

---

## Migration Checklist

### Migrating from Array to DTO:

- [ ] Create DTO class in `app/DTOs/`
- [ ] Add required properties with types
- [ ] Implement `fromRequest()` method
- [ ] Implement `toArray()` method
- [ ] Update service method signature to accept DTO
- [ ] Update controller to create DTO from request
- [ ] Update existing tests
- [ ] Run tests to verify
- [ ] Update documentation

### Example Migration:

**Before:**
```php
// Service
public function createMachine(array $data): Machine
{
    return Machine::create($data);
}

// Controller
public function store(StoreMachineRequest $request)
{
    $machine = $this->service->createMachine($request->validated());
    return new MachineResource($machine);
}
```

**After:**
```php
// 1. Create DTO
class MachineDto {
    public function __construct(
        public readonly string $machineCode,
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}
    
    public static function fromRequest(array $data): self {
        return new self(
            machineCode: $data['machine_code'],
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }
    
    public function toArray(): array {
        return [
            'machine_code' => $this->machineCode,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}

// 2. Update Service
public function createMachine(MachineDto $dto): Machine
{
    return Machine::create($dto->toArray());
}

// 3. Update Controller
public function store(StoreMachineRequest $request)
{
    $dto = MachineDto::fromRequest($request->validated());
    $machine = $this->service->createMachine($dto);
    return new MachineResource($machine);
}
```

---

**Last Updated:** February 16, 2026  
**Version:** 1.0  
**Maintained By:** Backend Team
