<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Support\AuditRedactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class AuditService
{
    public function __construct(private readonly Request $request) {}

    /** @param mixed $before @param mixed $after @param array<string, mixed> $extra */
    public function record(
        string $action,
        string $subjectType,
        ?string $subjectId,
        $before = null,
        $after = null,
        array $extra = [],
    ): AuditLog {
        $context = array_merge($this->requestContext(), AuditRedactor::redact($extra));

        return AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before' => $before === null ? null : AuditRedactor::redact($before),
            'after' => $after === null ? null : AuditRedactor::redact($after),
            'context' => $context,
            'request_id' => $context['request_id'] ?? null,
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function requestContext(): array
    {
        $userAgent = $this->request->userAgent();

        return [
            'ip' => $this->request->ip(),
            'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 500),
            'route' => $this->request->route()?->getName(),
            'locale' => app()->getLocale(),
            'request_id' => $this->request->header('X-Request-ID') ?: (string) Str::ulid(),
        ];
    }
}
