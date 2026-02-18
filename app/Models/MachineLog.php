<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineLog extends Model
{
    use HasUlidColumn;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'code');
    }
}
