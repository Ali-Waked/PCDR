<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Safe, deterministic variable interpolation for editable email templates.
 * Only allowlisted `{{ variable }}` placeholders are replaced and all values
 * are normalized; no PHP evaluation, file inclusion or dynamic callbacks.
 */
final class TemplateRenderer
{
    public function render(string $template, array $variables = [], bool $html = true): string
    {
        $allowed = array_keys((array) config('email.variables', []));
        $variables = $this->normalize($variables);

        $rendered = preg_replace_callback(
            '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
            function (array $matches) use ($allowed, $variables, $html): string {
                $key = $matches[1];

                if (! in_array($key, $allowed, true) || ! array_key_exists($key, $variables)) {
                    return '';
                }

                $value = strip_tags((string) $variables[$key]);

                return $html ? e($value) : $value;
            },
            $template,
        );

        return $rendered ?? $template;
    }

    /** @return array<string, string> */
    public function availableVariables(): array
    {
        /** @var array<string, string> $variables */
        $variables = config('email.variables', []);

        return $variables;
    }

    /** @param array<string, mixed> $variables @return array<string, string> */
    private function normalize(array $variables): array
    {
        $normalized = [];

        foreach ($variables as $key => $value) {
            $normalized[$key] = is_scalar($value) ? (string) $value : '';
        }

        return $normalized;
    }
}
