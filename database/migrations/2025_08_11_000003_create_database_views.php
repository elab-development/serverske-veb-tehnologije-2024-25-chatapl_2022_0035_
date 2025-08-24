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
        // View za aktivne sobe sa brojem korisnika i poruka
        DB::unprepared('
            CREATE VIEW active_rooms AS
            SELECT 
                r.id,
                r.name,
                r.description,
                r.is_private,
                r.created_at,
                r.updated_at,
                COUNT(DISTINCT ur.user_id) as active_users,
                COUNT(m.id) as total_messages,
                MAX(m.created_at) as last_activity
            FROM rooms r
            LEFT JOIN user_room ur ON r.id = ur.room_id AND ur.is_online = 1
            LEFT JOIN messages m ON r.id = m.room_id
            WHERE r.deleted_at IS NULL
            GROUP BY r.id, r.name, r.description, r.is_private, r.created_at, r.updated_at
        ');

        // View za korisničke statistike
        DB::unprepared('
            CREATE VIEW user_statistics AS
            SELECT 
                u.id,
                u.name,
                u.email,
                u.created_at,
                COUNT(DISTINCT m.id) as total_messages,
                COUNT(DISTINCT ur.room_id) as rooms_joined,
                MAX(m.created_at) as last_message_at,
                COUNT(DISTINCT CASE WHEN ur.is_online = 1 THEN ur.room_id END) as currently_online_rooms
            FROM users u
            LEFT JOIN messages m ON u.id = m.user_id
            LEFT JOIN user_room ur ON u.id = ur.user_id
            WHERE u.deleted_at IS NULL
            GROUP BY u.id, u.name, u.email, u.created_at
        ');

        // View za popularne sobe
        DB::unprepared('
            CREATE VIEW popular_rooms AS
            SELECT 
                r.id,
                r.name,
                r.description,
                COUNT(DISTINCT ur.user_id) as total_members,
                COUNT(m.id) as total_messages,
                COUNT(DISTINCT CASE WHEN ur.is_online = 1 THEN ur.user_id END) as online_members,
                AVG(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as activity_score
            FROM rooms r
            LEFT JOIN user_room ur ON r.id = ur.room_id
            LEFT JOIN messages m ON r.id = m.room_id
            WHERE r.deleted_at IS NULL
            GROUP BY r.id, r.name, r.description
            HAVING total_messages > 0
            ORDER BY activity_score DESC, total_members DESC
        ');

        // View za aktivne korisnike
        DB::unprepared('
            CREATE VIEW active_users AS
            SELECT 
                u.id,
                u.name,
                u.email,
                COUNT(m.id) as messages_last_7_days,
                COUNT(DISTINCT ur.room_id) as active_rooms,
                MAX(m.created_at) as last_activity,
                CASE 
                    WHEN MAX(m.created_at) >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN "Very Active"
                    WHEN MAX(m.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN "Active"
                    WHEN MAX(m.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN "Occasional"
                    ELSE "Inactive"
                END as activity_level
            FROM users u
            LEFT JOIN messages m ON u.id = m.user_id AND m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            LEFT JOIN user_room ur ON u.id = ur.user_id AND ur.is_online = 1
            WHERE u.deleted_at IS NULL
            GROUP BY u.id, u.name, u.email
        ');

        // View za sistem statistike
        DB::unprepared('
            CREATE VIEW system_statistics AS
            SELECT 
                (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as total_users,
                (SELECT COUNT(*) FROM rooms WHERE deleted_at IS NULL) as total_rooms,
                (SELECT COUNT(*) FROM messages) as total_messages,
                (SELECT COUNT(*) FROM user_room WHERE is_online = 1) as currently_online_users,
                (SELECT COUNT(*) FROM rooms WHERE deleted_at IS NULL AND is_private = 0) as public_rooms,
                (SELECT COUNT(*) FROM rooms WHERE deleted_at IS NULL AND is_private = 1) as private_rooms,
                (SELECT COUNT(*) FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as messages_last_24h,
                (SELECT COUNT(*) FROM messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as messages_last_7_days,
                (SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_users_last_30_days
        ');

        // View za moderatorske aktivnosti
        DB::unprepared('
            CREATE VIEW moderator_activities AS
            SELECT 
                u.id as moderator_id,
                u.name as moderator_name,
                r.id as room_id,
                r.name as room_name,
                COUNT(m.id) as messages_moderated,
                COUNT(DISTINCT al.id) as actions_taken,
                MAX(al.created_at) as last_moderation_action
            FROM users u
            JOIN user_room ur ON u.id = ur.user_id AND ur.role IN ("admin", "moderator")
            JOIN rooms r ON ur.room_id = r.id
            LEFT JOIN messages m ON r.id = m.room_id AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            LEFT JOIN audit_logs al ON u.id = al.user_id AND al.action IN ("message_deleted", "user_banned", "user_warned")
            WHERE u.deleted_at IS NULL AND r.deleted_at IS NULL
            GROUP BY u.id, u.name, r.id, r.name
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS active_rooms');
        DB::unprepared('DROP VIEW IF EXISTS user_statistics');
        DB::unprepared('DROP VIEW IF EXISTS popular_rooms');
        DB::unprepared('DROP VIEW IF EXISTS active_users');
        DB::unprepared('DROP VIEW IF EXISTS system_statistics');
        DB::unprepared('DROP VIEW IF EXISTS moderator_activities');
    }
}; 