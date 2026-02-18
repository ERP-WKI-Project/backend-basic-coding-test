<?php

namespace App\Models;

use App\Models\BaseAuthenticatable as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use SoftDeletes;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'employee_number';
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'user_shifts', 'user_id', 'shift_id');
    }

    public function userShifts(): HasMany
    {
        return $this->hasMany(UserShift::class, 'user_id', 'id');
    }

    public function machineLogs(): HasMany
    {
        return $this->hasMany(MachineLog::class, 'user_id', 'id');
    }
}
