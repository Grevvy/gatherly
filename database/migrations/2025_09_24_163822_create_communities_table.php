<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 120);
            $table->string('slug', 140)->unique(); // for clean URLs
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();

            // Visibility: public => visible & joinable per policy,
            // private => visible but members-only content,
            // hidden => not listed, invite/request only
            $table->enum('visibility', ['public', 'private'])->default('public');

            // Join policy: open => anyone can join,
            // request => must be approved,
            // invite => owner/admin must invite
            $table->enum('join_policy', ['open', 'request', 'invite'])->default('open');

            // Owner (creator) — assumes users table exists
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            // Tags for categorizing communities
            $table->json('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['visibility', 'join_policy']);
            $table->index('owner_id');
            // Optional: case-insensitive search helper index for Postgres if you want fast LIKE
            // $table->index([DB::raw('lower(name)')]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
