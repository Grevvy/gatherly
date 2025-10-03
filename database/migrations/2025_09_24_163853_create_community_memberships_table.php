<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('community_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('community_id')->constrained('communities')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Role is per-community (not global)
            $table->enum('role', ['owner', 'admin', 'moderator', 'member'])->default('member');

            // Status lets you model join requests & bans without extra tables
            $table->enum('status', ['active', 'pending', 'banned'])->default('active');

            $table->timestamps();

            // A user can only have one membership record per community
            $table->unique(['community_id', 'user_id']);

            // Helpful indexes for queries like: "all pending for this community"
            $table->index(['community_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['community_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_memberships');
    }
};
