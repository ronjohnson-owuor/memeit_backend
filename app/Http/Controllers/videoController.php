<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PhpParser\Node\Expr\Cast\Object_;

class videoController extends Controller
{
    //report video
    public function reportVideo(Request $request){
        $user= Auth::user();
        $reporter_id = $user->id;
        try{
            $video = $request->validate([
                "id" =>"required",
                "issue"=>"required"
            ]); 
        }catch(ValidationException $err){
            return response() ->json([
                "message"=>"unable to report video",
                "success" =>false
            ]);   
        }
        
        
        
        $video_id = $video['id'];
        $issue =$video['issue'];
        $post_reported = Post::where('id',$video_id) ->first();
        $video_owner = $post_reported ->post_owner_id;
        $video_link = asset($post_reported->post_media);
        
        if($video_owner == $reporter_id){
            return response() ->json([
                "message"=>"you cannot report your own video",
                "success" =>false
            ]);
        }
        Report::create([
            "video_id" =>$video_id,
            "video_owner" => $video_owner,
            "reporter_id" =>$reporter_id,
            "video_link" =>$video_link,
            "issue" =>$issue
        ]);
        // notify the video owner
        $message = "one of your video has been reported to us you can view it in your profile";
        Notification::create([
            "master_id" =>$video_owner,
            "message" => $message,
            "read" =>false
        ]);
        return response() ->json([
            "message"=>"video reported will be reviewed by our team",
            "success" =>true
        ]);
    }
    
    
    
    // retrieve reported videos from the database
    public function retrieveReportedvideos(){
        $user= Auth::user();
        $video_owner = $user->id;
        $reported_videos = Report::where("video_owner",$video_owner)->get();
        $final_report_to_client=[];
        foreach ($reported_videos as $reported_video) {
            $reporter = User::where('id',$reported_video->reporter_id)->first();
            $reporter_profile = $reporter->profile;
            $video = $reported_video->video_link;
            $reason = $reported_video->issue;
            $reporter_number=$reporter->phone;
            $reporter_name = $reporter->name;
            
            $overall_info = (Object)[
                "reporter_name" =>$reporter_name,
                "reporter_profile" =>$reporter_profile,
                "reporter_number" =>$reporter_number,
                "reported_video" =>$video,
                "issue" =>$reason
            ];
            $final_report_to_client[]=$overall_info;
        }
        
        return response() ->json([
            "success" =>true,
            "data" =>$final_report_to_client
        ]);  
    }
    
    
    
    
    // this are the videos that are show n to the person who reported them
    // show he can be able to drop the charges
    public function videos_for_the_reporter(){
        $user= Auth::user();
        $reporter_id = $user->id;
        $reported_videos = Report::where("reporter_id",$reporter_id)->get();
        $final_report_to_client=[];
        foreach ($reported_videos as $reported_video) {
            $reporter = User::where('id',$reported_video->reporter_id)->first();
            $reporter_profile =  $reporter->profile;
            $video = $reported_video->video_link;
            $reason = $reported_video->issue;
            $reporter_number=$reporter->phone;
            $reporter_name = $reporter->name;
            
            $overall_info = (Object)[
                "id" =>$reported_video->id,
                "video_owner" =>$reported_video->video_owner,
                "reported_video" =>$video,
                "issue" =>$reason
            ];
            $final_report_to_client[]=$overall_info;
        }
        
        return response() ->json([
            "success" =>true,
            "data" =>$final_report_to_client
        ]);  
    }
    
    
    // drop charges
    public function drop_charges(Request $request){
        $report = Report::where("id",$request->id)
        ->where("video_owner",$request->owner) ->first();
        $report->delete();
        Notification::create([
            "message" => "charges on your video has been dropped",
            "read" =>false,
            "master_id" =>$request->owner
        ]);
        return response() ->json([
            "message" => "charges dropped",
            "success" =>true
        ]);
    }
    
    
    
    
}


