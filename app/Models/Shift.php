<?php

namespace App\Models;

use App\Models\BaseModel as Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use HasUlidColumn, SoftDeletes;

    protected $fillable = [
        'ulid',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
    ];
}
