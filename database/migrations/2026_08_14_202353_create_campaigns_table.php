<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_profile_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('campaign_categories')
                ->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500);
            $table->longText('story');
            $table->decimal('goal_amount', 15, 2);
            $table->decimal('raised_amount', 15, 2)->default(0.00);
            $table->char('currency', 3)->default('USD');
            $table->string('cover_image_path', 2048)->nullable();
            $table->enum('status', [
                'draft',
                'pending',
                'active',
                'paused',
                'completed',
                'cancelled',
                'rejected',
            ])->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('is_verified');
            $table->index('organizer_profile_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
