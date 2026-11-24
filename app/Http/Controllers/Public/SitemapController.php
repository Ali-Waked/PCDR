<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Article;
use App\Models\Page;
use App\Models\Program;
use App\Models\Project;
use App\Models\Publication;
use App\Models\StrategicPillar;
use Illuminate\Http\Response;

final class SitemapController
{
    private const STATIC_PATHS = ['', 'about', 'strategic-areas', 'programs', 'news',
        'publications', 'transparency', 'partnerships', 'get-involved', 'contact', 'complaints'];

    public function __invoke(): Response
    {
        $entries = [];
        foreach (config('website.locales', ['ar', 'en']) as $locale) {
            foreach (self::STATIC_PATHS as $path) {
                $entries[] = ['loc' => url('/'.$locale.($path ? '/'.$path : '')), 'priority' => $path === '' ? '1.0' : '0.7'];
            }

            foreach ([
                StrategicPillar::class => 'strategic-areas',
                Program::class => 'programs',
                Article::class => 'news',
                Publication::class => 'publications',
            ] as $model => $prefix) {
                foreach ($model::query()->published()->with('translations')->get() as $record) {
                    $entries[] = [
                        'loc' => url('/'.$locale.'/'.$prefix.'/'.($record->t('slug', $locale) ?: $record->id)),
                        'priority' => '0.6',
                    ];
                }
            }

            foreach (Project::query()->published()->with('translations')->get() as $project) {
                $entries[] = [
                    'loc' => url('/'.$locale.'/projects/'.($project->t('slug', $locale) ?: $project->id)),
                    'priority' => '0.5',
                ];
            }

            foreach (Page::query()->published()->with('translations')->get() as $page) {
                $entries[] = [
                    'loc' => url('/'.$locale.'/pages/'.($page->t('slug', $locale) ?: $page->id)),
                    'priority' => '0.5',
                ];
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach ($entries as $entry) {
            $xml .= '  <url><loc>'.e($entry['loc']).'</loc><priority>'.$entry['priority'].'</priority>';
            foreach (config('website.locales', ['ar', 'en']) as $alternate) {
                $path = parse_url($entry['loc'], PHP_URL_PATH) ?: '';
                $suffix = preg_replace('#^/(ar|en)#', '', $path) ?: '';
                $xml .= '<xhtml:link rel="alternate" hreflang="'.$alternate.'" href="'.e(url('/'.$alternate.$suffix)).'"/>';
            }
            $xml .= '</url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
