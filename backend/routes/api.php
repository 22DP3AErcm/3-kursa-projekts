<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectBlockController;
use App\Http\Controllers\BlockItemController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MessageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/', function () {
    return "Welcome";
});

Route::get('/register', function () {
    return "Register";
});

Route::post("/register", [AuthController::class, "register"])->name("register");

Route::post("/login", [AuthController::class, "login"])->name("login");

Route::get('/logout', function () {
    return "Logout";
});

Route::middleware('auth:sanctum')->group(function () {
    // Events routes
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    
    // Projects routes
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{project}/invite', [ProjectController::class, 'inviteUser']);
    
    // Project blocks routes
    Route::post('/projects/{project}/blocks', [ProjectBlockController::class, 'store']);
    Route::put('/blocks/{block}', [ProjectBlockController::class, 'update']);
    Route::delete('/blocks/{block}', [ProjectBlockController::class, 'destroy']);
    Route::post('/projects/{project}/blocks/reorder', [ProjectBlockController::class, 'reorder']);
    Route::post('/projects/{project}/leave', [ProjectController::class, 'leaveProject']);
    
    // Block items routes
    Route::post('/blocks/{block}/items', [BlockItemController::class, 'store']);
    Route::put('/items/{item}', [BlockItemController::class, 'update']);
    Route::delete('/items/{item}', [BlockItemController::class, 'destroy']);
    Route::post('/items/{item}/move', [BlockItemController::class, 'moveItem']);
    Route::post('/blocks/{block}/items/reorder', [BlockItemController::class, 'reorder']);

    // Add these new membership routes
    Route::get('/projects/{project}/members', [ProjectController::class, 'getMembers']);
    Route::post('/projects/{project}/invite', [ProjectController::class, 'inviteUser']); // You already have this
    Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'removeMember']);

    // Mail routes
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::put('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);

    // Invitations routes
    Route::get('/invitations', [InvitationController::class, 'index']);
    Route::post('/invitations/{invitation}/respond', [InvitationController::class, 'respond']);
});