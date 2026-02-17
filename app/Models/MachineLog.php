<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineLog extends Model
{
    use HasUlidColumn;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'machine_code',
        'user_id',
        'event',
        'log_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user associated with this machine log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the machine associated with this machine log.
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'machine_code');
    }

    /**
     * Create a new machine log
     */
    public static function createLog(array $data): self
    {
        return self::create($data);
    }

    /**
     * Get machine log by id with relationships
     */
    public static function getWithRelationsById(int $id): ?self
    {
        return self::with('user', 'machine')->find($id);
    }

    /**
     * Get machine logs by machine code with pagination
     */
    public static function getByMachineCodeWithRelationsPaginated(
        string $machineCode,
        int $perPage = 15,
        int $page = 1,
        ?int $userId = null,
        ?string $event = null
    ) {
        $query = self::with('user', 'machine')->where('machine_code', $machineCode);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($event !== null) {
            $query->where('event', $event);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all machine logs with pagination and optional filters
     */
    public static function getWithRelationsPaginated(
        int $perPage = 15,
        int $page = 1,
        ?string $machineCode = null,
        ?int $userId = null,
        ?string $event = null
    ) {
        $query = self::with('user', 'machine');

        if ($machineCode !== null) {
            $query->where('machine_code', $machineCode);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($event !== null) {
            $query->where('event', $event);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Check if machine log exists
     */
    public static function existsById(int $id): bool
    {
        return self::where('id', $id)->exists();
    }
}
