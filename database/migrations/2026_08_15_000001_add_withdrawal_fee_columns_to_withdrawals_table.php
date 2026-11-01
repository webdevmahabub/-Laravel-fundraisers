<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            // Fee snapshot — stored at request time so changing the fee
            // configuration later never mutates historical withdrawal records.
            $table->decimal('withdrawal_fee_amount', 15, 2)->default(0.00)->after('amount');
            $table->decimal('net_amount', 15, 2)->default(0.00)->after('withdrawal_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn(['withdrawal_fee_amount', 'net_amount']);
        });
    }
};
