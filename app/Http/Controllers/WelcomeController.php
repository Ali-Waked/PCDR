<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Inertia\Inertia;
use Inertia\Response;

final class WelcomeController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'siteName' => $this->settings->get('general.site_name'),
            'tagline' => $this->settings->get('general.tagline'),
        ]);
    }
}
