<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\adsController;
use App\Http\Controllers\postController;
use App\Http\Controllers\userController;
use App\Http\Controllers\videoController;
use App\Http\Controllers\walletController;
use App\Http\Controllers\followerController;
use App\Http\Controllers\notificationController;


// routes that doesnt require authentication
Route::post("/login", [UserController::class, 'login']);
Route::post("/signup", [UserController::class, 'signIn']);
Route::get("/retrieve-post", [postController::class, 'retrievepost']);
Route::post("/get-user-profile", [UserController::class, 'getUserProfile']);
Route::get("/follower-recommendation", [followerController::class, 'get_all_users']);
Route::post("/all-users", [UserController::class, 'allusers']);
Route::post("/global-inbuilt", [adsController::class, 'inbuiltGlobal']);
Route::post("/global-banner", [adsController::class, 'bannerGlobal']);
Route::post("/home-banner", [adsController::class, 'bannerHome']);
Route::post("/home-inbuilt", [adsController::class, 'inbuiltHome']);
Route::post("/payment-result", [walletController::class, 'callBackFunction']);
 


// routes that require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post("/get-user-profile-with-token", [UserController::class, 'getUserProfile']);
    Route::get("/follower-recommendation-with-token", [followerController::class, 'get_all_users_registered']);
    Route::get("/post-recommendation", [postController::class, 'recommended_post']);
    Route::post("/create-post", [postController::class, 'addpost']);
    Route::post("/publish-ad", [adsController::class, 'publishAd']);
    Route::post("/update-ad", [adsController::class, 'updateAd']);
    Route::post("/delete-ad", [adsController::class, 'destroy']);
    Route::post("/active-expired-ad", [adsController::class, 'active_expired_ads']);
    Route::post("/report-video", [videoController::class, 'reportVideo']);
    Route::post("/retrieve-reported-videos", [videoController::class, 'videos_for_the_reporter']);
    Route::post("/my-reported-videos", [videoController::class, 'retrieveReportedvideos']);
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
    Route::post("/bio-edit", [userController::class, 'updateBio']);
    Route::post("/update-password", [userController::class, 'updatepassword']);
    Route::post("/update-profile", [userController::class, 'updateprofile']);
    /* wallet apis */
    Route::post("/withdraw-money", [walletController::class, 'widthdrawMoney']);
    Route::post("/send-money", [walletController::class, 'sendMoney']);
    Route::post("/get-ballance", [walletController::class, 'getBallance']);
    Route::post("/get-transaction", [walletController::class, 'transactionRecords']);
    Route::post("/confirm-deposit", [walletController::class, 'depositMoney']);
    Route::post("/generate-token", [walletController::class, 'generate_access_token']);
    Route::post("/stk-push", [walletController::class, 'stkPush']);
});
