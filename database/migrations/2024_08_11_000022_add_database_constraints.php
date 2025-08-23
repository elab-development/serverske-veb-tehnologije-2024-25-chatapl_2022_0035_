<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign key constraints
        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });

        Schema::table('user_room', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add check constraints
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_email_format CHECK (email REGEXP "^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$")');
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_name_length CHECK (LENGTH(name) >= 2 AND LENGTH(name) <= 255)');
        DB::statement('ALTER TABLE users ADD CONSTRAINT chk_users_password_length CHECK (LENGTH(password) >= 8)');

        DB::statement('ALTER TABLE rooms ADD CONSTRAINT chk_rooms_name_length CHECK (LENGTH(name) >= 3 AND LENGTH(name) <= 100)');
        DB::statement('ALTER TABLE rooms ADD CONSTRAINT chk_rooms_description_length CHECK (LENGTH(description) <= 500)');
        DB::statement('ALTER TABLE rooms ADD CONSTRAINT chk_rooms_member_count CHECK (member_count >= 0)');
        DB::statement('ALTER TABLE rooms ADD CONSTRAINT chk_rooms_message_count CHECK (message_count >= 0)');

        DB::statement('ALTER TABLE messages ADD CONSTRAINT chk_messages_content_length CHECK (LENGTH(content) >= 1 AND LENGTH(content) <= 1000)');
        DB::statement('ALTER TABLE messages ADD CONSTRAINT chk_messages_type CHECK (type IN ("text", "image", "file"))');

        DB::statement('ALTER TABLE user_room ADD CONSTRAINT chk_user_room_role CHECK (role IN ("admin", "moderator", "member"))');
        DB::statement('ALTER TABLE user_room ADD CONSTRAINT chk_user_room_joined_at CHECK (joined_at IS NOT NULL)');

        // Add unique constraints
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email', 'uq_users_email');
        });

        Schema::table('user_room', function (Blueprint $table) {
            $table->unique(['user_id', 'room_id'], 'uq_user_room_user_room');
        });

        // Add default values
        DB::statement('ALTER TABLE users ALTER COLUMN is_admin SET DEFAULT false');
        DB::statement('ALTER TABLE users ALTER COLUMN email_notifications SET DEFAULT true');
        DB::statement('ALTER TABLE users ALTER COLUMN push_notifications SET DEFAULT true');
        DB::statement('ALTER TABLE users ALTER COLUMN message_notifications SET DEFAULT true');
        DB::statement('ALTER TABLE users ALTER COLUMN room_invitation_notifications SET DEFAULT true');
        DB::statement('ALTER TABLE users ALTER COLUMN security_alerts SET DEFAULT true');

        DB::statement('ALTER TABLE rooms ALTER COLUMN is_private SET DEFAULT false');
        DB::statement('ALTER TABLE rooms ALTER COLUMN member_count SET DEFAULT 0');
        DB::statement('ALTER TABLE rooms ALTER COLUMN message_count SET DEFAULT 0');

        DB::statement('ALTER TABLE messages ALTER COLUMN type SET DEFAULT "text"');

        DB::statement('ALTER TABLE user_room ALTER COLUMN role SET DEFAULT "member"');
        DB::statement('ALTER TABLE user_room ALTER COLUMN is_online SET DEFAULT false');

        // Add not null constraints
        DB::statement('ALTER TABLE users ALTER COLUMN name SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN email SET NOT NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN password SET NOT NULL');

        DB::statement('ALTER TABLE rooms ALTER COLUMN name SET NOT NULL');

        DB::statement('ALTER TABLE messages ALTER COLUMN content SET NOT NULL');
        DB::statement('ALTER TABLE messages ALTER COLUMN user_id SET NOT NULL');
        DB::statement('ALTER TABLE messages ALTER COLUMN room_id SET NOT NULL');

        DB::statement('ALTER TABLE user_room ALTER COLUMN user_id SET NOT NULL');
        DB::statement('ALTER TABLE user_room ALTER COLUMN room_id SET NOT NULL');
        DB::statement('ALTER TABLE user_room ALTER COLUMN role SET NOT NULL');
        DB::statement('ALTER TABLE user_room ALTER COLUMN joined_at SET NOT NULL');

        // Add trigger constraints
        DB::unprepared('
            CREATE TRIGGER validate_message_content
            BEFORE INSERT ON messages
            FOR EACH ROW
            BEGIN
                IF NEW.content IS NULL OR LENGTH(TRIM(NEW.content)) = 0 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Message content cannot be empty";
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER validate_room_name
            BEFORE INSERT ON rooms
            FOR EACH ROW
            BEGIN
                IF NEW.name IS NULL OR LENGTH(TRIM(NEW.name)) < 3 THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Room name must be at least 3 characters long";
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER validate_user_email
            BEFORE INSERT ON users
            FOR EACH ROW
            BEGIN
                IF NEW.email NOT REGEXP "^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$" THEN
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Invalid email format";
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['room_id']);
        });

        Schema::table('user_room', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['room_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Drop check constraints
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_email_format');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_name_length');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_users_password_length');

        DB::statement('ALTER TABLE rooms DROP CONSTRAINT IF EXISTS chk_rooms_name_length');
        DB::statement('ALTER TABLE rooms DROP CONSTRAINT IF EXISTS chk_rooms_description_length');
        DB::statement('ALTER TABLE rooms DROP CONSTRAINT IF EXISTS chk_rooms_member_count');
        DB::statement('ALTER TABLE rooms DROP CONSTRAINT IF EXISTS chk_rooms_message_count');

        DB::statement('ALTER TABLE messages DROP CONSTRAINT IF EXISTS chk_messages_content_length');
        DB::statement('ALTER TABLE messages DROP CONSTRAINT IF EXISTS chk_messages_type');

        DB::statement('ALTER TABLE user_room DROP CONSTRAINT IF EXISTS chk_user_room_role');
        DB::statement('ALTER TABLE user_room DROP CONSTRAINT IF EXISTS chk_user_room_joined_at');

        // Drop unique constraints
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('uq_users_email');
        });

        Schema::table('user_room', function (Blueprint $table) {
            $table->dropUnique('uq_user_room_user_room');
        });

        // Drop triggers
        DB::unprepared('DROP TRIGGER IF EXISTS validate_message_content');
        DB::unprepared('DROP TRIGGER IF EXISTS validate_room_name');
        DB::unprepared('DROP TRIGGER IF EXISTS validate_user_email');
    }
}; 