<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'block_team_admins',
        'allow_stats_entry',
        'allow_division_matches',
        'home_team_change_date',
        'home_team_change_time',
        'home_team_change_status',
        'home_team_change_venue',
        'away_team_change_date',
        'away_team_change_time',
        'away_team_change_status',
        'away_team_change_venue',
        'require_match_approval',
        'auto_approve_results',
        'allow_officials_marking',
        'require_officials',
        'suspend_on_red_card',
        'yellow_cards_for_suspension',
        'points_for_win',
        'points_for_draw',
        'points_for_loss',
        'custom_terminology',
        'additional_settings',
    ];

    protected $casts = [
        'block_team_admins' => 'boolean',
        'allow_stats_entry' => 'boolean',
        'allow_division_matches' => 'boolean',
        'home_team_change_date' => 'boolean',
        'home_team_change_time' => 'boolean',
        'home_team_change_status' => 'boolean',
        'home_team_change_venue' => 'boolean',
        'away_team_change_date' => 'boolean',
        'away_team_change_time' => 'boolean',
        'away_team_change_status' => 'boolean',
        'away_team_change_venue' => 'boolean',
        'require_match_approval' => 'boolean',
        'auto_approve_results' => 'boolean',
        'allow_officials_marking' => 'boolean',
        'require_officials' => 'boolean',
        'suspend_on_red_card' => 'boolean',
        'yellow_cards_for_suspension' => 'integer',
        'points_for_win' => 'integer',
        'points_for_draw' => 'integer',
        'points_for_loss' => 'integer',
        'custom_terminology' => 'array',
        'additional_settings' => 'array',
    ];

    /**
     * Get the league that owns the settings.
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }
} 