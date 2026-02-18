<?php

namespace App\Models;

use App\Models\BaseAuthenticatable as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

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

    // Define a many-to-many relationship between User and Shift
    public function shifts(): BelongsToMany
    {
        // A user can belong to multiple shifts through the 'user_shifts' pivot table
        // 'user_id' is the foreign key for the user in the pivot table
        // 'shift_id' is the foreign key for the shift in the pivot table
        return $this->belongsToMany(Shift::class, 'user_shifts', 'user_id', 'shift_id');
    }

    // Define a one-to-many relationship between User and UserShift
    public function userShifts()
    {
        // A user can have multiple UserShift entries
        // This allows direct access to the pivot table records if you need additional data
        return $this->hasMany(UserShift::class, 'user_id', 'id');
    }
}
