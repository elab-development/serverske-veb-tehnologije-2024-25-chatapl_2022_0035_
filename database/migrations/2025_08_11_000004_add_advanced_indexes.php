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
        // Dodaj napredne indekse za optimizaciju performansi

        // Composite index za messages tabelu
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['room_id', 'created_at'], 'messages_room_created_index');
            $table->index(['user_id', 'created_at'], 'messages_user_created_index');
            $table->index(['room_id', 'user_id', 'created_at'], 'messages_room_user_created_index');
        });

        // Composite index za user_room tabelu
        Schema::table('user_room', function (Blueprint $table) {
            $table->index(['room_id', 'is_online'], 'user_room_room_online_index');
            $table->index(['user_id', 'is_online'], 'user_room_user_online_index');
            $table->index(['room_id', 'role'], 'user_room_room_role_index');
        });

        // Composite index za rooms tabelu
        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['is_private', 'created_at'], 'rooms_private_created_index');
            $table->index(['message_count', 'created_at'], 'rooms_message_count_created_index');
        });

        // Composite index za users tabelu
        Schema::table('users', function (Blueprint $table) {
            $table->index(['is_admin', 'created_at'], 'users_admin_created_index');
        });

        // Composite index za audit_logs tabelu
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_index');
            $table->index(['action', 'created_at'], 'audit_logs_action_created_index');
            $table->index(['resource_type', 'resource_id'], 'audit_logs_resource_index');
            $table->index(['ip_address', 'created_at'], 'audit_logs_ip_created_index');
            $table->index(['status', 'created_at'], 'audit_logs_status_created_index');
        });

        // Full-text search indeks za messages
        DB::unprepared('
            CREATE FULLTEXT INDEX messages_content_search 
            ON messages(content)
        ');

        // Full-text search indeks za rooms
        DB::unprepared('
            CREATE FULLTEXT INDEX rooms_name_description_search 
            ON rooms(name, description)
        ');

        // Partial indeks za aktivne korisnike
        DB::unprepared('
            CREATE INDEX idx_user_room_active_users 
            ON user_room(user_id, room_id) 
            WHERE is_online = 1
        ');

        // Partial indeks za nedavne poruke
        DB::unprepared('
            CREATE INDEX idx_messages_recent 
            ON messages(room_id, created_at) 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ');

        // Partial indeks za admin korisnike
        DB::unprepared('
            CREATE INDEX idx_users_admins 
            ON users(id, name, email) 
            WHERE is_admin = 1
        ');

        // Partial indeks za privatne sobe
        DB::unprepared('
            CREATE INDEX idx_rooms_private 
            ON rooms(id, name, created_at) 
            WHERE is_private = 1
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ukloni napredne indekse
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_room_created_index');
            $table->dropIndex('messages_user_created_index');
            $table->dropIndex('messages_room_user_created_index');
        });

        Schema::table('user_room', function (Blueprint $table) {
            $table->dropIndex('user_room_room_online_index');
            $table->dropIndex('user_room_user_online_index');
            $table->dropIndex('user_room_room_role_index');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_private_created_index');
            $table->dropIndex('rooms_message_count_created_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_admin_created_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_user_created_index');
            $table->dropIndex('audit_logs_action_created_index');
            $table->dropIndex('audit_logs_resource_index');
            $table->dropIndex('audit_logs_ip_created_index');
            $table->dropIndex('audit_logs_status_created_index');
        });

        // Ukloni full-text indekse
        DB::unprepared('DROP INDEX IF EXISTS messages_content_search ON messages');
        DB::unprepared('DROP INDEX IF EXISTS rooms_name_description_search ON rooms');

        // Ukloni partial indekse
        DB::unprepared('DROP INDEX IF EXISTS idx_user_room_active_users ON user_room');
        DB::unprepared('DROP INDEX IF EXISTS idx_messages_recent ON messages');
        DB::unprepared('DROP INDEX IF EXISTS idx_users_admins ON users');
        DB::unprepared('DROP INDEX IF EXISTS idx_rooms_private ON rooms');
    }
}; 