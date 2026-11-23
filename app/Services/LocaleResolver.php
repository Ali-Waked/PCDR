<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Resolves the locale for requests that do not carry an explicit locale
 * segment. The resolution order is user preference, remembered locale,
 * Accept-Language, then the configured default.
 */
final class LocaleResolver
{
    /** @return list<string> */
    public function supported(): array
    {
        $configured = config('locales.supported', config('website.locales', ['ar', 'en']));

        $supported = array_values(array_filter(
            is_array($configured) ? $configured : [],
            static fn (mixed $locale): bool => is_string($locale) && $locale !== '',
        ));

        return $supported === [] ? ['ar', 'en'] : array_values(array_map('strtolower', $supported));
    }

    public function default(): string
    {
        $configured = strtolower((string) config('locales.default', config('website.default_locale', 'ar')));

        return $this->isValid($configured) ? $configured : 'ar';
    }

    public function isValid(?string $locale): bool
    {
        return $locale !== null && in_array($locale, $this->supported(), true);
    }

    public function normalize(?string $locale): ?string
    {
        if ($locale === null) {
            return null;
        }

        $normalized = strtolower(trim($locale));

        return $this->isValid($normalized) ? $normalized : null;
    }

    public function resolve(Request $request): string
    {
        $user = $request->user();

        if ($user !== null && isset($user->locale)) {
            $fromUser = $this->normalize((string) $user->locale);

            if ($fromUser !== null) {
                return $fromUser;
            }
        }

        foreach ($this->configuredNames('locales.cookie_names', ['pcdr_locale', 'locale']) as $name) {
            $fromCookie = $this->normalize($request->cookies->get($name));

            if ($fromCookie !== null) {
                return $fromCookie;
            }
        }

        if ($request->hasSession()) {
            foreach ($this->configuredNames('locales.session_keys', ['locale', 'pcdr_locale']) as $key) {
                $fromSession = $this->normalize($request->session()->get($key));

                if ($fromSession !== null) {
                    return $fromSession;
                }
            }
        }

        $fromHeader = $this->fromAcceptLanguage($request->headers->get('Accept-Language'));

        return $fromHeader ?? $this->default();
    }

    /** @return list<string> Preferred locale first, followed by the remaining supported locales. */
    public function candidates(Request $request): array
    {
        $preferred = $this->resolve($request);

        return array_values(array_unique(array_merge(
            [$preferred],
            array_diff($this->supported(), [$preferred]),
        )));
    }

    public function fromAcceptLanguage(?string $header): ?string
    {
        if (! is_string($header) || trim($header) === '') {
            return null;
        }

        $ranges = [];

        foreach (explode(',', $header) as $order => $entry) {
            $parts = array_map('trim', explode(';', $entry));
            $range = strtolower(str_replace('_', '-', (string) array_shift($parts)));

            if ($range === '' || $range === '*') {
                continue;
            }

            $quality = 1.0;

            foreach ($parts as $parameter) {
                if (preg_match('/^q\s*=\s*([0-9]+(?:\.[0-9]+)?)$/i', $parameter, $matches) === 1) {
                    $quality = (float) $matches[1];
                }
            }

            if ($quality <= 0.0) {
                continue;
            }

            $ranges[] = ['range' => $range, 'quality' => $quality, 'order' => $order];
        }

        usort($ranges, static function (array $left, array $right): int {
            return [$right['quality'], $left['order']] <=> [$left['quality'], $right['order']];
        });

        foreach ($ranges as $range) {
            $exact = $this->normalize((string) $range['range']);

            if ($exact !== null) {
                return $exact;
            }

            $primary = explode('-', (string) $range['range'], 2)[0];
            $fromPrimary = $this->normalize($primary);

            if ($fromPrimary !== null) {
                return $fromPrimary;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function configuredNames(string $key, array $fallback): array
    {
        $configured = config($key, $fallback);

        $names = array_values(array_filter(
            is_array($configured) ? $configured : [],
            static fn (mixed $name): bool => is_string($name) && $name !== '',
        ));

        return $names === [] ? $fallback : $names;
    }
}
