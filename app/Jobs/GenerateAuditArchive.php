<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\AuditArchiveService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateAuditArchive implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ?string $month,
        public readonly ?int $initiatedBy,
        public readonly bool $force = false,
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
    public function handle(AuditArchiveService $archives): void
    {
        $archives->createArchive($this->month, $this->initiatedBy, $this->force);
    }

    public function uniqueId(): string
    {
        return 'audit-archive:'.($this->month ?? 'previous-month');
    }
}
