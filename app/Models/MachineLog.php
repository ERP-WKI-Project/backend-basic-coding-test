<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class MachineLog extends Model
{
    use HasUlidColumn;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
