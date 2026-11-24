<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Exceptions\Phase5GovernanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\InboundWebhookRequest;
use App\Http\Requests\Phase5\StoreWebhookEndpointRequest;
use App\Http\Requests\Phase5\WebhookDeliveryIndexRequest;
use App\Models\Phase5\IntegrationRegistry;
use App\Models\Phase5\WebhookDelivery;
use App\Models\User;
use App\Services\Phase5\Phase5Authorization;
use App\Services\Phase5\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly WebhookService $webhooks,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function endpoints(Request $request): Response
    {
        $user = $this->actor();
        $this->authorization->assertAllows($user, 'integrations.view');

        return Inertia::render('Phase5/Webhooks/Index', [
            'endpoints' => $this->webhooks->endpoints([
                'integration_id' => $request->query('integration_id'),
                'endpointPage' => $request->query('endpointPage'),
                'per_page' => $request->query('per_page'),
            ]),
            'deliveries' => $this->webhooks->deliveries([
                'status' => $request->query('status'),
                'event' => $request->query('event'),
                'endpoint_id' => $request->query('endpoint_id'),
                'deliveryPage' => $request->query('deliveryPage'),
                'per_page' => $request->query('per_page'),
            ]),
            'events' => array_values((array) config('phase5.integrations.webhook_events', [])),
            'gates' => $this->authorization->gates(),
        ]);
    }

    public function storeEndpoint(StoreWebhookEndpointRequest $request): RedirectResponse
    {
        try {
            $this->authorization->assertGate('webhooks_enabled', 'outbound');
            $this->webhooks->createEndpoint(
                $this->actor(),
                $request->integration(),
                $request->endpointInput(),
            );
        } catch (Phase5GovernanceException) {
            return back()->with('error', __('phase5.governance_disabled'));
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.endpoint_created'));
    }

    public function deliveries(WebhookDeliveryIndexRequest $request): JsonResponse
    {
        return response()->json($this->webhooks->deliveries($request->filters()));
    }

    public function retry(Request $request): RedirectResponse
    {
        $delivery = WebhookDelivery::query()->findOrFail($request->route('delivery'));

        try {
            $this->webhooks->retryDelivery($this->actor(), $delivery);
        } catch (Phase5GovernanceException) {
            return back()->with('error', __('phase5.governance_disabled'));
        } catch (InvalidArgumentException) {
            return back()->with('error', __('phase5.webhook_failed'));
        }

        return back()->with('success', __('phase5.webhook_retried'));
    }

    public function inbound(IntegrationRegistry $integration, InboundWebhookRequest $request): JsonResponse
    {
        try {
            $result = $this->webhooks->verifyInbound($integration, $request);
        } catch (Phase5GovernanceException) {
            return response()->json(['error' => 'webhook_disabled'], 403);
        } catch (InvalidArgumentException $exception) {
            $code = $exception->getMessage();
            $safeCodes = [
                'invalid_payload', 'invalid_headers', 'stale_timestamp', 'invalid_event',
                'invalid_signature', 'replayed_nonce',
            ];

            return response()->json(
                ['error' => in_array($code, $safeCodes, true) ? $code : 'invalid_request'],
                $code === 'replayed_nonce' ? 409 : 400,
            );
        }

        return response()->json([
            'status' => 'accepted',
            'event' => $result['event'],
            'payload_hash' => $result['payload_hash'],
        ], 202);
    }

    private function actor(): User
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403, __('phase5.no_permission'));
        }

        return $user;
    }
}
