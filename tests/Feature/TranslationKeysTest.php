<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranslationKeysTest extends TestCase
{
    #[Test]
    public function vue_and_blade_translation_keys_exist_in_lang_files(): void
    {
        $root = base_path();
        $locales = ['ar', 'en'];
        $langs = [];
        foreach ($locales as $locale) {
            $dir = $root."/lang/{$locale}";
            $langs[$locale] = [];
            if (! is_dir($dir)) {
                continue;
            }
            foreach (glob($dir.'/*.php') as $file) {
                $name = basename($file, '.php');
                $langs[$locale][$name] = require $file;
            }
        }

        $files = $this->scanFiles($root);
        $missing = [];
        $seen = [];

        foreach ($files as $file) {
            $src = file_get_contents($file);
            $rel = ltrim(str_replace($root, '', $file), '/');
            $usesLocale = str_contains($src, 'useLocale(') || str_contains($src, 'useLocale');
            $usesI18n = str_contains($src, 'useI18n(') || str_contains($src, 'useI18n');

            $patterns = [
                ['kind' => 't', 're' => '/\\bt\\(\\s*[\'"]([^\'"`$}{]+)[\'"]/'],
                ['kind' => 'php', 're' => '/__\\(\\s*[\'"]([^\'"`$}{]+)[\'"]/'],
                ['kind' => 'php', 're' => '/\\btrans\\(\\s*[\'"]([^\'"`$}{]+)[\'"]/'],
                ['kind' => 'php', 're' => '/@lang\\(\\s*[\'"]([^\'"`$}{]+)[\'"]/'],
            ];

            foreach ($patterns as $p) {
                if (! preg_match_all($p['re'], $src, $m, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                foreach ($m[1] as $match) {
                    [$key, $offset] = $match;
                    $line = substr_count(substr($src, 0, $offset), "\n") + 1;
                    $resolved = $p['kind'] === 't'
                        ? $this->resolveT($key, $usesLocale, $usesI18n)
                        : $this->resolvePhp($key);

                    foreach ($resolved as $r) {
                        $id = implode('|', [$p['kind'], $key, $r['file'], $r['key'], $rel]);
                        if (isset($seen[$id])) {
                            continue;
                        }
                        $seen[$id] = true;

                        foreach ($locales as $locale) {
                            $ok = $r['anyOf']
                                ? $this->hasAnyOf($langs[$locale], $r)
                                : $this->hasKey($langs[$locale], $r['file'], $r['key']);
                            if (! $ok) {
                                $missing[] = "[{$locale}] {$rel}:{$line} {$p['kind']}({$key}) -> {$r['file']}.{$r['key']}";
                            }
                        }
                    }
                }
            }
        }

        $this->assertSame([], $missing, "Missing translation keys:\n".implode("\n", array_slice($missing, 0, 200)));
    }

    private function scanFiles(string $root): array
    {
        $dirs = [$root.'/resources/js', $root.'/resources/views', $root.'/app', $root.'/routes'];
        $out = [];
        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($it as $f) {
                if ($f->isDir()) {
                    continue;
                }
                $path = $f->getPathname();
                if (str_contains($path, '/node_modules/') || str_contains($path, '/vendor/')) {
                    continue;
                }
                if (preg_match('/\.(vue|js|ts|php)$/', $path)) {
                    $out[] = $path;
                }
            }
        }

        return $out;
    }

    private function resolveT(string $key, bool $usesLocale, bool $usesI18n): array
    {
        if (str_starts_with($key, 'ui.')) {
            $k = substr($key, 3);
            if ($usesI18n && ! $usesLocale) {
                return [['file' => 'website', 'key' => "ui.{$k}", 'anyOf' => false]];
            }
            if ($usesLocale && ! $usesI18n) {
                return [['file' => 'ui', 'key' => $k, 'anyOf' => false]];
            }

            return [['file' => 'website', 'key' => "ui.{$k}", 'anyOf' => true, 'alt' => ['file' => 'ui', 'key' => $k]]];
        }
        if (str_starts_with($key, 'nav.')) {
            return [['file' => 'website', 'key' => 'navigation.'.substr($key, 4), 'anyOf' => false]];
        }
        if (str_contains($key, '.')) {
            return [];
        }
        if ($usesLocale && ! $usesI18n) {
            return [['file' => 'ui', 'key' => $key, 'anyOf' => false]];
        }
        if ($usesI18n && ! $usesLocale) {
            return [['file' => 'website', 'key' => "ui.{$key}", 'anyOf' => false]];
        }

        return [['file' => 'website', 'key' => "ui.{$key}", 'anyOf' => true, 'alt' => ['file' => 'ui', 'key' => $key]]];
    }

    private function resolvePhp(string $key): array
    {
        if (! str_contains($key, '.')) {
            return [];
        }
        [$file, $rest] = explode('.', $key, 2);

        return [['file' => $file, 'key' => $rest, 'anyOf' => false]];
    }

    private function hasAnyOf(array $lang, array $r): bool
    {
        if ($this->hasKey($lang, $r['file'], $r['key'])) {
            return true;
        }
        $alt = $r['alt'] ?? null;

        return $alt ? $this->hasKey($lang, $alt['file'], $alt['key']) : false;
    }

    private function hasKey(array $lang, string $file, string $key): bool
    {
        if (! array_key_exists($file, $lang)) {
            return false;
        }
        $cur = $lang[$file];
        foreach (explode('.', $key) as $part) {
            if (! is_array($cur) || ! array_key_exists($part, $cur)) {
                return false;
            }
            $cur = $cur[$part];
        }

        return true;
    }
}
