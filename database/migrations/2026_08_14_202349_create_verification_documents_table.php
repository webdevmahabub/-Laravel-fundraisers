<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_profile_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('type', [
                'national_id',
                'passport',
                'drivers_license',
                'business_registration',
                'utility_bill',
                'other',
            ]);
            $table->string('disk', 50)->default('local');
            $table->string('path', 2048);
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();

            $table->index('organizer_profile_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
    }
};
