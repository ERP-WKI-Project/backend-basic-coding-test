<?php

namespace App\Models;

use App\Models\BaseAuthenticatable as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
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

    protected $fillable = [
        'employee_number',
        'name',
        'email',
        'password',
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

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'user_id', 'id');
    }
}
