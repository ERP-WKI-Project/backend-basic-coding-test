<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class MachineLog extends Model
{
    use HasUlidColumn;

    protected $fillable = [
        'ulid',
        'user_id',
        'machine_code',
        'event',
        'log_message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
