<?php

namespace App\Http\Controllers;

use App\Models\GameMatch;
use App\Models\Team;
use App\Models\League;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MatchController extends Controller
{
    /**
     * Display a listing of matches.
     */
    public function index(Request $request)
    {
        $query = GameMatch::query()->with(['homeTeam', 'awayTeam', 'league']);
        
        // Filter by league
        if ($request->has('league_id')) {
            $query->where('league_id', $request->league_id);
        }
        
        // Filter by team (home or away)
        if ($request->has('team_id')) {
            $query->where(function($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('match_date', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('match_date', '<=', $request->to_date);
        }
        
        // Order by match date (default: upcoming first)
        $direction = $request->has('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
        $query->orderBy('match_date', $direction);
        
        $matches = $query->paginate($request->per_page ?? 15);
        
        return response()->json($matches);
    }

    /**
     * Get matches by league ID
     */
    public function getMatchesByLeague(string $leagueId, Request $request)
    {
        $league = League::findOrFail($leagueId);
        
        // Check if user has access to the league
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = GameMatch::where('league_id', $leagueId)->with(['homeTeam', 'awayTeam']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Order by match date
        $direction = $request->has('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
        $query->orderBy('match_date', $direction);
        
        $matches = $query->paginate($request->per_page ?? 15);
        
        return response()->json($matches);
    }

    /**
     * Get matches by team ID
     */
    public function getMatchesByTeam(string $teamId, Request $request)
    {
        $team = Team::findOrFail($teamId);
        
        // Check if user has access to the team's league
        $league = $team->league;
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $query = GameMatch::where(function($q) use ($teamId) {
            $q->where('home_team_id', $teamId)
              ->orWhere('away_team_id', $teamId);
        })->with(['homeTeam', 'awayTeam', 'league']);
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Order by match date
        $direction = $request->has('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
        $query->orderBy('match_date', $direction);
        
        $matches = $query->paginate($request->per_page ?? 15);
        
        return response()->json($matches);
    }

    /**
     * Store a newly created match in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'league_id' => 'required|exists:leagues,id',
            'home_team_id' => 'required|exists:teams,id',
            'away_team_id' => 'required|exists:teams,id|different:home_team_id',
            'match_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled,postponed',
            'home_team_score' => 'nullable|integer|min:0',
            'away_team_score' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Check if home and away teams belong to the specified league
        $homeTeam = Team::findOrFail($request->home_team_id);
        $awayTeam = Team::findOrFail($request->away_team_id);
        
        if ($homeTeam->league_id != $request->league_id || $awayTeam->league_id != $request->league_id) {
            return response()->json([
                'message' => 'Home and away teams must belong to the specified league'
            ], 422);
        }
        
        // Check if user has permission to create matches in this league
        $league = League::findOrFail($request->league_id);
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'league_manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Create match
        $match = GameMatch::create([
            'league_id' => $request->league_id,
            'home_team_id' => $request->home_team_id,
            'away_team_id' => $request->away_team_id,
            'match_date' => $request->match_date,
            'location' => $request->location ?? $homeTeam->home_venue,
            'notes' => $request->notes,
            'status' => $request->status,
            'home_team_score' => $request->home_team_score,
            'away_team_score' => $request->away_team_score,
        ]);

        // Log activity
        ActivityLog::log(
            Auth::id(),
            'created',
            'match',
            $match->id,
            "Created match between {$homeTeam->name} and {$awayTeam->name}",
            null,
            $match->toArray()
        );

        return response()->json($match, 201);
    }

    /**
     * Display the specified match.
     */
    public function show(string $id)
    {
        $match = GameMatch::with(['homeTeam', 'awayTeam', 'league'])->findOrFail($id);
        
        // Check if user has access to the match's league
        $league = $match->league;
        if (!$league->is_public && $league->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($match);
    }

    /**
     * Update the specified match in storage.
     */
    public function update(Request $request, string $id)
    {
        $match = GameMatch::findOrFail($id);
        
        // Check if user has permission to update this match
        $league = $match->league;
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'league_manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'home_team_id' => 'sometimes|required|exists:teams,id',
            'away_team_id' => 'sometimes|required|exists:teams,id|different:home_team_id',
            'match_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|in:scheduled,in_progress,completed,cancelled,postponed',
            'home_team_score' => 'nullable|integer|min:0',
            'away_team_score' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // If teams are being updated, check they belong to the same league
        if ($request->has('home_team_id')) {
            $homeTeam = Team::findOrFail($request->home_team_id);
            if ($homeTeam->league_id != $match->league_id) {
                return response()->json([
                    'message' => 'Home team must belong to the same league'
                ], 422);
            }
        }
        
        if ($request->has('away_team_id')) {
            $awayTeam = Team::findOrFail($request->away_team_id);
            if ($awayTeam->league_id != $match->league_id) {
                return response()->json([
                    'message' => 'Away team must belong to the same league'
                ], 422);
            }
        }
        
        // Store old state for logging and standings update
        $oldState = $match->toArray();
        $oldStatus = $match->status;
        
        // Update match
        $match->fill($request->all());
        
        // If status is being changed to or from completed, we need to handle team standings
        if (($oldStatus !== 'completed' && $match->status === 'completed') || 
            ($oldStatus === 'completed' && $match->status !== 'completed')) {
            
            // Perform the update within a transaction
            return DB::transaction(function() use ($match, $oldState, $oldStatus) {
                $homeTeam = Team::findOrFail($match->home_team_id);
                $awayTeam = Team::findOrFail($match->away_team_id);
                
                // If old status was completed, revert the previous result
                if ($oldStatus === 'completed') {
                    $this->updateTeamStandings(
                        $homeTeam, 
                        $awayTeam, 
                        $oldState['home_team_score'], 
                        $oldState['away_team_score'], 
                        true // Revert
                    );
                }
                
                // If new status is completed, apply the new result
                if ($match->status === 'completed') {
                    $this->updateTeamStandings(
                        $homeTeam, 
                        $awayTeam, 
                        $match->home_team_score, 
                        $match->away_team_score, 
                        false // Apply
                    );
                }
                
                $match->save();
                
                // Log activity
                ActivityLog::log(
                    Auth::id(),
                    'updated',
                    'match',
                    $match->id,
                    "Updated match between {$homeTeam->name} and {$awayTeam->name}",
                    $oldState,
                    $match->toArray()
                );
                
                return response()->json($match);
            });
        } else {
            // Regular update (no standings affected)
            $match->save();
            
            // Get team names for activity log
            $homeTeam = Team::findOrFail($match->home_team_id);
            $awayTeam = Team::findOrFail($match->away_team_id);
            
            // Log activity
            ActivityLog::log(
                Auth::id(),
                'updated',
                'match',
                $match->id,
                "Updated match between {$homeTeam->name} and {$awayTeam->name}",
                $oldState,
                $match->toArray()
            );
            
            return response()->json($match);
        }
    }

    /**
     * Update team standings based on match result
     */
    private function updateTeamStandings($homeTeam, $awayTeam, $homeScore, $awayScore, $revert = false)
    {
        // Get league settings
        $league = League::with('leagueSetting')->findOrFail($homeTeam->league_id);
        $settings = $league->leagueSetting;
        
        // Default point values if settings not available
        $winPoints = $settings->points_for_win ?? 3;
        $drawPoints = $settings->points_for_draw ?? 1;
        $lossPoints = $settings->points_for_loss ?? 0;
        
        // Determine match result
        $homeResult = '';
        $awayResult = '';
        
        if ($homeScore > $awayScore) {
            $homeResult = 'win';
            $awayResult = 'loss';
        } else if ($homeScore < $awayScore) {
            $homeResult = 'loss';
            $awayResult = 'win';
        } else {
            $homeResult = $awayResult = 'draw';
        }
        
        // Update standings based on results
        $multiplier = $revert ? -1 : 1;
        
        // Home team
        if ($homeResult === 'win') {
            $homeTeam->wins += (1 * $multiplier);
            $homeTeam->points += ($winPoints * $multiplier);
        } else if ($homeResult === 'loss') {
            $homeTeam->losses += (1 * $multiplier);
            $homeTeam->points += ($lossPoints * $multiplier);
        } else {
            $homeTeam->draws += (1 * $multiplier);
            $homeTeam->points += ($drawPoints * $multiplier);
        }
        
        // Away team
        if ($awayResult === 'win') {
            $awayTeam->wins += (1 * $multiplier);
            $awayTeam->points += ($winPoints * $multiplier);
        } else if ($awayResult === 'loss') {
            $awayTeam->losses += (1 * $multiplier);
            $awayTeam->points += ($lossPoints * $multiplier);
        } else {
            $awayTeam->draws += (1 * $multiplier);
            $awayTeam->points += ($drawPoints * $multiplier);
        }
        
        // Save changes
        $homeTeam->save();
        $awayTeam->save();
    }

    /**
     * Remove the specified match from storage.
     */
    public function destroy(string $id)
    {
        $match = GameMatch::findOrFail($id);
        
        // Check if user has permission to delete this match
        $league = $match->league;
        if ($league->user_id !== Auth::id() && !Auth::user()->hasRole(['admin', 'league_manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // If match was completed, we need to update team standings
        if ($match->status === 'completed') {
            // Perform the delete within a transaction
            return DB::transaction(function() use ($match, $id) {
                // Get teams
                $homeTeam = Team::findOrFail($match->home_team_id);
                $awayTeam = Team::findOrFail($match->away_team_id);
                
                // Store match info for logging
                $matchInfo = $match->toArray();
                
                // Revert team standings
                $this->updateTeamStandings(
                    $homeTeam, 
                    $awayTeam, 
                    $match->home_team_score, 
                    $match->away_team_score, 
                    true // Revert
                );
                
                // Delete match
                $match->delete();
                
                // Log activity
                ActivityLog::log(
                    Auth::id(),
                    'deleted',
                    'match',
                    $id,
                    "Deleted match between {$homeTeam->name} and {$awayTeam->name}",
                    $matchInfo,
                    null
                );
                
                return response()->json(null, 204);
            });
        } else {
            // Regular delete (no standings affected)
            // Get team names for activity log
            $homeTeam = Team::findOrFail($match->home_team_id);
            $awayTeam = Team::findOrFail($match->away_team_id);
            
            // Store match info for logging
            $matchInfo = $match->toArray();
            
            // Delete match
            $match->delete();
            
            // Log activity
            ActivityLog::log(
                Auth::id(),
                'deleted',
                'match',
                $id,
                "Deleted match between {$homeTeam->name} and {$awayTeam->name}",
                $matchInfo,
                null
            );
            
            return response()->json(null, 204);
        }
    }
} 