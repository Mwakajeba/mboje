<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout for large backups
    public $tries = 1; // Don't retry failed backups automatically

    protected $backupId;
    protected $backupType;
    protected $description;

    /**
     * Create a new job instance.
     */
    public function __construct($backupId, $backupType, $description = null)
    {
        $this->backupId = $backupId;
        $this->backupType = $backupType;
        $this->description = $description;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $backup = Backup::find($this->backupId);

            if (!$backup) {
                Log::error('Backup job failed: Backup record not found', [
                    'backup_id' => $this->backupId
                ]);
                return;
            }

            // Update status to in_progress
            $backup->update(['status' => 'in_progress']);

            $backupService = new BackupService();

            // Execute the appropriate backup method
            switch ($this->backupType) {
                case 'database':
                    $backupService->createDatabaseBackup($this->description, $backup);
                    break;
                case 'files':
                    $backupService->createFilesBackup($this->description, $backup);
                    break;
                case 'full':
                    $backupService->createFullBackup($this->description, $backup);
                    break;
                default:
                    throw new \Exception('Invalid backup type');
            }

            Log::info('Backup job completed successfully', [
                'backup_id' => $this->backupId,
                'type' => $this->backupType
            ]);

        } catch (\Exception $e) {
            Log::error('Backup job failed', [
                'backup_id' => $this->backupId,
                'type' => $this->backupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update backup status to failed
            $backup = Backup::find($this->backupId);
            if ($backup) {
                $backup->update([
                    'status' => 'failed',
                    'description' => ($this->description ?? '') . ' (Failed: ' . $e->getMessage() . ')'
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $backup = Backup::find($this->backupId);
        if ($backup) {
            $backup->update([
                'status' => 'failed',
                'description' => ($this->description ?? '') . ' (Failed: ' . $exception->getMessage() . ')'
            ]);
        }

        Log::error('Backup job failed permanently', [
            'backup_id' => $this->backupId,
            'type' => $this->backupType,
            'error' => $exception->getMessage()
        ]);
    }
}

