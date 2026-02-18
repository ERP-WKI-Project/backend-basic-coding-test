<?php

namespace App\Models;

use \App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserShift extends Model
{
    use HasUlidColumn;

    protected $fillable = [
        'ulid',
        'user_id',
        'shift_id',
        'shift_date',
        'machine_code',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function getShiftStartAttribute(): Carbon
    {
        return Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->shift->start_time);
    }

    public function getShiftEndAttribute(): Carbon
    {
        $start = $this->shift_start;
        $end = Carbon::parse($this->shift_date->format('Y-m-d') . ' ' . $this->shift->end_time);

        if ($end->lt($start)) {
            $end->addDay();
        }

        return $end;
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'machine_code');
    }
}
