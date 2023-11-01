<?php

namespace App\Http\Controllers;

use App\Models\Advert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class adsController extends Controller
{
    
    public function user($id){
        $user = User::where("id",$id)->first();
        return $user;
    }
    //publish an add
    public function publishAd(Request $request){
        try{
            
            $request ->validate([
                "ad_heading" =>"required",
                "ad_media" =>"required|mimes:jpg,png,jpeg|file",
                "ad_tags" =>"required",
                "ad_type" =>"required",
                "auto_renew" =>"required",
                "ad_expiry" => "required",
                "global" => "required",
                "link" => "required"
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
        $ad_file->move(public_path($path), $newFilename);
        
        $ad_heading = $request -> ad_heading;
        $ad_tags = $request -> ad_tags;
        $ad_type = $request -> ad_type;
        $auto_renew = $request -> auto_renew;
        $global = $request -> global;
        $link = $request -> link;
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
        
        if($auto_renew == "false"){
            $auto_renew = false;
        }else{
            $auto_renew = true;
        }
        
        if($global == "false"){
            $global = false;
        }else{
            $global = true;
        }
        
        //database array 
        $data_to_db = [
            "ad_heading" => $ad_heading,
            "ad_media" => $newFilename,
            "ad_owner_id" => $ad_owner_id,
            "ad_tags" => $ad_tags,
            "global" =>$global,
            "link" => $link,
            "ad_expiry" =>$ad_expiry_time,
            "ad_type" => $ad_type,
            "auto_renew" =>$auto_renew
         ];
         try{
          Advert::create($data_to_db);  
         }catch(\Throwable $error){
            return response() -> json([
                "error"=> $error -> getMessage(),
                "success" => false,
                "data_to_db" => $data_to_db
            ]);
         }
        return response() ->json([
            "success" => true,
            "message" => $ad_type." "."created successfully"
        ]);
    }
    
    
    // retrieve inbuilt global ads
    public function inbuiltGlobal(){
        $advertisements = Advert::where("ad_type" ,"inbuilt")
        ->where("global",true) ->get();
        $complete_ad =[];
        foreach($advertisements as $advertisement){
            $ad_media = asset("advertisement/".$advertisement->ad_media);
            $user = User::where("id",$advertisement->ad_owner_id) ->first();
            $advertisement->ad_media = $ad_media;
            $expiry_date = Carbon::parse($advertisement->ad_expiry);
            $advertisement -> name = $user ->name;
            $advertisement -> profile = asset("profiles/".$user ->profile);
            $date_created = Carbon::parse($advertisement->created_at)->format("d-M-Y");
            $advertisement -> date_created = $date_created;
            $is_in_the_future = $expiry_date->isFuture();
            if($is_in_the_future){
             $complete_ad[] = $advertisement;    
            } 
        }
        return response() -> json([
            "success"=> true,
            "data"=> $complete_ad
            ]);
    }
    
    
    
    // retrieve banner global ads
    public function bannerGlobal(){
        $advertisements = Advert::where("ad_type" ,"banner")
        ->where("global",true) ->get();
        $complete_ad =[];
        foreach($advertisements as $advertisement){
            $ad_media = asset("advertisement/".$advertisement->ad_media);
            $user = User::where("id",$advertisement->ad_owner_id) ->first();
            $advertisement->ad_media = $ad_media;
            $expiry_date = Carbon::parse($advertisement->ad_expiry);
            $advertisement -> name = $user ->name;
            $advertisement -> profile = asset("profiles/".$user ->profile);
            $date_created = Carbon::parse($advertisement->created_at)->format("d-M-Y");
            $advertisement -> date_created = $date_created;
            $is_in_the_future = $expiry_date->isFuture();
            if($is_in_the_future){
             $complete_ad[] = $advertisement;    
            } 
        }
        return response() -> json([
            "success"=> true,
            "data"=> $complete_ad
            ]);
    }
    
    
    // retrieve inbuilt home ads
    public function inbuiltHome(){
        $advertisements = Advert::where("ad_type" ,"inbuilt")
        ->where("global",false) ->get();
        $complete_ad =[];
        foreach($advertisements as $advertisement) {
            $ad_media = asset("advertisement/" . $advertisement->ad_media);
            $user = User::where("id", $advertisement->ad_owner_id) ->first();
            $advertisement->ad_media = $ad_media;
            $expiry_date = Carbon::parse($advertisement->ad_expiry);
            $advertisement -> name = $user ->name;
            $advertisement -> profile = asset("profiles/" . $user ->profile);
            $date_created = Carbon::parse($advertisement->created_at)->format("d-M-Y");
            $advertisement -> date_created = $date_created;
            $is_in_the_future = $expiry_date->isFuture();
            if($is_in_the_future) {
                $complete_ad[] = $advertisement;
            }
        }
        return response() -> json([
            "success"=> true,
            "data"=> $complete_ad,
            ]);
    }
    
    // retrieve banner home ads ->only shown on the homepage
    public function bannerHome(){
        $advertisements = Advert::where("ad_type" ,"banner")
        ->where("global",false) ->get();
        $complete_ad =[];
        foreach($advertisements as $advertisement){
            $ad_media = asset("advertisement/".$advertisement->ad_media);
            $user = User::where("id",$advertisement->ad_owner_id) ->first();
            $advertisement->ad_media = $ad_media;
            $expiry_date = Carbon::parse($advertisement->ad_expiry);
            $advertisement -> name = $user ->name;
            $advertisement -> profile = asset("profiles/".$user ->profile);
            $date_created = Carbon::parse($advertisement->created_at)->format("d-M-Y");
            $advertisement -> date_created = $date_created;
            $is_in_the_future = $expiry_date->isFuture();
            if($is_in_the_future){
             $complete_ad[] = $advertisement;    
            } 
        }
        return response() -> json([
            "success"=> true,
            "data"=> $complete_ad
            ]);
    } 
    
    
    /* get active ads for a user */
    
    public function active_expired_ads(){
        $user = Auth::user();
        $id = $user->id;
        $all_ads=[];
        $advertisements = Advert::where("ad_owner_id",$id)->get();
        $active =[];
        $expired =[];
        foreach ($advertisements as $advertisement) {
            $expiry = Carbon::parse($advertisement->ad_expiry);
            if($expiry->isFuture()){
                $advertisement->expired = false;
                $advertisement->ad_media = asset("advertisement/".$advertisement->ad_media);
                $active[] = $advertisement;
                $all_ads[] = $advertisement;
            }else{
                $advertisement->expired = true;
                $advertisement->ad_media = asset("advertisement/".$advertisement->ad_media);
                $expired[] = $advertisement;
                $all_ads[] = $advertisement;
            }
        }
        $total_active = count($active);
        $total_expired = count($expired);
        return response() -> json([
            "success"=> true,
            "data"=> $all_ads,
            "total_active" => $total_active,
            "total_expired" => $total_expired
            ]);
    }
    
    
    
    
    
    
    
}
