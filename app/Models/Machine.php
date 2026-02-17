<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    /** @use HasFactory<\Database\Factories\MachineFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'brand',
        'model',
        'serial_number',
        'capacity_per_hour',
        'capacity_unit',
        'production_line_id',
        'room_id',
        'purchase_date',
        'installation_date',
        'status',
        'notes'
    ];

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
