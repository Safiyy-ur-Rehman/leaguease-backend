<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'name',
        'slug',
        'logo_path',
        'description',
        'home_venue',
        'team_color',
        'contact_email',
        'contact_phone',
        'website',
        'wins',
        'losses',
        'draws',
        'points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'points' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($team) {
            // Generate a slug if not provided
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
                
                // Ensure the slug is unique
                $count = 2;
                $baseSlug = $team->slug;
                while (self::where('slug', $team->slug)->exists()) {
                    $team->slug = $baseSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Get the league that owns the team.
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the players for the team.
     */
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the home matches for the team.
     */
    public function homeMatches()
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    /**
     * Get the away matches for the team.
     */
    public function awayMatches()
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    /**
     * Get all matches for the team (home and away)
     */
    public function getAllMatchesAttribute()
    {
        return GameMatch::where('home_team_id', $this->id)
            ->orWhere('away_team_id', $this->id)
            ->orderBy('match_date', 'desc')
            ->get();
    }

    /**
     * Update team record and points based on match results
     */
    public function updateRecord()
    {
        $wins = $this->homeMatches()
            ->where('status', 'completed')
            ->where('home_team_score', '>', 'away_team_score')
            ->count();
            
        $wins += $this->awayMatches()
            ->where('status', 'completed')
            ->where('away_team_score', '>', 'home_team_score')
            ->count();
            
        $draws = $this->homeMatches()
            ->where('status', 'completed')
            ->whereColumn('home_team_score', 'away_team_score')
            ->count();
            
        $draws += $this->awayMatches()
            ->where('status', 'completed')
            ->whereColumn('home_team_score', 'away_team_score')
            ->count();
            
        $losses = $this->homeMatches()
            ->where('status', 'completed')
            ->where('home_team_score', '<', 'away_team_score')
            ->count();
            
        $losses += $this->awayMatches()
            ->where('status', 'completed')
            ->where('away_team_score', '<', 'home_team_score')
            ->count();
            
        // Get point settings from league
        $pointsForWin = $this->league->leagueSetting->points_for_win ?? 3;
        $pointsForDraw = $this->league->leagueSetting->points_for_draw ?? 1;
        $pointsForLoss = $this->league->leagueSetting->points_for_loss ?? 0;
        
        $totalPoints = ($wins * $pointsForWin) + ($draws * $pointsForDraw) + ($losses * $pointsForLoss);
        
        $this->update([
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'points' => $totalPoints
        ]);
    }
} 