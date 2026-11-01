<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->nullableMorphs('reportable');
            $table->string('reason', 100);
            $table->text('description')->nullable();
            $table->enum('status', [
                'open',
                'under_review',
                'resolved',
                'dismissed',
            ])->default('open');
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('reporter_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
