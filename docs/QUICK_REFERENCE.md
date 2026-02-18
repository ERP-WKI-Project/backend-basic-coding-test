# 🚀 Quick Reference: DTO Pattern & Architecture

Panduan cepat untuk development dengan pola arsitektur baru.

---

## 📦 DTO Pattern

### Create DTO from Request
```php
// In Controller
public function store(StoreUserRequest $request)
{
    $dto = UserDto::fromRequest($request->validated());
    $user = $this->userService->createUser($dto);
    return new UserResource($user);
}
```

### Create DTO Manually
```php
$dto = new UserDto(
    name: 'John Doe',
    email: 'john@example.com',
    employeeNumber: 'EMP-001',
    password: 'secret123'
);
```

### Update with DTO (Partial)
```php
// Merge existing data with updates
public function update(UpdateUserRequest $request, User $user)
{
    $data = array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'employee_number' => $user->employee_number,
    ], $request->validated());
    
    $dto = UserDto::fromRequest($data);
    $user = $this->userService->updateUser($user, $dto);
    return new UserResource($user);
}
```

---

## 🏗️ Available DTOs

| DTO | Required Fields | Optional Fields |
|-----|----------------|----------------|
| `UserDto` | name, email, employee_number | password |
| `MachineDto` | machine_code, name | description |
| `ShiftDto` | name, day_of_week, start_time, end_time | - |
| `UserShiftDto` | user_id, shift_id, shift_date, machine_code | notes |
| `MachineLogDto` | user, machine_code, event, log_message | severity, metadata |

---

## 🎨 Service Pattern

### Instance-based (✅ Correct)
```php
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}
    
    public function store(StoreUserRequest $request)
    {
        $dto = UserDto::fromRequest($request->validated());
        return $this->userService->createUser($dto);
    }
}
```

### Static (❌ Avoid)
```php
// DON'T DO THIS
UserService::createUser($data);  // Hard to test, tight coupling
```

---

## 📁 Resource Organization

### Namespace Structure
```
app/Http/Resources/
├── BackOffice/              ← For BackOffice controllers
│   ├── UserResource.php
│   ├── MachineResource.php
│   ├── ShiftResource.php
│   └── UserShiftResource.php
├── Machine/                 ← For Machine controllers
│   └── ProfileResource.php
└── MachineLogResource.php   ← Shared by both
```

### Import Statement
```php
// ✅ Correct
use App\Http\Resources\BackOffice\UserResource;

// ❌ Wrong (old location)
use App\Http\Resources\UserResource;
```

---

## 🔄 Common Workflows

### 1. Create New Feature

```bash
# 1. Create DTO
app/DTOs/FeatureDto.php

# 2. Create Service
app/Services/FeatureService.php

# 3. Create Resource
app/Http/Resources/BackOffice/FeatureResource.php

# 4. Create Controller
app/Http/Controllers/BackOffice/FeatureController.php

# 5. Create Request Validation
app/Http/Requests/StoreFeatureRequest.php

# 6. Write Tests
tests/Feature/FeatureTest.php
```

### 2. Add Endpoint

```php
// routes/backoffice/backoffice_v1.php
Route::apiResource('feature', BackOffice\FeatureController::class);
```

### 3. Test Endpoint

```bash
php artisan test --filter FeatureTest
```

---

## 🧪 Testing Patterns

### Test with DTO
```php
it('can create feature', function () {
    $dto = new FeatureDto(
        field1: 'value1',
        field2: 'value2'
    );
    
    $feature = $this->service->createFeature($dto);
    
    expect($feature)->toBeInstanceOf(Feature::class);
    expect($feature->field1)->toBe('value1');
});
```

### Test API Endpoint
```php
it('can create feature via api', function () {
    Sanctum::actingAs($user, [SystemAbility::BACKOFFICE->value]);
    
    $response = $this->postJson('/api/backoffice/v1/feature', [
        'field1' => 'value1',
        'field2' => 'value2',
    ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('features', ['field1' => 'value1']);
});
```

---

## 📚 Cheat Sheet

### Controller Template
```php
namespace App\Http\Controllers\BackOffice;

use App\DTOs\FeatureDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeatureRequest;
use App\Http\Resources\BackOffice\FeatureResource;
use App\Services\FeatureService;

class FeatureController extends Controller
{
    public function __construct(
        private readonly FeatureService $service
    ) {}
    
    public function store(StoreFeatureRequest $request)
    {
        $dto = FeatureDto::fromRequest($request->validated());
        $feature = $this->service->create($dto);
        return new FeatureResource($feature);
    }
}
```

### Service Template
```php
namespace App\Services;

use App\DTOs\FeatureDto;
use App\Models\Feature;
use Illuminate\Support\Facades\DB;

class FeatureService
{
    public function create(FeatureDto $dto): Feature
    {
        return DB::transaction(function () use ($dto) {
            // Business logic here
            return Feature::create($dto->toArray());
        });
    }
}
```

### DTO Template
```php
namespace App\DTOs;

class FeatureDto
{
    public function __construct(
        public readonly string $field1,
        public readonly string $field2,
        public readonly ?string $optionalField = null,
    ) {}
    
    public static function fromRequest(array $data): self
    {
        return new self(
            field1: $data['field1'],
            field2: $data['field2'],
            optionalField: $data['optional_field'] ?? null,
        );
    }
    
    public function toArray(): array
    {
        return [
            'field1' => $this->field1,
            'field2' => $this->field2,
            'optional_field' => $this->optionalField,
        ];
    }
}
```

### Resource Template
```php
namespace App\Http\Resources\BackOffice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field1' => $this->field1,
            'field2' => $this->field2,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

---

## 🚨 Common Mistakes to Avoid

### ❌ Don't Pass Arrays to Services
```php
// Bad
$this->service->create($request->all());

// Good
$dto = FeatureDto::fromRequest($request->validated());
$this->service->create($dto);
```

### ❌ Don't Put Business Logic in Controllers
```php
// Bad - Controller has business logic
public function store(Request $request)
{
    if (Feature::where('code', $request->code)->exists()) {
        throw new Exception('Duplicate');
    }
    $feature = Feature::create($request->all());
    event(new FeatureCreated($feature));
    return $feature;
}

// Good - Delegate to service
public function store(StoreFeatureRequest $request)
{
    $dto = FeatureDto::fromRequest($request->validated());
    $feature = $this->service->create($dto);
    return new FeatureResource($feature);
}
```

### ❌ Don't Use Static Service Methods
```php
// Bad - Static methods
public static function create(FeatureDto $dto) { ... }

// Good - Instance methods with DI
public function create(FeatureDto $dto): Feature { ... }
```

### ❌ Don't Mix Namespaces
```php
// Bad - Wrong namespace
use App\Http\Resources\FeatureResource;

// Good - Correct namespace
use App\Http\Resources\BackOffice\FeatureResource;
```

---

## 📖 Full Documentation

For detailed explanations:

- **[BUSINESS_FLOW_GUIDE.md](docs/BUSINESS_FLOW_GUIDE.md)** - Real-world examples
- **[ARCHITECTURE_PATTERNS.md](docs/ARCHITECTURE_PATTERNS.md)** - Pattern deep dive
- **[ARCHITECTURE_IMPROVEMENTS_SUMMARY.md](docs/ARCHITECTURE_IMPROVEMENTS_SUMMARY.md)** - What changed

---

## 🎯 Quick Commands

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter UserServiceTest

# Run tests with coverage
php artisan test --coverage

# Check for errors
composer analyse  # if phpstan is configured

# Format code
./vendor/bin/pint  # if Laravel Pint is installed
```

---

**Keep this file bookmarked for quick reference!** 📌

_Last Updated: February 16, 2026_
