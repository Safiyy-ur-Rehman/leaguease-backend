<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    use HasFactory;
    
    // Define the table name explicitly since we're not using the default name
    protected $table = 'matches';

    protected $fillable = [
        'league_id',
        'home_team_id',
        'away_team_id',
        'match_date',
        'venue',
        'status',
        'home_team_score',
        'away_team_score',
        'referee',
        'match_notes',
        'statistics',
        'is_featured',
        'weather_conditions',
        'broadcast_channel',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'statistics' => 'array',
        'is_featured' => 'boolean',
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
    ];

    /**
     * Get the league that owns the match.
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the home team for the match.
     */
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team for the match.
     */
    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Check if the match is completed.
     */
    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    /**
     * Get the winner of the match (if any).
     */
    public function getWinnerAttribute()
    {
        if (!$this->is_completed || $this->home_team_score === $this->away_team_score) {
            return null;
        }

        return $this->home_team_score > $this->away_team_score
            ? $this->homeTeam
            : $this->awayTeam;
    }

    /**
     * Update match score and related team records
     */
    public function updateScore($homeTeamScore, $awayTeamScore)
    {
        $oldStatus = $this->status;
        
        $this->update([
            'home_team_score' => $homeTeamScore,
            'away_team_score' => $awayTeamScore,
            'status' => 'completed'
        ]);
        
        // Update team records if the match is now completed
        if ($oldStatus !== 'completed' && $this->status === 'completed') {
            $this->homeTeam->updateRecord();
            $this->awayTeam->updateRecord();
        }
    }
} 