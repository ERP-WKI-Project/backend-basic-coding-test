<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'password_reset_tokens';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'email';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The type of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Create or replace a reset token for an email
     */
    public static function createResetToken(string $email, string $token): self
    {
        // Use updateOrCreate to ensure single token per email
        return self::updateOrCreate(
            ['email' => $email],
            ['token' => $token, 'created_at' => now()]
        );
    }

    /**
     * Find token record by token value
     */
    public static function getByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }

    /**
     * Delete token record by email
     */
    public static function deleteByEmail(string $email): bool
    {
        return (bool) self::where('email', $email)->delete();
    }

    /**
     * Check if token is valid (exists and not expired)
     * @param int $minutes valid minutes from created_at
     */
    public static function isValidToken(string $token, int $minutes = 60): bool
    {
        $row = self::getByToken($token);
        if (!$row) return false;
        if (!$row->created_at) return false;
        return $row->created_at->greaterThanOrEqualTo(now()->subMinutes($minutes));
    }
}
