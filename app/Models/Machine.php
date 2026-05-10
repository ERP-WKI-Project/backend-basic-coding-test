<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function machineLogs()
    {
        return $this->hasMany(MachineLog::class, 'machine_code', 'code');
    }

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'machine_code', 'code');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($machine) {
            if (empty($machine->ulid)) {
                $machine->ulid = Str::ulid();
            }
        });
    }
}
