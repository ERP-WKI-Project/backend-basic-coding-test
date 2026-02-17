<?php

namespace App\Models;

use App\Enums\MachineLog\MachineLogEventEnum;
use App\Enums\MachineLog\SeverityEnum;
use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineLog extends Model
{
    use HasUlidColumn;

    protected $fillable = [
        'machine_code',
        'user_id',
        'event',
        'log_message',
        'severity',
        'metadata',
    ];

    protected $casts = [
        'event' => MachineLogEventEnum::class,
        'severity' => SeverityEnum::class,
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the machine log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the machine that this log belongs to.
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'machine_code');
    }
}
