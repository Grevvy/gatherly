<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('boosted_at')->nullable()->after('content_updated_at');
            $table->timestamp('boosted_until')->nullable()->after('boosted_at');
            $table->index('boosted_until');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('boosted_at')->nullable()->after('status');
            $table->timestamp('boosted_until')->nullable()->after('boosted_at');
            $table->index('boosted_until');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['boosted_until']);
            $table->dropColumn(['boosted_at', 'boosted_until']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['boosted_until']);
            $table->dropColumn(['boosted_at', 'boosted_until']);
        });
    }
};
