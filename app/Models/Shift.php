<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class Shift extends Model
{
    use HasUlidColumn;

/**
 * Define a many-to-many relationship between Shift and User
 */
public function users()
{
    // A Shift can have many Users through the 'user_shifts' pivot table
    // 'shift_id' is the foreign key in the pivot table pointing to this Shift
    // 'user_id' is the foreign key in the pivot table pointing to the User
    // This allows you to get all users assigned to this shift easily
    return $this->belongsToMany(User::class, 'user_shifts', 'shift_id', 'user_id');
}
}
