<?php

use App\Http\Controllers\followerController;
use App\Http\Controllers\notificationController;
use App\Http\Controllers\postController;
use App\Http\Controllers\userController;
use App\Http\Controllers\videoController;
use Illuminate\Support\Facades\Route;


// routes that doesnt require authentication
Route::post("/login", [UserController::class, 'login']);
Route::post("/signup", [UserController::class, 'signIn']);
Route::get("/retrieve-post", [postController::class, 'retrievepost']);
Route::post("/get-user-profile", [UserController::class, 'getUserProfile']);
Route::get("/follower-recommendation", [followerController::class, 'get_all_users']);
Route::post("/all-users", [UserController::class, 'allusers']);
 


// routes that require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get("/follower-recommendation-with-token", [followerController::class, 'get_all_users_registered']);
    Route::get("/post-recommendation", [postController::class, 'recommended_post']);
    Route::post("/create-post", [postController::class, 'addpost']);
    Route::post("/report-video", [videoController::class, 'reportVideo']);
    Route::post("/retrieve-reported-videos", [videoController::class, 'videos_for_the_reporter']);
    Route::post("/drop-charges", [videoController::class, 'drop_charges']);
    Route::post("/edit-post", [postController::class, 'editpost']);
    Route::post("/retrieve-notification", [notificationController::class, 'getnotification']);
    Route::post("/mark-notification", [notificationController::class, 'markasread']);
    Route::post("/delete-notification", [notificationController::class, 'deletenotification']);
    Route::post("/delete-post", [postController::class, 'deletepost']);
    Route::post("/like-post", [postController::class, 'likepost']);
    Route::post("/comment-post", [postController::class, 'commentpost']);
    Route::post("/delete-comment", [postController::class, 'deletecomment']);
    Route::get("/get-user", [UserController::class, 'getUser']);
    
    
    
    Route::post("/manage-followers", [followerController::class, 'managefollowing']);
    
    Route::post("/basic-profile-edit", [userController::class, 'basicprofileedit']);
    Route::post("/update-password", [userController::class, 'updatepassword']);
    Route::post("/update-profile", [userController::class, 'updateprofile']);
    
});
