<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get leagues owned by the user.
     */
    public function leagues()
    {
        return $this->hasMany(League::class);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole($role)
    {
        // Admin role has all permissions
        if ($this->role === 'admin') {
            return true;
        }
        
        // Check for exact role match
        if (is_string($role)) {
            return $this->role === $role;
        }
        
        // Check for role in array
        if (is_array($role)) {
            return in_array($this->role, $role);
        }
        
        return false;
    }
    
    /**
     * Check if user has admin role
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * Check if user has league manager role
     */
    public function isLeagueManager()
    {
        return $this->role === 'league_manager' || $this->isAdmin();
    }
    
    /**
     * Check if user has team manager role
     */
    public function isTeamManager()
    {
        return $this->role === 'team_manager' || $this->isAdmin() || $this->isLeagueManager();
    }
}
