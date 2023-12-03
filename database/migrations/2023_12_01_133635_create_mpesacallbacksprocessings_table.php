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
        Schema::create('mpesacallbacksprocessings', function (Blueprint $table) {
            $table->id();
            $table ->text("transaction_receipt");
            $table ->text("merchant_Id");
            $table ->text("checkoutrequest_Id");
            $table ->boolean("processed");
            $table ->text("phone");
            $table ->text("amount");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesacallbacksprocessings');
    }
};
