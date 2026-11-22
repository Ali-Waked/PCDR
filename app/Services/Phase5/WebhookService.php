<?php

declare(strict_types=1);

namespace App\Services\Phase5;

use App\Events\Phase5Event;
use App\Exceptions\Phase5GovernanceException;
use App\Jobs\Phase5\DeliverWebhookJob;
use App\Models\Phase5\IncomingWebhookEvent;
use App\Models\Phase5\IntegrationRegistry;
use App\Models\Phase5\WebhookDelivery;
use App\Models\Phase5\WebhookEndpoint;
use App\Models\User;
use App\Services\AuditService;
use App\Support\Phase5Support;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class WebhookService
{
    /** @var list<string> */
    private const BLOCKED_HOSTS = [
        'localhost', 'metadata', 'metadata.google.internal', 'metadata.goog',
        'instance-data', 'instance-data.ec2.internal', '100.100.100.200',
    ];

    public function __construct(
        private readonly Phase5Authorization $authorization,
        private readonly AuditService $audit,
    ) {}

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function integrations(array $filters = []): LengthAwarePaginator
    {
        $query = IntegrationRegistry::query();

        if (! empty($filters['category'])) {
            $query->where('category', (string) $filters['category']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        $paginator = $query
            ->orderBy('key')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($filters['page'] ?? 1)));

        $paginator->setCollection($paginator->getCollection()->map(
            fn (IntegrationRegistry $integration): array => $this->presentIntegration($integration),
        ));

        return $paginator;
    }

    /** @param array<string, mixed> $data */
    public function createIntegration(User $actor, array $data): IntegrationRegistry
    {
        $record = DB::transaction(function () use ($data): IntegrationRegistry {
            $integration = IntegrationRegistry::query()->create([
                'key' => (string) $data['key'],
                'name' => Phase5Support::clean((string) $data['name'], 255),
                'provider' => Phase5Support::clean((string) $data['provider'], 96),
                'category' => (string) $data['category'],
                'metadata' => $this->safeMetadata($data['metadata'] ?? []),
                'enabled' => false,
                'health_status' => 'unknown',
            ]);

            $this->audit->record(
                'phase5.integration.created',
                'integration_registry',
                (string) $integration->id,
                null,
                ['key' => $integration->key, 'category' => $integration->category],
            );

            return $integration;
        });

        return $record;
    }

    /** @param array<string, mixed> $data */
    public function updateIntegration(User $actor, IntegrationRegistry $integration, array $data): IntegrationRegistry
    {
        DB::transaction(function () use ($integration, $data): void {
            $integration->update([
                'name' => Phase5Support::clean((string) $data['name'], 255),
                'provider' => Phase5Support::clean((string) $data['provider'], 96),
                'category' => (string) $data['category'],
                'metadata' => $this->safeMetadata($data['metadata'] ?? []),
            ]);

            $this->audit->record(
                'phase5.integration.updated',
                'integration_registry',
                (string) $integration->id,
                null,
                ['key' => $integration->key],
            );
        });

        return $integration->refresh();
    }

    public function toggleIntegration(User $actor, IntegrationRegistry $integration, bool $enabled): IntegrationRegistry
    {
        DB::transaction(function () use ($integration, $enabled): void {
            $integration->update(['enabled' => $enabled]);

            $this->audit->record(
                'phase5.integration.toggled',
                'integration_registry',
                (string) $integration->id,
                ['enabled' => ! $enabled],
                ['enabled' => $enabled],
            );
        });

        return $integration->refresh();
    }

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function endpoints(array $filters = []): LengthAwarePaginator
    {
        $query = WebhookEndpoint::query();

        if (! empty($filters['integration_id'])) {
            $query->where('integration_id', (int) $filters['integration_id']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        $paginator = $query
            ->with('integration:id,key,name,enabled')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($filters['page'] ?? 1)));

        $paginator->setCollection($paginator->getCollection()->map(
            fn (WebhookEndpoint $endpoint): array => $this->presentEndpoint($endpoint),
        ));

        return $paginator;
    }

    /** @param array<string, mixed> $data */
    public function createEndpoint(User $actor, IntegrationRegistry $integration, array $data): WebhookEndpoint
    {
        $this->authorization->assertGate('webhooks_enabled', 'outbound');

        $url = self::validateUrl((string) $data['url']);
        $secret = (string) $data['secret'];
        $events = array_values(array_unique(array_map('strval', $data['events'])));
        $allowedEvents = (array) config('phase5.integrations.webhook_events', []);

        foreach ($events as $event) {
            if (! in_array($event, $allowedEvents, true)) {
                throw new InvalidArgumentException('Unsupported webhook event.');
            }
        }

        $active = (bool) ($data['active'] ?? false);

        $endpoint = DB::transaction(function () use ($actor, $integration, $data, $url, $secret, $events, $active): WebhookEndpoint {
            $record = WebhookEndpoint::query()->create([
                'integration_id' => $integration->id,
                'name' => Phase5Support::clean((string) $data['name'], 255),
                'url' => $url,
                'events' => $events,
                'secret_encrypted' => $secret,
                'active' => $active,
                'created_by' => $actor->id,
            ]);

            $this->audit->record(
                'phase5.webhook.endpoint_created',
                'webhook_endpoint',
                (string) $record->id,
                null,
                ['integration_id' => $integration->id, 'event_count' => count($events), 'active' => $active],
            );

            return $record;
        });

        return $endpoint;
    }

    /** @param array<string, mixed> $filters @return LengthAwarePaginator<int, mixed> */
    public function deliveries(array $filters = []): LengthAwarePaginator
    {
        $query = WebhookDelivery::query();

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['event'])) {
            $query->where('event', (string) $filters['event']);
        }

        if (! empty($filters['endpoint_id'])) {
            $query->where('endpoint_id', (int) $filters['endpoint_id']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 50);

        $paginator = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', max(1, (int) ($filters['page'] ?? 1)));

        $paginator->setCollection($paginator->getCollection()->map(
            fn (WebhookDelivery $delivery): array => $this->presentDelivery($delivery),
        ));

        return $paginator;
    }

    public function fanout(Phase5Event $event): int
    {
        if (! $this->authorization->gateEnabled('webhooks_enabled')) {
            return 0;
        }

        $allowed = (array) config('phase5.integrations.webhook_events', []);
        if (! in_array($event->name, $allowed, true)) {
            return 0;
        }

        $endpoints = WebhookEndpoint::query()
            ->where('active', true)
            ->whereHas('integration', fn ($query) => $query->where('enabled', true))
            ->orderBy('id')
            ->get();

        $payload = $this->safePayload($event);
        $hash = $this->hashPayload($payload);
        $created = [];

        foreach ($endpoints as $endpoint) {
            $events = is_array($endpoint->events) ? $endpoint->events : [];
            if (! in_array($event->name, $events, true)) {
                continue;
            }

            $duplicate = WebhookDelivery::query()
                ->where('endpoint_id', $endpoint->id)
                ->where('event', $event->name)
                ->where('payload_hash', $hash)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();
            if ($duplicate) {
                continue;
            }

            $delivery = DB::transaction(fn (): WebhookDelivery => WebhookDelivery::query()->create([
                'endpoint_id' => $endpoint->id,
                'event' => $event->name,
                'payload_version' => 1,
                'payload_hash' => $hash,
                'status' => WebhookDelivery::STATUS_PENDING,
                'attempts' => 0,
                'response_code' => null,
                'error_code' => null,
                'next_attempt_at' => null,
                'created_at' => now(),
            ]));

            $created[] = ['id' => $delivery->id, 'payload' => $payload];
        }

        foreach ($created as $item) {
            DeliverWebhookJob::dispatch($item['id'], $item['payload'])->afterCommit();
        }

        return count($created);
    }

    /** @return array<string, mixed> */
    public function safePayload(Phase5Event $event): array
    {
        $payload = [];
        foreach ($event->payload as $key => $value) {
            if ($key === 'user_ids' || $key === 'secret' || $key === 'token') {
                continue;
            }
            if (is_scalar($value) || (is_array($value) && $this->isScalarArray($value))) {
                $payload[(string) $key] = $value;
            }
        }

        return [
            'event' => $event->name,
            'payload_version' => 1,
            'subject_type' => $event->subjectType,
            'subject_id' => $event->subjectId,
            'payload' => $payload,
        ];
    }

    /** @param array<string, mixed> $payload */
    public function hashPayload(array $payload): string
    {
        return hash('sha256', Phase5Support::encode($payload));
    }

    public function signature(string $timestamp, string $nonce, string $body, string $secret): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$nonce.'.'.$body, $secret);
    }

    public function retryDelivery(User $actor, WebhookDelivery $delivery): void
    {
        if (! $this->authorization->gateEnabled('webhooks_enabled')) {
            throw new Phase5GovernanceException('webhooks_enabled', 'outbound');
        }

        $max = (int) config('phase5.integrations.max_retries', 3);

        if (! in_array($delivery->status, [
            WebhookDelivery::STATUS_FAILED,
            WebhookDelivery::STATUS_DEAD_LETTER,
            WebhookDelivery::STATUS_DISABLED,
        ], true) || (int) $delivery->attempts >= $max + 1) {
            throw new InvalidArgumentException('This delivery cannot be retried.');
        }

        DB::transaction(function () use ($delivery): void {
            $before = $delivery->status;
            $delivery->update([
                'status' => WebhookDelivery::STATUS_PENDING,
                'next_attempt_at' => null,
                'error_code' => null,
                'response_code' => null,
            ]);

            $this->audit->record(
                'phase5.webhook.retried',
                'webhook_delivery',
                (string) $delivery->id,
                ['status' => $before],
                ['status' => WebhookDelivery::STATUS_PENDING],
            );
        });

        DeliverWebhookJob::dispatch($delivery->id, [
            'event' => $delivery->event,
            'payload_version' => $delivery->payload_version,
            'payload_hash' => $delivery->payload_hash,
        ])->afterCommit();
    }

    /**
     * Verify a signed inbound request and persist only safe metadata.
     *
     * @return array{event: string, nonce: string, payload_hash: string}
     */
    public function verifyInbound(IntegrationRegistry $integration, Request $request): array
    {
        if (! $this->authorization->gateEnabled('webhooks_enabled')) {
            throw new Phase5GovernanceException('webhooks_enabled', 'inbound');
        }

        $raw = (string) $request->getContent();
        $maxBytes = (int) config('phase5.integrations.max_payload_bytes', 262144);

        if ($raw === '' || strlen($raw) > $maxBytes) {
            throw new InvalidArgumentException('invalid_payload');
        }

        $timestamp = (string) $request->header('X-Phase5-Timestamp', '');
        $nonce = (string) $request->header('X-Phase5-Nonce', '');
        $signature = (string) $request->header('X-Phase5-Signature', '');
        $event = (string) $request->header('X-Phase5-Event', '');

        if (! ctype_digit($timestamp) || $nonce === '' || strlen($nonce) > 128 || $signature === '') {
            throw new InvalidArgumentException('invalid_headers');
        }

        $tolerance = (int) config('phase5.integrations.inbound_timestamp_tolerance_seconds', 300);
        if (abs(time() - (int) $timestamp) > $tolerance) {
            throw new InvalidArgumentException('stale_timestamp');
        }

        $allowed = (array) config('phase5.integrations.webhook_events', []);
        if (! in_array($event, $allowed, true)) {
            throw new InvalidArgumentException('invalid_event');
        }

        $verified = false;
        $endpoints = WebhookEndpoint::query()
            ->where('integration_id', $integration->id)
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'secret_encrypted']);

        foreach ($endpoints as $endpoint) {
            try {
                $secret = (string) $endpoint->secret_encrypted;
            } catch (Throwable) {
                continue;
            }
            if ($secret === '') {
                continue;
            }
            $expected = $this->signature($timestamp, $nonce, $raw, $secret);
            if (hash_equals($expected, $signature)) {
                $verified = true;

                break;
            }
        }

        if (! $verified) {
            throw new InvalidArgumentException('invalid_signature');
        }

        $hash = hash('sha256', $raw);
        $stored = null;

        try {
            $stored = DB::transaction(fn (): IncomingWebhookEvent => IncomingWebhookEvent::query()->create([
                'integration_id' => $integration->id,
                'event' => $event,
                'nonce' => $nonce,
                'payload_hash' => $hash,
                'processed' => true,
                'received_at' => now(),
                'created_at' => now(),
            ]));
        } catch (QueryException) {
            throw new InvalidArgumentException('replayed_nonce');
        }

        $this->audit->record(
            'phase5.webhook.inbound_accepted',
            'integration_registry',
            (string) $integration->id,
            null,
            ['event' => $event, 'payload_hash' => $hash],
        );

        return [
            'event' => $event,
            'nonce' => $nonce,
            'payload_hash' => $stored->payload_hash,
        ];
    }

    /** Reject non-HTTPS, userinfo-bearing and private/link-local/metadata literal destinations. */
    public static function validateUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('invalid_url');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, (array) config('phase5.integrations.allowed_schemes', ['https']), true)) {
            throw new InvalidArgumentException('invalid_url_scheme');
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) && (int) $parts['port'] !== 443) {
            throw new InvalidArgumentException('invalid_url');
        }

        $host = strtolower(rtrim((string) $parts['host'], '.'));
        if ($host === '' || in_array($host, self::BLOCKED_HOSTS, true)) {
            throw new InvalidArgumentException('blocked_host');
        }

        foreach (['.localhost', '.local', '.internal', '.metadata'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                throw new InvalidArgumentException('blocked_host');
            }
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $allowedIp = filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );

            if ($allowedIp === false || str_starts_with($host, '169.254.')) {
                throw new InvalidArgumentException('blocked_host');
            }
        } elseif (preg_match('/^(0x[0-9a-f]+|[0-9]+)(\.[0-9]+){1,3}$/i', $host)) {
            throw new InvalidArgumentException('blocked_host');
        }

        return $url;
    }

    /** @return array<string, mixed> */
    private function presentIntegration(IntegrationRegistry $integration): array
    {
        return [
            'id' => $integration->id,
            'key' => $integration->key,
            'name' => $integration->name,
            'provider' => $integration->provider,
            'category' => $integration->category,
            'enabled' => $integration->enabled,
            'health_status' => $integration->health_status,
            'metadata' => $this->safeMetadata(is_array($integration->metadata) ? $integration->metadata : []),
        ];
    }

    /** @return array<string, mixed> */
    private function presentEndpoint(WebhookEndpoint $endpoint): array
    {
        $events = is_array($endpoint->events) ? $endpoint->events : [];

        return [
            'id' => $endpoint->id,
            'integration_id' => $endpoint->integration_id,
            'integration' => $endpoint->relationLoaded('integration') && $endpoint->integration !== null
                ? ['key' => $endpoint->integration->key, 'enabled' => $endpoint->integration->enabled]
                : null,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'events' => array_values(array_map('strval', $events)),
            'active' => $endpoint->active,
            'last_success_at' => $endpoint->last_success_at?->toIso8601String(),
            'last_failure_at' => $endpoint->last_failure_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function presentDelivery(WebhookDelivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'endpoint_id' => $delivery->endpoint_id,
            'event' => $delivery->event,
            'payload_version' => $delivery->payload_version,
            'payload_hash' => $delivery->payload_hash,
            'status' => $delivery->status,
            'attempts' => $delivery->attempts,
            'response_code' => $delivery->response_code,
            'error_code' => $delivery->error_code,
            'created_at' => $delivery->created_at?->toIso8601String(),
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
        ];
    }

    /** @param mixed $metadata @return array<string, string|int|float|bool|null> */
    private function safeMetadata(mixed $metadata): array
    {
        if (! is_array($metadata)) {
            return [];
        }

        $result = [];
        foreach ($metadata as $key => $value) {
            $name = strtolower(str_replace(['-', ' '], '_', (string) $key));
            if (Str::contains($name, ['secret', 'token', 'password', 'credential', 'api_key', 'apikey', 'authorization'])) {
                continue;
            }
            if (! is_scalar($value) && $value !== null) {
                continue;
            }
            $result[(string) $key] = is_string($value) ? Phase5Support::clean($value, 200) : $value;
        }

        return $result;
    }

    /** @param mixed $value */
    private function isScalarArray($value): bool
    {
        return is_array($value) && count($value) <= 50
            && collect($value)->every(static fn (mixed $item): bool => is_scalar($item) || $item === null);
    }
}
