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
        // Create trigger for updating user_room last_seen_at
        DB::unprepared('
            CREATE TRIGGER update_user_room_last_seen
            AFTER UPDATE ON users
            FOR EACH ROW
            BEGIN
                UPDATE user_room 
                SET last_seen_at = NOW() 
                WHERE user_id = NEW.id;
            END
        ');

        // Create trigger for message count in rooms
        DB::unprepared('
            CREATE TRIGGER update_room_message_count_insert
            AFTER INSERT ON messages
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET message_count = message_count + 1 
                WHERE id = NEW.room_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER update_room_message_count_delete
            AFTER DELETE ON messages
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET message_count = message_count - 1 
                WHERE id = OLD.room_id;
            END
        ');

        // Create trigger for user activity tracking
        DB::unprepared('
            CREATE TRIGGER track_user_activity
            AFTER INSERT ON messages
            FOR EACH ROW
            BEGIN
                INSERT INTO user_activity_log (user_id, activity_type, related_id, created_at)
                VALUES (NEW.user_id, "message_sent", NEW.id, NOW());
            END
        ');

        // Create trigger for room member count
        DB::unprepared('
            CREATE TRIGGER update_room_member_count_insert
            AFTER INSERT ON user_room
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET member_count = member_count + 1 
                WHERE id = NEW.room_id;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER update_room_member_count_delete
            AFTER DELETE ON user_room
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET member_count = member_count - 1 
                WHERE id = OLD.room_id;
            END
        ');

        // Create trigger for audit logging
        DB::unprepared('
            CREATE TRIGGER audit_user_changes
            AFTER UPDATE ON users
            FOR EACH ROW
            BEGIN
                IF OLD.name != NEW.name OR OLD.email != NEW.email OR OLD.is_admin != NEW.is_admin THEN
                    INSERT INTO audit_logs (user_id, event_type, old_values, new_values, created_at)
                    VALUES (NEW.id, "user_updated", 
                           JSON_OBJECT("name", OLD.name, "email", OLD.email, "is_admin", OLD.is_admin),
                           JSON_OBJECT("name", NEW.name, "email", NEW.email, "is_admin", NEW.is_admin),
                           NOW());
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS update_user_room_last_seen');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_message_count_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_message_count_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS track_user_activity');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_member_count_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_member_count_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS audit_user_changes');
    }
}; 