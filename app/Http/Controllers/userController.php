<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Follower;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Expr\Cast\Object_;

class userController extends Controller
{
    
        /* LOGIN FUNCTION */
        public function login(Request $request){
            try{
                $userData = $request->validate([
                    'email' => 'required',
                    'password' => 'required'
                ]);
            }catch(ValidationException $valExe){
                return response() -> json([
                    'message' => 'log in unsuccessfull check input fields',
                    'success' => false
                ]);
            }
            
            // if validation is passed
            $user = User::where('email', $userData['email'])->first();
            if(!$user){
                return response() -> json([
                    "message" => "you dont have an account",
                    "success" => false
                ]);
            }
            
            $password_correct = Hash::check($userData['password'], $user->password);
            if(!$password_correct){
                return response() -> json([
                    "message" => "incorrect password",
                    "success" => false
                ]);
            }
        
            // create a login token which will be used to authenticate the user
            Notification::create([
                "master_id" =>$user->id,
                "message"=>"new log in to your account",
                "read" =>false
                
            ]);
            $login_token = $user -> createToken('user') ->plainTextToken;
            return response() -> json([
                "message" => "login succesfull",
                "token" => $login_token,
                "success" => true
            ]);
        }
        
        
        
        
        
        
        
        
        
    /* SIGNIN FUNCTION */
    public function signIn(Request $request){
    try {
        $userData = $request->validate([
            "name"=>"required",
            "email"=>"required|email|unique:users,email",
            "phone"=>"required",
            "gender"=>"required",
            "status"=>"required",
            "profile"=>"required|mimes:jpg,png,jpeg|file",
            "password"=>"required"
        ]);
    }catch(ValidationException $err) {
        return response() -> json([
            'message' => 'unable to signin check input fields',
            'success' => false
        ]);
    }
    
    $image_to_be_uploaded = $request->file('profile');
    $newFilename = time() .'memeit'.'.'.$image_to_be_uploaded->getClientOriginalExtension();
    $filePath = "profiles/".$newFilename;
    Storage::disk('s3')->put($filePath,file_get_contents($image_to_be_uploaded),'public');
    $path = Storage::disk('s3')->url($filePath);
    $protected_password = bcrypt($userData['password']);
    $user = User::create([
        'name' => $userData['name'],
        'email' =>  $userData['email'],
        'password' => $protected_password,
        'phone' => $userData['phone'],
        'gender' => $userData['gender'],
        'status' => $userData['status'],
        'profile' => $path
    ]);
    

    $user_token = $user -> createToken('user')->plainTextToken;
    Notification::create([
        "master_id" =>$user->id,
        "message"=>"welcome to memeit🎉",
        "read" =>false
        
    ]);
    return response() -> json([
        'message'=>"sign up successfull",
        'token' => $user_token,
        'success'=>true
    ]);
}   
    
    
   
/* BASIC EDITING FUNCTION */
    public function basicprofileedit(Request $request){
        try {
            $userData = $request->validate([
                "name"=>"required",
                "phone"=>"required",
                "status"=>"required",
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'profile not saved',
                'success' => false
            ]);
        }
        $user= Auth::user();
        $userDataToUpdate = [
            'name' => $userData['name'],
            'phone' => $userData['phone'],
            'status' => $userData['status']
        ];
        // the error👇🏿 is nothing just ignore it.
        $user->update($userDataToUpdate);
        Notification::create([
            "master_id" =>$user->id,
            "message"=>"your profie was edited",
            "read" =>false
            
        ]);
        return response() ->json([
            "message" =>"changes saved👌🏿",
            "success" =>true
        ]); 
    }
    
    
     // password update
    public function updatepassword(Request $request){
         try {
             $userData = $request->validate([
                 "old_password"=>"required",
                 "new_password"=>"required",
         ]);
        
          } catch(ValidationException $valExe) {
             return response() -> json([
             'message' => 'password not updated',
             'success' => false,
                    "error" =>$valExe
            ]);
       }
        
        $user = Auth::user();
        $password_correct = Hash::check($userData['old_password'], $user->password);
        if(!$password_correct){
            return response() -> json([
                "message" => "incorrect old password",
                "success" => false
            ]);
        }
        $user_new_password = bcrypt($userData["new_password"]);
        $userDataToUpdate = [
            "password" =>$user_new_password
        ];
        $user->update($userDataToUpdate);
        Notification::create([
            "master_id" =>$user->id,
            "message"=>"account password changed",
            "read" =>false
            
        ]);
        return response() ->json([
            "message" =>"password updated👌🏿",
            "success" =>true
        ]); 
    } 
    
    /* get user profile for everybody to see  */
    public function getUserProfile(Request $request){
        
        $id = $request ->master_id;
        if(!$id){
            return response() ->json([
                "message"=>"unable to retrieve user profile",
                "success"=>false
            ]);
        }
        /* START */
        $posts = Post::where("post_owner_id",$id)->orderBy('id', 'DESC')->get();
        $followers = Follower::where("master_id",$id)->count();
        $all_post = [];
        foreach ($posts as $post) {
            $all_comments_of_a_post =[];
            $post_created_at  = Carbon::parse($post->created_at)->format("d-M-Y");
            $user = User::where('id',$id)->first();
            // add likes and comments here too
            $likes= Like::where('post_id',$post->id)->count();
            $comments= Comment::where('post_id',$post->id)->get();
            foreach ($comments as $comment) {
                $comment_created_at  = Carbon::parse($comment->created_at)->format("d-M-Y");
                $user_who_commented = User::where('id',$comment->user_id)->first(); 
                $complete_comment = (Object)[
                    'user_name' => $user_who_commented ->name,
                    'profile' => $user_who_commented->profile,
                    'user_comment' => $comment->comment,
                    "created_at" =>$comment_created_at
                ];
                $all_comments_of_a_post[]=$complete_comment;
            }
            $media_path =null;
            if($post->media_type){
                if($post->media_type == 1){
                    $media_path = $post->post_media;
                }else if($post->media_type== 2){
                    $media_path = $post->post_media;  
                }
            }
            $complete_post = (Object)[
                'user_name' =>$user->name,
                "created_at" =>$post_created_at,
                'profile' => $user->profile,
                'post_description' => $post->post_description,
                'post_media' => $media_path,
                'id' =>$post ->id,
                'media_type' => $post ->media_type,
                'likes' => $likes,
                'comments' => $all_comments_of_a_post
            ];
            $all_post[]=$complete_post;
        }
        /* END */
        $user_profile = (Object)[
            "name" =>$user->name,
            "profile" =>$user->profile,
            "status" =>$user->status,
            "followers" =>$followers,
            "user_post" =>$all_post
        ];
        return response() ->json([
            "success" =>true,
            "data" =>$user_profile
        ]);
    }
    
    
    public function getUser(){
        $user = Auth::user();
        $id=$user->id;
        
        /* START */
        $posts = Post::where("post_owner_id",$id)->orderBy('id', 'DESC')->get();
        $followers = Follower::where("master_id",$id)->count();
        $all_post = [];
        
        
        foreach ($posts as $post) {
            $all_comments_of_a_post =[];
            $post_created_at  = Carbon::parse($post->created_at)->format("d-M-Y");
            $user = User::where('id',$id)->first();
            // add likes and comments here too
            $likes= Like::where('post_id',$post->id)->count();
            $comments= Comment::where('post_id',$post->id)->get();
            foreach ($comments as $comment) {
                $comment_created_at  = Carbon::parse($comment->created_at)->format("d-M-Y");
                $user_who_commented = User::where('id',$comment->user_id)->first(); 
                $complete_comment = (Object)[
                    'user_name' => $user_who_commented ->name,
                    'profile' => $user_who_commented->profile,
                    'user_comment' => $comment->comment,
                    "created_at" =>$comment_created_at
                ];
                $all_comments_of_a_post[]=$complete_comment;
            }
            $media_path =null;
            if($post->media_type){
                if($post->media_type == 1){
                    $media_path =$post->post_media;
                }else if($post->media_type== 2){
                    $media_path = $post->post_media;  
                }
            }
            $complete_post = (Object)[
                'user_name' => 'You',
                "created_at" =>$post_created_at,
                'profile' => $user->profile,
                'post_description' => $post->post_description,
                'post_media' => $media_path,
                'id' =>$post ->id,
                'media_type' => $post ->media_type,
                'likes' => $likes,
                'comments' => $all_comments_of_a_post
            ];
            $all_post[]=$complete_post;
        }
        /* END */
        
        $user = (Object)[
            "name" =>$user->name,
            "email" =>$user->email,
            "profile" =>$user->profile,
            "status" =>$user->status,
            "gender" =>$user ->gender,
            "phone" =>$user->phone,
            "followers" =>$followers,
            "user_post" =>$all_post
        ];
        return response() ->json([
            "success" =>true,
            "data" =>$user
        ]);
    }
    
    
    
    // profile update
    public function updateprofile(Request $request){
        try{
            $request ->validate([
                'new_profile' => 'required|mimes:jpg,png,jpeg|file'
            ]);
       } catch(ValidationException $error){
           return response()->json([
            "message"=>"there was an error with the image upload",
            "success"=>false,
            "error" =>$error
           ]);
       }
        try{
            $user = Auth::user();
            $currentProfile = $user->profile;
            if ($currentProfile) {
                $disk = Storage::disk('s3');
                if ($disk->exists($currentProfile)) {
                    // deletes image from the folder
                    $disk ->delete($currentProfile);
                }
            }
            $image = $request->file('new_profile');
            $newFilename =time().'memeit'.'.'.$image->getClientOriginalExtension();
            $filePath = "profiles/".$newFilename;
            Storage::disk('s3')->put($filePath,file_get_contents($image),'public');
            $path = Storage::disk('s3')->url($filePath);
            $userDataToUpdate = [
                'profile' => $path,
            ];
            // the error👇🏿 is nothing just ignore it.
            $user->update($userDataToUpdate);
            Notification::create([
                "master_id" =>$user->id,
                "message"=>"profile picture updated",
                "read" =>false
                
            ]);
            return response()->json([
                "message" =>"profile updated successfully",
                "success"=>true
            ]);
        }catch(\Throwable $th){
            return response()->json([
                'message' =>'profile not updated',
                "success"=>false,
                'error'=>$th
            ]);
        }
    }
    
    
    //get all users from the database this will be used to conduct search
    public function allusers(){
        $user_profiles= User::all();
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
            "data" =>$users,
        ]); 
    }
    
    
    
     
}
