<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MachineLog extends Model
{
    // Traits included in this model
    use HasFactory, HasUlidColumn;

    // HasUlidColumn → Automatically generates a ULID for the model's primary key instead of a numeric ID
    // HasFactory → Enables the use of factories for testing and seeding

    // Define a many-to-one relationship to the Shift model
    public function shift()
    {
        // Each record belongs to one Shift
        // 'shift_id' is the foreign key in this model
        // 'id' is the primary key on the Shift model
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    // Define a many-to-one relationship to the User model
    public function user()
    {
        // Each record belongs to one User
        // 'user_id' is the foreign key in this model
        // 'id' is the primary key on the User model
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
