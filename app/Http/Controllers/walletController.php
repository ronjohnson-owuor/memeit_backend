<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Dotenv\Exception\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class walletController extends Controller
{
   /* deposit money into your wallet */
   public function depositMoney(Request $request){    
        
        /* validate request */
        try{
            $request -> validate([
                "topup_amount" => 'required|integer|min:10'
            ]);
            $topup_amount = $request->input("topup_amount");  
        }catch(ValidationException $exe){
            return response() ->json(["success" =>false,"message" =>"check your amount"]);
        }
        
        
        
               /* 1.Validate user and get his or her id */
               $user = Auth::user(); 
               if(!$user){
                   return response()->json([
                       "message" => "unable to deposit make sure you are logged in.",
                       "success" => false
                   ]);
               }
               $user_id = $user ->id;
        
               
        
        /* check if the user had account if not create for him and initialize the balance */
        $user_wallet = Wallet::where("user_id",$user_id) ->first();
        if(!$user_wallet){
            $total_balance = Crypt::encrypt($topup_amount);
            Wallet::create([
                "user_id" => $user_id,
                "user_balance" => $total_balance   
            ]);
            
            return response() -> json([
                "message" => "account deposit is successfull",
                "balance" =>$topup_amount,
                "success" => true
            ]);
        }
        
        
        
        /* if the user already have an account */
        $previous_amount = Crypt::decrypt($user_wallet->user_balance);
        $new_amount = $previous_amount + $topup_amount;
        $new_encrypted_amount = Crypt::encrypt($new_amount);
        /* update user balance */
        $user_wallet ->user_balance = $new_encrypted_amount;
        $user_wallet ->update();
        return response() -> json([
            "message" =>"account deposit is successfull",
            "success" => true,
            "new balance" => $new_amount
        ]);
   }
   
   
   
   
   
   /* withdraw money */
   public function widthdrawMoney(Request $request){
    try{
        $request -> validate([
            "withdraw_amount" => 'required|integer|min:10'
        ]);
        $withdraw_amount = $request->input("withdraw_amount");  
    }catch(ValidationException $exe){
        return response() ->json(["success" =>false,"message" =>"check your amount limit"]);
    }
    
    
    
           /* 1.Validate user and get his or her id */
           $user = Auth::user(); 
           if(!$user){
               return response()->json([
                   "message" => "unable to withdraw make sure you are logged in.",
                   "success" => false
               ]);
           }
           $user_id = $user ->id;
    
           
    
    /* check if the user had account if not create for him and initialize the balance */
    $user_wallet = Wallet::where("user_id",$user_id) ->first();
    if(!$user_wallet){
        return response() ->json(["message" =>"you dont have a wallet.deposit amount to create your wallet","success" => false]);
    }
    
    
    
    /* if the user already have an account */
    $account_balance = Crypt::decrypt($user_wallet->user_balance);
    if($account_balance < $withdraw_amount){
        return response() -> json(["success"=>false,"message" =>"insufficient funds in account"]);
    }
    $new_account_balance = $account_balance - $withdraw_amount;
    $new_encrypted_amount = Crypt::encrypt($new_account_balance);
    /* update user balance */
    $user_wallet ->user_balance = $new_encrypted_amount;
    $user_wallet ->update();
    return response() -> json([
        "message" =>"confirm withdraw of ".$withdraw_amount." from your account",
        "success" => true,
        "new balance" => $new_account_balance
    ]);
   }
   
   
   
   
   
   
   
   /* send money to other people */
   public function sendMoney(Request $request){
    try{
        $request -> validate([
            "receiver_email" => 'required|email',
            "amount" => 'required|integer|min:10'
        ]);
        $receiver_email = $request->input("receiver_email"); 
        $amount = $request->input("amount");  
    }catch(ValidationException $exe){
        return response() ->json([
        "success" =>false,
        "message" =>"check your amount limit or your receiver's email"
        ]);
    }
    
    
    
           /* 1.Validate user and get his or her id */
           $user = Auth::user(); 
           if(!$user){
               return response()->json([
                   "message" => "cannot complete operation you need to login.",
                   "success" => false
               ]);
           }
           $user_id = $user ->id;
    
           /* check if the user is trying to debit his own account just to save on compute power 🤣😅😂 */
           if($receiver_email == $user->email){
            return response()->json([
                "message" => "You cannot send money to the same account as the sender and receiver.",
                "success" => false
            ]);
           }
           
    
    /* check if the user had account if not create for him and initialize the balance */
    $user_wallet = Wallet::where("user_id",$user_id) ->first();
    $receiver = User::where("email",$receiver_email) ->first();
    if(!$user_wallet){
        return response() ->json([
            "message" =>"you dont have a wallet.create one by depositing money",
            "success" => false]
        );
    }
    
    if(!$receiver){
        return response() ->json([
            "message" =>"sorry this user is not availlable on the website.", 
            "success"=> false]);
    }else{
        $receiver_id = $receiver ->id;
        $receiver_wallet = Wallet::where("user_id",$receiver_id) ->first(); 
        //check if receiver has wallet if not then create one for him
        if(!$receiver_wallet){
            return response() ->json([
                "message" =>"sorry this user has not activated his wallet so he can't receive any money.",
                 "success"=> false
            ]);  
        }
    }
    
    
    
    /* if the user already have an account */
    $account_balance = Crypt::decrypt($user_wallet->user_balance);
    if($account_balance < $amount){
        return response() -> json([
            "success"=>false,"message" =>
            "sorry insufficient funds in account"
        ]);
    }
    $new_account_balance = $account_balance - $amount;
    $new_encrypted_amount = Crypt::encrypt($new_account_balance);
    /* update user balance */
    $user_wallet ->user_balance = $new_encrypted_amount;
    $receiver_wallet_old_balance = $receiver_wallet -> user_balance;
    $receiver_wallet_new_balance = $receiver_wallet_old_balance + $amount;
    $receiver_wallet ->user_balance = Crypt::encrypt($receiver_wallet_new_balance);
    $receiver_wallet ->update();
    $user_wallet ->update();
    return response() -> json([
        "message" =>"confirmed ".$amount."sent to ".$receiver ->name ." from your account",
        "success" => true,
        "new balance" => $new_account_balance
    ]);
   }
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
}
