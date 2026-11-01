<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manual payments (bKash / Nagad / bank transfer) ask the donor for the
     * mobile number the money was sent from, so admins can match the incoming
     * transfer to the donation record during verification.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('sender_mobile', 30)->nullable()->after('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('sender_mobile');
        });
    }
};
