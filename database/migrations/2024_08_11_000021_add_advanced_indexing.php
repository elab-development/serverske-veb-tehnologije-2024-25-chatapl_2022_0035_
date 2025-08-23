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
        // Add composite indexes for better query performance
        
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index(['email', 'is_admin'], 'idx_users_email_admin');
            $table->index(['created_at', 'is_admin'], 'idx_users_created_admin');
            $table->index(['name', 'email'], 'idx_users_name_email');
        });

        // Rooms table indexes
        Schema::table('rooms', function (Blueprint $table) {
            $table->index(['is_private', 'created_at'], 'idx_rooms_private_created');
            $table->index(['name', 'is_private'], 'idx_rooms_name_private');
            $table->index(['member_count', 'message_count'], 'idx_rooms_popularity');
        });

        // Messages table indexes
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['room_id', 'created_at'], 'idx_messages_room_created');
            $table->index(['user_id', 'created_at'], 'idx_messages_user_created');
            $table->index(['type', 'created_at'], 'idx_messages_type_created');
            $table->index(['room_id', 'user_id', 'created_at'], 'idx_messages_room_user_created');
        });

        // User_room table indexes
        Schema::table('user_room', function (Blueprint $table) {
            $table->index(['room_id', 'role'], 'idx_user_room_room_role');
            $table->index(['user_id', 'role'], 'idx_user_room_user_role');
            $table->index(['is_online', 'last_seen_at'], 'idx_user_room_online_status');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_read_status');
            $table->index(['type', 'created_at'], 'idx_notifications_type_created');
            $table->index(['notifiable_id', 'created_at'], 'idx_notifications_user_created');
        });

        // Audit logs table indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'event_type', 'created_at'], 'idx_audit_user_event_created');
            $table->index(['event_type', 'severity', 'created_at'], 'idx_audit_event_severity_created');
            $table->index(['ip_address', 'created_at'], 'idx_audit_ip_created');
        });

        // Create full-text search indexes
        DB::statement('ALTER TABLE messages ADD FULLTEXT INDEX ft_messages_content (content)');
        DB::statement('ALTER TABLE rooms ADD FULLTEXT INDEX ft_rooms_name_description (name, description)');
        DB::statement('ALTER TABLE users ADD FULLTEXT INDEX ft_users_name_email (name, email)');

        // Create partial indexes for active records
        DB::statement('CREATE INDEX idx_messages_active ON messages (room_id, created_at) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_users_active ON users (email, created_at) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX idx_rooms_active ON rooms (is_private, created_at) WHERE deleted_at IS NULL');

        // Create covering indexes for common queries
        DB::statement('CREATE INDEX idx_messages_covering ON messages (room_id, created_at) INCLUDE (user_id, content, type)');
        DB::statement('CREATE INDEX idx_user_room_covering ON user_room (room_id, role) INCLUDE (user_id, joined_at, last_seen_at)');
        DB::statement('CREATE INDEX idx_notifications_covering ON notifications (notifiable_id, read_at) INCLUDE (type, data, created_at)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop composite indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email_admin');
            $table->dropIndex('idx_users_created_admin');
            $table->dropIndex('idx_users_name_email');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_private_created');
            $table->dropIndex('idx_rooms_name_private');
            $table->dropIndex('idx_rooms_popularity');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_room_created');
            $table->dropIndex('idx_messages_user_created');
            $table->dropIndex('idx_messages_type_created');
            $table->dropIndex('idx_messages_room_user_created');
        });

        Schema::table('user_room', function (Blueprint $table) {
            $table->dropIndex('idx_user_room_room_role');
            $table->dropIndex('idx_user_room_user_role');
            $table->dropIndex('idx_user_room_online_status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_read_status');
            $table->dropIndex('idx_notifications_type_created');
            $table->dropIndex('idx_notifications_user_created');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_user_event_created');
            $table->dropIndex('idx_audit_event_severity_created');
            $table->dropIndex('idx_audit_ip_created');
        });

        // Drop full-text indexes
        DB::statement('ALTER TABLE messages DROP INDEX ft_messages_content');
        DB::statement('ALTER TABLE rooms DROP INDEX ft_rooms_name_description');
        DB::statement('ALTER TABLE users DROP INDEX ft_users_name_email');

        // Drop partial indexes
        DB::statement('DROP INDEX IF EXISTS idx_messages_active');
        DB::statement('DROP INDEX IF EXISTS idx_users_active');
        DB::statement('DROP INDEX IF EXISTS idx_rooms_active');

        // Drop covering indexes
        DB::statement('DROP INDEX IF EXISTS idx_messages_covering');
        DB::statement('DROP INDEX IF EXISTS idx_user_room_covering');
        DB::statement('DROP INDEX IF EXISTS idx_notifications_covering');
    }
}; 