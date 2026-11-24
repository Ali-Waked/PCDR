<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditArchive;
use App\Services\AuditArchiveService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('pcdr:audit:archive:verify {archive : Archive ID or YYYY-MM period} {--dry-run : Verify without changing registry status}')]
#[Description('Re-verify a monthly archive and its SHA-256 manifest')]
final class VerifyAuditArchive extends Command
{
    public function handle(AuditArchiveService $archives): int
    {
        $identifier = (string) $this->argument('archive');
        $archive = AuditArchive::query()
            ->when(ctype_digit($identifier), fn ($query) => $query->where('id', (int) $identifier))
            ->when(! ctype_digit($identifier), fn ($query) => $query->where('period_key', $identifier))
            ->first();

        if ($archive === null) {
            $this->error("Archive {$identifier} was not found.");

            return self::FAILURE;
        }

        try {
            $result = $archives->verifyArchive($archive, ! (bool) $this->option('dry-run'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Archive {$archive->period_key} verified: {$result['record_count']} records.");

        return self::SUCCESS;
    }
}
