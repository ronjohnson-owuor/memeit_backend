<?php

namespace App\Http\Controllers;

use App\Models\Mpesacallbacksprocessing;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr\Cast\Object_;
use Safaricom\Mpesa\Mpesa;

class walletController extends Controller
{
    private $checkoutrequestId;
    private $password;
    private $timestamp;
    public function __construct()
    {
        $this->checkoutrequestId =null;
        $this->password =null;
        $this->timestamp =null;
    }
    
    /* deposit money into your wallet */
    public function depositMoney(Request $request)
    {

        /* validate request for confirming payment */
        try {
            $confirm_data = $request -> validate([
                "merchant_Id" => 'required',
                "checkout_Id" => 'required'
            ]);
        } catch(ValidationException $exe) {
            return response() ->json(["success" => false,"message" => "check your amount"]);
        }
        /* 1.Validate user and get his or her id */
         $user = Auth::user();
        if(!$user) {
            return response()->json([
                "message" => "Log in and try again",
                "success" => false
            ]);
        }
        $user_id = $user ->id;
        
        $payment_status_confirmation = Mpesacallbacksprocessing::where("merchant_Id",$confirm_data["merchant_Id"])
        ->where("checkoutrequest_Id",$confirm_data["checkout_Id"])
         ->where("processed",false) ->first();
         
         
         if(!$payment_status_confirmation){
            return response() -> json([
                "message" =>"we have not received your payment.
                maybe its still being processed...wait a minute then request confirmation again",
                "success" => false,
                "balance" =>0
            ]);
         }


        /* check if the user had account if not create for him and initialize the balance */
        $topup_amount = intval($payment_status_confirmation -> amount);
        $user_wallet = Wallet::where("user_id", $user_id) ->first();
        if(!$user_wallet) {
            $total_balance = Crypt::encrypt($topup_amount);
            Wallet::create([
                "user_id" => $user_id,
                "user_balance" => $total_balance
            ]);
            
            return response() -> json([
                "message" => "account deposit is successfull",
                "balance" => $topup_amount,
                "success" => true
            ]);
        }

        
        
        /* update transaction database */
        Transaction::create([
            "transaction_type"=>0,//deposit code is 0 withdrawal code is 1
            "transaction_owner_id"=>$user_id,
            "amount_transacted"=>$topup_amount,
            "partyB" =>$user -> phone,
            "transaction_id" =>$confirm_data["checkout_Id"]
            
        ]);

        
        
        
        /* if the user already have an account */
        $previous_amount = Crypt::decrypt($user_wallet->user_balance);
        $new_amount = $previous_amount + $topup_amount;
        $new_encrypted_amount = Crypt::encrypt($new_amount);
        /* update user balance */
        $user_wallet ->user_balance = $new_encrypted_amount;
        $user_wallet ->update();
        $payment_status_confirmation ->processed = true;
        $payment_status_confirmation ->update();
        return response() -> json([
            "message" => "account deposit is successfull",
            "success" => true,
            "new_balance" => $new_amount
        ]);

    }





    /* withdraw money */
    public function widthdrawMoney(Request $request)
    {
        try {
            $request -> validate([
                "withdraw_amount" => 'required|integer|min:10'
            ]);
            $withdraw_amount = $request->input("withdraw_amount");
        } catch(ValidationException $exe) {
            return response() ->json(["success" => false,"message" => "check your amount limit"]);
        }



               /* 1.Validate user and get his or her id */
               $user = Auth::user();
        if(!$user) {
            return response()->json([
                "message" => "unable to withdraw make sure you are logged in.",
                "success" => false
            ]);
        }
        $user_id = $user ->id;



        /* check if the user had account if not create for him and initialize the balance */
        $user_wallet = Wallet::where("user_id", $user_id) ->first();
        if(!$user_wallet) {
            return response() ->json(["message" => "you dont have a wallet.deposit amount to create your wallet","success" => false]);
        }



        /* if the user already have an account */
        $account_balance = Crypt::decrypt($user_wallet->user_balance);
        if($account_balance < $withdraw_amount) {
            return response() -> json(["success" => false,"message" => "insufficient funds in account"]);
        }
        $new_account_balance = $account_balance - $withdraw_amount;
        $new_encrypted_amount = Crypt::encrypt($new_account_balance);
        /* update user balance */
        $user_wallet ->user_balance = $new_encrypted_amount;
        $user_wallet ->update();
        return response() -> json([
            "message" => "confirm withdraw of " . $withdraw_amount . " from your account",
            "success" => true,
            "new_balance" => $new_account_balance
        ]);
    }




/* =============================GET USER CURRENT BALLANCE IN THE ACCOUNT============ */
    public function getBallance()
    {
        $user = auth() ->user();
        $user_id = $user ->id;
        $user_wallet = Wallet::where("user_id", $user_id) ->first();
        $balance = 0;
        if($user_wallet) {
            $balance = crypt::decrypt($user_wallet ->user_balance);
        }
        return response() -> json([
            "success" => true,
            "new_balance" => $balance
        ]);
    }
/* =============================END============ */


    
    
    
/* generate token for the stk push */
    public function generate_access_token(Request $request)
    {
        $consumer_key = env("MPESA_CONSUMER_KEY");
        $consumer_secret = env("MPESA_CONSUMER_SECRET");
        $credentials = base64_encode($consumer_key . ":" . $consumer_secret);
        $url = env("MPESA_TOKEN_GENERATION_URL");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
        //setting a custom header
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $access_token = json_decode($curl_response);
        return $access_token->access_token;
    }
    
    
    

    
    /* ========================== start of callback function from mpesa api============== */
    public function callBackFunction(Request $request) {
        try{
            /* retrieve the request from the callback url */
            $request_data = $request->getContent();
            // Decode the JSON string
            $decodedData = json_decode($request_data);
            $filename ='callback_data.json';
            Storage::disk('public')->put($filename,$request_data);
            // response data for processing
            $stk_callback = $decodedData ->Body->stkCallback;
            
        
            
            // Extract required information
            $merchantRequestId = $stk_callback ->MerchantRequestID;
            $checkoutRequestId = $stk_callback ->CheckoutRequestID;
            $resultCode = $stk_callback->ResultCode;

            if($resultCode != 0){
                Mpesacallbacksprocessing::create([
                    "transaction_receipt" =>"cancelled",
                    "merchant_Id" =>$merchantRequestId,
                    "checkoutrequest_Id" =>$checkoutRequestId,
                    "processed" =>false,
                    "phone" =>"0000000000",
                    "amount" => "0"
                ]);    
            }
            
            
            /* remember to save the callback.json files in s3 */
            if ($resultCode == 0) {
                // If the transaction is successfull then formart the data and save it in the database
                $response_data = $decodedData -> Body ->stkCallback->CallbackMetadata;
                $mpesaReceiptNumber = $response_data->Item[1]->Value;
                $mpesaAmount = $response_data->Item[0]->Value;
                $phoneNumber = $response_data->Item[4]->Value;
                
                
                
                /* insert the data to the database first after receiving the callback */
                Mpesacallbacksprocessing::create([
                    "transaction_receipt" =>$mpesaReceiptNumber,
                    "merchant_Id" =>$merchantRequestId,
                    "checkoutrequest_Id" =>$checkoutRequestId,
                    "processed" =>false,
                    "phone" =>$phoneNumber,
                    "amount" => $mpesaAmount
                ]);
                
                
                /* create a json success file in amazon s3 storage */
                $formattedData = (Object) [
                    'MerchantRequestID' => $merchantRequestId,
                    'CheckoutRequestID' => $checkoutRequestId,
                    'ResultCode' => $resultCode,
                    'MpesaReceiptNumber' => $mpesaReceiptNumber,
                    'PhoneNumber' => $phoneNumber,
                    'MpesaAmount' => $mpesaAmount
                ];
                $filename =time().'callback_success.json';
                $disk = Storage::disk('s3');
                $filepath ="callbacks/".$filename;
                $disk->put($filepath,json_encode($formattedData),'private'); 
            }
        }catch(Exception $exe){
            Log::info("there was an error in storing callback.json in the json ERROR =>".$exe ->getMessage());
        }
    }
    
    
    
    
    
    
     /* ============== START OF STK PUSH =============================== */
    /* simulate the stkPush*/
    public function stkPush(Request $request){
        try{
            $user_data = $request ->validate([
                "topup_amount" => ['required','numeric'],
                "number" =>['required','numeric']
            ]);
        }catch(ValidationException $exe){
            return response() ->json([
                "message" => "check input fields",
                "success" => false
            ]);
        }
        $BusinessShortCode = 174379;
        $access_token = $this->generate_access_token($request);
        $passkey =env("MPESA_PASS_KEY");
        $timestamp= Carbon::rawParse('now')->format('YmdHms');
        /* password is the combination of bussiness shortcode pass key and timestamp to base64 */
        $password = base64_encode($BusinessShortCode.$passkey.$timestamp);
        $this->password =$password;
        $this -> timestamp = $timestamp;
        $Amount= intval($user_data["topup_amount"]);
        $PartyA = intval($user_data["number"]);
        $PartyB = env("MPESA_PARTY_B");


        $url = env('MPESA_REQUEST_PROCESSING_URL');
  
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json','Authorization:Bearer ' . $access_token)); //setting custom header and getting the token from the pregenerated access token
        
        
        $curl_post_data = array(
          //Fill in the request parameters with valid values
          'BusinessShortCode' => $BusinessShortCode,
          'Password' => $password,
          'Timestamp' => $timestamp,
          'TransactionType' => 'CustomerPayBillOnline',
          'Amount' => $Amount,
          'PartyA' => $PartyA,
          'PartyB' => $PartyB,
          'PhoneNumber' => $PartyA,
          'CallBackURL' =>env("MPESA_CALLBACK_URL"),
          'AccountReference' => 'Machizi kodesfusion',
          'TransactionDesc' => 'account deposit on machizi'
        );
        
        $data_string = json_encode($curl_post_data);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//this prevent ssl request from being sent
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        
        $curl_response = curl_exec($curl);
        $returning_response =json_decode($curl_response);
        return response()->json([
            "stk_data" =>$returning_response,
            "success" =>true
        ]); 
        
    }
         /* ============== END OF STK PUSH =============================== */
         
         
         
         
         
         
    public  function transactionRecords(){
            $user = Auth::user();
            $user_id = $user ->id;
            
            
            $total_transactions = [];
            $total_deposits = Transaction::where("transaction_type",0) ->
            where("transaction_owner_id",$user_id)->get();
            $total_withdrawals = Transaction::where("transaction_type",1) ->
            where("transaction_owner_id",$user_id)->get();
            
            if($total_deposits){
                $total_transactions[]=$total_deposits;
            }else if($total_withdrawals){
                $total_transactions[]=$total_withdrawals;
            }
            return response() -> json([
                "total_deposit" => $total_deposits,
                "total_withdrawal" =>$total_withdrawals,
                "total_transacttions" =>$total_transactions,
                "success" =>true
            ]);
    }
    
}