<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Server-side allowlist sanitiser for the structured content editor.
 *
 * Only the output of this class may be rendered with v-html. Everything else
 * is treated as plain text by the public site.
 */
final class RichText
{
    /** @var list<string> */
    private const DANGEROUS_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template',
        'form', 'input', 'button', 'select', 'textarea', 'meta', 'link', 'base',
        'applet', 'frame', 'frameset', 'audio', 'video', 'source', 'track',
    ];

    /** @var list<string> */
    private const SAFE_TARGETS = ['_blank', '_self'];

    /** @var list<string> */
    private const SAFE_REL_TOKENS = ['noopener', 'noreferrer', 'nofollow'];

    /** @var list<string> */
    private const SAFE_ALIGNMENTS = ['left', 'right', 'center', 'justify', 'start', 'end'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        /** @var list<string> $allowed */
        $allowed = self::stringList(config('cms.editor.allowed_tags', []));

        /** @var list<string> $allowedAttributes */
        $allowedAttributes = self::stringList(config('cms.editor.allowed_attributes', []));

        /** @var list<string> $removedTags */
        $removedTags = array_values(array_unique(array_merge(
            self::DANGEROUS_TAGS,
            self::stringList(config('cms.editor.removed_tags', [])),
        )));

        $allowed = array_values(array_diff($allowed, $removedTags));

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"?><div id="pcdr-root">'.$html.'</div>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = null;
        if ($loaded) {
            foreach ($document->getElementsByTagName('div') as $candidate) {
                if ($candidate->getAttribute('id') === 'pcdr-root') {
                    $root = $candidate;

                    break;
                }
            }
        }

        if (! $root instanceof \DOMElement) {
            return self::stripFallback($html, $allowed, $removedTags);
        }

        self::prune($root, $allowed, $allowedAttributes, $removedTags);

        $output = '';
        foreach ($root->childNodes as $node) {
            $output .= $document->saveHTML($node);
        }

        return trim($output);
    }

    /**
     * Sanitised HTML that is safe to pass to v-html on public pages.
     */
    public static function render(?string $html): string
    {
        return self::clean($html);
    }

    public static function plainText(?string $html): string
    {
        return trim(html_entity_decode(strip_tags(self::clean($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * @param  list<string>  $allowed
     * @param  list<string>  $allowedAttributes
     * @param  list<string>  $removedTags
     */
    private static function prune(\DOMNode $node, array $allowed, array $allowedAttributes, array $removedTags): void
    {
        /** @var list<\DOMNode> $children */
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, $removedTags, true)) {
                    self::removeNode($child);

                    continue;
                }

                if (! in_array($tag, $allowed, true)) {
                    self::prune($child, $allowed, $allowedAttributes, $removedTags);
                    self::unwrap($child);

                    continue;
                }

                self::sanitizeAttributes($child, $allowedAttributes);
            } elseif ($child instanceof \DOMComment
                || $child instanceof \DOMProcessingInstruction
                || $child instanceof \DOMCdataSection
            ) {
                self::removeNode($child);

                continue;
            }

            self::prune($child, $allowed, $allowedAttributes, $removedTags);
        }
    }

    /** @param  list<string>  $allowedAttributes */
    private static function sanitizeAttributes(\DOMElement $element, array $allowedAttributes): void
    {
        $tag = strtolower($element->tagName);

        /** @var list<array{0: string, 1: string}> $attributes */
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            $attributes[] = [$attribute->name, $attribute->value];
        }

        foreach ($attributes as [$attributeName, $value]) {
            $name = strtolower($attributeName);

            if (! in_array($name, $allowedAttributes, true) || str_starts_with($name, 'on')) {
                $element->removeAttribute($attributeName);

                continue;
            }

            if ($name === 'href' && $tag === 'a') {
                $safe = self::safeUrl($value);

                if ($safe === null) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('href', $safe);
                }

                continue;
            }

            if ($name === 'src' && $tag === 'img') {
                $safe = self::safeUrl($value, true);

                if ($safe === null) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('src', $safe);
                }

                continue;
            }

            if ($name === 'target' && $tag === 'a') {
                $target = strtolower(self::decodeValue($value));

                if (! in_array($target, self::SAFE_TARGETS, true)) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('target', $target);

                    if ($target === '_blank') {
                        $element->setAttribute('rel', 'noopener noreferrer');
                    }
                }

                continue;
            }

            if ($name === 'rel' && $tag === 'a') {
                $tokens = preg_split('/\s+/', self::decodeValue($value)) ?: [];
                $safeTokens = array_values(array_filter(
                    array_map(fn (string $token): string => strtolower($token), $tokens),
                    fn (string $token): bool => in_array($token, self::SAFE_REL_TOKENS, true),
                ));

                if ($element->getAttribute('target') === '_blank') {
                    $safeTokens = array_values(array_unique(array_merge(
                        ['noopener', 'noreferrer'],
                        $safeTokens,
                    )));
                }

                if ($safeTokens === []) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('rel', implode(' ', $safeTokens));
                }

                continue;
            }

            if ($name === 'style') {
                $style = self::safeStyle($value);

                if ($style === '') {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('style', $style);
                }

                continue;
            }

            if (in_array($name, ['width', 'height'], true) && $tag === 'img') {
                $dimension = filter_var(self::decodeValue($value), FILTER_VALIDATE_INT);

                if (! is_int($dimension) || $dimension < 1 || $dimension > 4096) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute($name, (string) $dimension);
                }

                continue;
            }

            if ($name === 'class' && $tag !== 'img') {
                $tokens = preg_split('/\s+/', self::decodeValue($value)) ?: [];
                $safeTokens = array_values(array_filter(
                    $tokens,
                    static fn (string $token): bool => preg_match('/^[A-Za-z0-9_-]{1,40}$/', $token) === 1,
                ));

                if ($safeTokens === []) {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute('class', implode(' ', array_slice($safeTokens, 0, 8)));
                }

                continue;
            }

            if (in_array($name, ['alt', 'title'], true)) {
                $text = self::decodeValue($value);

                if ($text === '') {
                    $element->removeAttribute($attributeName);
                } else {
                    $element->setAttribute($name, mb_substr($text, 0, 500));
                }

                continue;
            }

            $element->removeAttribute($attributeName);
        }
    }

    private static function safeUrl(string $value, bool $imageOnly = false): ?string
    {
        $value = self::decodeValue($value);
        $value = str_replace('\\', '/', $value);
        $value = preg_replace('/\s+/', '', $value) ?? '';

        if ($value === '' || str_contains($value, "\0")) {
            return null;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $value, $matches) === 1) {
            $scheme = strtolower($matches[1]);
            $allowedSchemes = $imageOnly ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

            if (! in_array($scheme, $allowedSchemes, true)) {
                return null;
            }

            if (in_array($scheme, ['http', 'https'], true)
                && preg_match('~^https?://[^/?#]+~i', $value) !== 1
            ) {
                return null;
            }

            return $value;
        }

        if (str_starts_with($value, '//') || str_contains($value, ':')) {
            return null;
        }

        return $value;
    }

    private static function safeStyle(string $value): string
    {
        $value = self::decodeValue($value);

        if (preg_match('/expression|behavior|binding|url\s*\(/i', $value) === 1) {
            return '';
        }

        $declarations = [];
        foreach (explode(';', $value) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $propertyValue] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $propertyValue = strtolower($propertyValue);

            if ($property === 'text-align' && in_array($propertyValue, self::SAFE_ALIGNMENTS, true)) {
                $declarations[] = "text-align: {$propertyValue}";
            }
        }

        return implode('; ', $declarations);
    }

    private static function decodeValue(string $value): string
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($decoded === $value) {
                break;
            }

            $value = $decoded;
        }

        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? $value);
    }

    private static function removeNode(\DOMNode $node): void
    {
        $node->parentNode?->removeChild($node);
    }

    private static function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent instanceof \DOMNode) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    /**
     * @param  list<string>  $allowed
     * @param  list<string>  $removedTags
     */
    private static function stripFallback(string $html, array $allowed, array $removedTags): string
    {
        $pattern = '/<('.implode('|', array_map('preg_quote', $removedTags)).')\b[^>]*>.*?<\/\1\s*>/is';
        $html = preg_replace($pattern, '', $html) ?? $html;

        $tags = '';
        foreach ($allowed as $tag) {
            $tags .= '<'.$tag.'>';
        }

        return strip_tags($html, $tags);
    }

    /** @return list<string> */
    private static function stringList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => is_string($value) ? strtolower(trim($value)) : '', $values),
            static fn (string $value): bool => $value !== '' && preg_match('/^[a-z0-9]+$/', $value) === 1,
        ));
    }
}
