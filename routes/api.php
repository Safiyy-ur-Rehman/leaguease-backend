<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\LeagueSettingController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ActivityLogController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Test route to debug
Route::get('/test', function() {
    return response()->json(['message' => 'API is working']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Leagues
    Route::apiResource('leagues', LeagueController::class);
    Route::get('/leagues/{league}/standings', [LeagueController::class, 'standings']);
    
    // League Settings
    Route::get('/leagues/{league}/settings', [LeagueSettingController::class, 'show']);
    Route::put('/leagues/{league}/settings', [LeagueSettingController::class, 'update']);
    
    // Teams
    Route::apiResource('teams', TeamController::class);
    Route::get('/leagues/{league}/teams', [TeamController::class, 'getTeamsByLeague']);
    Route::post('/leagues/{league}/teams', [TeamController::class, 'createTeamInLeague']);
    
    // Players
    Route::apiResource('players', PlayerController::class);
    Route::get('/teams/{team}/players', [PlayerController::class, 'getPlayersByTeam']);
    Route::post('/teams/{team}/players', [PlayerController::class, 'createPlayerInTeam']);
    Route::put('/players/{player}/statistics', [PlayerController::class, 'updateStatistics']);
    
    // Matches
    Route::apiResource('matches', MatchController::class);
    Route::get('/leagues/{league}/matches', [MatchController::class, 'getMatchesByLeague']);
    Route::post('/leagues/{league}/matches', [MatchController::class, 'createMatchInLeague']);
    Route::put('/matches/{match}/score', [MatchController::class, 'updateScore']);
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        // Activity logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    });
});
