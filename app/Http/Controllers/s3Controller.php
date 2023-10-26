<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class s3Controller extends Controller
{
    public function addTos3(Request $request)
    {
        try{
        $request->validate([
            'file' => 'required',
        ]);
        $fileName = $request->file->getClientOriginalName();
        $filePath = 'profiles/' . $fileName;
        try{
         $path = Storage::disk('s3')->put($filePath, file_get_contents($request->file));   
        } catch(Throwable $err){
            return response() -> json([
                "message" =>"image not uploaded to s3",
                "succes" =>false,
                "error" =>$err,
                "filename" =>$fileName
            ]);  
        }
        
        $path = Storage::disk('s3')->url($path);
        return response() -> json([
            "message" =>"image successfully uploaded",
            "succes" =>true,
            "path" =>$path
        ]);            
        }catch(Throwable $err){
            return response() -> json([
                "message" =>"image not uploaded",
                "succes" =>false,
                "error" =>$err
            ]);  
        }

    }
}
 