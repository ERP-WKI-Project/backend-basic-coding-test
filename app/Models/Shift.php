<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasUlidColumn, HasFactory;
}
