<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_memberships', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('notifications_snoozed_until')->nullable()->after('interests');
        });
    }

    public function down(): void
    {
        Schema::table('community_memberships', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notifications_snoozed_until');
        });
    }
};
