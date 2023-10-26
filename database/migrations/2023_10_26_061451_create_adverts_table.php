<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('adverts', function (Blueprint $table) {
            $table->id();
            $table ->string("ad_heading");
            $table->string("ad_media");
            $table->string("ad_owner_id");
            $table->string("ad_tags");
            $table->timestamp("ad_expiry");
            $table->boolean("ad_type");
            $table->boolean("auto_renew");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adverts');
    }
};
