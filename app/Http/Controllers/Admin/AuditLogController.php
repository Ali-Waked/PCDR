<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditArchive;
use App\Models\AuditLog;
use App\Services\AuditLogQueryService;
use App\Services\AuditService;
use App\Support\CsvSafeWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly AuditLogQueryService $query,
    ) {}

    public function index(string $locale, Request $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $filters = $this->query->filters($request);
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(10, min(100, $perPage));

        $this->audit->record('audit_logs.view', 'audit_logs', null, null, null, [
            'filters' => array_keys(array_filter($filters, fn (string $value): bool => $value !== '')),
            'description' => 'Audit log explorer viewed',
        ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $this->query->list($filters, $perPage),
            'filters' => $filters,
            'stats' => $this->query->statistics($filters),
            'meta' => [
                'actors' => $this->query->actors(),
                'actions' => $this->query->actions(),
                'subjects' => $this->query->subjects(),
                'eventClasses' => ['security', 'content', 'people', 'system'],
                'severities' => ['critical', 'warning', 'info'],
            ],
            'can' => [
                'view' => Gate::allows('viewAny', AuditLog::class),
                'export' => Gate::allows('export', AuditLog::class),
                'archives' => Gate::allows('viewAny', AuditArchive::class),
            ],
        ]);
    }

    public function show(string $locale, AuditLog $auditLog): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $detail = $this->query->detail($auditLog);

        $this->audit->record('audit_logs.details_view', 'audit_logs', (string) $auditLog->id, null, [
            'action' => $auditLog->action,
        ], [
            'description' => 'Audit event details viewed',
        ]);

        return response()->json(['log' => $detail]);
    }

    public function export(string $locale, Request $request): StreamedResponse
    {
        Gate::authorize('export', AuditLog::class);

        $filters = $this->query->filters($request);
        $format = $request->string('format')->trim()->lower()->toString();
        $format = in_array($format, ['csv', 'jsonl'], true) ? $format : 'csv';
        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.'.$format;

        $this->audit->record('audit_logs.export', 'audit_logs', null, null, null, [
            'format' => $format,
            'filters' => array_keys(array_filter($filters, fn (string $value): bool => $value !== '')),
            'description' => 'Audit log export generated',
        ]);

        return response()->streamDownload(function () use ($filters, $format): void {
            if ($format === 'csv') {
                echo "\xEF\xBB\xBF";
                echo CsvSafeWriter::row([
                    'id', 'created_at', 'actor', 'actor_id', 'action', 'event_class',
                    'severity', 'subject_type', 'subject_id', 'request_id',
                    'before', 'after', 'context',
                ]);
            }

            $this->query->exportRows($filters, function (array $row) use ($format): void {
                if ($format === 'csv') {
                    echo CsvSafeWriter::row([
                        $row['id'], $row['created_at'], $row['actor'], $row['actor_id'],
                        $row['action'], $row['event_class'], $row['severity'],
                        $row['subject_type'], $row['subject_id'], $row['request_id'],
                        $row['before'], $row['after'], $row['context'],
                    ]);

                    return;
                }

                echo CsvSafeWriter::json($row).PHP_EOL;
            });
        }, $filename, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=UTF-8'
                : 'application/x-ndjson; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
