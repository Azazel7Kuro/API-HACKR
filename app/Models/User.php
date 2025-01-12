<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Log[] $logs
 *
 * @package App\Models
 */
class User extends Authenticatable implements JWTSubject
{
    protected $table = 'users';

    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'remember_token'
    ];

    public function logs()
    {
        return $this->hasMany(Log::class, 'id_user');
    }

    // Identifiant principal pour JWT (par défaut, l'id de l'utilisateur)
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Ajouter des claims personnalisés au token (peut être vide)
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function isAdmin()
    {
        return $this->roles->contains('name', 'admin');
    }

    public function isUser()
    {
        return $this->roles->contains('name', 'user');
    }
}
