<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable financial ledger — no updated_at intentionally
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('transactionable');
            $table->enum('type', [
                'donation',
                'withdrawal',
                'refund',
                'fee',
            ]);
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — ledger rows are never modified after creation

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
