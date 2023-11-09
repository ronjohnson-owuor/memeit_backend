<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Follower;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class postController extends Controller
{
    // add post to the database
    public function addpost(Request $request){
        try {
            $postData = $request->validate([
                "post_description"=>"required",
                'post_sensitive'=>"required",
                'post_tags' =>"required"
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'post not created,check your input',
                'success' => false,
                'error' => $valExe
            ]);
        }
        
        // type 3 are post with no image ,1 are post with video
        // and 2 are post with images The default post is type 3
        $sensitive = false;
        $is_sensitive = $postData['post_sensitive'];
        if($is_sensitive == "true"){
            $sensitive = true;
        }
        $type =3;
        $user = Auth::user();
        $id = $user -> id;
        $media_file = $request->file('post_media');
        // if media file is not availlable or equal to null
        /* thats means thats a type three media type hence no metadata
         required */
        if(!$media_file){
            Post::create([
                'post_description' => $postData['post_description'],
                'post_owner_id' => $id,
                'post_media' =>null,
                'post_sensitive'=>$sensitive,
                'post_tags' =>$postData['post_tags'],
                'media_type' => $type
            ]);
            Notification::create([
                "master_id" =>$id,
                "message"=>"your post has been created👍",
                "read" =>false
                
            ]);
            return response() -> json([
                'message' => 'post created',
                'success' => true,
            ]); 
        }
        
        
        // check if the extention is a video or a image because only these to formarts are supported
        //path to save the video eg if its a video then /videos and images /images
        $path='';
        if($media_file->isValid()){
            $mime = $media_file ->getMimeType();
            if(str_starts_with($mime,'image')){
                $path=0;
                $type=2;
            }elseif(str_starts_with($mime,'video')){
                $path=1;
                $type=1;
            }else{
                // if the video is not an image or a video then the user is trying to 
                /* upload an unsupported video formart to the database */
                return response() ->json([
                    "message" => "unsupported formart",
                    "success" =>false
                ]);
            }
        }
        // set the path respectively to the media type
        $file_ext = $media_file->getClientOriginalExtension();
        $newFilename = time() .'memeitfilesassets'.'.'.$file_ext;
        if($path == 0){
            $filepath="images/".$newFilename; 
        }else{
            $filepath= "videos/".$newFilename;   
        }
        $disk = Storage::disk('s3');
        try{
          $disk->put($filepath,file_get_contents($media_file),'public');  
        } catch(Throwable $th){
            return response() ->json([
                "success" => false,
                "message" => "check your internet connection and try again"
            ]);
        }
        
        $newPath = $disk -> url($filepath);
        // insert to the database
            Post::create([
                'post_description' => $postData['post_description'],
                'post_owner_id' => $id,
                'post_media' => $newPath,
                'post_sensitive'=>$sensitive,
                'post_tags' =>$postData['post_tags'],
                'media_type' => $type
            ]);
            Notification::create([
                "master_id" =>$id,
                "message"=>"your post has just been created👍",
                "read" =>false
                
            ]);
        // return response
        return response() -> json([
            'message' => 'post created successfully',
            'success' => true,
        ]);  
    }
    
    
    
    // delete post
    public function deletepost(Request $request){
        try {
            $postData = $request->validate([
                "post_id"=>"required",
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'unable to delete post',
                'success' => false,
                'error' => $valExe
            ]);
        }
        
        $user = Auth::user();
        $user_id = $user -> id;
        $post_to_be_deleted = Post::where('id',$postData['post_id'])
        ->where('post_owner_id',$user_id)->first();
        
        $likes_to_be_deleted = Like::where("post_id",$postData['post_id'])->get();
        $comments_to_be_deleted = Comment::where("post_id",$postData['post_id'])->get();
        $reports_to_be_deleted =  Report::where("video_id",$postData['post_id'])->get();
        
        if(!$post_to_be_deleted){
            return response() -> json([
                'message' => 'unable to delete post',
                'success' => false,
            ]);
        }
        
        
        /* delete all likes */
        if($likes_to_be_deleted){
            foreach ($likes_to_be_deleted as $likes) {
                $likes->delete();
            }
        }
        
        // delete all comments related to the post
        if($comments_to_be_deleted) {
            foreach ($comments_to_be_deleted as $comments) {
                $comments->delete();
            }
        }
        
        /* delete reports if any */
        if($reports_to_be_deleted){
            foreach ($reports_to_be_deleted as $report) {
                $report->delete();
            }
        }
        
        /* delete the actual post now */
        $post_to_be_deleted->delete();
        Notification::create([
            "master_id" =>$user_id,
            "message"=>"🚮you deleted a post",
            "read" =>false
            
        ]);
        return response() -> json([
            'message' => 'post deleted',
            'success' => true,
        ]);
    }
    
    
    
    // edit post USERS SHOULD ONLY EDIT THE POST DESCRIPTION
    /* if they want to edit others they should delete the post and try again */
    public function editpost(Request $request){
        try {
            $postData = $request->validate([
                "post_id" =>"required",
                "post_description"=>"required",
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'changes not saved',
                'success' => false,
                'error' => $valExe
            ]);
        }
        // add to the database
        $user = Auth::user();
        $user_id = $user -> id;
        $post_to_be_edited = Post::where('id',$postData['post_id'])
        ->where('post_owner_id',$user_id)->first();
        if(!$post_to_be_edited){
            return response() -> json([
                'message' => 'post not found try again later',
                'success' => false,
            ]);
        }
        $new_post_description =$postData['post_description'];
        $post_to_be_edited->post_description =$new_post_description;
        $post_to_be_edited->save();
        Notification::create([
            "master_id" =>$user_id,
            "message"=>"post editing finished👍",
            "read" =>false
            
        ]);
        return response() -> json([
            'message' => 'post edited',
            'success' => true,
        ]);  
    }
    
    
    // retrieve all post and their comments and likes
    public function retrievepost(){
        $posts = Post::orderBy('id', 'DESC')->get();
        $all_post = [];
        if($posts){
            foreach ($posts as $post) {
            $post_created_at  = Carbon::parse($post->created_at)->format("d-M-Y");
            $all_comments_of_a_post =[];
            $user = User::where('id',$post->post_owner_id)->first();
            // add likes and comments here too
            $likes= Like::where('post_id',$post->id)->count();
            $comments= Comment::where('post_id',$post->id)->get();
            /* Loop through the comment to edit it properly*/
            if($comments){
                foreach ($comments as $comment) {
                $user_who_commented = User::where('id',$comment->user_id)->first(); 
                $comment_created_at  = Carbon::parse($comment->created_at)->format("d-M-Y");
                $complete_comment = (Object)[
                    'user_name' => $user_who_commented ->name,
                    "user_id"=>$comment->user_id,
                    "created_at"=> $comment_created_at,
                    'profile' =>$user_who_commented->profile,
                    'user_comment' => $comment->comment
                ];
                $all_comments_of_a_post[]=$complete_comment;
            }  
            }

            $media_path =null;
            if($post->media_type){
                $media_path = $post->post_media;
            }
            
            $complete_post = (Object)[
                'user_name' => $user ->name,
                "user_id"=>$user->id,
                "id" => $post->id,
                'profile' =>$user->profile,
                "created_at"=> $post_created_at,
                'post_description' => $post->post_description,
                'post_media' => $media_path,
                'media_type' => $post ->media_type,
                'likes' => $likes,
                'comments' => $all_comments_of_a_post
            ];
            $all_post[]=$complete_post;
        }
                return response() -> json([
            'message' => 'post retrieved',
            'success' => true,
            'data'=>$all_post
        ]);
        }
        return response() -> json([
            'message' => 'post retrieved',
            'success' => true,
            'data'=>[]
        ]);
 
    }
    
    
    public function recommended_post(){
        // for users which are signed in
        $user = Auth::user();
        $user_id = $user->id;
        $all_post = [];
        $followers_list = Follower::where("follower_id",$user_id)->pluck('master_id');
        // get users where user is not already following
        $recommended_post = Post::whereIn('post_owner_id', $followers_list)->orderBy('id','DESC')->get();
        
        if($recommended_post){
            foreach ($recommended_post as $post) {
            $post_created_at  = Carbon::parse($post->created_at)->format("d-M-Y");
            $all_comments_of_a_post =[];
            $user = User::where('id',$post->post_owner_id)->first();
            // add likes and comments here too
            $likes= Like::where('post_id',$post->id)->count();
            $comments= Comment::where('post_id',$post->id)->get();
            /* Loop through the comment to edit it properly*/
            if($comments){
                foreach ($comments as $comment) {
                $user_who_commented = User::where('id',$comment->user_id)->first(); 
                $comment_created_at  = Carbon::parse($comment->created_at)->format("d-M-Y");
                $complete_comment = (Object)[
                    'user_name' => $user_who_commented ->name,
                    "user_id"=>$comment->user_id,
                    "created_at"=> $comment_created_at,
                    'profile' =>$user_who_commented->profile,
                    'user_comment' => $comment->comment
                ];
                $all_comments_of_a_post[]=$complete_comment;
            }  
            }

            $media_path =null;
            if($post->media_type){
                    $media_path =$post->post_media;
            }
            $complete_post = (Object)[
                'user_name' => $user ->name,
                "user_id"=>$user->id,
                "id" => $post->id,
                'profile' => $user->profile,
                "created_at"=> $post_created_at,
                'post_description' => $post->post_description,
                'post_media' => $media_path,
                'media_type' => $post ->media_type,
                'likes' => $likes,
                'comments' => $all_comments_of_a_post
            ];
            $all_post[]=$complete_post;
        }
        return response() -> json([
            'message' => 'post retrieved',
            'success' => true,
            'data'=>$all_post
        ]); 
        }
        
        return response() -> json([
            'message' => 'post retrieved',
            'success' => true,
            'data'=>[]
        ]); 

    }
    
    
    // Like and unlike post 
    public function likepost(Request $request){
        try {
            $postData = $request->validate([
                "post_id" =>"required",
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'post not liked',
                'success' => false,
                'error' => $valExe
            ]);
        }
        $user = Auth::user();
        $user_id = $user -> id;
        $post_id=$postData['post_id'];
        $post_to_be_liked = Like::where('post_id',$post_id)->where('user_id',$user_id)->first();
        
        if(!$post_to_be_liked){
            Like::create([
                'post_id' =>$postData['post_id'],
                'user_id' => $user_id
            ]);
            return response() -> json([
                'message' => 'post liked',
                'success' =>true,
            ]);
        }
        
        $post_to_be_liked->delete();
        return response() -> json([
            'message' => 'you unliked this post',
            'success' => true
        ]);
    }
    
    
    
    // comment on a post
    public function commentpost(Request $request){
        try {
            $postData = $request->validate([
                "post_id" =>"required",
                "comment" =>"required"
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'try again comment not added',
                'success' => false,
                'error' => $valExe
            ]);
        }
        $user = Auth::user();
        $user_id = $user -> id;
            Comment::create([
                'post_id' =>$postData['post_id'],
                "comment" =>$postData['comment'],
                'user_id' => $user_id
            ]);
            return response() -> json([
                'message' => 'comment saved',
                'success' =>true,
            ]);
    }
    
    
    // delete comment
    public function deletecomment(Request $request){
        try {
            $postData = $request->validate([
                "comment_id" =>"required",
            ]);
        } catch(ValidationException $valExe) {
            return response() -> json([
                'message' => 'comment not deleted',
                'success' => false,
                'error' => $valExe
            ]);
        }
        $user = Auth::user();
        $user_id = $user -> id;
        $comment_id=$postData['comment_id'];
        $comment_to_be_deleted = Comment::where('id',$comment_id)
        ->where('user_id',$user_id)->first();
        if(!$comment_to_be_deleted){
            return response() -> json([
                'message' => 'un able to delete comment',
                'success' => false,
            ]); 
        }
        
        $comment_to_be_deleted->delete();
            return response() -> json([
                'message' => 'comment deleted',
                'success' =>true,
            ]);
    }
    
    
}
