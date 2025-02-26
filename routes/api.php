<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\ConnectionsController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// user authentication
Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('account-verify', 'verifyAccount');
    Route::post('login', 'login');
    Route::post('forgot-password', 'forgotPassword');
    Route::post('reset-password', 'resetPassword');
});

Route::post('image-upload', [UserController::class, 'imageUpload']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(UserController::class)->prefix('user')->group(function () {
        Route::post('/', 'list');
        Route::post('update-profile', 'update');
        Route::post('post-list', 'userPostList');
        Route::post('chat-user-list', 'chatUserList');
    });

    Route::controller(ConnectionsController::class)->prefix('connection')->group(function () {
        Route::post('/', 'list');
        Route::post('create', 'create');
        Route::put('update/{id}', 'update');
        Route::delete('delete/{id}', 'delete');
        Route::get('pending', 'pendingRequests');
        Route::post('suggest-connection', 'suggestConnections');
        Route::post('connected-user-list', 'ConnectedUserList');
    });


    Route::controller(PostController::class)->prefix('post')->group(function () {
        Route::post('/', 'list');
        Route::post('create', 'create');
        Route::put('update/{id}', 'update');
        Route::get('view/{id}', 'view');
        Route::delete('delete/{id}', 'delete');
        Route::post('like-dislike/{id}', 'postLike');
    });

    Route::controller(PostCommentController::class)->prefix('comment')->group(function () {
        Route::post('/', 'list');
        Route::post('create', 'create');
        Route::put('update/{id}', 'update');
        Route::delete('delete/{id}', 'delete');
        Route::post('like-dislike/{id}', 'likeDislike');
    });

    Route::controller(MessagesController::class)->prefix('message')->group(function () {
        Route::post('receive', 'receiveMessages');
        Route::post('send', 'sendMessage');
        Route::put('update/{id}', 'update');
        Route::delete('delete/{id}', 'delete');
        Route::post('mark-delivered', 'markAsDelivered');
        Route::post('mark-seen', 'markAsSeen');
        Route::post('mark-all-delivered', 'markAllAsDelivered');
        Route::delete('delete-attachment/{messageId}/{attachmentId}', 'deleteMessageAttachment');
        Route::get('send-html-email', 'sendFile');
        // Group message routes
        Route::prefix('group')->group(function () {
            Route::post('send', 'sendGroupMessage');
            Route::post('receive-message',  'getGroupMessages');
        });
    });



    // Group routes
    Route::controller(GroupController::class)->prefix('group')->group(function () {
        Route::post('/', 'list');
        Route::post('create', 'create');
        Route::put('update/{id}', 'update');
        Route::delete('delete/{id}', 'delete');
        Route::get('view/{id}', 'view');
        Route::post('{groupId}/members', 'addMembers');
        Route::delete('{groupId}/members/{memberId}', 'removeMember');
        Route::put('{groupId}/members/{memberId}/role', 'updateMemberRole');
    });
});
