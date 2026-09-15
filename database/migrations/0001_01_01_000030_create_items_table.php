<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['lost', 'found']);
            $table->string('name');
            $table->string('category');
            $table->string('location');
            $table->text('description')->nullable();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('storage_location')->nullable();
            $table->string('image')->nullable();
            $table->json('verification_answers')->nullable();
            $table->enum('status', ['lost', 'found', 'claimed', 'verified', 'returned'])->default('lost');
            $table->enum('moderation_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->string('moderation_reason')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'moderation_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};