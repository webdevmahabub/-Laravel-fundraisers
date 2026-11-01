<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_profile_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'rejected',
            ])->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->json('payment_details')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('organizer_profile_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
