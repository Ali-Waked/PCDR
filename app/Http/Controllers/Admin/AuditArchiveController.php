<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAuditArchive;
use App\Models\AuditArchive;
use App\Models\AuditCleanupRun;
use App\Services\AuditArchiveService;
use App\Services\AuditLifecycleNotifier;
use App\Services\AuditRetentionService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditArchiveController extends Controller
{
    public function __construct(
        private readonly AuditArchiveService $archives,
        private readonly AuditRetentionService $retention,
        private readonly AuditLifecycleNotifier $notifier,
        private readonly AuditService $audit,
    ) {}

    public function index(string $locale, Request $request): Response
    {
        Gate::authorize('viewAny', AuditArchive::class);

        $status = $request->string('status')->trim()->toString();
        $validStatuses = [
            AuditArchive::STATUS_PENDING,
            AuditArchive::STATUS_PROCESSING,
            AuditArchive::STATUS_VERIFIED,
            AuditArchive::STATUS_FAILED,
            AuditArchive::STATUS_PRUNED,
        ];
        $status = in_array($status, $validStatuses, true) ? $status : '';

        $archiveQuery = AuditArchive::query()
            ->with('initiator:id,name')
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('period_start');

        $archives = $archiveQuery
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditArchive $archive): array => $this->archives->describeArchive($archive));

        $runs = AuditCleanupRun::query()
            ->with('archive:id,period_key')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (AuditCleanupRun $run): array => $this->retention->describeRun($run))
            ->all();

        $holds = $this->retention->activeHolds()
            ->map(fn ($hold): array => $this->retention->describeHold($hold))
            ->all();

        $this->audit->record('audit_archives.view', 'audit_archives', null, null, [
            'status' => $status,
        ], [
            'description' => 'Audit archive registry viewed',
        ]);

        return Inertia::render('Admin/AuditArchives/Index', [
            'archives' => $archives,
            'cleanupRuns' => $runs,
            'holds' => $holds,
            'statusFilter' => $status,
            'statuses' => $validStatuses,
            'retention' => $this->retention->configuration(),
            'can' => [
                'view' => Gate::allows('viewAny', AuditArchive::class),
                'download' => Gate::allows('download', AuditArchive::class),
                'manage' => Gate::allows('manage', AuditArchive::class),
                'cleanup' => Gate::allows('cleanup', AuditArchive::class),
            ],
        ]);
    }

    public function show(string $locale, AuditArchive $archive): JsonResponse
    {
        Gate::authorize('view', $archive);

        return response()->json([
            'archive' => $this->archives->describeArchive($archive->loadMissing('initiator:id,name')),
        ]);
    }

    public function store(string $locale, Request $request): RedirectResponse
    {
        Gate::authorize('manage', AuditArchive::class);

        $validated = $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'force' => ['sometimes', 'boolean'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        $month = is_string($validated['month'] ?? null) && $validated['month'] !== '' ? $validated['month'] : null;
        $force = (bool) ($validated['force'] ?? false);
        $initiatedBy = $request->user()?->id;

        if ((bool) ($validated['queue'] ?? false)) {
            GenerateAuditArchive::dispatch($month, $initiatedBy, $force);

            return back()->with('flash', [
                'success' => 'Audit archive generation was queued.',
            ]);
        }

        try {
            $this->archives->createArchive($month, $initiatedBy, $force);
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['error' => $exception->getMessage()])->setStatusCode(303);
        }

        return back()->with('flash', [
            'success' => 'The monthly audit archive was generated and verified.',
        ]);
    }

    public function verify(string $locale, AuditArchive $archive): RedirectResponse
    {
        Gate::authorize('manage', $archive);

        try {
            $inspection = $this->archives->verifyArchive($archive);
            $this->notifier->archiveVerified($archive->refresh());
        } catch (RuntimeException $exception) {
            return back()->with('flash', ['error' => $exception->getMessage()])->setStatusCode(303);
        }

        return back()->with('flash', [
            'success' => 'Archive integrity verified for '.$inspection['record_count'].' records.',
        ]);
    }

    public function download(string $locale, AuditArchive $archive): StreamedResponse
    {
        Gate::authorize('download', $archive);

        $disk = Storage::disk((string) config('audit_archive.disk', 'private'));
        if (! $disk->exists($archive->file_path)) {
            abort(404);
        }

        $this->archives->verifyArchive($archive, false);

        $stream = $disk->readStream($archive->file_path);
        if ($stream === false) {
            abort(500);
        }

        $filename = 'audit-logs-'.$archive->period_key.'.zip';

        $this->audit->record('audit_archives.download', 'audit_archives', (string) $archive->id, null, [
            'period' => $archive->period_key,
        ], [
            'description' => 'Audit archive downloaded',
        ]);

        return response()->streamDownload(function () use ($stream): void {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $filename, [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
