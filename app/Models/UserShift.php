<?php

namespace App\Models;

use \App\Models\BaseModel as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShift extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_date',
        'machine_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'shift_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the shift associated with this user shift.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    /**
     * Get the user associated with this user shift.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Create a new user shift
     */
    public static function createUserShift(array $data): self
    {
        return self::create($data);
    }

    /**
     * Get user shift by id
     */
    public static function getUserShiftById(int $id): ?self
    {
        return self::find($id);
    }

    /**
     * Update user shift by id
     */
    public static function updateUserShiftById(int $id, array $data): ?self
    {
        $userShift = self::getUserShiftById($id);

        if (!$userShift) {
            return null;
        }

        $userShift->fill($data);
        $userShift->save();

        return $userShift;
    }

    /**
     * Delete user shift by id
     */
    public static function deleteUserShiftById(int $id): bool
    {
        $userShift = self::getUserShiftById($id);

        if (!$userShift) {
            return false;
        }

        return (bool) $userShift->delete();
    }

    /**
     * Get user shifts by user id with pagination
     */
    public static function getUserShiftsByUserId(int $userId, int $perPage = 15, int $page = 1)
    {
        return self::where('user_id', $userId)->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get user shifts by shift date
     */
    public static function getUserShiftsByDate(\DateTime $date)
    {
        return self::whereDate('shift_date', $date)->get();
    }

    /**
     * Get user shifts for user by date range
     */
    public static function getUserShiftsByDateRange(int $userId, \DateTime $startDate, \DateTime $endDate)
    {
        return self::where('user_id', $userId)
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->get();
    }
}
