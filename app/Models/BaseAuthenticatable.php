<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BaseAuthenticatable extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasApiTokens;
    use SoftDeletes;

    protected $guarded = [];
}
