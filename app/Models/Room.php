<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class Room extends Model
{
    use HasUlidColumn;

    /**
     * Define a one-to-many relationship between Room and Machine
     */
    public function machines()
    {
        // A Room can have multiple Machines
        // 'room_id' is the foreign key in the Machine model pointing to this Room
        // 'id' is the primary key of the Room model
        // This allows you to easily fetch all machines located in a specific room
        return $this->hasMany(Machine::class, 'room_id', 'id');
    }
}
