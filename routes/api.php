<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentApprovalController;
use App\Http\Controllers\ContentChecklistItemController;
use App\Http\Controllers\ContentCommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    /*
    | User
    */
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
    });

    /*
    | Workspaces
    */
    Route::prefix('workspace')->group(function () {
        Route::controller(WorkspaceController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{workspace}', 'show');
        });

        /*
        | Workspace Members
        */
        Route::get('/{workspace}/members', [WorkspaceMemberController::class, 'index']);
        Route::post('/{workspace}/members', [WorkspaceMemberController::class, 'store']);
        Route::patch('/{workspace}/members/{user}', [WorkspaceMemberController::class, 'updateRole']);
        Route::delete('/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);

        /*
        | Workspace Contents
        */
        Route::get('/{workspace}/contents', [ContentController::class, 'index']);
        Route::post('/{workspace}/contents', [ContentController::class, 'store']);
    });

    /*
    | Contents
    */
    Route::prefix('contents')->group(function () {
        Route::get('/{content}', [ContentController::class, 'show']);
        Route::patch('/{content}', [ContentController::class, 'update']);
        Route::delete('/{content}', [ContentController::class, 'destroy']);

        // Kanban & Calendar
        Route::patch('/{content}/move', [ContentController::class, 'move']);
        Route::patch('/{content}/schedule', [ContentController::class, 'schedule']);

        /*
        | Comments
        */
        Route::get('/{content}/comments', [ContentCommentController::class, 'index']);
        Route::post('/{content}/comments', [ContentCommentController::class, 'store']);

        /*
        | Checklist
        */
        Route::get('/{content}/checklist', [ContentChecklistItemController::class, 'index']);
        Route::post('/{content}/checklist', [ContentChecklistItemController::class, 'store']);

        /*
        | Approvals
        */
        Route::get('/{content}/approvals', [ContentApprovalController::class, 'index']);
        Route::post('/{content}/approve', [ContentApprovalController::class, 'approve']);
        Route::post('/{content}/request-changes', [ContentApprovalController::class, 'requestChanges']);
    });

    /*
    | Checklist Items
    */
    Route::prefix('checklist-items')->group(function () {
        Route::patch('/{item}', [ContentChecklistItemController::class, 'update']);
        Route::delete('/{item}', [ContentChecklistItemController::class, 'destroy']);
    });
});
