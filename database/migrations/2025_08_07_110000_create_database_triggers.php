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
        // Trigger za automatsko ažuriranje broja poruka u sobi
        DB::unprepared('
            CREATE TRIGGER update_room_message_count_after_insert
            AFTER INSERT ON messages
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET message_count = message_count + 1,
                    last_message_at = NEW.created_at
                WHERE id = NEW.room_id;
            END;
        ');

        DB::unprepared('
            CREATE TRIGGER update_room_message_count_after_delete
            AFTER DELETE ON messages
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET message_count = message_count - 1
                WHERE id = OLD.room_id;
            END;
        ');

        // Trigger za automatsko ažuriranje broja korisnika u sobi
        DB::unprepared('
            CREATE TRIGGER update_room_user_count_after_insert
            AFTER INSERT ON user_room
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET user_count = user_count + 1
                WHERE id = NEW.room_id;
            END;
        ');

        DB::unprepared('
            CREATE TRIGGER update_room_user_count_after_delete
            AFTER DELETE ON user_room
            FOR EACH ROW
            BEGIN
                UPDATE rooms 
                SET user_count = user_count - 1
                WHERE id = OLD.room_id;
            END;
        ');

        // Trigger za automatsko ažuriranje last_seen_at u user_room
        DB::unprepared('
            CREATE TRIGGER update_user_room_last_seen
            AFTER INSERT ON messages
            FOR EACH ROW
            BEGIN
                UPDATE user_room 
                SET last_seen_at = NEW.created_at,
                    is_online = 1
                WHERE user_id = NEW.user_id AND room_id = NEW.room_id;
            END;
        ');

        // Trigger za automatsko logovanje brisanja poruka
        DB::unprepared('
            CREATE TRIGGER log_message_deletion
            BEFORE DELETE ON messages
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (user_id, action, resource_type, resource_id, details, created_at)
                VALUES (
                    (SELECT user_id FROM user_room WHERE room_id = OLD.room_id AND role = "admin" LIMIT 1),
                    "message_deleted",
                    "Message",
                    OLD.id,
                    JSON_OBJECT("deleted_by", OLD.user_id, "room_id", OLD.room_id, "content", OLD.content),
                    NOW()
                );
            END;
        ');

        // Trigger za automatsko ažuriranje user_stats
        DB::unprepared('
            CREATE TRIGGER update_user_stats_after_message
            AFTER INSERT ON messages
            FOR EACH ROW
            BEGIN
                INSERT OR REPLACE INTO user_stats (user_id, total_messages, last_message_at, created_at, updated_at)
                VALUES (
                    NEW.user_id, 
                    COALESCE((SELECT total_messages FROM user_stats WHERE user_id = NEW.user_id), 0) + 1,
                    NEW.created_at, 
                    NOW(), 
                    NOW()
                );
            END;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_message_count_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_message_count_after_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_user_count_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS update_room_user_count_after_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS update_user_room_last_seen');
        DB::unprepared('DROP TRIGGER IF EXISTS log_message_deletion');
        DB::unprepared('DROP TRIGGER IF EXISTS update_user_stats_after_message');
    }
}; 