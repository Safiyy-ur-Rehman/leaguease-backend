<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'country',
        'type',
        'sport_type',
        'season_name',
        'season_start_date',
        'season_end_date',
        'website_url',
        'logo_path',
        'description',
        'is_public',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'season_start_date' => 'date',
        'season_end_date' => 'date',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($league) {
            // Generate a slug if not provided
            if (empty($league->slug)) {
                $league->slug = Str::slug($league->name);
                
                // Ensure the slug is unique
                $count = 2;
                $baseSlug = $league->slug;
                while (self::where('slug', $league->slug)->exists()) {
                    $league->slug = $baseSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Get the user who owns the league.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the teams for the league.
     */
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    /**
     * Get the matches for the league.
     */
    public function matches()
    {
        return $this->hasMany(GameMatch::class);
    }

    /**
     * Get the settings for the league.
     */
    public function leagueSetting()
    {
        return $this->hasOne(LeagueSetting::class);
    }

    /**
     * Get the standings for the league.
     */
    public function getStandingsAttribute()
    {
        return $this->teams()
            ->where('is_active', true)
            ->orderBy('points', 'desc')
            ->orderBy('wins', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }
} 