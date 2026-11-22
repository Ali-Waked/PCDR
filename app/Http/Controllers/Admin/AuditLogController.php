<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditLogController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(string $locale, Request $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $this->audit->record('audit_logs.view', 'audit_logs', null, null, null, extra: [
            'filters' => array_keys($request->only(['action', 'subject'])),
        ]);

        $action = $request->string('action')->trim()->toString();
        $subject = $request->string('subject')->trim()->toString();

        $logs = AuditLog::query()
            ->with('actor:id,name')
            ->when($action !== '', fn ($query) => $query->where('action', 'like', "%{$action}%"))
            ->when($subject !== '', fn ($query) => $query->where('subject_type', 'like', "%{$subject}%"))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => ['action' => $action, 'subject' => $subject],
        ]);
    }

    public function export(string $locale, Request $request): StreamedResponse
    {
        Gate::authorize('export', AuditLog::class);

        $this->audit->record('audit_logs.export', 'audit_logs', null, null, null, extra: [
            'filters' => array_keys($request->only(['action', 'subject'])),
        ]);

        $logs = AuditLog::with('actor:id,name')->orderByDesc('created_at')->limit(10000)->get();

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'created_at', 'actor', 'action', 'subject_type', 'subject_id', 'request_id']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toISOString(),
                    $log->actor?->name,
                    $log->action,
                    $log->subject_type,
                    $log->subject_id,
                    $log->request_id,
                ]);
            }

            fclose($handle);
        }, 'pcdr-audit-logs.csv', ['Content-Type' => 'text/csv']);
    }
}
