<?php

declare(strict_types=1);

namespace App\Services;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allowlist-based HTML sanitizer for editable email content.
 *
 * The email templates are database-managed content and must never be treated
 * as trusted markup. Only presentation tags, safe inline styles and safe
 * http(s)/mailto links survive; scripts, event handlers and active content are
 * removed before the markup reaches a mailable or the preview iframe.
 */
final class EmailHtmlSanitizer
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'p', 'br', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 's',
        'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote',
        'a', 'img', 'hr', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
    ];

    /** @var list<string> */
    private const REMOVE_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input',
        'button', 'select', 'textarea', 'svg', 'math', 'base', 'meta',
        'link', 'noscript', 'template', 'video', 'audio', 'source',
    ];

    /** @var list<string> */
    private const ALLOWED_ATTRIBUTES = [
        'dir', 'lang', 'align', 'valign', 'bgcolor', 'border', 'cellpadding',
        'cellspacing', 'colspan', 'rowspan', 'width', 'height', 'href', 'src',
        'alt', 'title', 'style', 'role',
    ];

    /** @var list<string> */
    private const ALLOWED_STYLE_PROPERTIES = [
        'background-color', 'border', 'border-bottom', 'border-collapse',
        'border-color', 'border-left', 'border-radius', 'border-right',
        'border-spacing', 'border-style', 'border-top', 'border-width',
        'color', 'direction', 'display', 'font-family', 'font-size',
        'font-style', 'font-weight', 'height', 'letter-spacing',
        'line-height', 'list-style-type', 'margin', 'margin-bottom',
        'margin-left', 'margin-right', 'margin-top', 'max-width', 'min-width',
        'padding', 'padding-bottom', 'padding-left', 'padding-right',
        'padding-top', 'table-layout', 'text-align', 'text-decoration',
        'unicode-bidi', 'vertical-align', 'white-space', 'width',
    ];

    public function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"?><html><body>'.$html.'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body instanceof DOMElement) {
            return e($html);
        }

        $this->sanitizeChildren($body);

        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        /** @var list<DOMNode> $children */
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                if ($child instanceof DOMText && trim($child->nodeValue ?? '') === '') {
                    continue;
                }

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->unwrap($child);

                continue;
            }

            $this->sanitizeAttributes($child, $tag);
            $this->sanitizeChildren($child);
        }
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }

        /** @var list<DOMNode> $children */
        $children = iterator_to_array($element->childNodes);
        foreach ($children as $child) {
            $parent->insertBefore($child, $element);
        }

        $parent->removeChild($element);
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        /** @var list<\DOMAttr> $attributes */
        $attributes = iterator_to_array($element->attributes);

        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->name);
            $value = $attribute->value;

            $safe = ! str_starts_with($name, 'on')
                && in_array($name, self::ALLOWED_ATTRIBUTES, true)
                && ! preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value);

            if ($safe && $name === 'href') {
                $safe = $this->safeUrl($value, true);
            }

            if ($safe && $name === 'src') {
                $safe = $tag === 'img' && $this->safeUrl($value, false);
            }

            if ($safe && $name === 'style') {
                $value = $this->safeStyle($value);
                $safe = $value !== '';
            }

            if ($safe) {
                if ($name !== 'style') {
                    $attribute->value = $value;
                }

                continue;
            }

            $element->removeAttributeNode($attribute);
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('rel', 'noopener noreferrer');
        }

        if ($tag === 'img' && ! $element->hasAttribute('alt')) {
            $element->setAttribute('alt', '');
        }
    }

    private function safeUrl(string $url, bool $allowFragment): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return false;
        }

        if ($allowFragment && str_starts_with($url, '#')) {
            return true;
        }

        return (bool) preg_match('/^(?:https?:|mailto:)/i', $url);
    }

    private function safeStyle(string $style): string
    {
        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = explode(':', $declaration, 2);
            $property = strtolower(trim($property));
            $value = trim($value);

            if (! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true) || $value === '') {
                continue;
            }

            if (preg_match('/url\s*\(|expression\s*\(|@import|behavior\s*:|-moz-binding|javascript:|[{}<>\\\\]/i', $value)) {
                continue;
            }

            $safe[] = $property.': '.$value;
        }

        return implode('; ', $safe);
    }
}
