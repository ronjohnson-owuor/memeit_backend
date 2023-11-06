<?php

namespace App\Http\Controllers;

use App\Models\Follower;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class followerController extends Controller
{
    //follow and unfollow a person
    public function managefollowing(Request $request){
        
        try {
            $postData = $request->validate([
                "master_id"=>"required",
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'unable to follow this person',
                'success' => false,
                'error' => $valExe
            ]);
        } 
        $user = Auth::user();
        $id = $user -> id;
        $is_following = Follower::where('master_id',$postData['master_id'])
        ->where('follower_id',$id)->first();
        
        if(!$is_following){
            Follower::create([
                "master_id" =>$postData['master_id'],
                "follower_id" => $id
            ]);
            Notification::create([
                "master_id" =>$postData['master_id'],
                "message"=>"new following",
                "read" =>false
                
            ]);
            return response() ->json([
                "message" => "you are now following this person",
                "success" => true
            ]);
        }
        
        $is_following->delete();
        return response() ->json([
            "message" => "you just unfollowed this person",
            "success" => true
        ]);
    }
    
    // get all users
    public function get_all_users(){
        $user_profiles = User::inRandomOrder()->take(4)->get();
        $users=[];
        foreach($user_profiles as $user_profile){
            $profile = $user_profile->profile;
            $required_info = (Object)[
                "user_id" => $user_profile->id,
                "user_name" => $user_profile->name,
                "profile" => $profile
            ];
            $users[]=$required_info;
        }
        
        return response() ->json([
            "success"=>true,
            "message" =>"loading users..",
            "data" =>$users
        ]);
    }
    
    // 
    public function get_all_users_registered(){
        $user = Auth::user();
        $user_id = $user->id;
        $already_following = Follower::where("follower_id",$user_id)->pluck('master_id');
        //exclude following yourself
        $already_following[]=$user_id;
        // get users where user is not already following
        $user_profiles = User::whereNotIn('id', $already_following)->inRandomOrder()->take(4)->get();
        $users=[];
        foreach($user_profiles as $user_profile){
            $profile = $user_profile->profile;
            $required_info = (Object)[
                "user_id" => $user_profile->id,
                "user_name" => $user_profile->name,
                "profile" => $profile
            ];
            $users[]=$required_info;                             
            }
        return response() ->json([
            "success" =>true,
            "message"=>"loading users",
            "data" =>$users,
        ]);
        }

    }
