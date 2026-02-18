<?php

namespace App\Models;

use App\Models\BaseAuthenticatable as Authenticatable;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasUlidColumn;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ulid',
        'employee_number',
        'name',
        'email',
        'password',
        'is_active',
    ];

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
            'is_active' => 'boolean',
        ];
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'user_shifts', 'user_id', 'shift_id');
    }

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'user_id', 'id');
    }
}
