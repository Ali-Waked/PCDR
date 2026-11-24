<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditArchive;
use App\Models\AuditCleanupRun;
use App\Models\User;
use App\Notifications\AuditLifecycleNotification;
use Illuminate\Support\Facades\Notification;

final class AuditLifecycleNotifier
{
    public function __construct(private readonly AuditService $audit) {}

    public function archiveCreated(AuditArchive $archive): void
    {
        $this->record('audit_archives.created', $archive, 'Audit archive created');

        $this->notify(
            'Audit archive created',
            'A monthly audit archive was created and verified.',
            'success',
            $this->details($archive),
        );
    }

    public function archiveVerified(AuditArchive $archive): void
    {
        $this->record('audit_archives.verified', $archive, 'Audit archive verified');
    }

    public function archiveFailed(AuditArchive $archive, string $error): void
    {
        $this->record('audit_archives.failed', $archive, 'Audit archive failed', [
            'error' => str_replace(["\r", "\n"], ' ', $error),
        ]);

        $this->notify(
            'Audit archive failed',
            'A monthly audit archive could not be completed.',
            'failed',
            ['period' => $archive->period_key, 'error' => str_replace(["\r", "\n"], ' ', $error)],
        );
    }

    public function cleanupCompleted(AuditCleanupRun $run): void
    {
        $this->audit->record('audit_cleanup.completed', 'audit_cleanup_runs', (string) $run->id, null, [
            'archive_id' => $run->audit_archive_id,
            'deleted_count' => $run->deleted_count,
            'eligible_count' => $run->eligible_count,
        ]);

        $this->notify(
            'Audit cleanup completed',
            'Approved audit log cleanup finished.',
            'success',
            [
                'archive' => $run->archive?->period_key ?? 'unknown',
                'deleted' => $run->deleted_count,
                'eligible' => $run->eligible_count,
            ],
        );
    }

    public function cleanupFailed(AuditCleanupRun $run, string $error): void
    {
        $this->audit->record('audit_cleanup.failed', 'audit_cleanup_runs', (string) $run->id, null, [
            'archive_id' => $run->audit_archive_id,
            'error' => str_replace(["\r", "\n"], ' ', $error),
        ]);

        $this->notify(
            'Audit cleanup failed',
            'An approved audit log cleanup did not finish.',
            'failed',
            [
                'archive' => $run->archive?->period_key ?? 'unknown',
                'error' => str_replace(["\r", "\n"], ' ', $error),
            ],
        );
    }

    /** @param array<string, mixed> $extra */
    private function record(string $action, AuditArchive $archive, string $description, array $extra = []): void
    {
        $this->audit->record($action, 'audit_archives', (string) $archive->id, null, null, array_merge([
            'description' => $description,
            'period' => $archive->period_key,
            'record_count' => $archive->record_count,
        ], $extra));
    }

    /** @param array<string, mixed> $details */
    private function notify(string $title, string $message, string $status, array $details): void
    {
        $notification = new AuditLifecycleNotification($title, $message, $status, $details);

        $recipients = User::query()->where('is_platform_owner', true)->get();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }

        foreach ((array) config('audit_archive.notification.emails', []) as $email) {
            if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                Notification::route('mail', $email)->notify($notification);
            }
        }
    }

    /** @return array<string, mixed> */
    private function details(AuditArchive $archive): array
    {
        return [
            'period' => $archive->period_key,
            'record_count' => $archive->record_count,
            'status' => $archive->status,
        ];
    }
}
