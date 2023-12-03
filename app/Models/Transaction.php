<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable =[
        'transaction_type',
        'transaction_owner_id',
        'amount_transacted',
        'partyB',
        'transaction_id'
    ];
}
