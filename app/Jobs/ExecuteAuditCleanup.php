<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditArchive;
use App\Services\AuditRetentionService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ExecuteAuditCleanup implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $archiveId,
        public readonly string $confirmation,
        public readonly ?int $requestedBy = null,
    ) {
        $connection = config('audit_archive.queue.connection');
        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        $this->onQueue((string) config('audit_archive.queue.queue', 'audit'));
    }

    /**
     * Execute the job.
     */
    public function handle(AuditRetentionService $retention): void
    {
        $archive = AuditArchive::query()->findOrFail($this->archiveId);
        $retention->dryRun($archive->id);
        $retention->execute($archive, $this->confirmation, $this->requestedBy);
    }

    public function uniqueId(): string
    {
        return 'audit-cleanup:'.$this->archiveId;
    }
}
