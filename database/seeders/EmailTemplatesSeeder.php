<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

final class EmailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;

        foreach (config('email.default_templates', []) as $key => $locales) {
            $template = EmailTemplate::query()->firstOrCreate(
                ['key' => $key],
                ['is_active' => true, 'sort_order' => $order],
            );

            foreach ($locales as $locale => $values) {
                $template->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $values['name'],
                        'description' => $values['description'],
                        'subject' => $values['subject'],
                        'body' => $values['body'],
                    ],
                );
            }

            $order++;
        }
    }
}
