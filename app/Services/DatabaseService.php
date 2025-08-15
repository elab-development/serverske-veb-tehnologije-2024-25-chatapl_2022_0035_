<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DatabaseService
{
    /**
     * Izvrši kompleksan SQL upit sa JOIN-ovima i agregacijom
     */
    public static function getComplexRoomStats(): array
    {
        try {
            $stats = DB::select('
                SELECT 
                    r.id,
                    r.name,
                    r.description,
                    r.is_private,
                    r.created_at,
                    COUNT(DISTINCT ur.user_id) as total_members,
                    COUNT(DISTINCT CASE WHEN ur.is_online = 1 THEN ur.user_id END) as online_members,
                    COUNT(m.id) as total_messages,
                    COUNT(DISTINCT m.user_id) as unique_senders,
                    AVG(CASE WHEN m.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as weekly_activity,
                    MAX(m.created_at) as last_message_at,
                    (
                        SELECT COUNT(*) 
                        FROM messages m2 
                        WHERE m2.room_id = r.id 
                        AND m2.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ) as messages_last_24h
                FROM rooms r
                LEFT JOIN user_room ur ON r.id = ur.room_id
                LEFT JOIN messages m ON r.id = m.room_id
                WHERE r.deleted_at IS NULL
                GROUP BY r.id, r.name, r.description, r.is_private, r.created_at
                ORDER BY total_messages DESC, online_members DESC
            ');

            return $stats;
        } catch (Exception $e) {
            Log::error('Error getting complex room stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Izvrši transakciju sa više operacija
     */
    public static function executeTransaction(callable $callback)
    {
        return DB::transaction($callback);
    }

    /**
     * Kreiraj backup baze podataka
     */
    public static function createBackup(): string
    {
        try {
            $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
            $filepath = storage_path('app/backups/' . $filename);

            // Kreiraj direktorijum ako ne postoji
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            // Kreiraj backup komandu
            $command = sprintf(
                'mysqldump -u%s -p%s %s > %s',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.database'),
                $filepath
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                Log::info('Database backup created successfully: ' . $filename);
                return $filename;
            } else {
                throw new Exception('Backup failed with return code: ' . $returnCode);
            }
        } catch (Exception $e) {
            Log::error('Error creating database backup: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Optimizuj tabelu
     */
    public static function optimizeTable(string $tableName): bool
    {
        try {
            DB::statement("OPTIMIZE TABLE {$tableName}");
            Log::info("Table {$tableName} optimized successfully");
            return true;
        } catch (Exception $e) {
            Log::error("Error optimizing table {$tableName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Analiziraj tabelu
     */
    public static function analyzeTable(string $tableName): array
    {
        try {
            $result = DB::select("ANALYZE TABLE {$tableName}");
            return $result;
        } catch (Exception $e) {
            Log::error("Error analyzing table {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Dobavi statistike o tabelama
     */
    public static function getTableStats(): array
    {
        try {
            $stats = DB::select('
                SELECT 
                    TABLE_NAME,
                    TABLE_ROWS,
                    DATA_LENGTH,
                    INDEX_LENGTH,
                    (DATA_LENGTH + INDEX_LENGTH) as TOTAL_SIZE,
                    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as SIZE_MB
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ?
                ORDER BY TOTAL_SIZE DESC
            ', [config('database.connections.mysql.database')]);

            return $stats;
        } catch (Exception $e) {
            Log::error('Error getting table stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Dobavi informacije o indeksima
     */
    public static function getIndexInfo(string $tableName): array
    {
        try {
            $indexes = DB::select('
                SELECT 
                    INDEX_NAME,
                    COLUMN_NAME,
                    NON_UNIQUE,
                    SEQ_IN_INDEX,
                    CARDINALITY
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                ORDER BY INDEX_NAME, SEQ_IN_INDEX
            ', [config('database.connections.mysql.database'), $tableName]);

            return $indexes;
        } catch (Exception $e) {
            Log::error("Error getting index info for table {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Izvrši stored procedure
     */
    public static function callStoredProcedure(string $procedureName, array $parameters = []): array
    {
        try {
            $placeholders = str_repeat('?,', count($parameters) - 1) . '?';
            $sql = "CALL {$procedureName}({$placeholders})";
            
            $result = DB::select($sql, $parameters);
            return $result;
        } catch (Exception $e) {
            Log::error("Error calling stored procedure {$procedureName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Kreiraj stored procedure za statistike
     */
    public static function createStatsProcedure(): bool
    {
        try {
            DB::unprepared('
                CREATE PROCEDURE GetRoomStatistics(IN room_id INT)
                BEGIN
                    SELECT 
                        r.id,
                        r.name,
                        r.description,
                        COUNT(DISTINCT ur.user_id) as total_members,
                        COUNT(DISTINCT CASE WHEN ur.is_online = 1 THEN ur.user_id END) as online_members,
                        COUNT(m.id) as total_messages,
                        COUNT(DISTINCT m.user_id) as unique_senders,
                        MAX(m.created_at) as last_message_at,
                        (
                            SELECT COUNT(*) 
                            FROM messages m2 
                            WHERE m2.room_id = r.id 
                            AND m2.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ) as messages_last_24h
                    FROM rooms r
                    LEFT JOIN user_room ur ON r.id = ur.room_id
                    LEFT JOIN messages m ON r.id = m.room_id
                    WHERE r.id = room_id AND r.deleted_at IS NULL
                    GROUP BY r.id, r.name, r.description;
                END
            ');

            Log::info('Stored procedure GetRoomStatistics created successfully');
            return true;
        } catch (Exception $e) {
            Log::error('Error creating stored procedure: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kreiraj stored procedure za čišćenje starih podataka
     */
    public static function createCleanupProcedure(): bool
    {
        try {
            DB::unprepared('
                CREATE PROCEDURE CleanupOldData(IN days_old INT)
                BEGIN
                    DECLARE EXIT HANDLER FOR SQLEXCEPTION
                    BEGIN
                        ROLLBACK;
                        RESIGNAL;
                    END;
                    
                    START TRANSACTION;
                    
                    -- Obriši stare audit logove
                    DELETE FROM audit_logs 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY);
                    
                    -- Obriši stare poruke (zadrži poslednjih 1000)
                    DELETE FROM messages 
                    WHERE id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM messages 
                            ORDER BY created_at DESC 
                            LIMIT 1000
                        ) AS temp
                    );
                    
                    -- Optimizuj tabele
                    OPTIMIZE TABLE audit_logs;
                    OPTIMIZE TABLE messages;
                    
                    COMMIT;
                END
            ');

            Log::info('Stored procedure CleanupOldData created successfully');
            return true;
        } catch (Exception $e) {
            Log::error('Error creating cleanup procedure: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kreiraj stored procedure za migraciju podataka
     */
    public static function createMigrationProcedure(): bool
    {
        try {
            DB::unprepared('
                CREATE PROCEDURE MigrateUserData()
                BEGIN
                    DECLARE done INT DEFAULT FALSE;
                    DECLARE user_id INT;
                    DECLARE user_name VARCHAR(255);
                    DECLARE user_email VARCHAR(255);
                    
                    DECLARE user_cursor CURSOR FOR 
                        SELECT id, name, email FROM users WHERE is_admin IS NULL;
                    
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
                    
                    OPEN user_cursor;
                    
                    read_loop: LOOP
                        FETCH user_cursor INTO user_id, user_name, user_email;
                        IF done THEN
                            LEAVE read_loop;
                        END IF;
                        
                        UPDATE users 
                        SET is_admin = 0 
                        WHERE id = user_id;
                        
                    END LOOP;
                    
                    CLOSE user_cursor;
                END
            ');

            Log::info('Stored procedure MigrateUserData created successfully');
            return true;
        } catch (Exception $e) {
            Log::error('Error creating migration procedure: ' . $e->getMessage());
            return false;
        }
    }
} 