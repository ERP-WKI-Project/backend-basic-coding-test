<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class Shift extends Model
{
    use HasUlidColumn;

    protected $fillable = ['name', 'day_of_week', 'start_time', 'end_time'];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'shift_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_shifts');
    }
}
