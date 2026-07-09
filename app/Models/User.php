<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $name): bool
    {
        return $this->roles->contains('name', $name);
    }

    public function formations()
    {
        return $this->belongsToMany(Formation::class, 'inscriptions')
            ->withPivot('statut', 'date_inscription');
    }

    public function formationsEnseignees()
    {
        return $this->hasMany(Formation::class, 'formateur_id');
    }

    // Créer un token avec expiration
    public function createTokenWithExpiration(string $name, array $abilities = ['*'], int $hours = 24)
    {
        $expiresAt = Carbon::now()->addHours($hours);
        $token = $this->createToken($name, $abilities, $expiresAt);

        return $token;
    }
}
