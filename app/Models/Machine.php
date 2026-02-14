<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUlidColumn;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Machine extends Model
{
    use HasUlidColumn, SoftDeletes, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'status' => \App\Enums\Machine\MachineStatus::class,
    ];
}
