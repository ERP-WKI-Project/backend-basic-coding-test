<?php

namespace App\Models;

use App\Models\BaseModel as Model;

class UserShift extends Model
{
    // Cast the 'shift_date' attribute to a Carbon date object automatically
    // This allows you to use date methods on the attribute directly
    protected $casts = [
        'shift_date' => 'date',
    ];

    // Define a many-to-one relationship to the Machine model
    public function machine()
    {
        // Each UserShift belongs to one Machine
        // 'machine_id' is the foreign key in this model
        // 'id' is the primary key on the Machine model
        return $this->belongsTo(Machine::class, 'machine_id', 'id');
    }

    // Define a many-to-one relationship to the Shift model
    public function shift()
    {
        // Each UserShift belongs to one Shift
        // 'shift_id' is the foreign key in this model
        // 'id' is the primary key on the Shift model
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    // Define a many-to-one relationship to the User model
    public function user()
    {
        // Each UserShift belongs to one User
        // 'user_id' is the foreign key in this model
        // 'id' is the primary key on the User model
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
