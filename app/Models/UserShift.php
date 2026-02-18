<?php

namespace App\Models;

use App\Models\BaseModel as Model;

class UserShift extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'machine_id',
        'shift_date',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id', 'id');
    }
}
