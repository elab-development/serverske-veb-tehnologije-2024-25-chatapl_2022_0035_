<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--type=full : Type of backup (full, incremental, schema)} {--compress=true : Compress backup file} {--retention=30 : Days to retain backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database backup with various options';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $compress = $this->option('compress') === 'true';
        $retention = (int) $this->option('retention');

        $this->info("Starting {$type} database backup...");

        try {
            // Create backup directory if it doesn't exist
            $backupDir = storage_path('backups/database');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate backup filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$type}_{$timestamp}.sql";
            $filepath = "{$backupDir}/{$filename}";

            // Perform backup based on type
            switch ($type) {
                case 'full':
                    $this->createFullBackup($filepath);
                    break;
                case 'incremental':
                    $this->createIncrementalBackup($filepath);
                    break;
                case 'schema':
                    $this->createSchemaBackup($filepath);
                    break;
                default:
                    $this->error("Invalid backup type: {$type}");
                    return Command::FAILURE;
            }

            // Compress backup if requested
            if ($compress) {
                $filepath = $this->compressBackup($filepath);
            }

            // Clean old backups
            $this->cleanOldBackups($retention);

            // Log backup information
            $this->logBackupInfo($filename, $type, $filepath);

            $this->info("Database backup completed successfully: {$filename}");
            $this->info("Backup size: " . $this->formatFileSize(filesize($filepath)));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Create full database backup.
     */
    protected function createFullBackup(string $filepath): void
    {
        $this->info('Creating full database backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $command = "mysqldump --host={$host} --port={$port} --user={$username}";
        
        if ($password) {
            $command .= " --password={$password}";
        }

        $command .= " --single-transaction --routines --triggers --events --add-drop-database --databases {$database} > {$filepath}";

        $this->executeCommand($command);
    }

    /**
     * Create incremental backup (only changed data).
     */
    protected function createIncrementalBackup(string $filepath): void
    {
        $this->info('Creating incremental database backup...');

        // Get last backup timestamp
        $lastBackup = $this->getLastBackupTimestamp();

        if (!$lastBackup) {
            $this->warn('No previous backup found, creating full backup instead.');
            $this->createFullBackup($filepath);
            return;
        }

        // Create incremental backup for data changed since last backup
        $this->createIncrementalDataBackup($filepath, $lastBackup);
    }

    /**
     * Create schema-only backup.
     */
    protected function createSchemaBackup(string $filepath): void
    {
        $this->info('Creating schema-only backup...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $command = "mysqldump --host={$host} --port={$port} --user={$username}";
        
        if ($password) {
            $command .= " --password={$password}";
        }

        $command .= " --no-data --routines --triggers --events --add-drop-database --databases {$database} > {$filepath}";

        $this->executeCommand($command);
    }

    /**
     * Create incremental data backup.
     */
    protected function createIncrementalDataBackup(string $filepath, string $lastBackup): void
    {
        $tables = $this->getTablesWithChanges($lastBackup);
        
        if (empty($tables)) {
            $this->info('No changes detected since last backup.');
            return;
        }

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $command = "mysqldump --host={$host} --port={$port} --user={$username}";
        
        if ($password) {
            $command .= " --password={$password}";
        }

        $command .= " --single-transaction --where=\"updated_at >= '{$lastBackup}'\" --databases {$database} " . implode(' ', $tables) . " > {$filepath}";

        $this->executeCommand($command);
    }

    /**
     * Get tables with changes since last backup.
     */
    protected function getTablesWithChanges(string $lastBackup): array
    {
        $tables = [];
        
        // Check which tables have been updated since last backup
        $updatedTables = DB::select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND UPDATE_TIME > ?
        ", [config('database.connections.mysql.database'), $lastBackup]);

        foreach ($updatedTables as $table) {
            $tables[] = $table->TABLE_NAME;
        }

        return $tables;
    }

    /**
     * Get last backup timestamp.
     */
    protected function getLastBackupTimestamp(): ?string
    {
        $backupDir = storage_path('backups/database');
        $files = glob("{$backupDir}/backup_*.sql*");
        
        if (empty($files)) {
            return null;
        }

        // Get the most recent backup file
        $latestFile = end($files);
        $filename = basename($latestFile);
        
        // Extract timestamp from filename
        if (preg_match('/backup_\w+_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $filename, $matches)) {
            return str_replace('_', ' ', $matches[1]);
        }

        return null;
    }

    /**
     * Compress backup file.
     */
    protected function compressBackup(string $filepath): string
    {
        $this->info('Compressing backup file...');
        
        $compressedFile = $filepath . '.gz';
        $command = "gzip -f {$filepath}";
        
        $this->executeCommand($command);
        
        return $compressedFile;
    }

    /**
     * Clean old backup files.
     */
    protected function cleanOldBackups(int $retention): void
    {
        $this->info("Cleaning backups older than {$retention} days...");
        
        $backupDir = storage_path('backups/database');
        $cutoffDate = Carbon::now()->subDays($retention);
        
        $files = glob("{$backupDir}/backup_*");
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(filemtime($file));
            
            if ($fileTime->lt($cutoffDate)) {
                unlink($file);
                $deletedCount++;
            }
        }
        
        $this->info("Deleted {$deletedCount} old backup files.");
    }

    /**
     * Execute shell command.
     */
    protected function executeCommand(string $command): void
    {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Command failed: " . implode("\n", $output));
        }
    }

    /**
     * Log backup information.
     */
    protected function logBackupInfo(string $filename, string $type, string $filepath): void
    {
        $backupInfo = [
            'filename' => $filename,
            'type' => $type,
            'filepath' => $filepath,
            'size' => filesize($filepath),
            'created_at' => now(),
        ];

        // Store backup info in database or log file
        \Log::info('Database backup created', $backupInfo);
    }

    /**
     * Format file size for display.
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 