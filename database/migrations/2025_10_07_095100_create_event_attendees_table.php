<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['attendee','host','speaker'])->default('attendee');
            $table->enum('status', ['invited','accepted','declined','waitlist'])->default('accepted');
            $table->boolean('checked_in')->default(false);
            $table->timestamps();

            $table->unique(['event_id','user_id']);
            // Indexes to speed waitlist queries and counts
            $table->index(['event_id', 'status', 'created_at'], 'event_attendees_event_status_created_at_index');
            $table->index(['event_id', 'status'], 'event_attendees_event_status_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_attendees');
    }
};
