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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->text('payment_instructions')->nullable()->after('description');
            $table->string('account_holder_name')->nullable()->after('payment_instructions');
            $table->string('account_number')->nullable()->after('account_holder_name');
            $table->string('bank_name')->nullable()->after('account_number');
            $table->string('branch_name')->nullable()->after('bank_name');
            $table->string('routing_number')->nullable()->after('branch_name');
            $table->string('phone_number')->nullable()->after('routing_number');
            $table->string('qr_code_image_path')->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn([
                'payment_instructions',
                'account_holder_name',
                'account_number',
                'bank_name',
                'branch_name',
                'routing_number',
                'phone_number',
                'qr_code_image_path',
            ]);
        });
    }
};
