<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class notificationController extends Controller
{
    public function getnotification(){
      $user = Auth::user();
      $user_id = $user->id;
      $notification = Notification::where('master_id',$user_id)->get();
      return response() ->json([
        "success" =>true,
        "data"=>$notification
      ]);
    }
    
    // mark as read
    public function markasread(Request $request){
        $user = Auth::user();
        $user_id = $user->id;
        try{
            $data = $request ->validate([
                "post_id"=>"required"
            ]);
        }catch(ValidationException $err){
            return response()->json([
                "message" => "unable to mark notification",
                "success" => false
            ]);
        }
        $notification = Notification::where('id',$data["post_id"])
        ->where("master_id",$user_id)
        ->first();
        
        $userDataToUpdate = [
            "read" =>true
        ];
        $notification->update($userDataToUpdate);
        return response()->json([
            "message" => "marked as read",
            "success" => true,
            "data" =>null
        ]);
    }
    
    
    
    // delete notification
    public function deletenotification(Request $request){
        $user = Auth::user();
        $user_id = $user->id;
        try{
            $data = $request ->validate([
                "post_id"=>"required"
            ]);
        }catch(ValidationException $err){
            return response()->json([
                "message" => "unable to delete notification",
                "success" => false
            ]);
        }
        $notification = Notification::where('id',$data["post_id"])
        ->where("master_id",$user_id)
        ->first();
        $notification->delete();
        return response()->json([
            "message" => "notification deleted",
            "success" => true,
            "data" =>null
        ]);
    }
}
