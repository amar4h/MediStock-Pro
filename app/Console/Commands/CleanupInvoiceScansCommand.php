<?php

namespace App\Console\Commands;

use App\Models\InvoiceScan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupInvoiceScansCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'medistock:cleanup-invoice-scans
                            {--days= : Override retention days from config}
                            {--dry-run : Preview what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete invoice scan images older than the configured retention period and soft-delete their records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = $this->option('days')
            ? (int) $this->option('days')
            : config('medistock.invoice_scan.retention_days', 90);

        $dryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($retentionDays);

        $this->info("Cleaning up invoice scans older than {$retentionDays} days (before {$cutoff->toDateString()})...");

        if ($dryRun) {
            $this->warn('DRY RUN mode: no files or records will be deleted.');
        }

        // Find scans that are older than the retention period and not yet soft-deleted.
        $scans = InvoiceScan::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($scans->isEmpty()) {
            $this->info('No invoice scans found for cleanup.');
            Log::channel('daily')->info("medistock:cleanup-invoice-scans completed. Nothing to clean up (retention: {$retentionDays} days).");

            return self::SUCCESS;
        }

        $this->info("Found {$scans->count()} invoice scan(s) to clean up.");

        $deletedFiles   = 0;
        $deletedRecords = 0;
        $failedFiles    = 0;

        foreach ($scans as $scan) {
            // 1. Delete the image file from storage.
            if ($scan->image_path) {
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would delete file: {$scan->image_path}");
                } else {
                    try {
                        if (Storage::disk('local')->exists($scan->image_path)) {
                            Storage::disk('local')->delete($scan->image_path);
                            $deletedFiles++;
                        } else {
                            // File already missing; proceed to soft-delete the record.
                            $this->line("  File already missing: {$scan->image_path}");
                        }
                    } catch (\Throwable $e) {
                        $failedFiles++;
                        Log::channel('daily')->error("Failed to delete invoice scan file: {$scan->image_path}", [
                            'scan_id' => $scan->id,
                            'error'   => $e->getMessage(),
                        ]);
                        $this->error("  Failed to delete file: {$scan->image_path} - {$e->getMessage()}");
                        // Continue to soft-delete the record even if file deletion fails.
                    }
                }
            }

            // 2. Soft-delete the scan record.
            if (! $dryRun) {
                $scan->delete(); // Uses SoftDeletes trait.
                $deletedRecords++;
            }
        }

        if ($dryRun) {
            $this->info("DRY RUN complete. {$scans->count()} scan(s) would be cleaned up.");
        } else {
            $this->info("Cleanup complete. Files deleted: {$deletedFiles}, records soft-deleted: {$deletedRecords}, file deletion failures: {$failedFiles}.");

            Log::channel('daily')->info("medistock:cleanup-invoice-scans completed.", [
                'retention_days'  => $retentionDays,
                'files_deleted'   => $deletedFiles,
                'records_deleted' => $deletedRecords,
                'file_failures'   => $failedFiles,
            ]);
        }

        return self::SUCCESS;
    }
}
