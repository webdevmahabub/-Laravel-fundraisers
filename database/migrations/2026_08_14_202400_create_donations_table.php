<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            // Snapshot of donor info at time of donation (supports anonymous & guest)
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->boolean('is_anonymous')->default(false);
            // Financial breakdown — all values server-calculated, never trusted from client
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('platform_fee_pct', 8, 4);
            $table->decimal('platform_fee_amount', 15, 2);
            $table->decimal('payment_processing_fee', 15, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('payment_method', 50)->nullable();
            $table->string('gateway_transaction_id')->nullable()->unique();
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'refunded',
                'cancelled',
            ])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('donated_at')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
