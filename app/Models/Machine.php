<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Machine extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'machine_code',
        'name',
        'description',
        'location',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Create a new machine
     */
    public static function createMachine(array $data): self
    {
        return self::create($data);
    }

    /**
     * Get machine by machine_code
     */
    public static function getByMachineCode(string $machineCode): ?self
    {
        return self::where('machine_code', $machineCode)->first();
    }

    /**
     * Update machine by machine_code
     */
    public static function updateByMachineCode(string $machineCode, array $data): ?self
    {
        $machine = self::getByMachineCode($machineCode);

        if (!$machine) {
            return null;
        }

        $machine->fill($data);
        $machine->save();

        return $machine;
    }

    /**
     * Delete machine by machine_code
     */
    public static function deleteByMachineCode(string $machineCode): bool
    {
        $machine = self::getByMachineCode($machineCode);

        if (!$machine) {
            return false;
        }

        return (bool) $machine->delete();
    }

    /**
     * Get list of machines with pagination
     */
    public static function getListPaginated(int $perPage = 15, int $page = 1)
    {
        return self::query()->paginate($perPage, ['*'], 'page', $page);
    }
}
