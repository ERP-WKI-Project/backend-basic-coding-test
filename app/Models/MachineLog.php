<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\SoftDeletes;

class MachineLog extends Model
{
    use HasUlidColumn, SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function userShift()
    {
        return $this->belongsTo(UserShift::class, 'user_shift_id');
    }
}
