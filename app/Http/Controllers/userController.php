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
        
        /* validate user input username and password are required 
        in order to log in */
        try{
            
            $userData = $request->validate([
                'email' => 'required',
                'password' => 'required'
            ]);
            
        }catch(ValidationException $valExe){
            return response() -> json([
                'message' => 'you might have entered wrong data.check your form again',
                'success' => false
            ]);
        }
        
        
        /* once the validation is passed find the user email in the database and 
        check if the password given matches the password sent from the frontend*/
        $client_email= $userData['email'];
        $user = User::where('email',$client_email)->first();
        if(!$user){
            return response() -> json([
                "message" => "that account is not availlable",
                "success" => false
            ]);
        }
        $client_passcode =$userData['password'];
        $password_correct = Hash::check($client_passcode, $user->password);
        if(!$password_correct){
            return response() -> json([
                "message" => "incorrect password",
                "success" => false
            ]);
        }
        
        
        /* at this point the the user is logged in so show him a notification 
        then generate  a session token for the user. */
            Notification::create([
                "master_id" =>$user->id,
                "message"=>"there is a new login to your account🤗",
                "read" =>false
            ]);
        $login_token = $user -> createToken('user') ->plainTextToken;
            return response() -> json([
                "message" => "You are in create your post now",
                "token" => $login_token,
                "success" => true
            ]);
    }
    /*  END OF LOGIN FUNCTION */






    /* SIGNIN FUNCTION */
    public function signIn(Request $request){
        try {
            $userData = $request->validate([
                "name"=>"required",
                "email"=>"required|email|unique:users,email",
                "phone"=>"required",
                "gender"=>"required",
                "status"=>"required",
                "school" =>"required",
                "public" => "required",
                "profile"=>"required|mimes:jpg,png,jpeg|file",
                "password"=>"required"
            ]);
        }catch(ValidationException $err) {
            return response() -> json([
                'message' =>'you missed something in your form check again',
                'success' => false
            ]);
        }
        
        
        /* once the validation of user input is successfull then we proceed to analyze,prepare 
        and process user data from client. We use s3 for image storage in a folder called profiles */
        
        $profile_can_be_public = false;
        $profile_type =$userData['public'];
        if($profile_type == "true"){
            $profile_can_be_public = true;
        }
        
        /* start of image processing */
        $image_to_be_uploaded = $request->file('profile');
        $newFilename = time() .'memeit'.'.'.$image_to_be_uploaded->getClientOriginalExtension();
        $filePath = "profiles/".$newFilename;
        try{
            Storage::disk('s3')->put($filePath,file_get_contents($image_to_be_uploaded),'public');    
        }catch(\Throwable $th){
            return response() ->json([
                "success" => false,
                "message" =>"check your internet and try again"
            ]);
        }
    /* end of image processing and storage of image*/

    /* start of image processing for database storage */
    $path = Storage::disk('s3')->url($filePath);
    $protected_password = bcrypt($userData['password']);
    
    /* generate random bio for users which they will be able to edit later when they access their profile */
    $bio_arrrays = [
        "Hey there I am using machizi👋",
        "machizi forever.no tiktok just machizi",
        "me and my friends love machizi",
        "I am using machizi what about you🤔",
    ];
    $selected_bio = collect($bio_arrrays) -> random();
    
    /* prepare and insert data to the database */
    $customer_name = $userData['name'];
    $customer_email =$userData['email'];
    $customer_phone = $userData['phone'];
    $customer_school =$userData['school'];
    $customer_gender =$userData['gender'];
    $customer_status =$userData['status'];
    
    $user = User::create([
    'name' => $customer_name,
    'email' => $customer_email ,
    'password' => $protected_password,
    'phone' =>$customer_phone,
    'school' =>$customer_school,
    'gender' =>$customer_gender,
    'status' =>$customer_status,
    'profile_for_public' =>$profile_can_be_public,
    'profile' => $path,
    "verified" => false,
    "premium_user" => false,
    "bio" => $selected_bio
    ]);


    
    /* if the process is successfull create token and welcome the user to the app
    you notify the user with a welcome message  */
    $user_token = $user -> createToken('user')->plainTextToken;
    Notification::create([
        "master_id" =>$user->id,
        "message"=>"welcome to memeit🎉",
        "read" =>false
    ]);
    return response() -> json([
        'message'=>"🎉🎉congratulations.you are one of machizi members",
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
                'message' => 'check your input forms and try again.',
                'success' => false
            ]);
        }
        
        /*authenticate the user and then change his/her credentials in the database */
        $user= Auth::user();
        $new_name = $userData['name'];
        $new_phonenumber =  $userData['phone'];
        $new_status =  $userData['status'];
        $userDataToUpdate = [
            'name' => $new_name,
            'phone' =>$new_phonenumber,
            'status' =>$new_status
        ];
    /* the error👇🏿 is nothing just ignore it.But if you find a way to solve it please help
    the team.Thanks in advance. */
    $user->update($userDataToUpdate);
    
    
    
    /* notify the user that his/her profile was changed */
    Notification::create([
        "master_id" =>$user->id,
        "message"=>"profile data changed.",
        "read" =>false
    ]);
    return response() ->json([
        "message" =>"changes saved👌🏿",
        "success" =>true
    ]); 
    }
    
    
    
    
    
    
    
    
    /*  USER PASSWORD CHANGES => extra security to be added to the feature
    eg sending code to the gmail of the client first */
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
    
    
    
    
    
    
    /* this profile is what everybody will see when they click the the icon of a certain user
    there are two profiles 
    1.THE PROFILE THAT THE USER HIMSELF WILL SEE
    2.THE PROFILE OF THE USER THAT  OTHERS WILL SEE
    so this is profile number. 2
    */
    public function getUserProfile(Request $request){
        
        $id = $request ->master_id; //id of the user who's profile is in question.
        if(!$id){
            return response() ->json([
                "message"=>"unable to this profile maybe the user deleted it or its unavailable.",
                "success"=>false
            ]);
        }
        
        
        
        /* authenticate the visitor of the web page */
        $is_following = false;
        $user_visiting_profile = Auth::user();
        if ($user_visiting_profile) {
            $user_visiting_profile_id = $user_visiting_profile ->id;
            $checkIfIsFollowing = Follower::where("master_id",$id) ->
             where("follower_id",$user_visiting_profile_id) ->first();
             if($checkIfIsFollowing){$is_following=true;}
        }
        
        
        
        /* if the id of the user is is present then get the post that the user has post and also the id of the  people 
        who are following the user in question  */
        $posts = Post::where("post_owner_id",$id)->orderBy('id', 'DESC')->get();
        $following = Follower::where("master_id",$id)->get();
        $following_profiles = [];
        // from the followers id's we create followers profiles ready fo the client if the followers id's exist
        if($following){
            foreach ($following as $follow){
                $user_following_data = User::where("id",$follow->follower_id)->first();
                /* we get the id image and name only  */
                $follower_data = (Object)[
                    "id" => $user_following_data ->id,
                    "profile" => $user_following_data ->profile,
                    "name" => $user_following_data -> name
                ];
                $following_profiles[]=$follower_data;
            }  
        }
        
        
        /* count the number of followers that that  spesific user have and also get and
         arrange his/her post ready for the client. */
        $followers = Follower::where("master_id",$id)->count(); //count followers
        $all_post = []; //initialize  a new post with empty arrays
        foreach ($posts as $post) {
        $all_comments_of_a_post =[]; //empty comment for each and every post of the user
        $post_created_at  = Carbon::parse($post->created_at)->format("d-M-Y");
        $user = User::where('id',$id)->first();
        // add likes and comments here too
        $likes= Like::where('post_id',$post->id)->count();
        $comments= Comment::where('post_id',$post->id)->get();
        foreach ($comments as $comment) {
        $comment_created_at  = Carbon::parse($comment->created_at)->format("d-M-Y");
        $user_who_commented = User::where('id',$comment->user_id)->first(); 
        /* IMPORTANT NOTICE: this complete comment carries the comment ,image ,profile,and the comment that the user actually
        wrote but the IMPORTANT thing here is the user_id it is really necessary because if the user id 
        is not found others will not be able to open the profile of the user who commented.so make sure the user 
        id variable is not inteferreed with at all⚠.Thank you*/
        
        $commentor_name =$user_who_commented ->name;
        $commentor_profile = $user_who_commented->profile;
        $commentor_comments =$comment->comment;
        $commentor_id = $user_who_commented -> id;
        
        
        
        $complete_comment = (Object)[
            'user_name' =>$commentor_name ,
            'profile' =>$commentor_profile,
            'user_comment' => $commentor_comments,
            'user_id' =>$commentor_id,
            "created_at" =>$comment_created_at
        ];
        /* so here we combine all the comments of a post into one array and then attaching it
         to the associated post after sorting up the post */
        $all_comments_of_a_post[]=$complete_comment;
        }
        
        
        
        /*START OF POST MEDIA FILES AND THEIR PREPARATIONS  */
        $media_path =null;
        if($post->media_type){
            if($post->media_type == 1){
                $media_path = $post->post_media;
            }else if($post->media_type== 2){
                $media_path = $post->post_media;  
            }
        }
        /*END OF POST MEDIA FILES AND THEIR PREPARATIONS  */
        
        
        
        /* this is now the complete post ready to be sent to the client for viewing
        containing the comments that was prepared above and all the post details */
        $complete_post = (Object)[
            'user_name' =>$user->name,
            "created_at" =>$post_created_at,
            'profile' => $user->profile,
            'post_description' => $post->post_description,
            'post_media' => $media_path,
            'id' =>$post ->id,
            'bio' => $user ->bio,
            'user_id' =>$user->id,
            'media_type' => $post ->media_type,
            'likes' => $likes,
            'comments' => $all_comments_of_a_post
        ];
        $all_post[]=$complete_post;
        }
        /* END */
        
        
        
        /* here now we add the post to the required user so that thet can be shipped as one uint instead of 
        retrieving the user comment separately */
        $user_profile = (Object)[
            "name" =>$user->name,
            "profile" =>$user->profile,
            "status" =>$user->status,
            "followers" =>$followers,
            "followers_profiles"=>$following_profiles,
            'bio' => $user ->bio,
            "is_following" => $is_following,
            "user_post" =>$all_post
        ];
        
        
        /* return a response with the complete profile just assembled and let the users enjoy. */
        return response() ->json([
            "success" =>true,
            "data" =>$user_profile
        ]);
    }
    
    
    
    
    
    
    
    /* This is now what get the user own profile when the user click to view his or her own profile this
    is what is shown to them. it is just the same this as the one above the diffrence is that this one the user 
    himself send s a token to the backend while the one up above sends an id of the profile we are looking 
    for that is the only diffrence so for reffrence of the variables check above. */
    public function getUser(){
        
        // authenticate the user and from their get his/her id
        $user = Auth::user();
        $id=$user->id;

        /* NOTICE:The code above is explained in the getuserprofile function above you can check the
        explanation if their is something you are not understanding👆👆 */
        
        /* START */
        $posts = Post::where("post_owner_id",$id)->orderBy('id', 'DESC')->get();
        $followers = Follower::where("master_id",$id)->count();
        $followers_profile = Follower::where("master_id",$id)->get();
        $followingprofiles = [];
        $all_post = [];

        
        
        
        if($followers_profile){
            foreach ($followers_profile as $follow){
                $follower_id = $follow->follower_id;
                $user_following = User::where("id",intval($follower_id)) ->first();
                $follower_data = (Object)[
                    "id" => $user_following ->id,
                    "profile" => $user_following ->profile,
                    "name" => $user_following -> name,
                ];
                $followingprofiles[]=$follower_data;
            }  
        }


        
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
                    'user_id' => $user_who_commented -> user_id,
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
                'user_id'=> $user->id,
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
            "id"=>$id,
            "profile" =>$user->profile,
            "status" =>$user->status,
            "gender" =>$user ->gender,
            "phone" =>$user->phone,
            "followers" =>$followers,
            "followers_profiles" =>$followingprofiles,
            'bio' => $user ->bio,
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
            /* check if the current profile exist and deleteit before adding the new profile
            to the storage buckend make sure that when you are storing the image to s3 you have added the 'public ' to show that 
            the image is publicly accessible */
            $currentProfile = $user->profile;
            if ($currentProfile) {
                $disk = Storage::disk('s3');
                if ($disk->exists($currentProfile)) {
                    // deletes image from the folder
                    $disk ->delete($currentProfile);
                }
            }
            
            
            // upload the image to s3 with the 'public' notation to show that the image is public
            $image = $request->file('new_profile');
            $newFilename =time().'memeit'.'.'.$image->getClientOriginalExtension();
            $filePath = "profiles/".$newFilename;
            Storage::disk('s3')->put($filePath,file_get_contents($image),'public');
            $path = Storage::disk('s3')->url($filePath);
            
            
            
            
            // update database with the relevant data
            $userDataToUpdate = [
                'profile' => $path,
            ];
            // the error👇🏿 is nothing just ignore it.
            $user->update($userDataToUpdate);
            
            
            // notify the user that his profile has been changed
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
    
    
    
    
    
    
    
    function updateBio(Request $request){
        $user = Auth::user();
        $data = $request -> validate([
            "bio" => "required"
        ]);
        
        $to_be_updated = [
            "bio" => $data["bio"]
        ];
        
        $user -> update($to_be_updated);
        Notification::create([
            "master_id"=>$user->id,
            "message"=>"bio updated 😎",
            "read" => false
        ]);
            return response()->json([
                "message"=> "bio updated",
                "success"=>true
            ]);
        
    }
    
     
}
