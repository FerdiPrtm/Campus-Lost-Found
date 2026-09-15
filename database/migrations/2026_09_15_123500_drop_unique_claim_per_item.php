<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropUnique(['item_id', 'user_id']);
            $table->index(['item_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'user_id', 'status']);
            $table->unique(['item_id', 'user_id']);
        });
    }
};