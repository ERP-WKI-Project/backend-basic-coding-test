<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUlidColumn;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'day_of_week' => 'integer',
        'start_time' => 'string',
        'end_time' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the users for this shift.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_shifts', 'shift_id', 'user_id');
    }

    /**
     * Get the user shifts for this shift.
     */
    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'shift_id', 'id');
    }

    /**
     * Create a new shift
     */
    public static function createShift(array $data): self
    {
        return self::create($data);
    }

    /**
     * Get shift by id
     */
    public static function getShiftById(int $id): ?self
    {
        return self::find($id);
    }

    /**
     * Update shift by id
     */
    public static function updateShiftById(int $id, array $data): ?self
    {
        $shift = self::getShiftById($id);

        if (!$shift) {
            return null;
        }

        $shift->fill($data);
        $shift->save();

        return $shift;
    }

    /**
     * Delete shift by id
     */
    public static function deleteShiftById(int $id): bool
    {
        $shift = self::getShiftById($id);

        if (!$shift) {
            return false;
        }

        return (bool) $shift->delete();
    }

    /**
     * Get shifts by day of week
     */
    public static function getShiftsByDayOfWeek(int $dayOfWeek)
    {
        return self::where('day_of_week', $dayOfWeek)->get();
    }

    /**
     * Get list of shifts with pagination
     */
    public static function getListPaginated(int $perPage = 15, int $page = 1)
    {
        return self::query()->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get list of shifts by day of week with pagination
     */
    public static function getShiftsByDayOfWeekPaginated(int $dayOfWeek, int $perPage = 15, int $page = 1)
    {
        return self::where('day_of_week', $dayOfWeek)->paginate($perPage, ['*'], 'page', $page);
    }
}
