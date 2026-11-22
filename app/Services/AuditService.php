<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class AuditService
{
    private const REDACTED = '[REDACTED]';

    /** @var list<string> */
    private const SENSITIVE_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'session',
        'api_key',
        'apikey',
        'credential',
    ];

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
        $context = array_merge($this->requestContext(), $this->redact($extra));

        return AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before' => $before === null ? null : $this->redact($before),
            'after' => $after === null ? null : $this->redact($after),
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

    /** @param mixed $value @return mixed */
    private function redact($value)
    {
        if (! is_array($value)) {
            return is_string($value) ? Str::limit($value, 2000) : $value;
        }

        $clean = [];

        foreach ($value as $key => $item) {
            if ($this->isSensitiveKey((string) $key)) {
                $clean[$key] = self::REDACTED;

                continue;
            }

            $clean[$key] = $this->redact($item);
        }

        return $clean;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = Str::lower(str_replace(['-', ' '], '_', $key));

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
