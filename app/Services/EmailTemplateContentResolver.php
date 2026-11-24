<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailTemplate;

/**
 * Resolves a localized field from a database template, falling back to the
 * shipped default template, then renders allowlisted variables. This is the
 * single source of truth shared by mail preparation and read-only previews.
 */
final class EmailTemplateContentResolver
{
    public function __construct(private TemplateRenderer $renderer) {}

    /**
     * @param  array<string, string>  $variables
     */
    public function field(
        ?EmailTemplate $template,
        string $key,
        string $field,
        string $locale,
        array $variables,
    ): string {
        $value = $template?->translationFor($locale)?->{$field} ?? '';
        $defaults = (array) config('email.default_templates.'.$key, []);

        if ($value === '') {
            $value = $defaults[$locale][$field] ?? $defaults['en'][$field] ?? '';
        }

        return $this->render($value, $variables);
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function render(string $value, array $variables): string
    {
        return $this->renderer->render($value, $variables, false);
    }
}
