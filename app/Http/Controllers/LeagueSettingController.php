<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\LeagueSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LeagueSettingController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(string $leagueId)
    {
        $league = League::findOrFail($leagueId);
        
        // Check if user has access to the league
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $settings = LeagueSetting::where('league_id', $leagueId)->firstOrFail();
        
        return response()->json($settings);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $leagueId)
    {
        $league = League::findOrFail($leagueId);
        
        // Check if user has permission to update the league settings
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
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
            'yellow_cards_for_suspension' => 'integer|min:1',
            'points_for_win' => 'integer|min:0',
            'points_for_draw' => 'integer|min:0',
            'points_for_loss' => 'integer|min:0',
            'custom_terminology' => 'nullable|array',
            'additional_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $settings = LeagueSetting::where('league_id', $leagueId)->first();
        
        // If settings don't exist yet, create them
        if (!$settings) {
            $settings = new LeagueSetting(['league_id' => $leagueId]);
        }
        
        // Store old values for logging
        $oldValues = $settings->toArray();
        
        // Update settings
        $settings->fill($request->all());
        $settings->save();
        
        // Log activity
        ActivityLog::log(
            $request->user()->id,
            'updated',
            'league_settings',
            $settings->id,
            "Updated settings for league '{$league->name}'",
            $oldValues,
            $settings->toArray()
        );
        
        // If point values have changed, update all team standings
        if (isset($request->points_for_win) || isset($request->points_for_draw) || isset($request->points_for_loss)) {
            foreach ($league->teams as $team) {
                $team->updateRecord();
            }
        }

        return response()->json($settings);
    }
} 