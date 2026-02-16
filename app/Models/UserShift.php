<?php

namespace App\Models;

use App\Models\BaseModel as Model;

class UserShift extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'code');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
