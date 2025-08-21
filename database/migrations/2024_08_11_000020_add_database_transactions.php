<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add transaction support columns to existing tables
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_transaction_at')->nullable();
            $table->string('transaction_status')->default('active');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->timestamp('last_transaction_at')->nullable();
            $table->string('transaction_status')->default('active');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('last_transaction_at')->nullable();
            $table->string('transaction_status')->default('active');
        });

        // Create transaction log table
        Schema::create('database_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('table_name');
            $table->string('operation'); // INSERT, UPDATE, DELETE
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('status'); // PENDING, COMMITTED, ROLLED_BACK
            $table->timestamp('created_at');
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->text('error_message')->nullable();
            $table->index(['transaction_id', 'status']);
            $table->index(['table_name', 'created_at']);
        });

        // Create transaction locks table
        Schema::create('transaction_locks', function (Blueprint $table) {
            $table->id();
            $table->string('lock_key')->unique();
            $table->string('transaction_id');
            $table->timestamp('acquired_at');
            $table->timestamp('expires_at');
            $table->index(['lock_key', 'expires_at']);
        });

        // Create rollback points table
        Schema::create('rollback_points', function (Blueprint $table) {
            $table->id();
            $table->string('point_name')->unique();
            $table->string('transaction_id');
            $table->json('checkpoint_data');
            $table->timestamp('created_at');
            $table->boolean('is_active')->default(true);
            $table->index(['point_name', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_transaction_at', 'transaction_status']);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['last_transaction_at', 'transaction_status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['last_transaction_at', 'transaction_status']);
        });

        Schema::dropIfExists('database_transactions');
        Schema::dropIfExists('transaction_locks');
        Schema::dropIfExists('rollback_points');
    }
}; 