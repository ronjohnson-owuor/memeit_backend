<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $fillable=[
        "video_id",
        "video_owner",
        "reporter_id",
        "video_link",
        "issue"
    ];
}
