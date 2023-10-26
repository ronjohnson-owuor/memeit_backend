<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class adsController extends Controller
{
    //publish an add
    public function publishAd(Request $request){
        try{
            
            $request ->validate([
                "ad_heading" =>"required",
                "ad_media" =>"required|mimes:jpg,png,jpeg,mp4,webem|file",
                "ad_tags" =>"required",
                "ad_type" =>"required",
                "auto_renew" =>"required",
                "ad_expiry" => "required"
            ]);
            
        }catch(ValidationException $exe){
            return response()->json([
                "message"=> "check your input fields and try again",
                "sucess" => false
            ]);
        }
        
        $user = Auth::user();
        $ad_file = $request -> file("ad_media");
        $path='advertisement';
        $file_ext = $ad_file->getClientOriginalExtension();
        $newFilename = time() .'memeitfilesassets'.'.'.$file_ext;
        // $ad_file->move(public_path($path), $newFilename);
        
        
        $ad_heading = $request -> ad_heading;
        $ad_media = $request -> ad_media;
        $ad_tags = $request -> ad_tags;
        $ad_type = $request -> ad_type;
        $auto_renew = $request -> auto_renew;
        $ad_expiry_day = Carbon::now() ->addDay();
        $ad_expiry_month = Carbon::now() ->addMonth(); 
        $ad_owner_id = $user -> id;
        $ad_expiry = $request -> ad_expiry;
        $ad_expiry_time = "";
        
        if($ad_expiry == 0){
            $ad_expiry_time = $ad_expiry_day;
        }else{
            $ad_expiry_time = $ad_expiry_month;
        }
        
        
        
        //database array 
        $data_to_db = (Object)[
            "ad_heading" => $ad_heading,
            "ad_media" => $newFilename,
            "ad_owner_id" => $ad_owner_id,
            "ad_tags" => $ad_tags,
            "ad_expiry" =>$ad_expiry_time,
            "ad_type" => $ad_type,
            "auto_renew" =>$auto_renew
         ];
        return response() ->json($data_to_db);
        
    }
}
