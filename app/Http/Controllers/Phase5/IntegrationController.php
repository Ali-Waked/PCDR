<?php

declare(strict_types=1);

namespace App\Http\Controllers\Phase5;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phase5\StoreIntegrationRequest;
use App\Http\Requests\Phase5\UpdateIntegrationRequest;
use App\Models\Phase5\IntegrationRegistry;
use App\Models\User;
use App\Services\Phase5\Phase5Authorization;
use App\Services\Phase5\WebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

final class IntegrationController extends Controller
{
    public function __construct(
        private readonly WebhookService $integrations,
        private readonly Phase5Authorization $authorization,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Phase5/Integrations/Index', [
            'integrations' => $this->integrations->integrations([
                'category' => $request->query('category'),
                'page' => $request->query('page'),
                'per_page' => $request->query('per_page'),
            ]),
            'categories' => array_values((array) config('phase5.integrations.categories', [])),
            'gates' => $this->authorization->gates(),
        ]);
    }

    public function store(StoreIntegrationRequest $request): RedirectResponse
    {
        try {
            $this->integrations->createIntegration($this->actor(), $request->integrationInput());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.integration_created'));
    }

    public function update(UpdateIntegrationRequest $request, IntegrationRegistry $integration): RedirectResponse
    {
        try {
            $this->integrations->updateIntegration($this->actor(), $integration, $request->integrationInput());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('phase5.integration_updated'));
    }

    public function toggle(Request $request, IntegrationRegistry $integration): RedirectResponse
    {
        $this->integrations->toggleIntegration($this->actor(), $integration, $request->boolean('enabled'));

        return back()->with('success', __('phase5.integration_updated'));
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
