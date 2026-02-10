<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);

    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store']); // add by email
    Route::patch('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'updateRole']);
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);

    Route::get('/workspaces/{workspace}/contents', [ContentController::class, 'index']);
    Route::post('/workspaces/{workspace}/contents', [ContentController::class, 'store']);

    Route::get('/contents/{content}', [ContentController::class, 'show']);
    Route::patch('/contents/{content}', [ContentController::class, 'update']);
    Route::delete('/contents/{content}', [ContentController::class, 'destroy']);

    Route::patch('/contents/{content}/move', [ContentController::class, 'move']);       // kanban
    Route::patch('/contents/{content}/schedule', [ContentController::class, 'schedule']); // calendar

});
