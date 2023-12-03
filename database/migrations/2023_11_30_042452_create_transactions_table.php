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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            /* deposit =>1 withdrawals =>2 send money =>3 */
            $table->integer("transaction_type");
            $table ->integer("transaction_owner_id");
            $table ->integer("amount_transacted");
            $table ->text("partyB") ->nullable();
            $table ->text("transaction_id");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
