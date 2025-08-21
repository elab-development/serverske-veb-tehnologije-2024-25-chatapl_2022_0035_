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
        // Stored procedure for getting room statistics
        DB::unprepared('
            CREATE PROCEDURE GetRoomStatistics(IN room_id INT)
            BEGIN
                SELECT 
                    r.id,
                    r.name,
                    r.description,
                    COUNT(DISTINCT ur.user_id) as member_count,
                    COUNT(m.id) as message_count,
                    MAX(m.created_at) as last_message_at,
                    AVG(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as activity_score
                FROM rooms r
                LEFT JOIN user_room ur ON r.id = ur.room_id
                LEFT JOIN messages m ON r.id = m.room_id
                WHERE r.id = room_id
                GROUP BY r.id, r.name, r.description;
            END
        ');

        // Stored procedure for getting user activity summary
        DB::unprepared('
            CREATE PROCEDURE GetUserActivitySummary(IN user_id INT, IN days INT)
            BEGIN
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    COUNT(m.id) as messages_sent,
                    COUNT(DISTINCT m.room_id) as rooms_active_in,
                    MAX(m.created_at) as last_activity,
                    AVG(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as recent_activity_score
                FROM users u
                LEFT JOIN messages m ON u.id = m.user_id 
                    AND m.created_at >= DATE_SUB(NOW(), INTERVAL days DAY)
                WHERE u.id = user_id
                GROUP BY u.id, u.name, u.email;
            END
        ');

        // Stored procedure for cleaning old data
        DB::unprepared('
            CREATE PROCEDURE CleanOldData(IN days_old INT)
            BEGIN
                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    RESIGNAL;
                END;
                
                START TRANSACTION;
                
                -- Delete old messages
                DELETE FROM messages 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY);
                
                -- Delete old audit logs
                DELETE FROM audit_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY);
                
                -- Delete old notifications
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY);
                
                -- Update room statistics
                UPDATE rooms r 
                SET message_count = (
                    SELECT COUNT(*) FROM messages m WHERE m.room_id = r.id
                );
                
                COMMIT;
            END
        ');

        // Stored procedure for bulk user operations
        DB::unprepared('
            CREATE PROCEDURE BulkUpdateUserStatus(IN user_ids JSON, IN new_status VARCHAR(50))
            BEGIN
                DECLARE i INT DEFAULT 0;
                DECLARE user_id INT;
                DECLARE user_count INT;
                
                SET user_count = JSON_LENGTH(user_ids);
                
                WHILE i < user_count DO
                    SET user_id = JSON_EXTRACT(user_ids, CONCAT("$[", i, "]"));
                    
                    UPDATE users 
                    SET status = new_status, 
                        updated_at = NOW() 
                    WHERE id = user_id;
                    
                    SET i = i + 1;
                END WHILE;
            END
        ');

        // Stored procedure for message search
        DB::unprepared('
            CREATE PROCEDURE SearchMessages(IN search_term VARCHAR(255), IN room_id INT, IN limit_count INT)
            BEGIN
                SELECT 
                    m.id,
                    m.content,
                    m.created_at,
                    u.name as user_name,
                    u.id as user_id,
                    r.name as room_name
                FROM messages m
                JOIN users u ON m.user_id = u.id
                JOIN rooms r ON m.room_id = r.id
                WHERE (room_id IS NULL OR m.room_id = room_id)
                    AND (m.content LIKE CONCAT("%", search_term, "%"))
                ORDER BY m.created_at DESC
                LIMIT limit_count;
            END
        ');

        // Stored procedure for room recommendations
        DB::unprepared('
            CREATE PROCEDURE GetRoomRecommendations(IN user_id INT, IN limit_count INT)
            BEGIN
                SELECT 
                    r.id,
                    r.name,
                    r.description,
                    COUNT(DISTINCT ur.user_id) as member_count,
                    COUNT(m.id) as message_count,
                    MAX(m.created_at) as last_activity
                FROM rooms r
                LEFT JOIN user_room ur ON r.id = ur.room_id
                LEFT JOIN messages m ON r.id = m.room_id
                WHERE r.id NOT IN (
                    SELECT room_id FROM user_room WHERE user_id = user_id
                )
                    AND r.is_private = 0
                GROUP BY r.id, r.name, r.description
                ORDER BY message_count DESC, last_activity DESC
                LIMIT limit_count;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS GetRoomStatistics');
        DB::unprepared('DROP PROCEDURE IF EXISTS GetUserActivitySummary');
        DB::unprepared('DROP PROCEDURE IF EXISTS CleanOldData');
        DB::unprepared('DROP PROCEDURE IF EXISTS BulkUpdateUserStatus');
        DB::unprepared('DROP PROCEDURE IF EXISTS SearchMessages');
        DB::unprepared('DROP PROCEDURE IF EXISTS GetRoomRecommendations');
    }
}; 