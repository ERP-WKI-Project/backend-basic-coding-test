<?php

namespace App\Models;

use App\Models\BaseAuthenticatable as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    use SoftDeletes;
    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "employee_number",
        "name",
        "email",
        "email_verified_at",
        "password",
        "remember_token"
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'employee_number';
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'user_shifts', 'user_id', 'shift_id');
    }

    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'user_id', 'id');
    }

    public static function createUser(array $data): self
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return self::create($data);
    }

    public static function getByEmailAndPassword(string $email, string $password): ?self
    {
        $user = self::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public static function updateUserById(int|string $id, array $data)
    {
        $user = self::find($id);

        if (!$user) {
            return null;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->fill($data);
        $user->save();

        return $user;
    }


    public static function deleteUserById(int|string $id): bool
    {
        $user = self::find($id);
        if (!$user) {
            return false;
        }

        return (bool) $user->delete();
    }

    public static function getUserListPaginated(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return self::query()->paginate($perPage, $columns);
    }
}
