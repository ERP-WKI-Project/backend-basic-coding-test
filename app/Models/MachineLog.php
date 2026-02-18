<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineLog extends BaseModel
{
    use HasUlidColumn;

    protected $fillable = [
        'ulid',
        'machine_code',
        'user_id',
        'event',
        'log_message',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'machine_code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStartAfter(Builder $query, $date): Builder
    {
        return $query->whereDate($this->getTable() . '.created_at', '>=', $date);
    }

    public function scopeStartBefore(Builder $query, $date): Builder
    {
        return $query->whereDate($this->getTable() . '.created_at', '<=', $date);
    }
}
