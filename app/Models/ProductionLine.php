<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;

class ProductionLine extends Model
{
    use HasUlidColumn;

    /**
     * Define a one-to-many relationship between ProductionLine and Machine
     */
    public function machines()
    {
        // A ProductionLine can have multiple Machines
        // 'production_line_id' is the foreign key in the Machine model pointing to this ProductionLine
        // 'id' is the primary key of the ProductionLine model
        // This allows you to fetch all machines assigned to a specific production line
        return $this->hasMany(Machine::class, 'production_line_id', 'id');
    }
}
