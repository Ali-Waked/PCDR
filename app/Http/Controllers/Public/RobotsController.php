<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Illuminate\Http\Response;

final class RobotsController
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /ar',
            'Allow: /en',
            'Disallow: /cms',
            'Disallow: /ar/cms',
            'Disallow: /en/cms',
            'Disallow: /media/private',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
