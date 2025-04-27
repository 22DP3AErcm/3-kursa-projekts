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
use App\Http\Controllers\FinanceController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/', function () {
    return "Welcome";
});

Route::post("/register", [AuthController::class, "register"])->name("register");
Route::post("/login", [AuthController::class, "login"])->name("login");

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
    Route::post('/projects/{project}/leave', [ProjectController::class, 'leaveProject']);
    Route::get('/projects/{project}/members', [ProjectController::class, 'getMembers']);
    
    // Project blocks routes
    Route::post('/projects/{project}/blocks', [ProjectBlockController::class, 'store']);
    Route::put('/blocks/{block}', [ProjectBlockController::class, 'update']);
    Route::delete('/blocks/{block}', [ProjectBlockController::class, 'destroy']);
    Route::post('/projects/{project}/blocks/reorder', [ProjectBlockController::class, 'reorder']);
    
    // Block items routes
    Route::post('/blocks/{block}/items', [BlockItemController::class, 'store']);
    Route::put('/items/{item}', [BlockItemController::class, 'update']);
    Route::delete('/items/{item}', [BlockItemController::class, 'destroy']);
    Route::post('/items/{item}/move', [BlockItemController::class, 'moveItem']);
    Route::post('/blocks/{block}/items/reorder', [BlockItemController::class, 'reorder']);

    // Mail routes
    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{message}', [MessageController::class, 'show']);
    Route::put('/messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);

    // Invitations routes
    Route::get('/invitations', [InvitationController::class, 'index']);
    Route::post('/invitations/{invitation}/respond', [InvitationController::class, 'respond']);

    
    // User profile routes
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    // Finance routes - properly prefixed with /finance
    Route::prefix('finance')->group(function () {
        // Categories
        Route::get('/categories', [FinanceController::class, 'getCategories']);
        Route::post('/categories', [FinanceController::class, 'createCategory']);
        Route::put('/categories/{category}', [FinanceController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [FinanceController::class, 'deleteCategory']);
        
        // Projects
        Route::get('/projects', [FinanceController::class, 'getProjects']);
        Route::post('/projects', [FinanceController::class, 'createProject']);
        Route::get('/projects/{project}', [FinanceController::class, 'getProject']);
        Route::put('/projects/{project}', [FinanceController::class, 'updateProject']);
        Route::delete('/projects/{project}', [FinanceController::class, 'deleteProject']);
        
        // Transactions
        Route::get('/transactions', [FinanceController::class, 'getTransactions']);
        Route::post('/transactions', [FinanceController::class, 'createTransaction']);
        Route::put('/transactions/{transaction}', [FinanceController::class, 'updateTransaction']);
        Route::delete('/transactions/{transaction}', [FinanceController::class, 'deleteTransaction']);
        
        // Summary
        Route::get('/summary', [FinanceController::class, 'getSummary']);
    });
});