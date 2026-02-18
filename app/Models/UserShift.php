<?php

namespace App\Models;

use \App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
