<?php

namespace App\Models;

use \App\Models\BaseModel as Model;

class UserShift extends Model
{
    protected $casts = [
        'shift_date' => 'date',
    ];

    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_date',
        'machine_code',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
