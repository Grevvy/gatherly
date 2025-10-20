<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Community;

return new class extends Migration
{
    public function up()
    {
        // Update any hidden communities to private
        Community::where('visibility', 'hidden')->update(['visibility' => 'private']);

        // Modify the enum to remove 'hidden'
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility DROP DEFAULT");
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility TYPE VARCHAR(255)");
        DB::statement("DROP TYPE IF EXISTS community_visibility_enum CASCADE");
        DB::statement("CREATE TYPE community_visibility_enum AS ENUM('public', 'private')");
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility TYPE community_visibility_enum USING visibility::community_visibility_enum");
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility SET DEFAULT 'public'");
    }

    public function down()
    {
        // Add 'hidden' back to the enum
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility DROP DEFAULT");
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility TYPE VARCHAR(255)");
        DB::statement("DROP TYPE IF EXISTS community_visibility_enum CASCADE");
        DB::statement("CREATE TYPE community_visibility_enum AS ENUM('public', 'private', 'hidden')");
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility TYPE community_visibility_enum USING visibility::community_visibility_enum");
        DB::statement("ALTER TABLE communities ALTER COLUMN visibility SET DEFAULT 'public'");
    }
};