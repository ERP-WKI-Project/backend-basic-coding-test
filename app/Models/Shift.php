<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasUlidColumn;

    protected $fillable = [
        'name',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'shift_id', 'id');
    }
}
