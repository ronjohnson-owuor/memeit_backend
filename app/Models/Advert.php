<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advert extends Model
{
    use HasFactory;
    protected $fillable = [
        "ad_heading",
        "ad_media",
        "ad_owner_id",
        "ad_tags",
        "ad_expiry",
        "ad_type",
        "auto_renew"
    ];
}
