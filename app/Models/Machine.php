<?php

namespace App\Models;

use App\Enums\MachineStatus;
use App\Models\BaseModel as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'status' => MachineStatus::class,
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'machine_code', 'code');
    }

    public function machineLogs(): HasMany
    {
        return $this->hasMany(MachineLog::class, 'machine_code', 'code');
    }
}
