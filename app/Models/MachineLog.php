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


    public static function getUserMachineActivityReportPaginated(
        int $perPage = 15,
        int $page = 1,
        ?\DateTime $startDate = null,
        ?\DateTime $endDate = null,
        ?string $machineCode = null,
        ?int $userId = null,
        ?string $event = null
    ) {
        $query = self::query()
            ->join('users', 'machine_logs.user_id', '=', 'users.id')
            ->join('machines', 'machine_logs.machine_code', '=', 'machines.machine_code')
            ->leftJoin('user_shifts', function ($join) {
                $join->on('user_shifts.user_id', '=', 'machine_logs.user_id')
                    ->on('user_shifts.machine_code', '=', 'machine_logs.machine_code')
                    ->whereRaw('user_shifts.shift_date = DATE(machine_logs.created_at)');
            })
            ->leftJoin('shifts', 'user_shifts.shift_id', '=', 'shifts.id')
            ->select([
                'machine_logs.id as log_id',
                'machine_logs.machine_code as machine_code',
                'machine_logs.user_id as user_id',
                'machine_logs.event as event',
                'machine_logs.log_message as log_message',
                'machine_logs.created_at as log_created_at',
                'users.employee_number as user_employee_number',
                'users.name as user_name',
                'users.email as user_email',
                'machines.name as machine_name',
                'machines.description as machine_description',
                'machines.location as machine_location',
                'machines.status as machine_status',
                'user_shifts.shift_date as shift_date',
                'shifts.id as shift_id',
                'shifts.name as shift_name',
                'shifts.day_of_week as shift_day_of_week',
                'shifts.start_time as shift_start_time',
                'shifts.end_time as shift_end_time',
            ]);

        if ($startDate !== null && $endDate !== null) {
            $start = (clone $startDate)->setTime(0, 0, 0);
            $end = (clone $endDate)->setTime(23, 59, 59);
            $query->whereBetween('machine_logs.created_at', [
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
            ]);
        }

        if ($machineCode !== null) {
            $query->where('machine_logs.machine_code', $machineCode);
        }

        if ($userId !== null) {
            $query->where('machine_logs.user_id', $userId);
        }

        if ($event !== null) {
            $query->where('machine_logs.event', $event);
        }

        return $query
            ->orderBy('machine_logs.created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
