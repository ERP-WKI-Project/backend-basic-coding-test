<?php

namespace App\Models;

use App\Enums\MachineStatusEnum;
use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class Machine extends Model
{
    use HasUlidColumn;

    protected $fillable = [
        'ulid',
        'code',
        'name',
        'location',
        'status',
        'description',
    ];

    protected $casts = [
        'status' => MachineStatusEnum::class,
    ];

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'machine_id', 'id');
    }
}
