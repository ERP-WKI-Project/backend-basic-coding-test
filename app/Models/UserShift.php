<?php

namespace App\Models;

use \App\Models\BaseModel as Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShift extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'shift_date',
        'machine_code',
        'notes',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_code', 'machine_code');
    }

    public function machineLogs()
    {
        return $this->hasMany(MachineLog::class, 'user_id', 'user_id')
            ->where('machine_code', $this->machine_code)
            ->whereDate('created_at', $this->shift_date);
    }
}
