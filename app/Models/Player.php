<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'first_name',
        'last_name',
        'jersey_number',
        'date_of_birth',
        'position',
        'photo_path',
        'bio',
        'contact_email',
        'contact_phone',
        'nationality',
        'height',
        'weight',
        'statistics',
        'is_active',
        'is_captain',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'statistics' => 'array',
        'is_active' => 'boolean',
        'is_captain' => 'boolean',
    ];

    /**
     * Get the team that owns the player.
     */
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the player's full name.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the player's age.
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Update player statistics 
     */
    public function updateStatistics(array $statistics)
    {
        $currentStats = $this->statistics ?: [];
        $updatedStats = array_merge($currentStats, $statistics);
        
        $this->update([
            'statistics' => $updatedStats
        ]);
    }
} 