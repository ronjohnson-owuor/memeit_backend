<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpesacallbacksprocessing extends Model
{
    use HasFactory;
    protected $fillable =[
        'transaction_receipt',
        "merchant_Id",
        "checkoutrequest_Id",
        "processed",
        "phone",
        "amount"
    ];
}
