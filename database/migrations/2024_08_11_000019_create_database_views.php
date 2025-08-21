<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // View for room statistics
        DB::unprepared('
            CREATE VIEW room_statistics AS
            SELECT 
                r.id,
                r.name,
                r.description,
                r.is_private,
                r.created_at,
                COUNT(DISTINCT ur.user_id) as member_count,
                COUNT(m.id) as message_count,
                MAX(m.created_at) as last_message_at,
                AVG(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as activity_score
            FROM rooms r
            LEFT JOIN user_room ur ON r.id = ur.room_id
            LEFT JOIN messages m ON r.id = m.room_id
            GROUP BY r.id, r.name, r.description, r.is_private, r.created_at
        ');

        // View for user activity summary
        DB::unprepared('
            CREATE VIEW user_activity_summary AS
            SELECT 
                u.id,
                u.name,
                u.email,
                u.is_admin,
                u.created_at,
                COUNT(m.id) as total_messages,
                COUNT(DISTINCT m.room_id) as rooms_active_in,
                MAX(m.created_at) as last_activity,
                COUNT(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as messages_last_7_days,
                COUNT(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as messages_last_30_days
            FROM users u
            LEFT JOIN messages m ON u.id = m.user_id
            GROUP BY u.id, u.name, u.email, u.is_admin, u.created_at
        ');

        // View for message analytics
        DB::unprepared('
            CREATE VIEW message_analytics AS
            SELECT 
                DATE(m.created_at) as message_date,
                COUNT(*) as total_messages,
                COUNT(DISTINCT m.user_id) as unique_users,
                COUNT(DISTINCT m.room_id) as active_rooms,
                AVG(LENGTH(m.content)) as avg_message_length,
                COUNT(CASE WHEN m.type = "file" THEN 1 END) as file_messages,
                COUNT(CASE WHEN m.type = "image" THEN 1 END) as image_messages
            FROM messages m
            GROUP BY DATE(m.created_at)
            ORDER BY message_date DESC
        ');

        // View for user room participation
        DB::unprepared('
            CREATE VIEW user_room_participation AS
            SELECT 
                u.id as user_id,
                u.name as user_name,
                r.id as room_id,
                r.name as room_name,
                ur.role,
                ur.joined_at,
                ur.last_seen_at,
                COUNT(m.id) as messages_in_room,
                MAX(m.created_at) as last_message_in_room
            FROM users u
            JOIN user_room ur ON u.id = ur.user_id
            JOIN rooms r ON ur.room_id = r.id
            LEFT JOIN messages m ON u.id = m.user_id AND r.id = m.room_id
            GROUP BY u.id, u.name, r.id, r.name, ur.role, ur.joined_at, ur.last_seen_at
        ');

        // View for security events
        DB::unprepared('
            CREATE VIEW security_events AS
            SELECT 
                al.id,
                al.user_id,
                u.name as user_name,
                al.event_type,
                al.severity,
                al.ip_address,
                al.user_agent,
                al.url,
                al.method,
                al.response_status,
                al.created_at,
                CASE 
                    WHEN al.response_status >= 400 THEN "error"
                    WHEN al.response_status >= 300 THEN "redirect"
                    WHEN al.response_status >= 200 THEN "success"
                    ELSE "unknown"
                END as status_category
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.event_type IN (
                "login_failed", "login_success", "registration_failed", 
                "security_violation", "rate_limit_exceeded", "csrf_token_mismatch"
            )
            ORDER BY al.created_at DESC
        ');

        // View for notification statistics
        DB::unprepared('
            CREATE VIEW notification_statistics AS
            SELECT 
                DATE(n.created_at) as notification_date,
                n.type,
                COUNT(*) as total_notifications,
                COUNT(CASE WHEN n.read_at IS NOT NULL THEN 1 END) as read_notifications,
                COUNT(CASE WHEN n.read_at IS NULL THEN 1 END) as unread_notifications,
                AVG(CASE WHEN n.read_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(MINUTE, n.created_at, n.read_at) 
                    END) as avg_read_time_minutes
            FROM notifications n
            GROUP BY DATE(n.created_at), n.type
            ORDER BY notification_date DESC, n.type
        ');

        // View for system health
        DB::unprepared('
            CREATE VIEW system_health AS
            SELECT 
                "users" as metric,
                COUNT(*) as count,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days
            FROM users
            UNION ALL
            SELECT 
                "rooms" as metric,
                COUNT(*) as count,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days
            FROM rooms
            UNION ALL
            SELECT 
                "messages" as metric,
                COUNT(*) as count,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days
            FROM messages
            UNION ALL
            SELECT 
                "notifications" as metric,
                COUNT(*) as count,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7_days,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as last_30_days
            FROM notifications
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS room_statistics');
        DB::unprepared('DROP VIEW IF EXISTS user_activity_summary');
        DB::unprepared('DROP VIEW IF EXISTS message_analytics');
        DB::unprepared('DROP VIEW IF EXISTS user_room_participation');
        DB::unprepared('DROP VIEW IF EXISTS security_events');
        DB::unprepared('DROP VIEW IF EXISTS notification_statistics');
        DB::unprepared('DROP VIEW IF EXISTS system_health');
    }
}; 