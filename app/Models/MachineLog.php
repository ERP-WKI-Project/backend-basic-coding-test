<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class MachineLog extends Model
{
    use HasUlidColumn;

    protected $fillable = [
        'ulid',
        'machine_id',
        'user_id',
        'event',
        'log_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
