<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use SoftDeletes;

    /**
     * The model's default attribute values.
     *
     * @var array
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user shifts for this machine.
     */
    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'machine_code', 'machine_code');
    }

    /**
     * Get the machine logs for this machine.
     */
    public function machineLogs(): HasMany
    {
        return $this->hasMany(MachineLog::class, 'machine_code', 'machine_code');
    }
}
