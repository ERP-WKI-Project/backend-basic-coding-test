<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'machine_code',
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'machine_code';
    }

    public function machineLogs(): HasMany
    {
        return $this->hasMany(MachineLog::class, 'machine_code', 'machine_code');
    }

    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'machine_code', 'machine_code');
    }
}

