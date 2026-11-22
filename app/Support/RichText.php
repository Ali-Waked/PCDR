<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Server-side allowlist sanitiser for the structured content editor.
 * Never renders raw user-authored HTML in Vue with v-html.
 */
final class RichText
{
    public static function clean(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $allowed = config('cms.editor.allowed_tags', []);
        $allowedAttributes = config('cms.editor.allowed_attributes', []);

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML('<?xml encoding="UTF-8"?><div id="pcdr-root">'.$html.'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $loaded ? $document->getElementsByTagName('div')->item(0) : null;
        if (! $root instanceof \DOMElement) {
            return self::stripFallback($html, $allowed);
        }
        self::prune($root, $allowed, $allowedAttributes);

        $output = '';
        foreach ($root->childNodes as $node) {
            $output .= $document->saveHTML($node);
        }

        return trim($output);
    }

    public static function plainText(?string $html): string
    {
        return trim(html_entity_decode(strip_tags(self::clean($html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /** @return list<string> */
    private static function prune(\DOMNode $node, array $allowed, array $allowedAttributes): void
    {
        /** @var list<\DOMNode> $children */
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                if (! in_array(strtolower($child->tagName), $allowed, true)) {
                    self::unwrap($child);

                    continue;
                }

                /** @var list<\DOMAttr> $attributes */
                $attributes = [];
                foreach ($child->attributes as $attribute) {
                    $attributes[] = $attribute;
                }
                foreach ($attributes as $attribute) {
                    $name = strtolower($attribute->name);
                    if (str_starts_with($name, 'on') || ! in_array($name, $allowedAttributes, true)) {
                        $child->removeAttribute($attribute->name);

                        continue;
                    }
                    if (in_array($name, ['href', 'src'], true)
                        && ! preg_match('/^(https?:\/\/|\/|#|mailto:)/i', trim($attribute->value))) {
                        $child->removeAttribute($attribute->name);
                    }
                    if ($name === 'target' && $attribute->value === '_blank') {
                        $child->setAttribute('rel', 'noopener noreferrer');
                    }
                }
            } elseif ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);

                continue;
            }

            self::prune($child, $allowed, $allowedAttributes);
        }
    }

    private static function unwrap(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }

    private static function stripFallback(string $html, array $allowed): string
    {
        $tags = '';
        foreach ($allowed as $tag) {
            $tags .= '<'.$tag.'>';
        }

        return strip_tags($html, $tags);
    }
}
