<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentApprovalController;
use App\Http\Controllers\ContentChecklistItemController;
use App\Http\Controllers\ContentCommentController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    /*
    | User
    */
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    /*
    | Workspaces
    */
    Route::prefix('workspaces')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index']);
        Route::post('/', [WorkspaceController::class, 'store']);
        Route::get('/{workspace}', [WorkspaceController::class, 'show']);

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

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/workspaces', [WorkspaceController::class, 'index']);
//     Route::post('/workspaces', [WorkspaceController::class, 'store']);
//     Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);

//     Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
//     Route::post('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store']); // add by email
//     Route::patch('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'updateRole']);
//     Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);

//     Route::get('/workspaces/{workspace}/contents', [ContentController::class, 'index']);
//     Route::post('/workspaces/{workspace}/contents', [ContentController::class, 'store']);

//     Route::get('/contents/{content}', [ContentController::class, 'show']);
//     Route::patch('/contents/{content}', [ContentController::class, 'update']);
//     Route::delete('/contents/{content}', [ContentController::class, 'destroy']);

//     Route::patch('/contents/{content}/move', [ContentController::class, 'move']);       // kanban
//     Route::patch('/contents/{content}/schedule', [ContentController::class, 'schedule']); // calendar

//     Route::get('/contents/{content}/comments', [ContentCommentController::class, 'index']);
//     Route::post('/contents/{content}/comments', [ContentCommentController::class, 'store']);

//     Route::get('/contents/{content}/checklist', [ContentChecklistItemController::class, 'index']);
//     Route::post('/contents/{content}/checklist', [ContentChecklistItemController::class, 'store']);

//     Route::patch('/checklist-items/{item}', [ContentChecklistItemController::class, 'update']);
//     Route::delete('/checklist-items/{item}', [ContentChecklistItemController::class, 'destroy']);

//     Route::get('/contents/{content}/approvals', [ContentApprovalController::class, 'index']);
//     Route::post('/contents/{content}/approve', [ContentApprovalController::class, 'approve']);
//     Route::post('/contents/{content}/request-changes', [ContentApprovalController::class, 'requestChanges']);
// });
