<?php

declare(strict_types=1);

namespace App\Jobs\Phase5;

use App\Models\Phase5\WebhookDelivery;
use App\Models\Phase5\WebhookEndpoint;
use App\Services\Phase5\Phase5Authorization;
use App\Services\Phase5\WebhookService;
use App\Support\Phase5Support;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

#[Tries(4)]
#[Backoff([5, 30, 120])]
final class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 10;

    public bool $failOnTimeout = true;

    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly int $deliveryId,
        public readonly array $payload,
    ) {}

    public function handle(WebhookService $webhooks, Phase5Authorization $authorization): void
    {
        $delivery = WebhookDelivery::query()->find($this->deliveryId);
        if ($delivery === null || in_array($delivery->status, [
            WebhookDelivery::STATUS_DELIVERED,
            WebhookDelivery::STATUS_DEAD_LETTER,
        ], true)) {
            return;
        }

        $endpoint = WebhookEndpoint::query()->with('integration:id,enabled')->find($delivery->endpoint_id);
        $attempt = (int) $delivery->attempts + 1;
        $maxAttempts = (int) config('phase5.integrations.max_retries', 3) + 1;
        $final = $attempt >= $maxAttempts;

        if (! $authorization->gateEnabled('webhooks_enabled')
            || $endpoint === null
            || ! $endpoint->active
            || $endpoint->integration === null
            || ! $endpoint->integration->enabled) {
            $delivery->update([
                'status' => WebhookDelivery::STATUS_DISABLED,
                'attempts' => $attempt,
                'error_code' => null,
                'next_attempt_at' => null,
            ]);

            return;
        }

        if (isset($this->payload['payload'])
            && $webhooks->hashPayload($this->payload) !== $delivery->payload_hash) {
            $this->fail($delivery, $endpoint, 'ERR_PAYLOAD_MISMATCH', null, $attempt, $final);

            return;
        }

        try {
            WebhookService::validateUrl($endpoint->url);
            $secret = (string) $endpoint->secret_encrypted;
            if ($secret === '') {
                throw new InvalidArgumentException('missing_secret');
            }

            $body = $this->body($delivery);
            $timestamp = (string) time();
            $nonce = (string) Str::ulid();
            $signature = $webhooks->signature($timestamp, $nonce, $body, $secret);

            $delivery->update([
                'status' => WebhookDelivery::STATUS_SENDING,
                'attempts' => $attempt,
                'next_attempt_at' => null,
            ]);

            $response = Http::connectTimeout((int) config('phase5.integrations.connect_timeout_seconds', 3))
                ->timeout((int) config('phase5.integrations.timeout_seconds', 8))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Phase5-Event' => $delivery->event,
                    'X-Phase5-Timestamp' => $timestamp,
                    'X-Phase5-Nonce' => $nonce,
                    'X-Phase5-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($endpoint->url);

            if (! $response->successful()) {
                $this->fail($delivery, $endpoint, 'HTTP_'.$response->status(), $response->status(), $attempt, $final);

                return;
            }

            $delivery->update([
                'status' => WebhookDelivery::STATUS_DELIVERED,
                'attempts' => $attempt,
                'response_code' => $response->status(),
                'error_code' => null,
                'next_attempt_at' => null,
                'delivered_at' => now(),
            ]);
            $endpoint->update(['last_success_at' => now()]);
            $endpoint->integration->update([
                'health_status' => 'healthy',
                'last_success_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $code = $exception instanceof InvalidArgumentException
                ? Phase5Support::errorCode($exception)
                : Phase5Support::errorCode($exception);
            Log::warning('phase5.webhook.delivery_failed', [
                'delivery_id' => $delivery->id,
                'error_code' => $code,
            ]);
            $this->fail($delivery, $endpoint, $code, null, $attempt, $final);
        }
    }

    private function body(WebhookDelivery $delivery): string
    {
        $payload = isset($this->payload['payload']) && is_array($this->payload['payload'])
            ? $this->payload
            : [
                'event' => $delivery->event,
                'payload_version' => $delivery->payload_version,
                'payload_hash' => $delivery->payload_hash,
            ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function fail(
        WebhookDelivery $delivery,
        WebhookEndpoint $endpoint,
        string $errorCode,
        ?int $responseCode,
        int $attempt,
        bool $final,
    ): void {
        $delay = min(300, 5 * (2 ** max(0, $attempt - 1)));

        $delivery->update([
            'status' => $final ? WebhookDelivery::STATUS_DEAD_LETTER : WebhookDelivery::STATUS_FAILED,
            'attempts' => $attempt,
            'response_code' => $responseCode,
            'error_code' => Phase5Support::clean($errorCode, 96),
            'next_attempt_at' => $final ? null : now()->addSeconds($delay),
        ]);
        $endpoint->update(['last_failure_at' => now()]);
        $endpoint->integration?->update([
            'health_status' => $final ? 'unhealthy' : 'degraded',
            'last_failure_at' => now(),
        ]);

        if (! $final && $this->job !== null && config('queue.default') !== 'sync') {
            $this->release($delay);
        }
    }
}
