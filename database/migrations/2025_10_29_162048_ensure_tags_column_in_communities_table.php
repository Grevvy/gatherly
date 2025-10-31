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
        // Only add tags column if it doesn't exist
        if (!Schema::hasColumn('communities', 'tags')) {
            Schema::table('communities', function (Blueprint $table) {
                $table->json('tags')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            if (Schema::hasColumn('communities', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
